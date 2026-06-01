<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\MspReportsImport;
use App\Models\MspReport;
use App\Models\MspClient;
use App\Models\MspUploadBatch;
use App\Models\MspPlantilla;
use App\Services\SharePointService;
use App\Services\MspPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Browsershot\Browsershot;

/**
 * Controlador del módulo MSP Reports.
 *
 * Gestiona el ciclo de vida completo de los reportes mensuales MSP (Managed Service Provider)
 * de Ovnicom: importación de Excel desde SharePoint, visualización de estadísticas por cliente,
 * generación de PDFs con Browsershot/Chromium, envío masivo/individual por correo vía SendGrid
 * y chat de inteligencia artificial con contexto de datos MSP.
 *
 * Vistas:
 *   - admin.reports.msp.index          → Pantalla de subida/importación de Excel
 *   - admin.reports.msp.clientes       → Listado paginado de clientes con estadísticas
 *   - admin.reports.msp.cliente_detalle → Detalle individual de un cliente
 *   - admin.reports.msp.pdf_template   → Plantilla HTML del reporte PDF
 *   - admin.reports.msp.descarga_masiva → Descarga ZIP con múltiples PDFs
 *   - admin.reports.msp.correos        → Pantalla de envío de correos
 *   - admin.reports.msp.chat           → Chat IA sobre datos MSP
 *
 * Rutas principales (prefijo /admin/msp-reports):
 *   GET  /                  → index()
 *   GET  /clientes          → clientes()
 *   GET  /clientes/{name}   → clienteDetalle()
 *   POST /clientes/{name}   → updateCliente()
 *   GET  /pdf/{name}        → pdfPreview()
 *   GET  /pdf/{name}/download → pdfDownload()
 *   GET  /descarga-masiva   → descargaMasivaIndex()
 *   POST /descarga-masiva   → descargaMasivaZip()
 *   GET  /correos           → correos()
 *   POST /correos/enviar    → enviarCorreo()
 *   POST /correos/masivo    → enviarMasivo()
 *   GET  /chat              → chat()
 *   POST /chat/api          → chatApi()
 *   POST /sharepoint/import → sharepointImport()
 *   POST /batch/{batch}/refresh → refreshBatch()
 *
 * @see \App\Services\MspPdfService   Generación de PDF reutilizable (web + API)
 * @see \App\Services\SharePointService   Integración con Microsoft SharePoint
 */
class MspReportController extends Controller
{
    // =========================================================================
    // VENTANA 1 — Subir Excel
    // =========================================================================

    /**
     * Pantalla principal del módulo MSP Reports (importación de Excel).
     *
     * En petición AJAX/JSON devuelve la lista de archivos Excel disponibles en SharePoint.
     * En petición normal renderiza la vista de subida con los últimos 10 lotes importados
     * y el estado de las credenciales de SharePoint.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $sp = app(SharePointService::class);

        // AJAX → lista de archivos
        if ($request->ajax() || $request->wantsJson()) {
            try {
                return response()->json(['files' => $sp->listExcelFiles()]);
            } catch (\Throwable $e) {
                Log::error('SharePoint listExcelFiles failed', ['error' => $e->getMessage(), 'user' => auth()->id()]);
                return response()->json(['error' => $e->getMessage()]);
            }
        }

        // Página normal
        $hasCredentials = $sp->hasCredentials();
        $missingEnvVars = $hasCredentials ? [] : $sp->missingCredentials();
        $batches        = MspUploadBatch::orderByDesc('created_at')->take(10)->get();

        return view('admin.reports.msp.index', compact(
            'hasCredentials', 'missingEnvVars', 'batches'
        ));
    }

    // =========================================================================
    // VENTANA 2 — Ver información de clientes
    // =========================================================================

    /**
     * Listado paginado de clientes con estadísticas del período seleccionado.
     *
     * Combina dos fuentes de datos:
     *  1. Tabla `msp_clients` → información del cliente (email, logo, RUC).
     *  2. Tabla `msp_reports` → agregados del período (total tickets, incidentes,
     *     solicitudes, tiempo promedio de vida del ticket).
     *
     * Si se filtra por período, solo muestra los clientes que tienen registros en ese período.
     * La búsqueda de texto filtra únicamente sobre el nombre del cliente.
     *
     * @param  \Illuminate\Http\Request  $request  Parámetros: periodo, search (opcional)
     * @return \Illuminate\View\View               Vista admin.reports.msp.clientes
     */
    public function clientes(Request $request)
    {
        $periodos = MspReport::uniquePeriodos();
        $periodo  = $request->input('periodo', $periodos[count($periodos) - 1] ?? null);
        $search   = $request->input('search', '');

        $statsQuery = MspReport::query()
            ->select(
                'customer_name',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw("SUM(CASE WHEN tipo_ticket = 'Incidente' THEN 1 ELSE 0 END) as incidentes"),
                DB::raw("SUM(CASE WHEN tipo_ticket = 'Solicitud' THEN 1 ELSE 0 END) as solicitudes"),
                DB::raw('AVG(tiempo_vida_ticket) as tiempo_prom')
            )
            ->groupBy('customer_name');

        if ($periodo) $statsQuery->where('periodo', $periodo);

        $statsMap = $statsQuery->get()->keyBy('customer_name');

        $clientesQuery = MspClient::query();

        if ($search) {
            $clientesQuery->where('customer_name', 'like', "%{$search}%");
        }

        if ($periodo) {
            $clientesQuery->whereIn('customer_name', $statsMap->keys());
        }

        $clientes = $clientesQuery->orderBy('customer_name')->paginate(30)->withQueryString();

        $clientes->getCollection()->transform(function ($cliente) use ($statsMap) {
            $stats = $statsMap->get($cliente->customer_name);
            $cliente->total_tickets = $stats?->total_tickets ?? 0;
            $cliente->incidentes    = $stats?->incidentes ?? 0;
            $cliente->solicitudes   = $stats?->solicitudes ?? 0;
            $cliente->tiempo_prom   = $stats?->tiempo_prom ?? 0;
            return $cliente;
        });

        return view('admin.reports.msp.clientes', compact('clientes', 'periodos', 'periodo', 'search'));
    }

