<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MspService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para la búsqueda de clientes MSP por RUC.
 *
 * Actúa como proxy autenticado hacia la API externa del MSP, permitiendo
 * que la aplicación móvil consulte clientes sin exponer las credenciales
 * del MSP directamente.
 *
 * Ruta base: /api/v1/msp/customers
 * Autenticación: Bearer token (Sanctum)
 */
class MspCustomerController extends Controller
{
    /**
     * Inyecta el servicio MSP que gestiona la comunicación con la API externa.
     *
     * @param  MspService $msp  Servicio encargado de consultas a la API del MSP
     */
    public function __construct(protected MspService $msp) {}

    /**
     * Busca un cliente MSP por número de RUC.
     *
     * Consulta la API externa del MSP usando el RUC proporcionado.
     * Devuelve los datos del cliente incluyendo id, nombre comercial,
     * dirección y datos de contacto según lo que retorne el MSP.
     *
     * GET /api/v1/msp/customers?ruc={ruc}
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request  Requiere: ruc (string, min:3)
     * @return JsonResponse      200 con { success: true, data: [...] }
     *                           | 422 validación fallida
     *                           | 500 error de comunicación con la API MSP
     */
    public function findByRuc(Request $request): JsonResponse
    {
        $request->validate(['ruc' => 'required|string|min:3']);

        try {
            $customers = $this->msp->findCustomerByRuc($request->ruc);
            return response()->json(['success' => true, 'data' => $customers]);
        } catch (\Exception $e) {
            Log::error('MSP findCustomerByRuc failed', ['ruc' => $request->ruc, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al consultar el cliente.'], 500);
        }
    }
}
