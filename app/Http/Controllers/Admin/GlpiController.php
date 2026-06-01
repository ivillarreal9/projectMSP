<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Controlador del módulo GLPI.
 *
 * Gestiona el inventario de activos IT conectándose a la API REST de GLPI.
 * Los datos se sirven desde caché (24h para items, 50min para session token)
 * para minimizar llamadas a la API externa.
 *
 * Tipos de activos soportados (config/glpi.php → asset_types):
 *   Computer, NetworkEquipment, Printer, Phone, Monitor, Peripheral
 *
 * Vistas:
 *   - admin.glpi.index  → Dashboard con resumen por tipo de activo
 *   - admin.glpi.items  → Listado agrupado por tipo+modelo con búsqueda y ordenamiento
 *   - admin.glpi.show   → Detalle de un activo individual
 *   - admin.glpi.create → Formulario de creación de activo
 *   - admin.glpi.edit   → Formulario de edición de activo
 *
 * Rutas principales (prefijo /admin/glpi):
 *   POST /session/init         → sessionInit()
 *   POST /session/kill         → sessionKill()
 *   POST /cache/refresh        → refreshCache()
 *   GET  /                     → index()
 *   GET  /{itemtype}           → items()
 *   GET  /{itemtype}/{id}      → show()
 *   POST /{itemtype}           → store()
 *   GET  /{itemtype}/{id}/edit → edit()
 *   PUT  /{itemtype}/{id}      → update()
 *
 * @see \App\Services\GlpiService  Capa de acceso a la API GLPI con caché
 */
class GlpiController extends Controller
{
    /**
     * Inyecta GlpiService vía constructor (IoC Container de Laravel).
     *
     * @param  \App\Services\GlpiService  $glpi
     */
    public function __construct(protected GlpiService $glpi) {}

