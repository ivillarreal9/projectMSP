<?php

// app/Http/Controllers/SharePointWebhookController.php

namespace App\Http\Controllers;

use App\Jobs\SyncSharePointExcelJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SharePointWebhookController extends Controller
{
    // ── Validación inicial del webhook (Microsoft lo llama al registrar) ──
    public function validate(Request $request)
    {
        $token = $request->query('validationToken');
        if ($token) {
            return response($token, 200)->header('Content-Type', 'text/plain');
        }
        return response('ok', 200);
    }

    // ── Notificación de cambio en SharePoint ──────────────────
    public function notify(Request $request)
    {
        Log::info('SharePoint webhook recibido', $request->all());

        $notifications = $request->input('value', []);

        foreach ($notifications as $notification) {
            $clientState = $notification['clientState'] ?? '';

            // Verificar que viene de nuestra app
            if ($clientState !== config('app.key')) {
                Log::warning('SharePoint webhook: clientState inválido');
                continue;
            }

            // Despachar job de sincronización
            $periodo = Carbon::now()->translatedFormat('F Y');
            SyncSharePointExcelJob::dispatch($periodo, false);

            Log::info('SharePoint webhook: job despachado', ['periodo' => $periodo]);
        }

        // Microsoft requiere respuesta 202 inmediata
        return response('', 202);
    }

    // ── Registrar/renovar webhook ─────────────────────────────
    public function register(Request $request)
    {
        try {
            $sharePoint = app(\App\Services\SharePointService::class);
            $notifUrl   = route('msp.webhook.notify');
            $result     = $sharePoint->registerWebhook($notifUrl);

            return response()->json([
                'ok'         => true,
                'id'         => $result['id'],
                'expiration' => $result['expirationDateTime'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
