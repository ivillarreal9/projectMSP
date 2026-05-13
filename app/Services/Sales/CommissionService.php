<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\Cache;

class CommissionService
{
    const CACHE_TTL = 300;

    const MONTH_LABELS = [
        1 => 'Enero',    2 => 'Febrero',   3 => 'Marzo',     4 => 'Abril',
        5 => 'Mayo',     6 => 'Junio',     7 => 'Julio',     8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function __construct(private OdooService $odoo) {}

    // ── Cargos (OTF + MRC) ────────────────────────────────────

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

    // ── Por período (todos los vendedores) ────────────────────

    public function getByPeriod(string $year, string $month): array
    {
        $cacheKey = "commissions:period:{$year}:{$month}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month) {

            $records = $this->odoo->execute('sale.order', 'search_read',
                [[
                    ['year_comission',            '=', $year],
                    ['month_comission',           '=', $month],
                    ['to_invoice_button_clicked', '=', true],
                    ['state',                     '!=', 'cancel'],
                ]],
                [
                    'fields' => ['name', 'year_comission', 'month_comission',
                                 'state', 'user_id', 'amount_total',
                                 'partner_id', 'date_order'],
                    'order'  => 'date_order desc',
                    'limit'  => 0,
                ]
            ) ?? [];

            $orderIds = collect($records)->pluck('id')->all();
            $cargos   = $this->getCargosByOrders($orderIds);

            $records = collect($records)->map(function ($r) use ($cargos) {
                $id = $r['id'];
                $r['total_otf'] = ($cargos['otf'][$id] ?? collect())->sum('sub_amount');
                $r['total_mrc'] = ($cargos['mrc'][$id] ?? collect())->sum('sub_amount');
                return $r;
            })->all();

            return $this->process($records);
        });
    }

    // ── Por año (todos los vendedores) ────────────────────────

    public function getByYear(string $year): array
    {
        $cacheKey = "commissions:year:{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year) {

            $records = $this->odoo->execute('sale.order', 'search_read',
                [[
                    ['year_comission',            '=', $year],
                    ['to_invoice_button_clicked', '=', true],
                    ['state',                     '!=', 'cancel'],
                ]],
                [
                    'fields' => ['name', 'year_comission', 'month_comission',
                                 'state', 'user_id', 'amount_total',
                                 'partner_id', 'date_order'],
                    'order'  => 'date_order desc',
                    'limit'  => 0,
                ]
            ) ?? [];

            $orderIds = collect($records)->pluck('id')->all();
            $cargos   = $this->getCargosByOrders($orderIds);

            $records = collect($records)->map(function ($r) use ($cargos) {
                $id = $r['id'];
                $r['total_otf'] = ($cargos['otf'][$id] ?? collect())->sum('sub_amount');
                $r['total_mrc'] = ($cargos['mrc'][$id] ?? collect())->sum('sub_amount');
                return $r;
            })->all();

            return $this->process($records);
        });
    }

    // ── Procesamiento ─────────────────────────────────────────

    private function process(array $records): array
    {
        $collection = collect($records);

        $byVendedor = $collection
            ->groupBy(fn($r) => is_array($r['user_id']) ? $r['user_id'][0] : ($r['user_id'] ?: 0))
            ->map(function ($group) {
                $uid          = $group->first()['user_id'];
                $vendedorId   = is_array($uid) ? $uid[0] : ($uid ?: 0);
                $vendedorName = is_array($uid) ? $uid[1] : '—';
                $totalOtf     = $group->sum('total_otf');
                $totalMrc     = $group->sum('total_mrc');

                return [
                    'vendedor_id'   => $vendedorId,
                    'vendedor_name' => $vendedorName,
                    'cantidad'      => $group->count(),
                    'total_otf'     => round($totalOtf, 2),
                    'total_mrc'     => round($totalMrc, 2),
                    'total'         => round($totalOtf + $totalMrc, 2),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $totalOtf = round($byVendedor->sum('total_otf'), 2);
        $totalMrc = round($byVendedor->sum('total_mrc'), 2);

        return [
            'records'     => $records,
            'cantidad'    => $collection->count(),
            'total_otf'   => $totalOtf,
            'total_mrc'   => $totalMrc,
            'total'       => round($totalOtf + $totalMrc, 2),
            'by_vendedor' => $byVendedor,
        ];
    }

    // ── Por vendedor / mes ────────────────────────────────────

    public function getForVendedorMonth(int $vendedorId, string $year, string $month): ?array
    {
        $cacheKey = "commissions:vendedor:{$vendedorId}:period:{$year}:{$month}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($vendedorId, $year, $month) {

            $records = $this->odoo->execute('sale.order', 'search_read',
                [[
                    ['user_id',                   '=', $vendedorId],
                    ['year_comission',            '=', $year],
                    ['month_comission',           '=', $month],
                    ['to_invoice_button_clicked', '=', true],
                    ['state',                     '!=', 'cancel'],
                ]],
                ['fields' => ['name', 'user_id', 'amount_total', 'date_order'], 'limit' => 0]
            ) ?? [];

            $vendedorName = $this->resolveVendedorName($records, $vendedorId);
            if ($vendedorName === null) return null;

            $orderIds  = collect($records)->pluck('id')->all();
            $cargos    = $this->getCargosByOrders($orderIds);
            $totalOtf  = $this->sumCargos($cargos['otf']);
            $totalMrc  = $this->sumCargos($cargos['mrc']);

            $periodo = ucfirst(
                \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)
                    ->locale('es')
                    ->translatedFormat('F Y')
            );

            return [
                'vendedor_id' => $vendedorId,
                'vendedor'    => $vendedorName,
                'periodo'     => $periodo,
                'otf'         => round($totalOtf, 2),
                'mrc'         => round($totalMrc, 2),
                'total'       => round($totalOtf + $totalMrc, 2),
            ];
        });
    }

    // ── Por vendedor / año ────────────────────────────────────

    public function getForVendedorYear(int $vendedorId, string $year): ?array
    {
        $cacheKey = "commissions:vendedor:{$vendedorId}:year:{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($vendedorId, $year) {

            $records = $this->odoo->execute('sale.order', 'search_read',
                [[
                    ['user_id',                   '=', $vendedorId],
                    ['year_comission',            '=', $year],
                    ['to_invoice_button_clicked', '=', true],
                    ['state',                     '!=', 'cancel'],
                ]],
                ['fields' => ['name', 'user_id', 'month_comission', 'amount_total', 'date_order'], 'limit' => 0]
            ) ?? [];

            $vendedorName = $this->resolveVendedorName($records, $vendedorId);
            if ($vendedorName === null) return null;

            $orderIds      = collect($records)->pluck('id')->all();
            $cargos        = $this->getCargosByOrders($orderIds);
            $ordersByMonth = collect($records)->groupBy(fn($r) => (int) $r['month_comission']);

            $meses = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthOrderIds = $ordersByMonth->get($m, collect())->pluck('id')->all();

                $otf = collect($monthOrderIds)
                    ->reduce(fn($c, $id) => $c + ($cargos['otf'][$id] ?? collect())->sum('sub_amount'), 0.0);
                $mrc = collect($monthOrderIds)
                    ->reduce(fn($c, $id) => $c + ($cargos['mrc'][$id] ?? collect())->sum('sub_amount'), 0.0);

                $meses[] = [
                    'mes'   => $m,
                    'label' => self::MONTH_LABELS[$m],
                    'otf'   => round($otf, 2),
                    'mrc'   => round($mrc, 2),
                    'total' => round($otf + $mrc, 2),
                ];
            }

            $totales = [
                'otf'   => round(array_sum(array_column($meses, 'otf')), 2),
                'mrc'   => round(array_sum(array_column($meses, 'mrc')), 2),
                'total' => round(array_sum(array_column($meses, 'total')), 2),
            ];

            return [
                'vendedor_id' => $vendedorId,
                'vendedor'    => $vendedorName,
                'year'        => (int) $year,
                'meses'       => $meses,
                'totales'     => $totales,
            ];
        });
    }

    // ── Helpers ───────────────────────────────────────────────

    private function resolveVendedorName(array $records, int $vendedorId): ?string
    {
        if (!empty($records)) {
            $uid = $records[0]['user_id'];
            if (is_array($uid)) return $uid[1];
        }

        $users = $this->odoo->execute('res.users', 'search_read',
            [[['id', '=', $vendedorId]]],
            ['fields' => ['name'], 'limit' => 1]
        ) ?? [];

        return $users[0]['name'] ?? null;
    }

    private function sumCargos(\Illuminate\Support\Collection $grouped): float
    {
        return $grouped->reduce(fn($carry, $group) => $carry + $group->sum('sub_amount'), 0.0);
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
