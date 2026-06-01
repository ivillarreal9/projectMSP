<?php

namespace App\Services\Sales;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio del dashboard ejecutivo de ventas.
 *
 * Agrega y calcula los indicadores clave de rendimiento (KPIs) y tendencias
 * del área de ventas consultando datos de Odoo a través de OdooService.
 *
 * Provee tres tipos de análisis:
 *   1. KPIs del período seleccionado (leads, ganadas, revenue, win rate)
 *   2. Tendencia mensual del año (evolución mes a mes)
 *   3. Stats por ejecutiva (desglose individual de performance)
 *
 * Todos los resultados se cachean 24 h porque los datos históricos de Odoo
 * no cambian con frecuencia y las consultas son costosas (múltiples llamadas JSON-RPC).
 *
 * Dependencias externas:
 *   - OdooService: todas las consultas a Odoo se delegan a este servicio
 *   - config/sales.php: executive_ids lista los usuarios ejecutivos del dashboard
 */
class DashboardService
{
    /** TTL de caché para todos los datos del dashboard: 24 horas. */
    const CACHE_TTL = 86400;

    /**
     * @param  OdooService $odoo Inyectado automáticamente por el contenedor de Laravel
     */
    public function __construct(private OdooService $odoo) {}

    /**
     * Obtiene los KPIs del dashboard para un rango de fechas.
     *
     * Calcula en una sola consulta (por métrica):
     *   - leads:         leads creados en el período
     *   - won:           oportunidades ganadas en el período
     *   - revenueWon:    revenue esperado de las oportunidades ganadas
     *   - revenueOrders: monto real de órdenes de venta confirmadas
     *   - winRate:       porcentaje de cierre = won / (leads + won)
     *
     * @param  string $dateFrom Fecha de inicio en formato 'Y-m-d H:i:s'
     * @param  string $dateTo   Fecha de fin en formato 'Y-m-d H:i:s'
     * @return array            Mapa: leads, won, revenueWon, revenueOrders, winRate
     */
    public function getDashboardKpis(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "dashboard:kpis:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateFrom, $dateTo) {

            $leads = $this->odoo->execute('crm.lead', 'search_count', [[
                ['type',        '=',  'lead'],
                ['create_date', '>=', $dateFrom],
                ['create_date', '<=', $dateTo],
            ]]) ?? 0;

            $won = $this->odoo->execute('crm.lead', 'search_count', [[
                ['stage_id.is_won', '=',  true],
                ['date_closed',     '>=', $dateFrom],
                ['date_closed',     '<=', $dateTo],
            ]]) ?? 0;

            $wonLeads = $this->odoo->execute('crm.lead', 'search_read', [[
                ['stage_id.is_won', '=',  true],
                ['date_closed',     '>=', $dateFrom],
                ['date_closed',     '<=', $dateTo],
            ]], ['fields' => ['expected_revenue'], 'limit' => 0]) ?? [];
            $revenueWon = collect($wonLeads)->sum('expected_revenue');

            $orders = $this->odoo->execute('sale.order', 'search_read', [[
                ['state',      'in', ['sale', 'done']],
                ['date_order', '>=', $dateFrom],
                ['date_order', '<=', $dateTo],
            ]], ['fields' => ['amount_total'], 'limit' => 0]) ?? [];
            $revenueOrders = collect($orders)->sum('amount_total');

            $totalOport = $leads + $won;
            $winRate    = $totalOport > 0 ? round(($won / $totalOport) * 100, 1) : 0;

            return compact('leads', 'won', 'revenueWon', 'revenueOrders', 'winRate');
        });
    }

    /**
     * Calcula la tendencia mensual de ventas para todos los meses de un año.
     *
     * Para cada mes del 1 al 12 ejecuta 3 consultas a Odoo (leads, won, revenue_won).
     * El resultado incluye todos los meses aunque no tengan datos, para que los
     * gráficos de línea siempre muestren el año completo.
     *
     * @param  int   $year Año para el que se calcula la tendencia
     * @return array       Array de 12 entradas, cada una con: month, label, leads, won, revenue
     */
    public function getMonthlyTrend(int $year): array
    {
        $cacheKey = "dashboard:monthly:{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year) {
            $months = [];

            for ($m = 1; $m <= 12; $m++) {
                $from = Carbon::create($year, $m, 1)->startOfMonth()->format('Y-m-d H:i:s');
                $to   = Carbon::create($year, $m, 1)->endOfMonth()->format('Y-m-d H:i:s');

                $leads = $this->odoo->execute('crm.lead', 'search_count', [[
                    ['type',        '=',  'lead'],
                    ['create_date', '>=', $from],
                    ['create_date', '<=', $to],
                ]]) ?? 0;

                $won = $this->odoo->execute('crm.lead', 'search_count', [[
                    ['stage_id.is_won', '=',  true],
                    ['date_closed',     '>=', $from],
                    ['date_closed',     '<=', $to],
                ]]) ?? 0;

                $wonLeads = $this->odoo->execute('crm.lead', 'search_read', [[
                    ['stage_id.is_won', '=',  true],
                    ['date_closed',     '>=', $from],
                    ['date_closed',     '<=', $to],
                ]], ['fields' => ['expected_revenue'], 'limit' => 0]) ?? [];
                $revenue = collect($wonLeads)->sum('expected_revenue');

                $months[] = [
                    'month'   => $m,
                    'label'   => Carbon::create($year, $m)->translatedFormat('M'),
                    'leads'   => $leads,
                    'won'     => $won,
                    'revenue' => $revenue,
                ];
            }

            return $months;
        });
    }

    /**
     * Obtiene el rendimiento de cada ejecutiva configurada en sales.executive_ids para el período.
     *
     * Por cada ejecutiva ejecuta 3 consultas separadas a Odoo (won, leads, revenue_won)
     * y calcula su win_rate individual. El resultado se ordena de mayor a menor revenue
     * para presentar el ranking de performance en el dashboard.
     *
     * Nota: hace N*3 llamadas a Odoo donde N = número de ejecutivas. Para N > 5
     * considerar migrar a getMetricsForAllExecutives() de OdooService que hace 4 bulk calls.
     *
     * @param  string $dateFrom Fecha de inicio en formato 'Y-m-d H:i:s'
     * @param  string $dateTo   Fecha de fin en formato 'Y-m-d H:i:s'
     * @return array            Lista de ejecutivas ordenada por revenue desc, cada una con:
     *                          id, name, image, leads, won, revenue, win_rate
     */
    public function getStatsByExecutive(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "dashboard:byexec:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateFrom, $dateTo) {
            $ids = config('sales.executive_ids', []);
            if (empty($ids)) return [];

            $executives = $this->odoo->execute('res.users', 'search_read',
                [[['id', 'in', $ids]]],
                ['fields' => ['id', 'name', 'image_128'], 'limit' => 50]
            ) ?? [];

            return collect($executives)->map(function ($exec) use ($dateFrom, $dateTo) {
                $userId = $exec['id'];

                $won = $this->odoo->execute('crm.lead', 'search_count', [[
                    ['user_id',         '=',  $userId],
                    ['stage_id.is_won', '=',  true],
                    ['date_closed',     '>=', $dateFrom],
                    ['date_closed',     '<=', $dateTo],
                ]]) ?? 0;

                $leads = $this->odoo->execute('crm.lead', 'search_count', [[
                    ['user_id',     '=',  $userId],
                    ['type',        '=',  'lead'],
                    ['create_date', '>=', $dateFrom],
                    ['create_date', '<=', $dateTo],
                ]]) ?? 0;

                $wonLeads = $this->odoo->execute('crm.lead', 'search_read', [[
                    ['user_id',         '=',  $userId],
                    ['stage_id.is_won', '=',  true],
                    ['date_closed',     '>=', $dateFrom],
                    ['date_closed',     '<=', $dateTo],
                ]], ['fields' => ['expected_revenue'], 'limit' => 0]) ?? [];
                $revenue = collect($wonLeads)->sum('expected_revenue');

                $total   = $leads + $won;
                $winRate = $total > 0 ? round(($won / $total) * 100, 1) : 0;

                return [
                    'id'       => $userId,
                    'name'     => $exec['name'],
                    'image'    => $exec['image_128'] ?? null,
                    'leads'    => $leads,
                    'won'      => $won,
                    'revenue'  => $revenue,
                    'win_rate' => $winRate,
                ];
            })->sortByDesc('revenue')->values()->all();
        });
    }
}