    /**
     * Vista de detalle de un cliente individual con estadísticas del período.
     *
     * Adicionalmente intenta resolver el CustomerId del cliente consultando la API
     * MSP (ver resolveCustomerId). Si el cliente existe en MSP, adjunta los datos
     * MSP al modelo sin persistirlos (propiedad dinámica `msp_data`).
     *
     * @param  \Illuminate\Http\Request  $request   Parámetro opcional: periodo
     * @param  string                    $customer  Nombre del cliente (URL-encoded)
     * @return \Illuminate\View\View                Vista admin.reports.msp.cliente_detalle
     */
    public function clienteDetalle(Request $request, string $customer)
    {
        $customer    = urldecode($customer);
        $periodo     = $request->input('periodo');
        $stats       = MspReport::statsForCustomer($customer, $periodo);
        $periodos    = MspReport::uniquePeriodos();
        $clienteInfo = MspClient::where('customer_name', $customer)->first();

        $clienteInfo = $this->resolveCustomerId($clienteInfo);

        return view('admin.reports.msp.cliente_detalle',
            compact('customer', 'stats', 'periodos', 'periodo', 'clienteInfo'));
    }

    /**
     * Resuelve y cachea el CustomerId de un cliente en la API MSP.
     *
     * Lógica de resolución:
     *  - Si ya tiene `customer_id` → consulta directa por ID (más eficiente).
     *  - Si solo tiene `numero_cuenta` (RUC) → busca por RUC y persiste el CustomerId
     *    para futuras consultas, evitando búsquedas repetidas.
     *  - Si no tiene ninguno → devuelve el cliente sin datos MSP.
     *
     * Los datos obtenidos de MSP se adjuntan como propiedad dinámica `msp_data`
     * sin ser guardados en la base de datos.
     *
     * Los errores no son fatales: se loguean como warning y el método devuelve
     * el cliente sin `msp_data` para no romper la vista de detalle.
     *
     * @param  \App\Models\MspClient|null  $cliente  Modelo del cliente (puede ser null)
     * @return \App\Models\MspClient|null            El mismo modelo enriquecido o null
     */
    protected function resolveCustomerId(?MspClient $cliente): ?MspClient
    {
        if (!$cliente) return null;

        try {
            $msp = app(\App\Services\MspService::class);

            if ($cliente->customer_id) {
                // Ya tenemos el ID — consulta directa
                $results = $msp->findCustomerById($cliente->customer_id);
            } elseif ($cliente->numero_cuenta) {
                // Primera vez — buscar por RUC y guardar el CustomerId
                $results = $msp->findCustomerByRuc($cliente->numero_cuenta);
                if (!empty($results[0]['CustomerId'])) {
                    $cliente->update(['customer_id' => $results[0]['CustomerId']]);
                    $cliente->refresh();
                }
            } else {
                return $cliente;
            }

            // Adjuntar datos MSP al modelo sin persistirlos
            if (!empty($results[0])) {
                $cliente->msp_data = $results[0];
            }
        } catch (\Throwable $e) {
            Log::warning('MSP resolveCustomerId falló: ' . $e->getMessage(), [
                'customer' => $cliente->customer_name,
            ]);
        }

        return $cliente;
    }

