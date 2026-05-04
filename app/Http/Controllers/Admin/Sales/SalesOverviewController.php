<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;
use Carbon\Carbon;

class SalesOverviewController extends Controller
{
    public function index(OdooService $odoo)
    {
        
        $year  = (int) request('year',  now()->year);
        $month = (int) request('month', now()->month);

        $availableYears = range(now()->year, now()->year - 3);

        // Período seleccionado
        $dateFrom = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d H:i:s');
        $dateTo   = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d H:i:s');

        // Mes anterior
        $prevMonth = Carbon::create($year, $month, 1)->subMonth();
        $prevFrom  = $prevMonth->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $prevTo    = $prevMonth->copy()->endOfMonth()->format('Y-m-d H:i:s');

        // Acumulado año
        $yearFrom = Carbon::create($year, 1, 1)->startOfYear()->format('Y-m-d H:i:s');
        $yearTo   = Carbon::create($year, 12, 31)->endOfYear()->format('Y-m-d H:i:s');

        // KPIs del período
        $kpis = $odoo->getDashboardKpis($dateFrom, $dateTo);

        // Tendencia mensual del año (para las gráficas)
        $monthlyData = $odoo->getMonthlyTrend($year);

        // Stats por ejecutiva — mes actual
        $byExecutive = $odoo->getStatsByExecutive($dateFrom, $dateTo);

        // Stats por ejecutiva — mes anterior
        $byExecutivePrev = $odoo->getStatsByExecutive($prevFrom, $prevTo);

        // Stats por ejecutiva — acumulado año
        $byExecutiveYear = $odoo->getStatsByExecutive($yearFrom, $yearTo);

        return view('admin.sales.overview.overview', compact(
            'kpis',
            'monthlyData',
            'byExecutive',
            'byExecutivePrev',
            'byExecutiveYear',
            'year',
            'month',
            'availableYears',
        ));
    }
}