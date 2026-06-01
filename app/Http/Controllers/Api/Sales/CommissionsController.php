<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para la consulta de comisiones de vendedores desde la API.
 *
 * Expone los cálculos de comisiones calculados por CommissionService (basado
 * en datos de Odoo) con dos granularidades: mensual y anual. Ambos endpoints
 * retornan 404 cuando el vendedor no existe o no tiene comisiones en el
 * período solicitado, en lugar de devolver un cuerpo vacío.
 *
 * Ruta base: /api/v1/commissions
 * Autenticación: Bearer token (Sanctum)
 */
class CommissionsController extends Controller
{
    /**
     * Inyecta el servicio de comisiones que calcula y cachea los datos de Odoo.
     *
     * @param  CommissionService $commissions  Servicio de cálculo de comisiones
     */
    public function __construct(private CommissionService $commissions) {}

    /**
     * Retorna las comisiones de un vendedor para un mes específico.
     *
     * Si no se especifican year y month se usa el mes y año actuales.
     * Devuelve 404 cuando el vendedor_id no existe en Odoo o no tiene
     * comisiones registradas en el período indicado.
     *
     * GET /api/v1/commissions/{vendedor_id}/month?year={year}&month={month}
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request      Opcional: year (int, 2020-2099), month (int, 1-12)
     * @param  int     $vendedor_id  ID del vendedor en Odoo
     * @return JsonResponse          200 con los datos de comisiones del mes
     *                               | 404 vendedor no encontrado o sin comisiones en el período
     *                               | 422 validación fallida (year/month fuera de rango)
     */
    public function month(Request $request, int $vendedor_id): JsonResponse
    {
        $request->validate([
            'year'  => ['sometimes', 'integer', 'min:2020', 'max:2099'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        $year  = (string) ($request->integer('year',  now()->year));
        $month = (string) ($request->integer('month', now()->month));

        $data = $this->commissions->getForVendedorMonth($vendedor_id, $year, $month);

        if ($data === null) {
            return response()->json(
                ['message' => 'Vendedor no encontrado o sin comisiones en este período.'],
                404
            );
        }

        return response()->json($data);
    }

    /**
     * Retorna las comisiones acumuladas de un vendedor durante un año completo.
     *
     * Agrega todas las comisiones del vendedor en los 12 meses del año indicado.
     * Si no se especifica year se usa el año actual. Devuelve 404 cuando el
     * vendedor_id no existe en Odoo o no tiene comisiones en el año indicado.
     *
     * GET /api/v1/commissions/{vendedor_id}/year?year={year}
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request      Opcional: year (int, 2020-2099)
     * @param  int     $vendedor_id  ID del vendedor en Odoo
     * @return JsonResponse          200 con los datos de comisiones anuales
     *                               | 404 vendedor no encontrado o sin comisiones en el año
     *                               | 422 validación fallida (year fuera de rango)
     */
    public function year(Request $request, int $vendedor_id): JsonResponse
    {
        $request->validate([
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2099'],
        ]);

        $year = (string) ($request->integer('year', now()->year));

        $data = $this->commissions->getForVendedorYear($vendedor_id, $year);

        if ($data === null) {
            return response()->json(
                ['message' => 'Vendedor no encontrado o sin comisiones en este año.'],
                404
            );
        }

        return response()->json($data);
    }
}
