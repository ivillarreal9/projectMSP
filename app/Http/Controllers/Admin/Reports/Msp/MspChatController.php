<?php

namespace App\Http\Controllers\Admin\Reports\Msp;

use App\Ai\Agents\OvniMspAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MspChatController extends Controller
{
    public function api(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:20',
        ]);

        $message = trim($request->input('message'));
        $history = $request->input('history', []);

        try {
            $agent    = new OvniMspAgent(userMessage: $message, chatHistory: $history);
            $response = (string) $agent->prompt($message);

            $action  = null;
            $trimmed = trim($response);

            // Detectar JSON de acción en la respuesta
            if (preg_match('/\{[^{}]*"action"\s*:\s*"(download_pdf|send_email)"[^{}]*\}/s', $trimmed, $match)) {
                try {
                    $decoded = json_decode($match[0], true, 512, JSON_THROW_ON_ERROR);

                    if (isset($decoded['action'])) {
                        $action = $decoded;

                        if ($decoded['action'] === 'download_pdf') {
                            $action['download_url'] = route('admin.msp.pdf.download',
                                urlencode($decoded['customer'])
                            ) . '?periodo=' . urlencode($decoded['periodo'] ?? '');

                            $response = preg_replace('/\{[^{}]*"action"[^{}]*\}/s', '', $response);
                            $response = trim($response) ?: "✅ Generando PDF de **{$decoded['customer']}** — **{$decoded['periodo']}**...";
                        }

                        if ($decoded['action'] === 'send_email') {
                            // Construir URL de envío de correo
                            $action['email_url'] = route('admin.msp.correos.enviar');

                            $response = preg_replace('/\{[^{}]*"action"[^{}]*\}/s', '', $response);
                            $response = trim($response) ?: "✉️ Enviando reporte de **{$decoded['customer']}** a **{$decoded['email']}**...";
                        }
                    }
                } catch (\Throwable) {}
            }

            return response()->json([
                'response' => $response,
                'action'   => $action,
            ]);

        } catch (\Exception $e) {
            \Log::error('OvniChat error: ' . $e->getMessage());

            $msg = str_contains(strtolower($e->getMessage()), 'rate limit')
                ? '⏳ Demasiadas consultas seguidas. Espera unos segundos e intenta de nuevo.'
                : '❌ Error al procesar tu consulta. Por favor intenta de nuevo.';

            return response()->json(['response' => $msg], 200);
        }
    }
}