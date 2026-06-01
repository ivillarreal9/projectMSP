<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MspService;
use App\Services\Sales\OdooService;

/**
 * Controlador de sincronización entre Odoo y MSP.
 *
 * Permite identificar y enlazar los clientes de Odoo (ERP) con los clientes de la
 * plataforma MSP (Managed Service Provider). Esto es necesario porque en ambos sistemas
 * los clientes se crean de forma independiente y sus nombres no siempre coinciden exactamente.
 *
 * Algoritmo de matching (buildFilas):
 *  1. Exacto: cruza el `ReferenceId` de MSP con el `account_no` de Odoo (números de cuenta).
 *     Soporta múltiples cuentas en el formato: "Cuenta 1: 100, Cuenta 2: 101".
 *  2. Fuzzy: para los que no tienen match exacto, usa `similar_text()` con umbral de 75%
 *     para detectar nombres similares con diferencias tipográficas o de formato.
 *  3. Sin match: clientes que solo existen en uno de los dos sistemas.
 *
 * Vistas:
 *   - admin.sincronizar.coincidencias    → Clientes con match (exacto o fuzzy)
 *   - admin.sincronizar.sin-coincidencia → Clientes sin ningún match (solo en Odoo o solo en MSP)
 *
 * Rutas principales (prefijo /admin/sincronizar):
 *   GET  /                     → index() → coincidencias()
 *   GET  /coincidencias        → coincidencias()
 *   GET  /sin-coincidencia     → sinCoincidencia()
 *   POST /clear-cache          → clearCache()
 *   POST /enlazar              → enlazar()     [AJAX JSON]
 *   GET  /preview              → preview()     [AJAX JSON]
 *   POST /ejecutar             → ejecutar()    [AJAX JSON]
 *
 * @see \App\Services\MspService          Actualiza el ReferenceId en la plataforma MSP
 * @see \App\Services\Sales\OdooService   Obtiene el listado de partners de Odoo para sincronización
 */
class SincronizarController extends Controller
{
    /**
     * Ruta de entrada: redirige a la vista de coincidencias.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return $this->coincidencias();
    }

    /**
     * Limpia la caché de datos de Odoo y MSP para forzar una recarga desde las APIs.
     *
     * Los datos de ambos sistemas se cachean para evitar consultas repetidas durante
     * la sesión. Este método permite refrescar manualmente cuando se sabe que hubo
     * cambios recientes en Odoo o en la plataforma MSP.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache()
    {
        \Illuminate\Support\Facades\Cache::forget('odoo:sync:partners');
        \Illuminate\Support\Facades\Cache::forget('msp:customers:sync');

        return back()->with('success', '🔄 Datos de Odoo y MSP actualizados correctamente.');
    }

    /**
     * Vista de clientes con coincidencias entre Odoo y MSP (exactas y fuzzy).
     *
     * Solo muestra los registros de tipo 'exacto' y 'fuzzy', ordenados de mayor
     * a menor similitud porcentual. Los registros sin match no aparecen aquí
     * (ver sinCoincidencia()).
     *
     * @return \Illuminate\View\View  Vista admin.sincronizar.coincidencias con: filas
     */
    public function coincidencias()
    {
        ['filas' => $filas, 'errors' => $errors] = $this->buildFilas();

        $filas = array_filter($filas, fn($f) => in_array($f['tipo'], ['exacto', 'fuzzy']));
        usort($filas, fn($a, $b) => ($b['similitud'] ?? -1) <=> ($a['similitud'] ?? -1));

        if (!empty($errors)) session()->flash('error', implode(' | ', $errors));

        return view('admin.sincronizar.coincidencias', ['filas' => array_values($filas)]);
    }