    /**
     * Actualiza la información de un cliente MSP (email, RUC/número de cuenta, logo).
     *
     * Usa updateOrCreate para manejar tanto clientes existentes como nuevos.
     * El logo se almacena en el disco `public` bajo `logos/clientes/` y solo
     * se actualiza si se adjunta un archivo nuevo; de lo contrario se conserva el existente.
     * Filtra valores null para no sobreescribir datos previos con vacíos.
     *
     * @param  \Illuminate\Http\Request  $request   Campos: email_cliente, numero_cuenta, logo (imagen)
     * @param  string                    $customer  Nombre del cliente (URL-encoded)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCliente(Request $request, string $customer)
    {
        $customer = urldecode($customer);

        $request->validate([
            'email_cliente' => 'nullable|email|max:255',
            'numero_cuenta' => 'nullable|string|max:100',
            'logo'          => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
        ]);

        $data = [
            'email_cliente' => $request->input('email_cliente'),
            'numero_cuenta' => $request->input('numero_cuenta'),
        ];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos/clientes', 'public');
        }

        MspClient::updateOrCreate(
            ['customer_name' => $customer],
            array_filter($data, fn($v) => $v !== null)
        );

        return back()->with('success', '✅ Información del cliente actualizada correctamente.');
    }

    // =========================================================================
    // VENTANA 3 — PDF (individual y descarga masiva)
    // =========================================================================

    /**
     * Vista previa HTML del reporte PDF de un cliente.
     *
     * Renderiza la plantilla PDF directamente en el navegador (sin generar archivo)
     * para que el usuario pueda revisar el contenido antes de descargar.
     * El logo del cliente se resuelve como Base64 para que funcione en el PDF offline.
     *
     * @param  \Illuminate\Http\Request  $request   Parámetro opcional: periodo
     * @param  string                    $customer  Nombre del cliente (URL-encoded)
     * @return \Illuminate\View\View                Vista admin.reports.msp.pdf_template
     */
    public function pdfPreview(Request $request, string $customer)
    {
        $customer    = urldecode($customer);
        $periodo     = $request->input('periodo');
        $stats       = MspReport::statsForCustomer($customer, $periodo);
        $logoUrl     = $this->resolveLogoUrl($customer, $periodo);
        $ovnicomLogo = $this->getOvnicomLogo();

        return view('admin.reports.msp.pdf_template',
            compact('customer', 'stats', 'periodo', 'logoUrl', 'ovnicomLogo'));
    }

    /**
     * Genera y descarga el PDF de un cliente (fuerza regeneración sin caché).
     *
     * Usa MspPdfService como servicio compartido para la generación real del archivo.
     * Tras generar el PDF intenta subirlo a SharePoint; si la subida falla, el error
     * se loguea pero la descarga continúa sin interrumpir al usuario.
     *
     * @param  \Illuminate\Http\Request  $request   Parámetro requerido: periodo
     * @param  string                    $customer  Nombre del cliente (URL-encoded)
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function pdfDownload(Request $request, string $customer)
    {
        $customer = urldecode($customer);
        $periodo  = $request->input('periodo');

        $pdf      = app(MspPdfService::class);
        $path     = $pdf->generate($customer, $periodo, forceRegenerate: true);
        $filename = $pdf->buildFilename($customer, $periodo);

        try {
            app(SharePointService::class)->uploadPdf($path, $filename);
        } catch (\Throwable $e) {
            Log::error("SharePoint PDF upload individual [{$filename}]: " . $e->getMessage());
        }

        return response()->download($path, $filename);
    }

    /**
     * Vista de descarga masiva de PDFs.
     *
     * Muestra el listado de clientes disponibles para el período seleccionado,
     * permitiendo seleccionar cuáles incluir en el ZIP.
     *
     * @param  \Illuminate\Http\Request  $request  Parámetro opcional: periodo
     * @return \Illuminate\View\View               Vista admin.reports.msp.descarga_masiva
     */
    public function descargaMasivaIndex(Request $request)
    {
        $periodos = MspReport::uniquePeriodos();
        $periodo  = $request->input('periodo', $periodos[0] ?? null);

        $clientes = collect();
        if ($periodo) {
            $customerNames = MspReport::where('periodo', $periodo)->distinct()->pluck('customer_name');
            $clientes = MspClient::whereIn('customer_name', $customerNames)
                ->orderBy('customer_name')
                ->get();
        }

        return view('admin.reports.msp.descarga_masiva', compact('periodos', 'periodo', 'clientes'));
    }

