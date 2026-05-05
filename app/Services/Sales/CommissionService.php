<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\Cache;

class CommissionService
{
    const CACHE_TTL = 300;

    public function __construct(private OdooService $odoo) {}

    // ── Comisiones del período ────────────────────────────────

    public function getByPeriod(string $year, string $month): array
    {
        $cacheKey = "commissions:period:{$year}:{$month}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month) {

            $domain = [
                ['year_comission',            '=', $year],
                ['month_comission',           '=', $month],
                ['to_invoice_button_clicked', '=', true],
                ['state',  '!=', 'cancel'],
            ];

            $records = $this->odoo->execute('sale.order', 'search_read',
                [$domain],
                [
                    'fields' => [
                        'name',
                        'year_comission',
                        'month_comission',
                        'state',
                        'user_id',
                        'amount_total',
                        'total_otf',
                        'total_mrc',
                        'partner_id',
                        'date_order',
                    ],
                    'order' => 'date_order desc',
                    'limit' => 0,
                ]
            ) ?? [];

            return $this->process($records);
        });
    }

    // ── Procesamiento ─────────────────────────────────────────

    private function process(array $records): array
    {
        $collection = collect($records);

        $byVendedor = $collection
            ->groupBy(fn($r) => is_array($r['user_id']) ? $r['user_id'][0] : ($r['user_id'] ?: 0))
            ->map(fn($group) => [
                'vendedor_id'   => is_array($group->first()['user_id']) ? $group->first()['user_id'][0] : '—',
                'vendedor_name' => is_array($group->first()['user_id']) ? $group->first()['user_id'][1] : '—',
                'cantidad'      => $group->count(),
                'total_otf'     => $group->sum('total_otf'),
                'total_mrc'     => $group->sum('total_mrc'),
                'total'         => $group->sum('total_otf') + $group->sum('total_mrc'),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'records'     => $records,
            'cantidad'    => $collection->count(),
            'total_otf'   => $collection->sum('total_otf'),
            'total_mrc'   => $collection->sum('total_mrc'),
            'total'       => $collection->sum('total_otf') + $collection->sum('total_mrc'),
            'by_vendedor' => $byVendedor,
        ];
    }

    // ── Caché ─────────────────────────────────────────────────

    public function clearCache(string $year, string $month): void
    {
        Cache::forget("commissions:period:{$year}:{$month}");
    }
}