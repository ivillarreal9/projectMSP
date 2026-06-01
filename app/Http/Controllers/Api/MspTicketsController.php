<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MspService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para consultas de tickets MSP desde la aplicación móvil.
 *
 * Expone tres endpoints con distinto nivel de granularidad para optimizar
 * el tráfico de red en la app: búsqueda unificada por RUC, listado de
 * tickets por CustomerId, y detalle de respuestas/SLAs por TicketId.
 *
 * Todos los endpoints soportan paginación manual mediante los parámetros
 * opcionales `limit` y `page`.
 *
 * Ruta base: /api/v1/msp
 * Autenticación: Bearer token (Sanctum)
 */
class MspTicketsController extends Controller
{
    /**
     * Inyecta el servicio MSP que gestiona la comunicación con la API externa.
     *
     * @param  MspService $msp  Servicio encargado de consultas a la API del MSP
     */
    public function __construct(protected MspService $msp) {}

    /**
     * Búsqueda unificada de cliente, tickets, ticket_users, responses y SLAs por RUC.
     *
     * Realiza en una sola llamada todas las consultas necesarias para mostrar
     * el perfil completo de un cliente en la app: datos del cliente, historial
     * de tickets, agentes asignados, respuestas y métricas de SLA.
     * Soporta paginación opcional sobre el array de tickets.
     *
     * GET /api/v1/msp/search?ruc={ruc}[&limit={limit}&page={page}]
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request  Requiere: ruc (string, min:3)
     *                           Opcional: limit (int, 1-500), page (int, min:1)
     * @return JsonResponse      200 con { success: true, data: { cliente, tickets, ticket_users, responses, slas, [total, page, limit] } }
     *                           | 422 validación fallida
     *                           | 500 error de comunicación con la API MSP
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'ruc'   => 'required|string|min:3',
            'limit' => 'nullable|integer|min:1|max:500',
            'page'  => 'nullable|integer|min:1',
        ]);

        try {
            $result = $this->msp->unifiedSearch($request->ruc);

            if ($request->filled('limit')) {
                $limit           = (int) $request->limit;
                $page            = (int) ($request->page ?? 1);
                $tickets         = array_slice($result['tickets'], ($page - 1) * $limit, $limit);
                $result['tickets']      = $tickets;
                $result['total']        = count($result['tickets']);
                $result['page']         = $page;
                $result['limit']        = $limit;
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('MSP unifiedSearch failed', ['ruc' => $request->ruc, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al realizar la búsqueda.'], 500);
        }
    }

    /**
     * Lista tickets y agentes asignados de un cliente por su CustomerId del MSP.
     *
     * Consulta la API externa del MSP para obtener el historial de tickets
     * y la lista de usuarios asignados (ticket_users). Devuelve también el
     * array plano de IDs de tickets para usarlos en llamadas posteriores
     * a ticketDetails. Soporta paginación opcional sobre el array de tickets.
     *
     * GET /api/v1/msp/tickets?customerId={customerId}[&limit={limit}&page={page}]
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request  Requiere: customerId (string)
     *                           Opcional: limit (int, 1-500), page (int, min:1)
     * @return JsonResponse      200 con { success: true, total: int, data: { tickets, ticket_users }, ticketIds: [] }
     *                           | 422 validación fallida
     *                           | 500 error de comunicación con la API MSP
     */
    public function tickets(Request $request): JsonResponse
    {
        $request->validate([
            'customerId' => 'required|string',
            'limit'      => 'nullable|integer|min:1|max:500',
            'page'       => 'nullable|integer|min:1',
        ]);

        try {
            $result  = $this->msp->fetchTicketsByCustomer($request->customerId);
            $tickets = $result['data']['tickets'];
            $total   = count($tickets);

            if ($request->filled('limit')) {
                $limit   = (int) $request->limit;
                $page    = (int) ($request->page ?? 1);
                $tickets = array_slice($tickets, ($page - 1) * $limit, $limit);
            }

            return response()->json([
                'success'   => true,
                'total'     => $total,
                'data'      => [
                    'tickets'      => $tickets,
                    'ticket_users' => $result['data']['ticket_users'],
                ],
                'ticketIds' => $result['ticketIds'],
            ]);
        } catch (\Exception $e) {
            Log::error('MSP fetchTicketsByCustomer failed', ['customerId' => $request->customerId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al consultar los tickets.'], 500);
        }
    }

    /**
     * Obtiene respuestas y métricas SLA de uno o varios tickets por sus IDs.
     *
     * Diseñado para cargarse de forma diferida después de listar los tickets,
     * permitiendo a la app mostrar datos básicos primero y solicitar el
     * detalle (respuestas, tiempos de resolución, SLA cumplido/incumplido)
     * bajo demanda o en segundo plano.
     *
     * GET /api/v1/msp/ticket-details?ticketIds[]=xxx&ticketIds[]=yyy
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request  Requiere: ticketIds (array de strings, min:1 elemento)
     * @return JsonResponse      200 con { success: true, data: { responses: [], slas: [] } }
     *                           | 422 validación fallida
     *                           | 500 error de comunicación con la API MSP
     */
    public function ticketDetails(Request $request): JsonResponse
    {
        $request->validate([
            'ticketIds'   => 'required|array|min:1',
            'ticketIds.*' => 'string',
        ]);

        try {
            $result = $this->msp->fetchTicketDetails($request->ticketIds);

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('MSP fetchTicketDetails failed', ['ticketIds' => $request->ticketIds, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al consultar el detalle de tickets.'], 500);
        }
    }
}
