<?php

namespace App\Http\Controllers\Admin\Reports\Msp;

use App\Ai\Agents\OvniMspAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del chat IA para el módulo MSP Reports (Ovni).
 *
 * Expone un endpoint JSON que recibe mensajes del usuario, los procesa
 * mediante {@see OvniMspAgent} y devuelve la respuesta del asistente.
 * Además detecta si la respuesta contiene un JSON de acción estructurada
 * (descarga de PDF o envío de correo) y lo transforma en URLs concretas
 * que el frontend puede ejecutar directamente sin lógica adicional.
 *
 * Ruta: POST /admin/msp/chat/api  (nombre: admin.msp.chat.api)
 */
class MspChatController extends Controller
{
    /**
     * Procesa un mensaje del usuario y devuelve la respuesta del agente IA.
     *
     * Flujo:
     * 1. Valida el mensaje (requerido, string, max 1000 chars) y el historial (array, max 20 turnos).
     * 2. Instancia {@see OvniMspAgent} con el mensaje y el historial.
     * 3. Llama al agente y obtiene la respuesta en texto.
     * 4. Busca en la respuesta un JSON de acción con el patrón:
     *    `{"action": "download_pdf"|"send_email", ...}`
     * 5. Si encuentra la acción `download_pdf`:
     *    - Construye la URL de descarga usando la ruta `admin.msp.pdf.download`.
     *    - Elimina el JSON de la respuesta textual para no mostrarlo al usuario.
     *    - Genera un mensaje de confirmación si la respuesta queda vacía.
     * 6. Si encuentra la acción `send_email`:
     *    - Adjunta la URL del endpoint de envío (`admin.msp.correos.enviar`).
     *    - Elimina el JSON de la respuesta textual.
     *    - Genera un mensaje de confirmación si la respuesta queda vacía.
     * 7. Maneja errores de rate limit con mensaje amigable; otros errores con mensaje genérico.
     *
     * @param  Request $request Petición HTTP con los campos:
     *                          - message (string, requerido, max:1000): mensaje del usuario.
     *                          - history (array, opcional, max:20): historial [{role, content}].
     * @return JsonResponse     JSON con:
     *                          - response (string): texto de respuesta del asistente.
     *                          - action (array|null): datos de acción estructurada con URL, o null.
     *                            Para download_pdf incluye: action, customer, periodo, download_url.
     *                            Para send_email incluye: action, customer, periodo, email, email_url.
     */
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