<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;
use App\Services\Sales\CommissionService;  // ← nuevo
use Carbon\Carbon;

class SalesOverviewController extends Controller
{
    public function index(OdooService $odoo, CommissionService $commissions)  // ← inyectar
    {
        $year  = (int) request('year',  now()->year);
        $month = (int) request('month', now()->month);

        $availableYears = range(now()->year, now()->year - 3);

        $dateFrom = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d H:i:s');
        $dateTo   = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d H:i:s');

        $prevMonth = Carbon::create($year, $month, 1)->subMonth();
        $prevFrom  = $prevMonth->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $prevTo    = $prevMonth->copy()->endOfMonth()->format('Y-m-d H:i:s');

        $yearFrom = Carbon::create($year, 1, 1)->startOfYear()->format('Y-m-d H:i:s');
        $yearTo   = Carbon::create($year, 12, 31)->endOfYear()->format('Y-m-d H:i:s');

        $kpis            = $odoo->getDashboardKpis($dateFrom, $dateTo);
        $monthlyData     = $odoo->getMonthlyTrend($year);
        $byExecutive     = $odoo->getStatsByExecutive($dateFrom, $dateTo);
        $byExecutivePrev = $odoo->getStatsByExecutive($prevFrom, $prevTo);
        $byExecutiveYear = $odoo->getStatsByExecutive($yearFrom, $yearTo);

        // ── Comisiones — mes anterior ─────────────────────────
        $commissionPeriod = Carbon::create($year, $month, 1)->subMonth();
        $commissionData   = $commissions->getByPeriod(
            (string) $commissionPeriod->year,
            (string) $commissionPeriod->month
        );

        return view('admin.sales.overview.overview', compact(
            'kpis',
            'monthlyData',
            'byExecutive',
            'byExecutivePrev',
            'byExecutiveYear',
            'commissionData',      // ← era $commissions, ahora $commissionData
            'commissionPeriod',    // ← para el label en la vista
            'year',
            'month',
            'availableYears',
        ));
    }
}