    /**
     * Genera un ZIP con los PDFs de múltiples clientes y lo descarga.
     *
     * Comportamiento:
     *  - Límite de 30 clientes por petición para controlar el tiempo de respuesta.
     *  - Genera cada PDF individualmente con Browsershot, agrega al ZIP y sube a SharePoint.
     *  - Los errores por cliente son registrados y acumulados; solo devuelve error total
     *    si ningún PDF pudo generarse.
     *  - El ZIP se elimina del servidor tras la descarga (deleteFileAfterSend).
     *
     * @param  \Illuminate\Http\Request  $request  Campos: periodo, clientes[] (array de nombres)
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function descargaMasivaZip(Request $request)
    {
        $request->validate([
            'periodo'    => 'required|string',
            'clientes'   => 'required|array|min:1|max:30',
            'clientes.*' => 'required|string',
        ]);

        $periodo = $request->input('periodo');
        $nombres = $request->input('clientes');

        $zipName = 'MSP-Reportes-' . str_replace(' ', '-', $periodo) . '.zip';
        $zipPath = storage_path("app/public/msp_pdfs/zips/{$zipName}");

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', '❌ No se pudo crear el archivo ZIP.');
        }

        $generados = 0;
        $errores   = [];

        foreach ($nombres as $customerName) {
            try {
                $stats       = MspReport::statsForCustomer($customerName, $periodo);
                $logoUrl     = $this->resolveLogoUrl($customerName, $periodo);
                $ovnicomLogo = $this->getOvnicomLogo();

                $html = view('admin.reports.msp.pdf_template', [
                    'customer'    => $customerName,
                    'stats'       => $stats,
                    'periodo'     => $periodo,
                    'logoUrl'     => $logoUrl,
                    'ovnicomLogo' => $ovnicomLogo,
                ])->render();

                $filename = $this->buildPdfFilename($customerName, $periodo);
                $pdfPath  = storage_path("app/public/msp_pdfs/{$filename}");

                $this->generatePdf($html, $pdfPath);
                $zip->addFile($pdfPath, $filename);

                try {
                    app(SharePointService::class)->uploadPdf($pdfPath, $filename);
                } catch (\Throwable $e) {
                    Log::error("SharePoint PDF upload masivo [{$filename}]: " . $e->getMessage());
                }

                $generados++;

            } catch (\Throwable $e) {
                Log::error("Error generando PDF para [{$customerName}]: " . $e->getMessage());
                $errores[] = $customerName;
            }
        }

        $zip->close();

        if ($generados === 0) {
            @unlink($zipPath);
            return back()->with('error',
                '❌ No se pudo generar ningún PDF. Clientes fallidos: ' . implode(', ', $errores)
            );
        }

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    // =========================================================================
    // VENTANA 4 — Envío de correos (SendGrid)
    // =========================================================================

    /**
     * Vista de envío de correos con el PDF adjunto.
     *
     * Lista los clientes del período seleccionado que tienen tickets importados.
     * Solo se muestran clientes que efectivamente tienen datos en `msp_reports`.
     *
     * @param  \Illuminate\Http\Request  $request  Parámetro opcional: periodo
     * @return \Illuminate\View\View               Vista admin.reports.msp.correos
     */
    public function correos(Request $request)
    {
        $periodos = MspReport::uniquePeriodos();
        $periodo  = $request->input('periodo', $periodos[0] ?? null);

        $customerNames = MspReport::query()
            ->where('periodo', $periodo)
            ->distinct()
            ->pluck('customer_name');

        $clientes = MspClient::whereIn('customer_name', $customerNames)
            ->orderBy('customer_name')
            ->get();

        return view('admin.reports.msp.correos', compact('clientes', 'periodos', 'periodo'));
    }

