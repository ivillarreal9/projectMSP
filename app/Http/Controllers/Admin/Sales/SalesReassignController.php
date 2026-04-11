<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;
use Illuminate\Http\Request;

class SalesReassignController extends Controller
{
    const PER_PAGE = 50;

    public function index(Request $request, OdooService $odoo)
    {
        $days      = (int) $request->get('days', 60);
        $ejecutiva = $request->get('ejecutiva', '');
        $page      = max(1, (int) $request->get('page', 1));
        $offset    = ($page - 1) * self::PER_PAGE;

        $allClients = collect($odoo->getClientsForReassign($days));

        if ($ejecutiva !== '') {
            $allClients = $allClients->filter(function ($c) use ($ejecutiva) {
                $uid = is_array($c['user_id']) ? (string) $c['user_id'][0] : '';
                return $uid === $ejecutiva;
            });
        }

        $today = now();
        $allClients = $allClients->map(function ($c) use ($today) {
            // Usar activity_date_deadline si existe, sino creation_date
            $ref = !empty($c['activity_date_deadline'])
                ? $c['activity_date_deadline']
                : $c['creation_date'];

            $daysOld = $ref
                ? (int) abs($today->diffInDays(\Carbon\Carbon::parse($ref)))
                : 999;

            $riskLabel = match(true) {
                $daysOld <= 30  => 'Al día',
                $daysOld <= 60  => 'Atención',
                default         => 'En riesgo',
            };

            return array_merge($c, [
                'days_old'        => $daysOld,
                'risk_label'      => $riskLabel,
                'executive'       => is_array($c['user_id']) ? $c['user_id'][1] : '—',
                'user_id_int'     => is_array($c['user_id']) ? (string) $c['user_id'][0] : '',
                'date_last_invoice' => $c['creation_date'] ?? null,
            ]);
        })->values();

        $totalClients = $allClients->count();
        $alDia        = $allClients->where('risk_label', 'Al día')->count();
        $atencion     = $allClients->where('risk_label', 'Atención')->count();
        $enRiesgo     = $allClients->where('risk_label', 'En riesgo')->count();

        $totalPages = (int) ceil($totalClients / self::PER_PAGE);
        $page       = min($page, max(1, $totalPages));
        $clients    = $allClients->slice($offset, self::PER_PAGE)->values();

        $ejecutivas = $allClients->map(fn($c) => [
            'id'   => $c['user_id_int'],
            'name' => $c['executive'],
        ])->unique('id')->filter(fn($e) => $e['id'] !== '')->sortBy('name')->values();

        return view('admin.sales.reassign.index', compact(
            'clients', 'days', 'ejecutiva', 'ejecutivas',
            'totalClients', 'alDia', 'atencion', 'enRiesgo',
            'page', 'totalPages',
        ));
    }

    public function export(Request $request)
    {
        $clients = $request->input('clients', []);

        $filename = 'reasignacion_' . now()->format('Y-m-d') . '.csv';
        $csv      = "\xEF\xBB\xBF"; // BOM para Excel
        $csv     .= "Nombre,Ejecutiva,Días sin actividad,Última factura\n";

        foreach ($clients as $c) {
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"',
                [$c['name'], $c['executive'], $c['days'], $c['last_invoice']]
            )) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}