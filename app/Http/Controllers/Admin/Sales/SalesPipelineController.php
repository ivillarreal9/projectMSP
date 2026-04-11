<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesPipelineController extends Controller
{
    const PER_PAGE = 50;

    public function index(OdooService $odoo)
    {
        // Exportar CSV (sin paginación, todos los registros)
        if (request('export') === 'csv') {
            return $this->exportCsv($odoo);
        }

        $kpis       = $odoo->getKpis();
        $ejecutivas = $odoo->getExecutives();

        // Filtros desde la request
        $userId  = request('ejecutiva', '');
        $state   = request('etapa', '');
        $page    = max(1, (int) request('page', 1));
        $offset  = ($page - 1) * self::PER_PAGE;

        // Contar total con filtros aplicados
        $total = $odoo->countPipeline($userId, $state);
        $totalPages = (int) ceil($total / self::PER_PAGE);
        $page = min($page, max(1, $totalPages));

        // Traer página actual
        $pipeline = $odoo->getPipeline($userId, $state, self::PER_PAGE, $offset);

        // Agrupación por etapa para Chart.js (siempre sobre todos)
        $allForChart = $odoo->getPipelineForChart();
        $porEtapa = collect($allForChart)
            ->groupBy('state')
            ->map(fn($group, $state) => [
                'etapa'    => match($state) {
                    'draft' => 'Borrador',
                    'sent'  => 'Enviada',
                    'sale'  => 'Confirmada',
                    default => $state,
                },
                'monto'    => $group->sum('amount_total'),
                'cantidad' => $group->count(),
            ])
            ->values();

        return view('admin.sales.pipeline.index', compact(
            'pipeline',
            'kpis',
            'ejecutivas',
            'porEtapa',
            'total',
            'totalPages',
            'page',
            'userId',
            'state',
        ));
    }

    private function exportCsv(OdooService $odoo): StreamedResponse
    {
        $userId = request('ejecutiva', '');
        $state  = request('etapa', '');
        $pipeline = $odoo->getPipeline($userId, $state, 0, 0); // 0 = sin límite

        $filename = 'pipeline_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($pipeline) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Cotización', 'Cliente', 'Ejecutiva',
                'Monto', 'Estado', 'Fecha', 'Vencimiento', 'Días pendientes',
            ]);

            foreach ($pipeline as $order) {
                $vence    = $order['validity_date']
                    ? \Carbon\Carbon::parse($order['validity_date'])
                    : null;
                $diasLeft = $vence ? (int) now()->diffInDays($vence, false) : '';

                fputcsv($handle, [
                    $order['name'],
                    is_array($order['partner_id']) ? $order['partner_id'][1] : '',
                    is_array($order['user_id'])    ? $order['user_id'][1]    : '',
                    $order['amount_total'],
                    match($order['state']) {
                        'draft' => 'Borrador',
                        'sent'  => 'Enviada',
                        'sale'  => 'Confirmada',
                        default => $order['state'],
                    },
                    $order['date_order']    ? \Carbon\Carbon::parse($order['date_order'])->format('d/m/Y')    : '',
                    $order['validity_date'] ? \Carbon\Carbon::parse($order['validity_date'])->format('d/m/Y') : '',
                    $diasLeft,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}