    /**
     * Envía el reporte PDF de un cliente individual por correo electrónico vía SendGrid.
     *
     * Delega la lógica de generación del PDF y construcción del payload en sendReportEmail().
     * Soporta plantillas visuales opcionales (banner HTML) y variables de sustitución
     * en asunto y cuerpo del mensaje.
     *
     * @param  \Illuminate\Http\Request  $request  Campos: customer_name, email, periodo, subject,
     *                                             mensaje (opcional), plantilla_id (opcional)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enviarCorreo(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string',
            'email'         => 'required|email',
            'periodo'       => 'required|string',
            'subject'       => 'required|string|max:200',
            'mensaje'       => 'nullable|string',
            'plantilla_id'  => 'nullable|integer|exists:msp_plantillas,id',
        ]);

        $result = $this->sendReportEmail(
            customer:     $request->input('customer_name'),
            email:        $request->input('email'),
            periodo:      $request->input('periodo'),
            subject:      $request->input('subject'),
            mensaje:      $request->input('mensaje', ''),
            plantillaId:  $request->input('plantilla_id'),
        );

        if ($result['success']) {
            return back()->with('success', "✅ Correo enviado a {$request->input('email')} con el PDF adjunto.");
        }

        return back()->with('error', '❌ Error al enviar: ' . $result['error']);
    }

    /**
     * Envío masivo de reportes PDF por correo a múltiples clientes en una sola petición.
     *
     * Optimización clave: pre-carga todos los modelos MspClient de una sola query
     * antes del loop para evitar el problema N+1 (una query por cliente).
     * Los errores son acumulados y reportados en el mensaje de éxito parcial.
     *
     * Variables de sustitución disponibles en asunto/mensaje:
     *  [[cliente]], [[periodo]], [[incidentes]], [[solicitudes]], [[t_inc]], [[t_sol]], [[cuenta]]
     *
     * @param  \Illuminate\Http\Request  $request  Campos: periodo, clientes[] (customer_name + email),
     *                                             subject, mensaje (opcional), plantilla_id (opcional)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enviarMasivo(Request $request)
    {
        $request->validate([
            'periodo'                  => 'required|string',
            'clientes'                 => 'required|array',
            'clientes.*.customer_name' => 'required|string',
            'clientes.*.email'         => 'required|email',
            'subject'                  => 'required|string|max:200',
            'mensaje'                  => 'nullable|string',
            'plantilla_id'             => 'nullable|integer|exists:msp_plantillas,id',
        ]);

        $periodo     = $request->input('periodo');
        $subject     = $request->input('subject');
        $mensaje     = $request->input('mensaje', '');
        $plantillaId = $request->input('plantilla_id');

        $enviados = 0;
        $errores  = [];

        // Pre-cargar todos los clientes de una vez para evitar N queries dentro del loop.
        $customerNames = collect($request->input('clientes'))->pluck('customer_name');
        $clientesMap   = MspClient::whereIn('customer_name', $customerNames)
            ->get()
            ->keyBy('customer_name');

        foreach ($request->input('clientes') as $cliente) {
            $result = $this->sendReportEmail(
                customer:     $cliente['customer_name'],
                email:        $cliente['email'],
                periodo:      $periodo,
                subject:      $subject,
                mensaje:      $mensaje,
                plantillaId:  $plantillaId,
                clienteModel: $clientesMap->get($cliente['customer_name']),
            );

            if ($result['success']) {
                $enviados++;
            } else {
                $errores[] = "{$cliente['customer_name']}: {$result['error']}";
                Log::error("Error enviando correo masivo a [{$cliente['customer_name']}]: " . $result['error']);
            }
        }

        $msg = "✅ {$enviados} correos enviados.";
        if ($errores) $msg .= ' ⚠️ Errores: ' . implode(' | ', $errores);

        return back()->with('success', $msg);
    }

    // =========================================================================
    // Chat IA
    // =========================================================================

    /**
     * Vista del chat de inteligencia artificial sobre datos MSP.
     *
     * @return \Illuminate\View\View  Vista admin.reports.msp.chat
     */
    public function chat(Request $request)
    {
        return view('admin.reports.msp.chat');
    }

    /**
     * Endpoint AJAX del chat IA con contexto de datos MSP.
     *
     * Inyecta al LLM (vía Laravel\AI) el contexto del sistema con los períodos
     * y clientes disponibles. Si el mensaje del usuario menciona el nombre de un
     * cliente, enriquece el contexto con las estadísticas reales de ese cliente
     * en el período más reciente.
     *
     * Comportamiento especial de acciones estructuradas:
     * Si el LLM responde con JSON en lugar de texto, se trata como una acción
     * (ej: {"action":"download_pdf","customer":"NOMBRE","periodo":"PERIODO"})
     * y se devuelve en el campo `action` de la respuesta JSON para que el frontend
     * la ejecute directamente.
     *
     * @param  \Illuminate\Http\Request  $request  Campos: message (texto del usuario),
     *                                             history (array de mensajes previos, opcional)
     * @return \Illuminate\Http\JsonResponse       {response: string, action: array|null}
     */
    public function chatApi(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'nullable|array',
        ]);

        $message = $request->input('message');
        $history = $request->input('history', []);

        $periodos = MspReport::uniquePeriodos();
        $clientes = MspReport::query()->select('customer_name')->distinct()->pluck('customer_name')->toArray();

        $systemPrompt = "Eres un asistente experto en reportes MSP (Managed Service Provider) de Ovnicom. 
Tienes acceso a datos de tickets de soporte técnico de clientes empresariales.

PERÍODOS DISPONIBLES: " . implode(', ', $periodos) . "
CLIENTES DISPONIBLES (" . count($clientes) . " total): " . implode(', ', array_slice($clientes, 0, 50)) . (count($clientes) > 50 ? '...' : '') . "

