<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\Cache;

class CommissionService extends OdooService
{
    private const CACHE_TTL = 300; // 5 minutos

    private const MES_LABELS = [
        1  => 'Enero',      2  => 'Febrero',   3  => 'Marzo',
        4  => 'Abril',      5  => 'Mayo',       6  => 'Junio',
        7  => 'Julio',      8  => 'Agosto',     9  => 'Septiembre',
        10 => 'Octubre',    11 => 'Noviembre',  12 => 'Diciembre',
    ];

    public function getMonthly(int $vendedorId, int $year, int $month): ?array
    {
        $key = "commissions.{$vendedorId}.{$year}.{$month}";

        return Cache::remember($key, self::CACHE_TTL, fn () =>
            $this->fetchMonthly($vendedorId, $year, $month)
        );
    }

    public function getYearly(int $vendedorId, int $year): ?array
    {
        $key = "commissions.yearly.{$vendedorId}.{$year}";

        return Cache::remember($key, self::CACHE_TTL, fn () =>
            $this->fetchYearly($vendedorId, $year)
        );
    }

    private function fetchMonthly(int $vendedorId, int $year, int $month): ?array
    {
        $uid  = $this->login();
        $user = $uid ? $this->readVendedor($uid, $vendedorId) : null;

        if (!$user) {
            return null;
        }

        ['otf' => $otf, 'mrc' => $mrc, 'adendas' => $adendas] =
            $this->queryCommissions($uid, $vendedorId, $year, $month);

        return [
            'vendedor_id' => $vendedorId,
            'vendedor'    => $user['name'],
            'periodo'     => self::MES_LABELS[$month] . ' ' . $year,
            'otf'         => round($otf, 2),
            'mrc'         => round($mrc, 2),
            'adendas'     => round($adendas, 2),
            'total'       => round($otf + $mrc + $adendas, 2),
        ];
    }

    private function fetchYearly(int $vendedorId, int $year): ?array
    {
        $uid  = $this->login();
        $user = $uid ? $this->readVendedor($uid, $vendedorId) : null;

        if (!$user) {
            return null;
        }

        $meses   = [];
        $totales = ['otf' => 0.0, 'mrc' => 0.0, 'adendas' => 0.0, 'total' => 0.0];

        for ($m = 1; $m <= 12; $m++) {
            ['otf' => $otf, 'mrc' => $mrc, 'adendas' => $adendas] =
                $this->queryCommissions($uid, $vendedorId, $year, $m);

            $otf     = round($otf, 2);
            $mrc     = round($mrc, 2);
            $adendas = round($adendas, 2);
            $total   = round($otf + $mrc + $adendas, 2);

            $meses[] = [
                'mes'     => $m,
                'label'   => self::MES_LABELS[$m],
                'otf'     => $otf,
                'mrc'     => $mrc,
                'adendas' => $adendas,
                'total'   => $total,
            ];

            $totales['otf']     += $otf;
            $totales['mrc']     += $mrc;
            $totales['adendas'] += $adendas;
            $totales['total']   += $total;
        }

        return [
            'vendedor_id' => $vendedorId,
            'vendedor'    => $user['name'],
            'year'        => $year,
            'meses'       => $meses,
            'totales'     => array_map(fn ($v) => round($v, 2), $totales),
        ];
    }

    private function readVendedor(int $uid, int $vendedorId): ?array
    {
        $result = $this->call('object', 'execute_kw', [
            $this->db, $uid, $this->apiKey,
            'res.users', 'read',
            [[$vendedorId]],
            ['fields' => ['id', 'name']],
        ]);

        $rows = $result['result'] ?? [];

        return $rows[0] ?? null;
    }

    private function queryCommissions(int $uid, int $vendedorId, int $year, int $month): array
    {
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $dateTo   = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

        // Ajustar ODOO_COMMISSION_MODEL al modelo real de comisiones en Odoo
        $model = env('ODOO_COMMISSION_MODEL', 'sale.commission.line');

        $result = $this->call('object', 'execute_kw', [
            $this->db, $uid, $this->apiKey,
            $model,
            'search_read',
            [[
                ['agent_id.user_id', '=', $vendedorId],
                ['date', '>=', $dateFrom],
                ['date', '<=', $dateTo],
            ]],
            ['fields' => ['commission_type', 'amount']],
        ]);

        $rows    = $result['result'] ?? [];
        $otf     = 0.0;
        $mrc     = 0.0;
        $adendas = 0.0;

        foreach ($rows as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            match ($row['commission_type'] ?? '') {
                'otf'    => $otf     += $amount,
                'mrc'    => $mrc     += $amount,
                'adenda' => $adendas += $amount,
                default  => null,
            };
        }

        return compact('otf', 'mrc', 'adendas');
    }
}
