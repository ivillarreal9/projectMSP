<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReassignController extends Controller
{
    const PER_PAGE = 50;

    public function index(Request $request, OdooService $odoo)
    {
        $days      = (int) ($request->get('days', 60));
        $ejecutiva = $request->get('ejecutiva') ?? '';
        $page      = max(1, (int) $request->get('page', 1));
        $offset    = ($page - 1) * self::PER_PAGE;

        // ── Total y paginación ───────────────────────────────
        $totalClients = $odoo->countClientsForReassign($days, $ejecutiva);
        $totalPages   = (int) ceil($totalClients / self::PER_PAGE);
        $page         = min($page, max(1, $totalPages));

        // ── Página actual ────────────────────────────────────
        $rawClients = collect($odoo->getClientsForReassignPaginated(
            $days, $ejecutiva, self::PER_PAGE, $offset
        ));

        // ── Última factura desde account.move ────────────────
        $partnerIds  = $rawClients->pluck('id')->filter()->map(fn($id) => (int)$id)->values()->all();
        $invoiceMap  = $odoo->getLastInvoiceDateByPartners($partnerIds);

        $today   = now();
        $clients = $rawClients->map(function ($c) use ($today, $invoiceMap) {
            $partnerId   = (int) $c['id'];
            $lastInvoice = $invoiceMap[$partnerId] ?? null;

            $daysOld = $lastInvoice
                ? (int) abs($today->diffInDays(\Carbon\Carbon::parse($lastInvoice)))
                : 999;

            $riskLabel = match(true) {
                $daysOld <= 30  => 'Al día',
                $daysOld <= 60  => 'Atención',
                default         => 'En riesgo',
            };

            return array_merge($c, [
                'days_old'          => $daysOld,
                'risk_label'        => $riskLabel,
                'executive'         => is_array($c['user_id']) ? $c['user_id'][1] : '—',
                'user_id_int'       => is_array($c['user_id']) ? (string) $c['user_id'][0] : '',
                'date_last_invoice' => $lastInvoice,
            ]);
        })->values();

        // ── KPIs de riesgo — sobre la página actual ──────────
        $alDia    = $clients->filter(fn($c) => $c['risk_label'] === 'Al día')->count();
        $atencion = $clients->filter(fn($c) => $c['risk_label'] === 'Atención')->count();
        $enRiesgo = $clients->filter(fn($c) => $c['risk_label'] === 'En riesgo')->count();

        // ── Ejecutivas para filtro ───────────────────────────
        $ejecutivas = collect($odoo->getExecutives())->map(fn($e) => [
            'id'   => (string) $e['id'],
            'name' => $e['name'],
        ])->values();

        return view('admin.sales.reassign.index', compact(
            'clients', 'days', 'ejecutiva', 'ejecutivas',
            'totalClients', 'alDia', 'atencion', 'enRiesgo',
            'page', 'totalPages',
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $clients  = $request->input('clients', []);
        $filename = 'reasignacion_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($clients) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM para Excel

            fputcsv($handle, ['Nombre', 'Ejecutiva', 'Días sin actividad', 'Última factura']);

            foreach ($clients as $c) {
                fputcsv($handle, [
                    $c['name']         ?? '',
                    $c['executive']    ?? '',
                    $c['days']         ?? '',
                    $c['last_invoice'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}