Puedes ayudar a:
1. Consultar estadísticas de un cliente específico
2. Descargar el PDF de un cliente (responde con JSON: {\"action\":\"download_pdf\",\"customer\":\"NOMBRE\",\"periodo\":\"PERIODO\"})
3. Enviar el PDF por correo (responde con JSON: {\"action\":\"send_email\",\"customer\":\"NOMBRE\",\"periodo\":\"PERIODO\",\"email\":\"EMAIL\"})
4. Comparar clientes o períodos
5. Dar resúmenes generales

Cuando el usuario pida descargar o enviar un PDF, responde ÚNICAMENTE con el JSON de acción.
Para cualquier otra consulta responde en español de forma clara y concisa.";

        $messages     = array_map(fn($h) => ['role' => $h['role'], 'content' => $h['content']], $history);
        $statsContext = '';

        foreach ($clientes as $c) {
            if (stripos($message, $c) !== false) {
                $periodo = $periodos[count($periodos) - 1] ?? null;
                $stats   = MspReport::statsForCustomer($c, $periodo);
                $statsContext = "\n\nDATOS ACTUALES DE {$c} (período {$periodo}):\n" . json_encode([
                    'total_tickets'            => $stats['total_tickets'],
                    'cant_incidentes'          => $stats['cant_incidentes'],
                    'cant_solicitudes'         => $stats['cant_solicitudes'],
                    'tiempo_prom_incidentes'   => round($stats['tiempo_prom_incidentes'], 2),
                    'tiempo_prom_solicitudes'  => round($stats['tiempo_prom_solicitudes'], 2),
                    'por_ubicacion_incidentes' => $stats['por_ubicacion_incidentes'],
                    'alarma_vs_reportado'      => $stats['alarma_vs_reportado'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                break;
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message . $statsContext];

        $ai       = app(\Laravel\AI\AI::class);
        $response = $ai->ask($systemPrompt, $messages);
        $content  = $response->content ?? $response->text ?? (string) $response;

        $action = null;
        if (str_starts_with(trim($content), '{')) {
            try {
                $action = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                // El LLM devolvió algo que no es JSON válido; lo tratamos como texto normal.
            }
        }

        return response()->json(['response' => $content, 'action' => $action]);
    }

    // =========================================================================
    // SharePoint
    // =========================================================================

    /**
     * Importa un archivo Excel de SharePoint a la base de datos local.
     *
     * Flujo:
     *  1. Descarga el archivo desde SharePoint (por item_id si está disponible,
     *     o por nombre como fallback).
     *  2. Crea un registro MspUploadBatch para rastrear la importación.
     *  3. Ejecuta MspReportsImport (Maatwebsite Excel) que procesa cada fila.
     *  4. Actualiza las métricas del batch (total de registros y clientes únicos).
     *  5. Elimina el archivo temporal descargado.
     *
     * @param  \Illuminate\Http\Request  $request  Campos: filename, periodo, item_id (SharePoint ID, opcional)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sharepointImport(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'periodo'  => 'required|string|max:50',
        ]);

        $sp       = app(SharePointService::class);
        $periodo  = trim($request->input('periodo'));
        $filename = $request->input('filename');
        $itemId   = $request->input('item_id');

        try {
            $tempPath = $itemId
                ? $sp->downloadFileById($itemId, $filename)
                : $sp->downloadFileByName($filename);

            $batch = MspUploadBatch::create([
                'filename'        => $filename . ' (SharePoint)',
                'sharepoint_item_id' => $itemId,
                'periodo'         => $periodo,
                'total_registros' => 0,
                'clientes_unicos' => 0,
            ]);

            Excel::import(new MspReportsImport($periodo, $batch->id), $tempPath);

            @unlink($tempPath);

            $total  = MspReport::where('batch_id', $batch->id)->count();
            $unicos = MspReport::where('batch_id', $batch->id)->distinct('customer_name')->count();
            $batch->update(['total_registros' => $total, 'clientes_unicos' => $unicos]);

            return back()->with('success', "✅ Importados {$total} registros de {$unicos} clientes para {$periodo}.");

        } catch (\Throwable $e) {
            Log::error('Error importando de SharePoint: ' . $e->getMessage());
            return back()->with('error', '❌ Error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Genera un PDF a partir de HTML usando Browsershot (Chromium headless).
     *
     * Configurado específicamente para ejecutarse en contenedores Docker Linux:
     *  - `noSandbox()` → requerido en entornos sin privilegios (Docker sin --cap-add).
     *  - `disable-dev-shm-usage` → evita errores de memoria compartida en contenedores pequeños.
     *  - `disable-gpu` → necesario en servidores sin GPU/X11.
     *  - Rutas de Chrome/Node configurables vía variables de entorno BROWSERSHOT_*.
     *  - `waitUntilNetworkIdle()` → asegura que los recursos externos (CSS/fuentes) carguen.
     *  - Timeout de 120 segundos para PDFs con muchos datos.
     *
     * @param  string  $html        HTML completo del reporte a convertir
     * @param  string  $outputPath  Ruta absoluta donde guardar el PDF generado
     * @return void
     */
    private function generatePdf(string $html, string $outputPath): void
    {
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        Browsershot::html($html)
            ->setChromePath(env('BROWSERSHOT_CHROME_PATH', '/usr/bin/chromium'))
            ->setNodeBinary(env('BROWSERSHOT_NODE_PATH', '/usr/bin/node'))
            ->setNpmBinary(env('BROWSERSHOT_NPM_PATH', '/usr/bin/npm'))
            ->noSandbox()
            ->addChromiumArguments([
                'disable-dev-shm-usage',
                'disable-gpu',
            ])
            ->format('A4')
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->save($outputPath);
    }

    /**
     * Construye un nombre de archivo PDF seguro (sin caracteres problemáticos en sistema de archivos).
     *
     * Reemplaza espacios, barras, comas y backslashes con guiones para garantizar
     * compatibilidad en sistemas Windows, Linux y SharePoint.
     * Formato resultante: MSP-{cliente}-{periodo}.pdf
     *
     * @param  string       $customer  Nombre del cliente
     * @param  string|null  $periodo   Período del reporte (ej: "Enero 2025")
     * @return string                  Nombre de archivo seguro (ej: "MSP-Empresa-SA-Enero-2025.pdf")
     */
    private function buildPdfFilename(string $customer, ?string $periodo): string
    {
        $safeCustomer = str_replace([' ', '/', ',', '\\'], '-', $customer);
        $safePeriodo  = str_replace(' ', '-', $periodo ?? 'reporte');
        return "MSP-{$safeCustomer}-{$safePeriodo}.pdf";
    }

    /**
     * Método centralizado de envío de reporte por correo vía SendGrid API v3.
     *
     * Reutilizado tanto para envío individual (enviarCorreo) como masivo (enviarMasivo).
     * Pasos internos:
     *  1. Obtiene estadísticas del cliente para el período.
     *  2. Sustituye variables dinámicas ([[cliente]], [[periodo]], etc.) en asunto y cuerpo.
     *  3. Si hay plantilla seleccionada, construye el banner HTML con la imagen.
     *  4. Genera el PDF con Browsershot.
     *  5. Construye y envía el payload SendGrid con el PDF como adjunto en Base64.
     *  6. Devuelve array con resultado: ['success' => bool, 'error' => string (si falla)].
     *
     * No lanza excepciones — siempre devuelve un array de resultado para que el
     * caller pueda continuar con los demás clientes en envíos masivos.
     *
     * @param  string           $customer      Nombre del cliente
     * @param  string           $email         Dirección de correo destino
     * @param  string           $periodo       Período del reporte
     * @param  string           $subject       Asunto del correo (puede incluir variables)
     * @param  string           $mensaje       Cuerpo del mensaje (puede incluir variables)
     * @param  int|null         $plantillaId   ID de MspPlantilla para banner opcional
     * @param  MspClient|null   $clienteModel  Modelo pre-cargado (evita query en envío masivo)
     * @return array{success: bool, error?: string}
     */
    private function sendReportEmail(
        string      $customer,
        string      $email,
        string      $periodo,
        string      $subject,
        string      $mensaje = '',
        ?int        $plantillaId = null,
        ?MspClient  $clienteModel = null,
    ): array {
        try {
            $stats   = MspReport::statsForCustomer($customer, $periodo);
            // Usa el modelo pre-cargado si viene del loop masivo; hace la query solo en envíos individuales.
            $cliente = $clienteModel ?? MspClient::where('customer_name', $customer)->first();

            // Sustituir variables en asunto y mensaje
            $variables = [
                '[[cliente]]'     => $customer,
                '[[periodo]]'     => $periodo,
                '[[incidentes]]'  => $stats['cant_incidentes'],
                '[[solicitudes]]' => $stats['cant_solicitudes'],
                '[[t_inc]]'       => number_format($stats['tiempo_prom_incidentes'], 3),
                '[[t_sol]]'       => number_format($stats['tiempo_prom_solicitudes'], 3),
                '[[cuenta]]'      => $cliente?->numero_cuenta ?? 'N/A',
            ];

            $subject = str_replace(array_keys($variables), array_values($variables), $subject);
            $mensaje = $mensaje
                ? str_replace(array_keys($variables), array_values($variables), $mensaje)
                : '';

            // Banner de plantilla (opcional)
            $bannerHtml = '';
            if ($plantillaId) {
                $plantilla = MspPlantilla::find($plantillaId);
                if ($plantilla && $plantilla->imagen_path) {
                    $imagenUrl  = Storage::disk('public')->url($plantilla->imagen_path);
                    $bannerHtml = "<div style='text-align:center;margin-bottom:16px;'>
                        <img src='{$imagenUrl}' style='max-width:600px;width:100%;border-radius:8px;' alt='Banner'>
                    </div>";
                }
            }

            // Generar PDF
            $logoUrl     = $this->resolveLogoUrl($customer, $periodo, $cliente);
            $ovnicomLogo = $this->getOvnicomLogo();
            $html        = view('admin.reports.msp.pdf_template',
                compact('customer', 'stats', 'periodo', 'logoUrl', 'ovnicomLogo')
            )->render();

            $filename = $this->buildPdfFilename($customer, $periodo);
            $pdfPath  = storage_path("app/public/msp_pdfs/{$filename}");

            $this->generatePdf($html, $pdfPath);

            // Construir cuerpo del correo
            $bodyHtml = $bannerHtml;
            $bodyHtml .= $mensaje
                ? nl2br(e($mensaje))
                : "<p>Estimado cliente,<br>Adjunto encontrará su informe MSP del período <strong>{$periodo}</strong>.</p>";

            // Payload SendGrid
            $sendgridKey = config('services.sendgrid.api_key');
            $fromEmail   = config('services.sendgrid.from', 'yalveo@ovni.com');

            if (empty($sendgridKey)) {
                return ['success' => false, 'error' => 'SENDGRID_API_KEY no configurado en .env'];
            }

            $payload = [
                'personalizations' => [[
                    'to'      => [['email' => $email, 'name' => $customer]],
                    'subject' => $subject,
                ]],
                'from'    => ['email' => $fromEmail, 'name' => 'Ovnicom MSP Reports'],
                'content' => [[
                    'type'  => 'text/html',
                    'value' => $bodyHtml,
                ]],
                'attachments' => [[
                    'content'     => base64_encode(file_get_contents($pdfPath)),
                    'type'        => 'application/pdf',
                    'filename'    => $filename,
                    'disposition' => 'attachment',
                ]],
            ];

            $response = Http::withToken($sendgridKey)
                ->post('https://api.sendgrid.com/v3/mail/send', $payload);

            if ($response->successful() || $response->status() === 202) {
                return ['success' => true];
            }

            return ['success' => false, 'error' => 'SendGrid: ' . $response->body()];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene el logo del cliente como cadena Base64 embebible en HTML/PDF.
     *
     * Retorna null si el cliente no tiene logo configurado en base de datos.
     * El formato devuelto es "data:{mime};base64,{datos}" listo para usar en <img src>.
     *
     * @param  string           $customer  Nombre del cliente
     * @param  string|null      $periodo   Período (no usado actualmente, reservado para futuro)
     * @param  MspClient|null   $cliente   Modelo pre-cargado (evita una query adicional)
     * @return string|null                 Data URI del logo o null si no existe
     */
    private function resolveLogoUrl(string $customer, ?string $periodo, ?MspClient $cliente = null): ?string
    {
        $cliente ??= MspClient::where('customer_name', $customer)->first();
        return $cliente?->getLogoBase64();
    }

    /**
     * Carga el logo de Ovnicom como Data URI Base64 para embebido en PDF.
     *
     * El PDF se genera sin acceso a red (modo offline Chromium), por lo que
     * las imágenes deben estar embebidas en Base64. Lee el archivo directamente
     * del sistema de archivos en storage/app/public/logos/ovnicom.png.
     *
     * @return string|null  Data URI "data:{mime};base64,{datos}" o null si no existe el archivo
     */
    private function getOvnicomLogo(): ?string
    {
        $path = storage_path('app/public/logos/ovnicom.png');
        if (!file_exists($path)) return null;

        $mime   = mime_content_type($path);
        $base64 = base64_encode(file_get_contents($path));

        return "data:{$mime};base64,{$base64}";
    }
    
    /**
     * Refresca un lote de importación re-descargando el Excel original de SharePoint.
     *
     * Restricciones de seguridad:
     *  - Solo se permite actualizar lotes creados en los últimos 7 días.
     *  - Requiere que el batch tenga un sharepoint_item_id registrado.
     *
     * Comportamiento destructivo controlado:
     *  Elimina TODOS los registros del batch antes de re-importar para garantizar
     *  consistencia. Esto significa que si el Excel fuente cambió, los datos reflejarán
     *  la versión actual del archivo en SharePoint.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MspUploadBatch  $batch  Lote a refrescar (model binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshBatch(Request $request, MspUploadBatch $batch)
    {
        // Validar que no hayan pasado más de 7 días
        if ($batch->created_at->diffInDays(now()) > 7) {
            return back()->with('error', '❌ Solo se puede actualizar dentro de los 7 días posteriores a la importación.');
        }

        if (!$batch->sharepoint_item_id) {
            return back()->with('error', '❌ Este batch no tiene referencia a SharePoint.');
        }

        $sp = app(SharePointService::class);

        try {
            $tempPath = $sp->downloadFileById($batch->sharepoint_item_id, $batch->filename);

            // Eliminar registros anteriores del batch
            MspReport::where('batch_id', $batch->id)->delete();

            // Re-importar
            Excel::import(new MspReportsImport($batch->periodo, $batch->id), $tempPath);

            @unlink($tempPath);

            $total  = MspReport::where('batch_id', $batch->id)->count();
            $unicos = MspReport::where('batch_id', $batch->id)->distinct('customer_name')->count();

            $batch->update([
                'total_registros' => $total,
                'clientes_unicos' => $unicos,
            ]);

            return back()->with('success', "✅ Actualizado: {$total} registros de {$unicos} clientes para {$batch->periodo}.");

        } catch (\Throwable $e) {
            Log::error('Error refrescando batch: ' . $e->getMessage());
            return back()->with('error', '❌ Error: ' . $e->getMessage());
        }
    }
}