    /**
     * Inicia sesión en la API GLPI y almacena el token de sesión en caché.
     *
     * El token de sesión se almacena en la caché de Laravel con TTL de 50 minutos
     * para reutilizarse en llamadas subsiguientes sin re-autenticar.
     * También marca `glpi_session_active` en la sesión del usuario para indicar
     * el estado en la UI.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionInit()
    {
        try {
            $token = $this->glpi->initSession();
            session(['glpi_session_active' => true]);
            return back()->with('success', 'Sesión GLPI iniciada correctamente. Token: ' . substr($token, 0, 8) . '...');
        } catch (Exception $e) {
            return back()->with('error', 'No se pudo iniciar sesión en GLPI: ' . $e->getMessage());
        }
    }

    /**
     * Cierra la sesión activa en GLPI e invalida el token de caché.
     *
     * Siempre elimina el marcador de sesión del usuario (`glpi_session_active`)
     * incluso si la llamada a la API falla, para evitar que la UI muestre un estado
     * incorrecto de sesión activa.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionKill()
    {
        try {
            $this->glpi->killSession();
            session()->forget('glpi_session_active');
            return back()->with('success', 'Sesión GLPI cerrada correctamente.');
        } catch (Exception $e) {
            session()->forget('glpi_session_active');
            return back()->with('error', 'Error al cerrar sesión: ' . $e->getMessage());
        }
    }

    /**
     * Fuerza la recarga de la caché de inventario GLPI.
     *
     * Ejecuta el mismo proceso que el comando artisan `glpi:warm-cache`,
     * pero disparado manualmente desde la interfaz web. Útil cuando el scheduler
     * no ha corrido aún y se necesitan datos actualizados inmediatamente.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshCache()
    {
        try {
            $this->glpi->warmCache();
            return back()->with('success', 'Caché de GLPI actualizado correctamente.');
        } catch (Exception $e) {
            Log::error('GLPI refreshCache: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar el caché: ' . $e->getMessage());
        }
    }

    /**
     * Dashboard de GLPI con resumen de inventario por tipo de activo.
     *
     * Para NetworkEquipment hace una única llamada con `expand_dropdowns=true`
     * para obtener todos los equipos y sub-clasificarlos por tipo (Switch, Router, etc.)
     * y contar cuántos están en depósito. Este diseño reemplaza el enfoque anterior
     * que hacía 2 llamadas por tipo (count + depósito), reduciendo el número de
     * requests a la API GLPI en el dashboard.
     *
     * Para los demás tipos solo obtiene el total (range=0-0) para eficiencia.
     * Los errores por tipo son ignorados silenciosamente (total=0) para no romper el dashboard.
     *
     * Un activo se considera "en depósito" si su estado (`states_id.name`) contiene "dep"
     * (coincide con "Depósito", "En depósito", etc., de forma case-insensitive).
     *
     * @return \Illuminate\View\View  Vista admin.glpi.index con: summary (array por tipo)
     */
    public function index()
    {
        $assetTypes = config('glpi.asset_types');
        $summary    = [];

        foreach ($assetTypes as $type => $label) {
            try {
                if ($type === 'NetworkEquipment') {
                    // Una sola llamada: todos los equipos con dropdowns expandidos.
                    // Antes: 2 llamadas por cada tipo (count + deposito) → N tipos = 2N requests.
                    $allResult = $this->glpi->getAllItems('NetworkEquipment', [
                        'range'            => '0-4999',
                        'expand_dropdowns' => true,
                        'get_hateoas'      => false,
                    ]);

                    $allItems = $allResult['items'] ?? [];
                    $grouped  = [];

                    foreach ($allItems as $item) {
                        $typeData = $item['networkequipmenttypes_id'] ?? null;
                        $typeId   = is_array($typeData) ? ($typeData['id'] ?? 0) : 0;
                        $typeName = is_array($typeData) ? ($typeData['name'] ?? 'Sin tipo') : ($typeData ?: 'Sin tipo');

                        if (!isset($grouped[$typeId])) {
                            $grouped[$typeId] = [
                                'id'          => $typeId,
                                'nombre'      => $typeName,
                                'total'       => 0,
                                'en_deposito' => 0,
                            ];
                        }

                        $grouped[$typeId]['total']++;

                        $estado       = $item['states_id'] ?? null;
                        $estadoNombre = strtolower(is_array($estado) ? ($estado['name'] ?? '') : ($estado ?? ''));
                        if (str_contains($estadoNombre, 'dep')) {
                            $grouped[$typeId]['en_deposito']++;
                        }
                    }

                    $grouped = array_values(array_filter($grouped, fn($g) => $g['total'] > 0));

                    $summary[$type] = [
                        'label'   => $label,
                        'total'   => $allResult['total'] ?: count($allItems),
                        'grouped' => $grouped,
                    ];

                } else {
                    $result         = $this->glpi->getAllItems($type, ['range' => '0-0']);
                    $summary[$type] = [
                        'label'   => $label,
                        'total'   => $result['total'],
                        'grouped' => null,
                    ];
                }
            } catch (Exception $e) {
                Log::error("GLPI index [{$type}]: " . $e->getMessage());
                $summary[$type] = ['label' => $label, 'total' => 0, 'grouped' => null];
            }
        }

        return view('admin.glpi.index', compact('summary'));
    }

