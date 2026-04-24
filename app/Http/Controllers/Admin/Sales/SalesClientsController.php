<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;
use Illuminate\Http\Request;

class SalesClientsController extends Controller
{
    const PER_PAGE = 50;

    public function index(Request $request, OdooService $odoo)
    {
        $ejecutiva = $request->get('ejecutiva') ?? '';
        $riesgo    = $request->get('riesgo')    ?? '';
        $page      = max(1, (int) $request->get('page', 1));
        $offset    = ($page - 1) * self::PER_PAGE;

        $total      = $odoo->countClients($ejecutiva);
        $totalPages = (int) ceil($total / self::PER_PAGE);
        $page       = min($page, max(1, $totalPages));

        // KPIs globales: traer todos los IDs del segmento y cruzar con account.move
        $allPartnerIds    = $odoo->getAllClientIds($ejecutiva);
        $globalInvoiceMap = $odoo->getLastInvoiceDateByPartners($allPartnerIds);

        $today         = now();
        $countAlDia    = 0;
        $countAtencion = 0;
        $countEnRiesgo = 0;

        foreach ($allPartnerIds as $pid) {
            $lastInvoice  = $globalInvoiceMap[$pid] ?? null;
            $daysInactive = $lastInvoice
                ? (int) abs($today->diffInDays(\Carbon\Carbon::parse($lastInvoice)))
                : 999;

            if ($daysInactive <= 30)     $countAlDia++;
            elseif ($daysInactive <= 60) $countAtencion++;
            else                         $countEnRiesgo++;
        }

        // Página actual
        $rawClients     = $odoo->getClientsPaginated($ejecutiva, self::PER_PAGE, $offset);
        $partnerIds     = collect($rawClients)->pluck('id')->filter()->map(fn($id) => (int)$id)->values()->all();
        $pageInvoiceMap = $odoo->getLastInvoiceDateByPartners($partnerIds);

        $allEnriched = collect($rawClients)->map(function ($c) use ($today, $pageInvoiceMap) {
            $partnerId   = (int) $c['id'];
            $lastInvoice = $pageInvoiceMap[$partnerId] ?? null;

            $daysInactive = $lastInvoice
                ? (int) abs($today->diffInDays(\Carbon\Carbon::parse($lastInvoice)))
                : 999;

            $riskLabel = match(true) {
                $daysInactive <= 30  => 'Al dia',
                $daysInactive <= 60  => 'Atencion',
                default              => 'En riesgo',
            };

            $riskColor = match($riskLabel) {
                'Al dia'    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                'Atencion'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                default     => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            };

            $riskDot = match($riskLabel) {
                'Al dia'   => 'bg-green-500',
                'Atencion' => 'bg-amber-400',
                default    => 'bg-red-500',
            };

            $daysColor = match($riskLabel) {
                'Al dia'   => 'text-green-600 dark:text-green-400',
                'Atencion' => 'text-amber-600 dark:text-amber-400',
                default    => 'text-red-600 dark:text-red-400',
            };

            $riskDisplay = match($riskLabel) {
                'Al dia'   => 'Al día',
                'Atencion' => 'Atención',
                default    => 'En riesgo',
            };

            return array_merge($c, [
                'days_inactive'  => $daysInactive,
                'last_invoice'   => $lastInvoice,
                'risk_label'     => $riskLabel,
                'risk_display'   => $riskDisplay,
                'risk_color'     => $riskColor,
                'risk_dot'       => $riskDot,
                'days_color'     => $daysColor,
                'executive_name' => is_array($c['user_id']) ? $c['user_id'][1] : '—',
            ]);
        });

        $clients = $allEnriched->when($riesgo !== '', function ($col) use ($riesgo) {
            return $col->filter(function ($c) use ($riesgo) {
                return match($riesgo) {
                    'al_dia'   => $c['risk_label'] === 'Al dia',
                    'atencion' => $c['risk_label'] === 'Atencion',
                    'critico'  => $c['risk_label'] === 'En riesgo',
                    default    => true,
                };
            });
        })->values();

        $totalFiltrado = $riesgo !== '' ? $clients->count() : $total;

        $ejecutivas = collect($odoo->getExecutives())->map(fn($e) => [
            'id'   => (string) $e['id'],
            'name' => $e['name'],
        ])->values();

        $ejecutivaNombre = $ejecutiva !== ''
            ? ($ejecutivas->firstWhere('id', $ejecutiva)['name'] ?? '')
            : '';

        return view('admin.sales.clients.index', compact(
            'clients', 'ejecutiva', 'ejecutivaNombre', 'riesgo',
            'ejecutivas', 'total', 'totalFiltrado', 'page', 'totalPages',
            'countAlDia', 'countAtencion', 'countEnRiesgo',
        ));
    }
    // ── Método nuevo: todos los IDs de clientes (para KPIs globales) ──

    /**
     * Devuelve array de partner IDs del segmento completo (sin paginar).
     * Se cachea 10 minutos. Se usa para calcular KPIs globales de riesgo.
     */
    public function getAllClientIds(?string $ejecutivaId = ''): array
    {
        $ejecutivaId = $ejecutivaId ?? '';
        $cacheKey    = "odoo:clients:ids:{$ejecutivaId}";

        return Cache::remember($cacheKey, self::CACHE_CLIENTS, function () use ($ejecutivaId) {
            $domain = [
                ['customer_rank', '>', 0],
                ['is_company',    '=', true],
            ];
            if ($ejecutivaId !== '') {
                $domain[] = ['user_id', '=', (int) $ejecutivaId];
            }

            $results = $this->execute('res.partner', 'search_read',
                [$domain],
                ['fields' => ['id'], 'limit' => 0]
            ) ?? [];

            return collect($results)->pluck('id')->map(fn($id) => (int)$id)->values()->all();
        });
    }
}