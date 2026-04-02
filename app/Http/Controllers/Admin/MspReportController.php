<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\MspReportsImport;
use App\Models\MspReport;
use App\Models\MspUploadBatch;
use App\Services\SharePointService;  // ← AGREGAR ESTA LÍNEA
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // NO cargar SharePoint al inicio
        $files       = [];
        $spError     = null;
        $hasCredentials = app(\App\Services\SharePointService::class)->hasCredentials();

        $batches  = \App\Models\MspUploadBatch::orderByDesc('created_at')->take(10)->get();
        $settings = \App\Models\MspSetting::allAsArray();

        return view('admin.reports.msp.index', compact(
            'files', 'spError', 'hasCredentials', 'batches', 'settings'
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

        // Crear registro de batch
        $batch = \App\Models\MspUploadBatch::create([
            'filename'         => $file->getClientOriginalName(),
            'periodo'          => $periodo,
            'total_registros'  => 0,
            'clientes_unicos'  => 0,
        ]);

        Excel::import(new MspReportsImport($periodo, $batch->id), $file);

        // Actualizar estadísticas del batch
        $total   = MspReport::where('periodo', $periodo)->count();
        $unicos  = MspReport::where('periodo', $periodo)->distinct('customer_name')->count();
        $batch->update(['total_registros' => $total, 'clientes_unicos' => $unicos]);

        return back()->with('success', "✅ Importados {$total} registros de {$unicos} clientes para el período {$periodo}.");
    }

    // =========================================================================
    // VENTANA 2 — Ver información de clientes
    // =========================================================================

    public function clientes(Request $request)
    {
        $periodos = MspReport::uniquePeriodos();
        $periodo  = $request->input('periodo', $periodos[0] ?? null);
        $search   = $request->input('search', '');

        $query = MspReport::query()
            ->select('customer_name', 'email_cliente', 'logo_path',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw("SUM(CASE WHEN tipo_ticket = 'Incidente' THEN 1 ELSE 0 END) as incidentes"),
                DB::raw("SUM(CASE WHEN tipo_ticket = 'Solicitud' THEN 1 ELSE 0 END) as solicitudes"),
                DB::raw('AVG(tiempo_vida_ticket) as tiempo_prom')
            )
            ->groupBy('customer_name', 'email_cliente', 'logo_path');

        if ($periodo) $query->where('periodo', $periodo);
        if ($search)  $query->where('customer_name', 'like', "%{$search}%");

        $clientes = $query->orderBy('customer_name')->paginate(30)->withQueryString();

        return view('admin.reports.msp.clientes', compact('clientes', 'periodos', 'periodo', 'search'));
    }

    public function clienteDetalle(Request $request, string $customer)
    {
        $customer    = urldecode($customer);
        $periodo     = $request->input('periodo');
        $stats       = MspReport::statsForCustomer($customer, $periodo);
        $periodos    = MspReport::uniquePeriodos();
        $clienteInfo = MspReport::where('customer_name', $customer)
                        ->select('email_cliente', 'logo_path', 'numero_cuenta')
                        ->first();

        return view('admin.reports.msp.cliente_detalle', 
            compact('customer', 'stats', 'periodos', 'periodo', 'clienteInfo'));
    }

    // =========================================================================
    // VENTANA 3 — PDF
    // =========================================================================

    public function pdfPreview(Request $request, string $customer)
    {
        $customer = urldecode($customer); // ← AGREGAR
        $periodo  = $request->input('periodo');
        $stats    = MspReport::statsForCustomer($customer, $periodo);
        $logoUrl  = $this->resolveLogoUrl($customer, $periodo);

        return view('admin.reports.msp.pdf_template', compact('customer', 'stats', 'periodo', 'logoUrl'));
    }

    public function pdfDownload(Request $request, string $customer)
    {
        $customer = urldecode($customer); // ← AGREGAR
        $periodo  = $request->input('periodo');
        $stats    = MspReport::statsForCustomer($customer, $periodo);
        $logoUrl  = $this->resolveLogoUrl($customer, $periodo);

        $html     = view('admin.reports.msp.pdf_template', compact('customer', 'stats', 'periodo', 'logoUrl'))->render();
        $filename = 'MSP-' . str_replace([' ', '/'], '-', $customer) . '-' . ($periodo ?? 'reporte') . '.pdf';
        $path     = storage_path("app/public/msp_pdfs/{$filename}");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(false);
    }

    // =========================================================================
    // VENTANA 4 — Envío de correos (SendGrid)
    // =========================================================================

    public function correos(Request $request)
    {
        $periodos  = MspReport::uniquePeriodos();
        $periodo   = $request->input('periodo', $periodos[0] ?? null);

        $clientes = MspReport::query()
            ->select('customer_name', 'email_cliente')
            ->where('periodo', $periodo)
            ->distinct()
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
        ]);

        $customer = $request->input('customer_name');
        $periodo  = $request->input('periodo');

        // Generar PDF
        $stats   = MspReport::statsForCustomer($customer, $periodo);
        $logoUrl = $this->resolveLogoUrl($customer, $periodo);
        $html    = view('admin.reports.msp.pdf_template', compact('customer', 'stats', 'periodo', 'logoUrl'))->render();

        $filename = 'MSP-' . str_replace([' ', '/'], '-', $customer) . '-' . $periodo . '.pdf';
        $pdfPath  = storage_path("app/public/msp_pdfs/{$filename}");

        if (!is_dir(dirname($pdfPath))) {
            mkdir(dirname($pdfPath), 0755, true);
        }

        Browsershot::html($html)->format('A4')->showBackground()->waitUntilNetworkIdle()->save($pdfPath);

        // Enviar vía SendGrid
        $sendgridKey = \App\Models\MspSetting::get('sendgrid_api_key');
        $fromEmail   = \App\Models\MspSetting::get('sendgrid_from_email', 'ivillarreal@ovni.com');
        $fromName    = 'Ovnicom MSP Reports';

        $pdfContent  = base64_encode(file_get_contents($pdfPath));

        $payload = [
            'personalizations' => [[
                'to' => [['email' => $request->input('email'), 'name' => $customer]],
                'subject' => $request->input('subject'),
            ]],
            'from' => ['email' => $fromEmail, 'name' => $fromName],
            'content' => [[
                'type'  => 'text/html',
                'value' => $request->input('mensaje')
                    ? nl2br($request->input('mensaje'))
                    : "<p>Estimado cliente,<br>Adjunto encontrará su informe MSP del período <strong>{$periodo}</strong>.</p>",
            ]],
            'attachments' => [[
                'content'     => $pdfContent,
                'type'        => 'application/pdf',
                'filename'    => $filename,
                'disposition' => 'attachment',
            ]],
        ];

        $response = \Illuminate\Support\Facades\Http::withToken($sendgridKey)
            ->post('https://api.sendgrid.com/v3/mail/send', $payload);

        if ($response->successful() || $response->status() === 202) {
            return back()->with('success', "✅ Correo enviado a {$request->input('email')} con el PDF adjunto.");
        }

        return back()->with('error', '❌ Error al enviar: ' . $response->body());
    }

    public function enviarMasivo(Request $request)
    {
        $request->validate([
            'periodo'   => 'required|string',
            'clientes'  => 'required|array',
            'clientes.*.customer_name' => 'required|string',
            'clientes.*.email'         => 'required|email',
            'subject'   => 'required|string|max:200',
        ]);

        $periodo  = $request->input('periodo');
        $subject  = $request->input('subject');
        $enviados = 0;
        $errores  = [];

        foreach ($request->input('clientes') as $cliente) {
            try {
                $request->merge([
                    'customer_name' => $cliente['customer_name'],
                    'email'         => $cliente['email'],
                    'periodo'       => $periodo,
                    'subject'       => $subject,
                ]);
                $this->enviarCorreo($request);
                $enviados++;
            } catch (\Throwable $e) {
                $errores[] = $cliente['customer_name'] . ': ' . $e->getMessage();
            }
        }

        $msg = "✅ {$enviados} correos enviados.";
        if ($errores) $msg .= ' ⚠️ Errores: ' . implode(', ', $errores);
        return back()->with('success', $msg);
    }

    // =========================================================================
    // Chat IA — responde preguntas y puede generar/enviar PDFs
    // =========================================================================

    public function chat(Request $request)
    {
        return view('admin.reports.msp.chat');
    }

    public function chatApi(Request $request)
    {
        $request->validate(['message' => 'required|string', 'history' => 'nullable|array']);

        $message = $request->input('message');
        $history = $request->input('history', []);

        // Contexto de datos para la IA
        $periodos  = MspReport::uniquePeriodos();
        $clientes  = MspReport::query()->select('customer_name')->distinct()->pluck('customer_name')->toArray();

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

        // Construir mensajes para la IA
        $messages = array_map(fn($h) => ['role' => $h['role'], 'content' => $h['content']], $history);

        // Si la pregunta involucra datos de un cliente específico, adjuntar stats
        $statsContext = '';
        foreach ($clientes as $c) {
            if (stripos($message, $c) !== false) {
                $periodo = $periodos[count($periodos) - 1] ?? null;
                $stats = MspReport::statsForCustomer($c, $periodo);
                $statsContext = "\n\nDATOS ACTUALES DE {$c} (período {$periodo}):\n" . json_encode([
                    'total_tickets'          => $stats['total_tickets'],
                    'cant_incidentes'        => $stats['cant_incidentes'],
                    'cant_solicitudes'       => $stats['cant_solicitudes'],
                    'tiempo_prom_incidentes' => round($stats['tiempo_prom_incidentes'], 2),
                    'tiempo_prom_solicitudes'=> round($stats['tiempo_prom_solicitudes'], 2),
                    'por_ubicacion_incidentes' => $stats['por_ubicacion_incidentes'],
                    'alarma_vs_reportado'    => $stats['alarma_vs_reportado'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                break;
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message . $statsContext];

        // Llamar a Laravel AI (OpenAI/Anthropic según config)
        $ai = app(\Laravel\AI\AI::class);
        $response = $ai->ask($systemPrompt, $messages);

        $content = $response->content ?? $response->text ?? (string)$response;

        // Detectar si es una acción JSON
        $action = null;
        if (str_starts_with(trim($content), '{')) {
            try {
                $action = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {}
        }

        return response()->json(['response' => $content, 'action' => $action]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function resolveLogoUrl(string $customer, ?string $periodo): ?string
    {
        $logo = MspReport::where('customer_name', $customer)
            ->when($periodo, fn($q) => $q->where('periodo', $periodo))
            ->whereNotNull('logo_path')
            ->where('logo_path', '!=', '')
            ->value('logo_path');

        if ($logo) {
            $fullPath = storage_path('app/public/' . $logo);
            if (file_exists($fullPath)) {
                // Convertir a base64 para que Browsershot pueda usarlo
                $mime    = mime_content_type($fullPath);
                $base64  = base64_encode(file_get_contents($fullPath));
                return "data:{$mime};base64,{$base64}";
            }
        }

        return null;
    }

    public function sharepointIndex(Request $request)
    {
        $sp      = app(\App\Services\SharePointService::class);
        $files   = [];
        $error   = null;

        try {
            $files = $sp->listExcelFiles();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        // Si es petición AJAX/fetch devolver JSON
        if ($request->ajax() || $request->wantsJson()) {
            if ($error) {
                return response()->json(['error' => $error]);
            }
            return response()->json(['files' => $files]);
        }

        // Si es petición normal devolver vista
        $periodos = \App\Models\MspReport::uniquePeriodos();
        return view('admin.reports.msp.sharepoint', compact('files', 'error', 'periodos'));
    }
    
    public function sharepointImport(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'periodo'  => 'required|string|max:50',
        ]);

        $sp       = app(\App\Services\SharePointService::class);
        $periodo  = trim($request->input('periodo'));
        $filename = $request->input('filename');
        $itemId   = $request->input('item_id');

        try {
            // Descargar usando item_id si está disponible, sino por nombre
            if ($itemId) {
                $tempPath = $sp->downloadFileById($itemId, $filename);
            } else {
                $tempPath = $sp->downloadFileByName($filename);
            }

            $batch = \App\Models\MspUploadBatch::create([
                'filename'        => $filename . ' (SharePoint)',
                'periodo'         => $periodo,
                'total_registros' => 0,
                'clientes_unicos' => 0,
            ]);

            \Maatwebsite\Excel\Facades\Excel::import(
                new \App\Imports\MspReportsImport($periodo, $batch->id),
                $tempPath
            );

            @unlink($tempPath);

            $total  = \App\Models\MspReport::where('periodo', $periodo)->count();
            $unicos = \App\Models\MspReport::where('periodo', $periodo)->distinct('customer_name')->count();
            $batch->update(['total_registros' => $total, 'clientes_unicos' => $unicos]);

            return back()->with('success', "✅ Importados {$total} registros de {$unicos} clientes para {$periodo}.");

        } catch (\Throwable $e) {
            return back()->with('error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function saveSettings(Request $request)
    {
        $fields = [
            'azure_tenant_id', 'azure_client_id', 'azure_client_secret',
            'sharepoint_site_url', 'sharepoint_folder_id', 'sharepoint_default_file',
            'sendgrid_api_key', 'sendgrid_from_email',
        ];

        foreach ($fields as $field) {
            $value = $request->input($field);
            // Solo actualizar si se envió un valor no vacío
            // Para campos de contraseña, si viene vacío, no sobreescribir
            $isSecret = in_array($field, ['azure_client_secret', 'sendgrid_api_key']);
            if ($isSecret && empty($value)) {
                continue; // No sobreescribir secretos si se deja en blanco
            }
            if ($value !== null) {
                \App\Models\MspSetting::set($field, $value);
            }
        }

        return back()->with('success', '✅ Credenciales guardadas correctamente.');
    }

    public function updateCliente(Request $request, string $customer)
    {
        $customer = urldecode($customer); // ← CLAVE

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
            $path = $request->file('logo')->store('logos/clientes', 'public');
            $data['logo_path'] = $path;
        }

        $affected = \App\Models\MspReport::where('customer_name', $customer)->update($data);

        return back()->with('success', "✅ {$affected} registros actualizados.");
    }
    
}