    /**
     * Vista de clientes sin ninguna coincidencia entre sistemas.
     *
     * Separa en dos listas:
     *  - `odooSinMatch`: clientes en Odoo que no encontraron pareja en MSP.
     *  - `mspSinMatch`: clientes en MSP que no encontraron pareja en Odoo
     *    (aunque hayan tenido un ReferenceId previo que no calza con ningún account_no actual).
     *
     * Ambas listas se ordenan alfabéticamente por nombre para facilitar la revisión manual.
     *
     * @return \Illuminate\View\View  Vista admin.sincronizar.sin-coincidencia con: odooSinMatch, mspSinMatch
     */
    public function sinCoincidencia()
    {
        ['filas' => $filas, 'errors' => $errors] = $this->buildFilas();

        // Odoo que no tienen ningún match
        $odooSinMatch = array_values(array_filter($filas, fn($f) => $f['tipo'] === 'odoo_only'));
        usort($odooSinMatch, fn($a, $b) => strcmp($a['odoo_nombre'], $b['odoo_nombre']));

        // MSP que no tienen ningún match (aunque tengan un ReferenceId previo que no calza con nada)
        $mspSinMatch = array_values(array_filter($filas, fn($f) => $f['tipo'] === 'msp_only'));
        usort($mspSinMatch, fn($a, $b) => strcmp($a['msp_nombre'], $b['msp_nombre']));

        if (!empty($errors)) session()->flash('error', implode(' | ', $errors));

        return view('admin.sincronizar.sin-coincidencia', compact('odooSinMatch', 'mspSinMatch'));
    }

