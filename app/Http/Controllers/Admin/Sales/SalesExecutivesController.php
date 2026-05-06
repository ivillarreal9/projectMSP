<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;
use Carbon\Carbon;

class SalesExecutivesController extends Controller
{
    // Shell — sin llamadas a Odoo
    public function index()
    {
        $year           = (int) request('year',  now()->year);
        $month          = (int) request('month', now()->month);
        $availableYears = range(now()->year, now()->year - 3);

        return view('admin.sales.executives.index', compact('year', 'month', 'availableYears'));
    }

    // Endpoint AJAX
    public function data(OdooService $odoo)
    {
        $year  = (int) request('year',  now()->year);
        $month = (int) request('month', now()->month);

        $dateFrom = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d H:i:s');
        $dateTo   = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d H:i:s');

        $executives = $odoo->getExecutives();

        $executives = collect($executives)->map(function ($exec) use ($odoo, $dateFrom, $dateTo) {
            $metrics    = $odoo->getMetricsByExecutive($exec['id'], $dateFrom, $dateTo);
            $totalOport = ($metrics['leads'] ?? 0) + ($metrics['won'] ?? 0);
            $winRate    = $totalOport > 0
                ? round(($metrics['won'] / $totalOport) * 100, 1)
                : 0;

            $hasPhoto = !empty($exec['image_128']) && !str_starts_with($exec['image_128'], 'PD94');

            return array_merge($exec, $metrics, [
                'win_rate'    => $winRate,
                'total_oport' => $totalOport,
                'image'       => $hasPhoto ? 'data:image/png;base64,' . $exec['image_128'] : null,
                'initials'    => collect(explode(' ', $exec['name']))->take(2)
                                    ->map(fn($w) => strtoupper(substr($w, 0, 1)))->join(''),
            ]);
        })->values()->all();

        $totalLeads     = collect($executives)->sum('leads');
        $totalWon       = collect($executives)->sum('won');
        $totalNoContact = collect($executives)->sum('noContact');
        $teamWinRate    = ($totalLeads + $totalWon) > 0
            ? round(($totalWon / ($totalLeads + $totalWon)) * 100, 1)
            : 0;

        return response()->json([
            'executives'     => $executives,
            'totalLeads'     => $totalLeads,
            'totalWon'       => $totalWon,
            'totalNoContact' => $totalNoContact,
            'teamWinRate'    => $teamWinRate,
            'periodoLabel'   => Carbon::create($year, $month)->translatedFormat('F Y'),
            'year'           => $year,
            'month'          => $month,
        ]);
    }

    public function show(OdooService $odoo, int $id)
    {
        $executives = $odoo->getExecutives();
        $base       = collect($executives)->firstWhere('id', $id);
        abort_if(!$base, 404, 'Ejecutiva no encontrada.');

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