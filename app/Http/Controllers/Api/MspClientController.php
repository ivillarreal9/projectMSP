<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MspClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para la gestión masiva de datos complementarios de clientes MSP.
 *
 * Permite actualizar en lote los campos adicionales (email de contacto y número
 * de cuenta) de los clientes registrados en la tabla local msp_clients. Este
 * controlador no crea registros nuevos — únicamente actualiza clientes que ya
 * existen en la base de datos.
 *
 * Ruta base: /api/v1/msp-clients
 * Autenticación: Bearer token (Sanctum)
 */
class MspClientController extends Controller
{
    /**
     * Actualiza en lote el email de contacto y/o número de cuenta de clientes MSP.
     *
     * Procesa hasta 1 000 clientes por llamada. Por cada entrada del array:
     * - Si la fila no tiene campos a actualizar (ambos nulos o vacíos) se omite y
     *   suma al contador `skipped`.
     * - Si el cliente no existe en la tabla local se omite y suma al contador `skipped`.
     * - Si la actualización falla con excepción se registra en el array `errors`.
     * La respuesta siempre es 200 aunque no se haya actualizado ningún registro;
     * los contadores reflejan el resultado.
     *
     * POST /api/v1/msp-clients/bulk-update
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request  Requiere: clients (array, min:1, max:1000) donde cada elemento
     *                           tiene: customer_name (string, max:255, requerido),
     *                                  email_cliente (string email, max:255, nullable),
     *                                  numero_cuenta (string, max:100, nullable)
     * @return JsonResponse      200 con { updated: int, skipped: int, errors: [], total: int }
     *                           | 422 validación fallida (array vacío, campos inválidos)
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'clients'                 => 'required|array|min:1|max:1000',
            'clients.*.customer_name' => 'required|string|max:255',
            'clients.*.email_cliente' => 'nullable|email|max:255',
            'clients.*.numero_cuenta' => 'nullable|string|max:100',
        ]);

        $updated = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($request->input('clients') as $item) {
            try {
                $name = trim($item['customer_name']);

                $fields = array_filter([
                    'email_cliente' => $item['email_cliente'] ?? null,
                    'numero_cuenta' => $item['numero_cuenta'] ?? null,
                ], fn($v) => $v !== null && $v !== '');

                if (empty($fields)) {
                    $skipped++;
                    continue;
                }

                $rows = MspClient::where('customer_name', $name)->update($fields);

                if ($rows > 0) {
                    $updated++;
                } else {
                    $skipped++; // cliente no existe en MSP
                }

            } catch (\Throwable $e) {
                $errors[] = ($item['customer_name'] ?? '?') . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'updated' => $updated,
            'skipped' => $skipped,
            'errors'  => $errors,
            'total'   => count($request->input('clients')),
        ]);
    }
}
