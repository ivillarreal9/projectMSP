<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\MspReportsImport;
use App\Models\MspReport;
use App\Models\MspClient;
use App\Models\MspUploadBatch;
use App\Models\MspPlantilla;
use App\Services\SharePointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Browsershot\Browsershot;

class MspReportController extends Controller
{
    // =========================================================================
    // VENTANA 1 — Subir Excel
    // =========================================================================

    public function index()
    {
        $sp             = app(SharePointService::class);
        $hasCredentials = $sp->hasCredentials();
        $missingEnvVars = $hasCredentials ? [] : $sp->missingCredentials();
        $batches        = MspUploadBatch::orderByDesc('created_at')->take(10)->get();

        return view('admin.reports.msp.index', compact(
            'hasCredentials', 'missingEnvVars', 'batches'
        ));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
            'periodo'    => 'required|string|max:50',
        ]);

        $file    = $request->file('excel_file');
        $periodo = trim($request->input('periodo'));

        $batch = MspUploadBatch::create([
            'filename'        => $file->getClientOriginalName(),
            'periodo'         => $periodo,
            'total_registros' => 0,
            'clientes_unicos' => 0,
        ]);

        Excel::import(new MspReportsImport($periodo, $batch->id), $file);

        $total  = MspReport::where('batch_id', $batch->id)->count();
        $unicos = MspReport::where('batch_id', $batch->id)->distinct('customer_name')->count();
        $batch->update(['total_registros' => $total, 'clientes_unicos' => $unicos]);

        return back()->with('success', "✅ Importados {$total} registros de {$unicos} clientes para el período {$periodo}.");
    }

    // =========================================================================
    // VENTANA 2 — Ver información de clientes
    // =========================================================================

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

    public function clienteDetalle(Request $request, string $customer)
    {
        $customer    = urldecode($customer);
        $periodo     = $request->input('periodo');
        $stats       = MspReport::statsForCustomer($customer, $periodo);
        $periodos    = MspReport::uniquePeriodos();
        $clienteInfo = MspClient::where('customer_name', $customer)->first();

        return view('admin.reports.msp.cliente_detalle',
            compact('customer', 'stats', 'periodos', 'periodo', 'clienteInfo'));
    }

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

    public function pdfDownload(Request $request, string $customer)
    {
        $customer    = urldecode($customer);
        $periodo     = $request->input('periodo');
        $stats       = MspReport::statsForCustomer($customer, $periodo);
        $logoUrl     = $this->resolveLogoUrl($customer, $periodo);
        $ovnicomLogo = $this->getOvnicomLogo();

        $html     = view('admin.reports.msp.pdf_template',
            compact('customer', 'stats', 'periodo', 'logoUrl', 'ovnicomLogo')
        )->render();

        $filename = $this->buildPdfFilename($customer, $periodo);
        $path     = storage_path("app/public/msp_pdfs/{$filename}");

        $this->generatePdf($html, $path);

        return response()->download($path, $filename);
    }

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

        foreach ($request->input('clientes') as $cliente) {
            $result = $this->sendReportEmail(
                customer:    $cliente['customer_name'],
                email:       $cliente['email'],
                periodo:     $periodo,
                subject:     $subject,
                mensaje:     $mensaje,
                plantillaId: $plantillaId,
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

    public function chat(Request $request)
    {
        return view('admin.reports.msp.chat');
    }

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

    public function sharepointIndex(Request $request)
    {
        $sp    = app(SharePointService::class);
        $files = [];
        $error = null;

        try {
            $files = $sp->listExcelFiles();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return $error
                ? response()->json(['error' => $error])
                : response()->json(['files' => $files]);
        }

        $periodos = MspReport::uniquePeriodos();
        return view('admin.reports.msp.sharepoint', compact('files', 'error', 'periodos'));
    }

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
     * Genera un PDF con Browsershot configurado para funcionar en Docker.
     * Centraliza rutas de Chrome/Node y flags necesarios para contenedores Linux.
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
     * Construye un nombre de archivo PDF seguro a partir del nombre del cliente y período.
     */
    private function buildPdfFilename(string $customer, ?string $periodo): string
    {
        $safeCustomer = str_replace([' ', '/', ',', '\\'], '-', $customer);
        $safePeriodo  = str_replace(' ', '-', $periodo ?? 'reporte');
        return "MSP-{$safeCustomer}-{$safePeriodo}.pdf";
    }

    /**
     * Envía un reporte por correo vía SendGrid. Método centralizado reutilizable
     * por envío individual y masivo.
     */
    private function sendReportEmail(
        string  $customer,
        string  $email,
        string  $periodo,
        string  $subject,
        string  $mensaje = '',
        ?int    $plantillaId = null,
    ): array {
        try {
            // Obtener stats y cliente para sustituir variables
            $stats   = MspReport::statsForCustomer($customer, $periodo);
            $cliente = MspClient::where('customer_name', $customer)->first();

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
            $logoUrl     = $this->resolveLogoUrl($customer, $periodo);
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
            $fromEmail   = config('services.sendgrid.from', 'ivillarreal@ovni.com');

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

    private function resolveLogoUrl(string $customer, ?string $periodo): ?string
    {
        $cliente = MspClient::where('customer_name', $customer)->first();
        return $cliente?->getLogoBase64();
    }

    private function getOvnicomLogo(): ?string
    {
        $path = storage_path('app/public/logos/ovnicom.png');
        if (!file_exists($path)) return null;

        $mime   = mime_content_type($path);
        $base64 = base64_encode(file_get_contents($path));

        return "data:{$mime};base64,{$base64}";
    }
}