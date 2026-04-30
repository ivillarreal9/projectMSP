<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;

class SalesExecutivesController extends Controller
{
    public function index(OdooService $odoo)
    {
        $executives = $odoo->getExecutives();

        $executives = collect($executives)->map(function ($exec) use ($odoo) {
            $metrics = $odoo->getMetricsByExecutive($exec['id']);

            $totalOport = ($metrics['leads'] ?? 0) + ($metrics['won'] ?? 0);
            $winRate    = $totalOport > 0
                ? round(($metrics['won'] / $totalOport) * 100, 1)
                : 0;

            return array_merge($exec, $metrics, [
                'win_rate'    => $winRate,
                'total_oport' => $totalOport,
            ]);
        })->values()->all();

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
        ));
    }

    public function show(OdooService $odoo, int $id)
    {
        // Busca al ejecutivo en la lista
        $executives = $odoo->getExecutives();

        $base = collect($executives)->firstWhere('id', $id);

        abort_if(!$base, 404, 'Ejecutiva no encontrada.');

        // Métricas
        $metrics    = $odoo->getMetricsByExecutive($id);
        $totalOport = ($metrics['leads'] ?? 0) + ($metrics['won'] ?? 0);
        $winRate    = $totalOport > 0
            ? round(($metrics['won'] / $totalOport) * 100, 1)
            : 0;

        $exec = array_merge($base, $metrics, [
            'win_rate'    => $winRate,
            'total_oport' => $totalOport,
        ]);

        // Datos extra — si OdooService los tiene, úsalos; si no, quedan vacíos
        // y la vista muestra el estado vacío elegantemente
        if (method_exists($odoo, 'getOpportunitiesByExecutive')) {
            $exec['opportunities'] = $odoo->getOpportunitiesByExecutive($id);
        }

        if (method_exists($odoo, 'getActivitiesByExecutive')) {
            $exec['activities'] = $odoo->getActivitiesByExecutive($id);
        }

        return view('admin.sales.executives.show', compact('exec'));
    }
}