    /**
     * Listado de activos de un tipo específico agrupados por tipo → modelo.
     *
     * Valida que el itemtype sea uno de los tipos permitidos en config/glpi.php;
     * devuelve 404 para cualquier tipo no reconocido.
     *
     * Agrupación: Los activos se organizan jerárquicamente:
     *   Tipo (ej: Switch) → Modelo (ej: Cisco Catalyst 9200) → { total, deposito, items[] }
     *
     * Ordenamiento disponible (parámetro `sort`):
     *  - 'total_desc' (por defecto): más unidades primero
     *  - 'deposito_desc': más en depósito primero
     *  - 'alfa_asc': alfabético ascendente
     *  - 'alfa_desc': alfabético descendente
     *
     * La búsqueda filtra por nombre del activo en el lado del cliente (post-query)
     * ya que la API GLPI no soporta filtros de texto avanzados de forma eficiente.
     *
     * El campo de tipo y modelo varía por itemtype (ej: networkequipmenttypes_id vs computertypes_id),
     * y los dropdowns vienen expandidos como objetos {id, name} cuando expand_dropdowns=true.
     *
     * @param  \Illuminate\Http\Request  $request   Parámetros: search (opcional), sort (opcional)
     * @param  string                    $itemtype  Tipo de activo GLPI (Computer, NetworkEquipment, etc.)
     * @return \Illuminate\View\View                Vista admin.glpi.items
     */
    public function items(Request $request, string $itemtype)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);

        $search = $request->get('search', '');
        $sort   = $request->get('sort', 'total_desc');
        $label  = $assetTypes[$itemtype];

        $typeField = match($itemtype) {
            'NetworkEquipment' => 'networkequipmenttypes_id',
            'Computer'         => 'computertypes_id',
            'Printer'          => 'printertypes_id',
            'Phone'            => 'phonetypes_id',
            'Monitor'          => 'monitortypes_id',
            'Peripheral'       => 'peripheraltypes_id',
            default            => null,
        };

        $modelField = match($itemtype) {
            'NetworkEquipment' => 'networkequipmentmodels_id',
            'Computer'         => 'computermodels_id',
            'Printer'          => 'printermodels_id',
            'Phone'            => 'phonemodels_id',
            'Monitor'          => 'monitormodels_id',
            'Peripheral'       => 'peripheralmodels_id',
            default            => null,
        };

        try {
            $all      = $this->glpi->getAllItems($itemtype, [
                'range'            => '0-499',
                'expand_dropdowns' => true,
                'get_hateoas'      => false,
            ]);

            $allItems = $all['items'] ?? [];
            $total    = $all['total'] ?? count($allItems);

            if ($search) {
                $allItems = array_values(array_filter($allItems, fn($i) =>
                    str_contains(strtolower($i['name'] ?? ''), strtolower($search))
                ));
            }

            // Agrupar: tipo → modelo → { total, deposito, items[] }
            $grouped = [];

            foreach ($allItems as $item) {
                $tipo = '';
                if ($typeField && isset($item[$typeField])) {
                    $v    = $item[$typeField];
                    $tipo = is_array($v) ? ($v['name'] ?? '') : ($v ?: '');
                }
                $tipo = $tipo ?: 'Sin tipo';

                $modelo = '';
                if ($modelField && isset($item[$modelField])) {
                    $v      = $item[$modelField];
                    $modelo = is_array($v) ? ($v['name'] ?? '') : ($v ?: '');
                }
                $modelo = $modelo ?: 'Sin modelo';

                if (!isset($grouped[$tipo][$modelo])) {
                    $grouped[$tipo][$modelo] = ['total' => 0, 'deposito' => 0, 'items' => []];
                }

                $grouped[$tipo][$modelo]['total']++;
                $grouped[$tipo][$modelo]['items'][] = $item;

                $estado       = $item['states_id'] ?? null;
                $estadoNombre = strtolower(is_array($estado) ? ($estado['name'] ?? '') : ($estado ?? ''));
                if (str_contains($estadoNombre, 'dep')) {
                    $grouped[$tipo][$modelo]['deposito']++;
                }
            }

            // Ordenar modelos dentro de cada tipo
            foreach ($grouped as $tipo => &$modelos) {
                match ($sort) {
                    'deposito_desc' => uasort($modelos, fn($a, $b) => $b['deposito'] - $a['deposito']),
                    'alfa_asc'      => uksort($modelos, fn($a, $b) => strcmp($a, $b)),
                    'alfa_desc'     => uksort($modelos, fn($a, $b) => strcmp($b, $a)),
                    default         => uasort($modelos, fn($a, $b) => $b['total'] - $a['total']),
                };
            }
            unset($modelos);

            // Ordenar tipos
            match ($sort) {
                'deposito_desc' => uasort($grouped, fn($a, $b) =>
                    array_sum(array_column($b, 'deposito')) - array_sum(array_column($a, 'deposito'))
                ),
                'alfa_asc'  => uksort($grouped, fn($a, $b) => strcmp($a, $b)),
                'alfa_desc' => uksort($grouped, fn($a, $b) => strcmp($b, $a)),
                default     => uasort($grouped, fn($a, $b) =>
                    array_sum(array_column($b, 'total')) - array_sum(array_column($a, 'total'))
                ),
            };

        } catch (Exception $e) {
            $grouped = [];
            $total   = 0;
            session()->flash('error', 'Error al conectar con GLPI: ' . $e->getMessage());
        }

        return view('admin.glpi.items', compact(
            'grouped', 'total', 'itemtype', 'label', 'search', 'sort'
        ));
    }

    /**
     * Vista de detalle de un activo GLPI individual.
     *
     * @param  string  $itemtype  Tipo de activo GLPI
     * @param  int     $id        ID del activo en GLPI
     * @return \Illuminate\View\View  Vista admin.glpi.show con: item, itemtype, label
     */
    public function show(string $itemtype, int $id)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);

        try {
            $item  = $this->glpi->getItem($itemtype, $id);
            $label = $assetTypes[$itemtype];
        } catch (Exception $e) {
            abort(404, 'Activo no encontrado: ' . $e->getMessage());
        }

        return view('admin.glpi.show', compact('item', 'itemtype', 'label'));
    }

    /**
     * Formulario de creación de un nuevo activo GLPI.
     *
     * Carga la lista de entidades disponibles para asignar el activo.
     * Si la carga de entidades falla (ej: sesión GLPI expirada), devuelve
     * array vacío para que el formulario siga funcionando sin el selector de entidad.
     *
     * @param  string  $itemtype  Tipo de activo a crear
     * @return \Illuminate\View\View  Vista admin.glpi.create con: itemtype, label, entities
     */
    public function create(string $itemtype)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);
        $label = $assetTypes[$itemtype];
        try { $entities = $this->glpi->getMyEntities(); } catch (Exception) { $entities = []; }
        return view('admin.glpi.create', compact('itemtype', 'label', 'entities'));
    }

    /**
     * Persiste un nuevo activo en GLPI vía API REST.
     *
     * Los campos null son filtrados con array_filter antes de enviarlos a la API
     * para no sobreescribir valores por defecto de GLPI con valores vacíos.
     *
     * @param  \Illuminate\Http\Request  $request   Campos: name (req), serial, otherserial, comment, entities_id
     * @param  string                    $itemtype  Tipo de activo GLPI
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, string $itemtype)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'serial'      => 'nullable|string|max:255',
            'otherserial' => 'nullable|string|max:255',
            'comment'     => 'nullable|string',
            'entities_id' => 'nullable|integer',
        ]);
        try {
            $this->glpi->addItem($itemtype, array_filter($validated));
            return redirect()->route('admin.glpi.items', $itemtype)->with('success', 'Activo creado correctamente.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error al crear: ' . $e->getMessage());
        }
    }

    /**
     * Formulario de edición de un activo GLPI existente.
     *
     * @param  string  $itemtype  Tipo de activo GLPI
     * @param  int     $id        ID del activo en GLPI
     * @return \Illuminate\View\View  Vista admin.glpi.edit con: item, itemtype, label, entities
     */
    public function edit(string $itemtype, int $id)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);
        try {
            $item     = $this->glpi->getItem($itemtype, $id);
            $label    = $assetTypes[$itemtype];
            $entities = $this->glpi->getMyEntities();
        } catch (Exception $e) { abort(404, $e->getMessage()); }
        return view('admin.glpi.edit', compact('item', 'itemtype', 'label', 'entities'));
    }

    /**
     * Actualiza un activo existente en GLPI vía API REST.
     *
     * Al igual que store(), filtra valores null antes de enviar para preservar
     * los campos que el usuario dejó vacíos sin limpiar datos existentes en GLPI.
     *
     * @param  \Illuminate\Http\Request  $request   Campos: name (req), serial, otherserial, comment, entities_id
     * @param  string                    $itemtype  Tipo de activo GLPI
     * @param  int                       $id        ID del activo en GLPI
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $itemtype, int $id)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'serial'      => 'nullable|string|max:255',
            'otherserial' => 'nullable|string|max:255',
            'comment'     => 'nullable|string',
            'entities_id' => 'nullable|integer',
        ]);
        try {
            $this->glpi->updateItem($itemtype, $id, array_filter($validated));
            return redirect()->route('admin.glpi.show', [$itemtype, $id])->with('success', 'Activo actualizado.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

}