    /**
     * Enlaza manualmente pares de clientes Odoo-MSP vía AJAX.
     *
     * Permite al usuario confirmar manualmente pares identificados en la vista
     * de coincidencias o crear vínculos nuevos desde la vista de sin coincidencia.
     * Llama a MspService::updateCustomer() para escribir el número de cuenta de Odoo
     * como ReferenceId en el cliente MSP.
     *
     * Errores MSP conocidos (traducidos a mensajes amigables):
     *  - 'permission to delete' → el usuario MSP no tiene permisos de administrador.
     *  - 'already have a customer' → el nombre ya existe en MSP (duplicado).
     *
     * @param  \Illuminate\Http\Request  $request  Campo: pares[] con customer_id, customer_name, numero_cuenta
     * @return \Illuminate\Http\JsonResponse        {ok: bool, enlazados: int, errores: string[]}
     */
    public function enlazar(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'pares'                 => 'required|array|min:1',
            'pares.*.customer_id'   => 'required|string',
            'pares.*.customer_name' => 'required|string',
            'pares.*.numero_cuenta' => 'required|string',
        ]);

        $msp     = new MspService();
        $ok      = 0;
        $errores = [];

        foreach ($request->input('pares') as $par) {
            try {
                $msp->updateCustomer(
                    $par['customer_id'],
                    $par['customer_name'],
                    $par['numero_cuenta'],
                );
                $ok++;
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (str_contains($msg, 'permission to delete')) {
                    $msg = 'Sin permiso MSP RM';
                } elseif (str_contains($msg, 'already have a customer')) {
                    $msg = 'Nombre duplicado en MSP';
                }
                $errores[] = "{$par['customer_name']}: {$msg}";
            }
        }

        return response()->json([
            'ok'        => $ok > 0,
            'enlazados' => $ok,
            'errores'   => $errores,
        ]);
    }

    /**
     * Devuelve la lista de clientes que serán actualizados en la sincronización masiva.
     *
     * Filtra los registros fuzzy que cumplen todos los criterios para actualización automática:
     *  - Tipo fuzzy (no exactos, que ya están correctamente vinculados).
     *  - Tienen número de cuenta de Odoo.
     *  - Tienen CustomerId de MSP.
     *  - No tienen RmReferenceId (excluye los vinculados a MSP RM que requieren permisos especiales).
     *
     * Es un endpoint de vista previa sin efectos secundarios; se llama antes de ejecutar().
     *
     * @return \Illuminate\Http\JsonResponse  {total: int, clientes: array}
     */
    public function preview()
    {
        ['filas' => $filas] = $this->buildFilas();

        $paraActualizar = array_values(array_filter($filas, fn($f) =>
            $f['tipo'] === 'fuzzy'
            && !empty($f['numero_cuenta'])
            && !empty($f['customer_id'])
            && empty($f['rm_reference_id'])  // excluir vinculados a MSP RM
        ));

        $preview = array_map(fn($f) => [
            'odoo_nombre'   => $f['odoo_nombre'],
            'msp_nombre'    => $f['msp_nombre'],
            'numero_cuenta' => $f['numero_cuenta'],
            'customer_id'   => $f['customer_id'],
            'similitud'     => $f['similitud'],
        ], $paraActualizar);

        return response()->json(['total' => count($preview), 'clientes' => $preview]);
    }

    /**
     * Ejecuta la sincronización masiva de clientes en lote.
     *
     * Procesa el lote recibido (generalmente generado por preview()) y llama a
     * MspService::updateCustomer() para cada cliente. Los errores son acumulados
     * y devueltos sin detener el proceso completo (best-effort).
     *
     * Limit: set_time_limit(120) para manejar lotes grandes sin timeout de PHP.
     *
     * @param  \Illuminate\Http\Request  $request  Campo: lote[] con customer_id, msp_nombre, numero_cuenta
     * @return \Illuminate\Http\JsonResponse        {actualizados: int, errores: string[]}
     */
    public function ejecutar(\Illuminate\Http\Request $request)
    {
        set_time_limit(120);

        $lote = $request->input('lote', []);

        if (empty($lote)) {
            return response()->json(['error' => 'Lote vacío.'], 422);
        }

        $msp     = new MspService();
        $ok      = 0;
        $errores = [];

        foreach ($lote as $cliente) {
            $customerId   = $cliente['customer_id']   ?? null;
            $mspNombre    = $cliente['msp_nombre']    ?? '';
            $numeroCuenta = $cliente['numero_cuenta'] ?? '';

            if (!$customerId || !$numeroCuenta) {
                $errores[] = "Datos incompletos: {$mspNombre}";
                continue;
            }

            try {
                $msp->updateCustomer($customerId, $mspNombre, $numeroCuenta);
                $ok++;
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (str_contains($msg, 'permission to delete')) {
                    $errores[] = "{$mspNombre}: sin permiso MSP RM (requiere permisos de administrador en MSP)";
                } elseif (str_contains($msg, 'already have a customer')) {
                    $errores[] = "{$mspNombre}: nombre duplicado en MSP";
                } else {
                    $errores[] = "{$mspNombre}: {$msg}";
                }
            }
        }

        return response()->json(['actualizados' => $ok, 'errores' => $errores]);
    }

    // ─── Lógica compartida ────────────────────────────────────────────────────

    /**
     * Construye la lista completa de filas de comparación Odoo vs MSP.
     *
     * Proceso en 4 pasos:
     *
     * Paso 1 — Match exacto por número de cuenta:
     *   Itera los clientes MSP buscando su `ReferenceId` en el índice de Odoo.
     *   El ReferenceId puede contener múltiples cuentas en formato
     *   "Cuenta 1: 100, Cuenta 2: 101" → extrae todos los números de 3+ dígitos.
     *
     * Paso 2 — Separar libres:
     *   Los clientes que no tuvieron match exacto quedan "libres" para el paso fuzzy.
     *
     * Paso 3 — Match fuzzy con similar_text():
     *   Normaliza los nombres (minúsculas, elimina sufijos numéricos tipo "- 123456",
     *   colapsa espacios, elimina puntuación). Umbral: similitud >= 75%.
     *   Si un cliente MSP tiene múltiples matches en Odoo, los combina en una sola fila.
     *
     * Paso 4 — Sin match:
     *   Clientes de Odoo que sobran (tipo 'odoo_only') y clientes MSP sin match (tipo 'msp_only').
     *
     * @return array{filas: array, errors: string[], mspTodos: array}
     *   - filas: todos los registros con campos tipo, odoo_nombre, msp_nombre, similitud, etc.
     *   - errors: errores no fatales de conexión a Odoo o MSP.
     *   - mspTodos: array raw completo de MSP (para uso futuro de vistas).
     */
    private function buildFilas(): array
    {
        $errors = [];

        try {
            $odooRaw = (new OdooService())->fetchAllPartnersForSync();
        } catch (\Throwable $e) {
            $odooRaw = [];
            $errors[] = 'Odoo: ' . $e->getMessage();
        }

        try {
            $mspRaw = (new MspService())->fetchCustomers();
        } catch (\Throwable $e) {
            $mspRaw = [];
            $errors[] = 'MSP API: ' . $e->getMessage();
        }

        $odooMap = collect($odooRaw)
            ->filter(fn($r) => !empty($r['account_no']) && $r['account_no'] !== false)
            ->keyBy('account_no');

        // Normalizar ReferenceId (trim) antes de indexar
        $mspRaw = array_map(fn($r) => array_merge($r, ['ReferenceId' => trim($r['ReferenceId'] ?? '')]), $mspRaw);

        $normalize = fn(string $s): string => strtolower(trim(
            preg_replace('/\s+/', ' ',
            preg_replace('/[^a-záéíóúüña-z0-9\s]/iu', ' ',
            preg_replace('/\s*-\s*\d{6,}$/', '', $s)))
        ));

        $filas     = [];
        $usadoOdoo = [];
        $usadoMsp  = [];

        // Paso 1: exactos por account_no en ReferenceId (soporta múltiples cuentas: "Cuenta 1: 100, Cuenta 2: 101")
        foreach ($mspRaw as $msp) {
            $ref = $msp['ReferenceId'] ?? '';
            if (empty($ref)) continue;

            // Extraer todos los números de cuenta (secuencias de dígitos de 3 o más)
            preg_match_all('/\b\d{3,}\b/', $ref, $matches);
            $cuentasEnMsp = array_unique($matches[0] ?? []);

            if (empty($cuentasEnMsp)) continue;

            foreach ($cuentasEnMsp as $clave) {
                $odoo = $odooMap->get($clave);
                if ($odoo) {
                    similar_text($normalize($odoo['complete_name']), $normalize($msp['CustomerName']), $sim);
                    $filas[] = [
                        'odoo_nombre'   => $odoo['complete_name'],
                        'numero_cuenta' => $odoo['account_no'],
                        'msp_nombre'    => $msp['CustomerName'],
                        'reference_id'  => $msp['ReferenceId'],
                        'customer_id'   => $msp['CustomerId'] ?? null,
                        'similitud'     => round($sim),
                        'tipo'          => 'exacto',
                    ];
                    $usadoOdoo[$clave] = true;
                    $usadoMsp[$msp['CustomerId']] = true;
                }
            }
        }

        // Paso 2: libres
        $odooLibres = $odooMap->filter(fn($_, $k) => !isset($usadoOdoo[$k]));
        $mspLibres  = collect($mspRaw)->filter(fn($r) => !isset($usadoMsp[$r['CustomerId']]));

        $odooNorm = $odooLibres->map(fn($r) => $normalize($r['complete_name']))->all();

        // Paso 3: fuzzy — todos los Odoo matches ≥ 75 % por cliente MSP
        $fuzzyUsadoOdoo = [];

        foreach ($mspLibres as $msp) {
            $mspNorm = $normalize($msp['CustomerName']);
            $matches = [];

            foreach ($odooNorm as $accountNo => $odooNombre) {
                similar_text($mspNorm, $odooNombre, $sim);
                if ($sim >= 75) {
                    $matches[$accountNo] = round($sim);
                }
            }

            if (!empty($matches)) {
                arsort($matches); // mayor similitud primero

                $cuentas   = array_keys($matches);
                $bestSim   = reset($matches);

                // Formato consistente: Cuenta 1: 100, Cuenta 2: 101...
                $refId = count($cuentas) === 1
                    ? $cuentas[0]
                    : implode(', ', array_map(fn($an, $idx) => "Cuenta " . ($idx + 1) . ": $an", $cuentas, array_keys($cuentas)));

                $odooNombres = implode(' | ', array_map(
                    fn($an) => $odooLibres->get($an)['complete_name'] ?? $an,
                    $cuentas
                ));

                $filas[] = [
                    'odoo_nombre'    => $odooNombres,
                    'numero_cuenta'  => $refId,
                    'msp_nombre'     => $msp['CustomerName'],
                    'reference_id'   => $msp['ReferenceId']    ?? '',
                    'customer_id'    => $msp['CustomerId']     ?? null,
                    'rm_reference_id'=> $msp['RmReferenceId']  ?? null,
                    'similitud'      => $bestSim,
                    'tipo'           => 'fuzzy',
                ];

                foreach ($cuentas as $an) {
                    $fuzzyUsadoOdoo[$an] = true;
                }
            } else {
                $filas[] = [
                    'odoo_nombre'   => '—',
                    'numero_cuenta' => '—',
                    'msp_nombre'    => $msp['CustomerName'],
                    'reference_id'  => $msp['ReferenceId'] ?? '',
                    'customer_id'   => $msp['CustomerId']  ?? null,
                    'similitud'     => null,
                    'tipo'          => 'msp_only',
                ];
            }
        }

        // Paso 4: Odoo sin ningún match
        foreach ($odooLibres as $accountNo => $odoo) {
            if (isset($fuzzyUsadoOdoo[$accountNo])) continue;
            $filas[] = [
                'odoo_nombre'   => $odoo['complete_name'],
                'numero_cuenta' => $odoo['account_no'],
                'msp_nombre'    => '—',
                'reference_id'  => '—',
                'customer_id'   => null,
                'similitud'     => null,
                'tipo'          => 'odoo_only',
            ];
        }

        return ['filas' => $filas, 'errors' => $errors, 'mspTodos' => $mspRaw];
    }
}
