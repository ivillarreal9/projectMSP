<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;
use Carbon\Carbon;

class SalesExecutivesController extends Controller
{
    public function index(OdooService $odoo)
    {
        // ── Período seleccionado ──────────────────────────────
        $year  = (int) request('year',  now()->year);
        $month = (int) request('month', now()->month);

        $dateFrom = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d H:i:s');
        $dateTo   = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d H:i:s');

        $availableYears = range(now()->year, now()->year - 3);

        // ── Ejecutivas con métricas filtradas ─────────────────
        $executives = $odoo->getExecutives();

        $executives = collect($executives)->map(function ($exec) use ($odoo, $dateFrom, $dateTo) {
            $metrics    = $odoo->getMetricsByExecutive($exec['id'], $dateFrom, $dateTo);
            $totalOport = ($metrics['leads'] ?? 0) + ($metrics['won'] ?? 0);
            $winRate    = $totalOport > 0
                ? round(($metrics['won'] / $totalOport) * 100, 1)
                : 0;

            return array_merge($exec, $metrics, [
                'win_rate'    => $winRate,
                'total_oport' => $totalOport,
            ]);
        })->values()->all();

        // ── KPIs globales del equipo ──────────────────────────
        $totalLeads     = collect($executives)->sum('leads');
        $totalWon       = collect($executives)->sum('won');
        $totalPipeline  = collect($executives)->sum('pipeline');
        $totalNoContact = collect($executives)->sum('noContact');
        $teamWinRate    = ($totalLeads + $totalWon) > 0
            ? round(($totalWon / ($totalLeads + $totalWon)) * 100, 1)
            : 0;

        return view('admin.sales.executives.index', compact(
            'executives',
            'totalLeads',
            'totalWon',
            'totalPipeline',
            'totalNoContact',
            'teamWinRate',
            'year',
            'month',
            'availableYears',
        ));
    }

    public function show(OdooService $odoo, int $id)
    {
        $executives = $odoo->getExecutives();
        $base       = collect($executives)->firstWhere('id', $id);

        abort_if(!$base, 404, 'Ejecutiva no encontrada.');

        // Heredar período desde query string
        $year  = (int) request('year',  now()->year);
        $month = (int) request('month', now()->month);

        $dateFrom = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d H:i:s');
        $dateTo   = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d H:i:s');

        $metrics    = $odoo->getMetricsByExecutive($id, $dateFrom, $dateTo);
        $totalOport = ($metrics['leads'] ?? 0) + ($metrics['won'] ?? 0);
        $winRate    = $totalOport > 0
            ? round(($metrics['won'] / $totalOport) * 100, 1)
            : 0;

        $exec = array_merge($base, $metrics, [
            'win_rate'    => $winRate,
            'total_oport' => $totalOport,
        ]);

        if (method_exists($odoo, 'getOpportunitiesByExecutive')) {
            $exec['opportunities'] = $odoo->getOpportunitiesByExecutive($id);
        }

        if (method_exists($odoo, 'getActivitiesByExecutive')) {
            $exec['activities'] = $odoo->getActivitiesByExecutive($id);
        }

        return view('admin.sales.executives.show', compact('exec', 'year', 'month'));
    }
}