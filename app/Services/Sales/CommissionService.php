<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\Cache;

class CommissionService
{
    const CACHE_TTL = 300;

    public function __construct(private OdooService $odoo) {}

    // ── Cargos ────────────────────────────────────────────────

    private function getCargosByOrders(array $orderIds): array
    {
        if (empty($orderIds)) return ['otf' => collect(), 'mrc' => collect()];

        $cargos = $this->odoo->execute('cargo.order', 'search_read',
            [[
                ['order_id',    'in', $orderIds],
                ['charge_type', 'in', [2, 4, 8]],
                ['sub_state',   'not in', ['new', 'anulado']],
            ]],
            [
                'fields' => ['order_id', 'cargo_type', 'sub_amount'],
                'limit'  => 0,
            ]
        ) ?? [];

        $otf = collect($cargos)
            ->where('cargo_type', 'otf')
            ->groupBy(fn($c) => is_array($c['order_id']) ? $c['order_id'][0] : $c['order_id']);

        $mrc = collect($cargos)
            ->where('cargo_type', 'mrc')
            ->groupBy(fn($c) => is_array($c['order_id']) ? $c['order_id'][0] : $c['order_id']);

        return ['otf' => $otf, 'mrc' => $mrc];
    }

    // ── Adendas ───────────────────────────────────────────────

    private function getAdendas(array $vendedorIds, string $year, string $month): array
    {
        if (empty($vendedorIds)) return [];

        $adendas = $this->odoo->execute('cargo.adenda', 'search_read',
            [[
                ['partner_comercial_user_id_dynamic', 'in', $vendedorIds],
                ['year_comission',                    '=', $year],
                ['month_comission',                   '=', $month],
            ]],
            [
                'fields' => ['partner_comercial_user_id_dynamic', 'diff_amount'],
                'limit'  => 0,
            ]
        ) ?? [];

        return collect($adendas)
            ->groupBy(fn($a) => is_array($a['partner_comercial_user_id_dynamic'])
                ? $a['partner_comercial_user_id_dynamic'][0]
                : $a['partner_comercial_user_id_dynamic']
            )
            ->map(fn($group) => $group->sum('diff_amount'))
            ->all();
    }

    // ── Por período ───────────────────────────────────────────

    public function getByPeriod(string $year, string $month): array
    {
        $cacheKey = "commissions:period:{$year}:{$month}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month) {

            $domain = [
                ['year_comission',            '=', $year],
                ['month_comission',           '=', $month],
                ['to_invoice_button_clicked', '=', true],
                ['state',                     '!=', 'cancel'],
            ];

            $records = $this->odoo->execute('sale.order', 'search_read',
                [$domain],
                [
                    'fields' => [
                        'name', 'year_comission', 'month_comission',
                        'state', 'user_id', 'amount_total',
                        'partner_id', 'date_order',
                    ],
                    'order' => 'date_order desc',
                    'limit' => 0,
                ]
            ) ?? [];

            $orderIds    = collect($records)->pluck('id')->all();
            $vendedorIds = collect($records)
                ->map(fn($r) => is_array($r['user_id']) ? $r['user_id'][0] : $r['user_id'])
                ->filter()->unique()->values()->all();

            $cargos  = $this->getCargosByOrders($orderIds);
            $adendas = $this->getAdendas($vendedorIds, $year, $month);

            $records = collect($records)->map(function ($r) use ($cargos, $adendas) {
                $id         = $r['id'];
                $vendedorId = is_array($r['user_id']) ? $r['user_id'][0] : $r['user_id'];

                $r['total_otf']    = ($cargos['otf'][$id] ?? collect())->sum('sub_amount');
                $r['total_mrc']    = ($cargos['mrc'][$id] ?? collect())->sum('sub_amount');
                $r['total_adenda'] = $adendas[$vendedorId] ?? 0;
                return $r;
            })->all();

            return $this->process($records, $adendas);
        });
    }

    // ── Por año ───────────────────────────────────────────────

    public function getByYear(string $year): array
    {
        $cacheKey = "commissions:year:{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year) {

            $domain = [
                ['year_comission',            '=', $year],
                ['to_invoice_button_clicked', '=', true],
                ['state',                     '!=', 'cancel'],
            ];

            $records = $this->odoo->execute('sale.order', 'search_read',
                [$domain],
                [
                    'fields' => [
                        'name', 'year_comission', 'month_comission',
                        'state', 'user_id', 'amount_total',
                        'partner_id', 'date_order',
                    ],
                    'order' => 'date_order desc',
                    'limit' => 0,
                ]
            ) ?? [];

            $orderIds    = collect($records)->pluck('id')->all();
            $vendedorIds = collect($records)
                ->map(fn($r) => is_array($r['user_id']) ? $r['user_id'][0] : $r['user_id'])
                ->filter()->unique()->values()->all();

            $adendas = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthAdendas = $this->getAdendas($vendedorIds, $year, (string) $m);
                foreach ($monthAdendas as $vid => $diff) {
                    $adendas[$vid] = ($adendas[$vid] ?? 0) + $diff;
                }
            }

            $cargos = $this->getCargosByOrders($orderIds);

            $records = collect($records)->map(function ($r) use ($cargos, $adendas) {
                $id         = $r['id'];
                $vendedorId = is_array($r['user_id']) ? $r['user_id'][0] : $r['user_id'];

                $r['total_otf']    = ($cargos['otf'][$id] ?? collect())->sum('sub_amount');
                $r['total_mrc']    = ($cargos['mrc'][$id] ?? collect())->sum('sub_amount');
                $r['total_adenda'] = $adendas[$vendedorId] ?? 0;
                return $r;
            })->all();

            return $this->process($records, $adendas);
        });
    }

    // ── Procesamiento ─────────────────────────────────────────

    private function process(array $records, array $adendas = []): array
    {
        $collection = collect($records);

        $byVendedor = $collection
            ->groupBy(fn($r) => is_array($r['user_id']) ? $r['user_id'][0] : ($r['user_id'] ?: 0))
            ->map(function ($group) use ($adendas) {
                $vendedorId   = is_array($group->first()['user_id']) ? $group->first()['user_id'][0] : '—';
                $vendedorName = is_array($group->first()['user_id']) ? $group->first()['user_id'][1] : '—';
                $totalOtf     = $group->sum('total_otf');
                $totalMrc     = $group->sum('total_mrc') + ($adendas[$vendedorId] ?? 0); // ← adenda va en MRC

                return [
                    'vendedor_id'   => $vendedorId,
                    'vendedor_name' => $vendedorName,
                    'cantidad'      => $group->count(),
                    'total_otf'     => $totalOtf,
                    'total_mrc'     => $totalMrc,
                    'total'         => $totalOtf + $totalMrc,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $totalOtf = $byVendedor->sum('total_otf');
        $totalMrc = $byVendedor->sum('total_mrc');

        return [
            'records'     => $records,
            'cantidad'    => $collection->count(),
            'total_otf'   => $totalOtf,
            'total_mrc'   => $totalMrc,
            'total'       => $totalOtf + $totalMrc,
            'by_vendedor' => $byVendedor,
        ];
    }

    // ── Caché ─────────────────────────────────────────────────

    public function clearCache(string $year, string $month): void
    {
        Cache::forget("commissions:period:{$year}:{$month}");
    }

    public function clearCacheYear(string $year): void
    {
        Cache::forget("commissions:year:{$year}");
    }

    public function odoo(): OdooService
    {
        return $this->odoo;
    }

    
}