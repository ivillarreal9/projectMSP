<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;

class SalesExecutivesController extends Controller
{
    public function index(OdooService $odoo)
    {
        $executives = $odoo->getExecutives();

        // Enriquecer cada ejecutiva con sus métricas
        $executives = collect($executives)->map(function ($exec) use ($odoo) {
            $metrics = $odoo->getMetricsByExecutive($exec['id']);

            $totalOport  = ($metrics['leads'] ?? 0) + ($metrics['won'] ?? 0);
            $winRate     = $totalOport > 0
                ? round(($metrics['won'] / $totalOport) * 100, 1)
                : 0;

            return array_merge($exec, $metrics, [
                'win_rate'   => $winRate,
                'total_oport' => $totalOport,
            ]);
        })->values()->all();
        // KPIs globales del equipo
        $totalLeads    = collect($executives)->sum('leads');
        $totalWon      = collect($executives)->sum('won');
        $totalPipeline = collect($executives)->sum('pipeline');
        $totalNoContact= collect($executives)->sum('noContact');
        $teamWinRate   = ($totalLeads + $totalWon) > 0
            ? round(($totalWon / ($totalLeads + $totalWon)) * 100, 1)
            : 0;

        return view('admin.sales.executives.index', compact(
            'executives',
            'totalLeads',
            'totalWon',
            'totalPipeline',
            'totalNoContact',
            'teamWinRate',
        ));
    }
}