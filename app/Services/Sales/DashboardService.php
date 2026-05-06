<?php

namespace App\Services\Sales;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    const CACHE_TTL = 300;

    public function __construct(private OdooService $odoo) {}

    public function getDashboardKpis(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "dashboard:kpis:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateFrom, $dateTo) {

            $leads = $this->odoo->execute('crm.lead', 'search_count', [[
                ['type',        '=',  'lead'],
                ['create_date', '>=', $dateFrom],
                ['create_date', '<=', $dateTo],
            ]]) ?? 0;

            $won = $this->odoo->execute('crm.lead', 'search_count', [[
                ['stage_id.is_won', '=',  true],
                ['date_closed',     '>=', $dateFrom],
                ['date_closed',     '<=', $dateTo],
            ]]) ?? 0;

            $wonLeads = $this->odoo->execute('crm.lead', 'search_read', [[
                ['stage_id.is_won', '=',  true],
                ['date_closed',     '>=', $dateFrom],
                ['date_closed',     '<=', $dateTo],
            ]], ['fields' => ['expected_revenue'], 'limit' => 0]) ?? [];
            $revenueWon = collect($wonLeads)->sum('expected_revenue');

            $orders = $this->odoo->execute('sale.order', 'search_read', [[
                ['state',      'in', ['sale', 'done']],
                ['date_order', '>=', $dateFrom],
                ['date_order', '<=', $dateTo],
            ]], ['fields' => ['amount_total'], 'limit' => 0]) ?? [];
            $revenueOrders = collect($orders)->sum('amount_total');

            $totalOport = $leads + $won;
            $winRate    = $totalOport > 0 ? round(($won / $totalOport) * 100, 1) : 0;

            return compact('leads', 'won', 'revenueWon', 'revenueOrders', 'winRate');
        });
    }

    public function getMonthlyTrend(int $year): array
    {
        $cacheKey = "dashboard:monthly:{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year) {
            $months = [];

            for ($m = 1; $m <= 12; $m++) {
                $from = Carbon::create($year, $m, 1)->startOfMonth()->format('Y-m-d H:i:s');
                $to   = Carbon::create($year, $m, 1)->endOfMonth()->format('Y-m-d H:i:s');

                $leads = $this->odoo->execute('crm.lead', 'search_count', [[
                    ['type',        '=',  'lead'],
                    ['create_date', '>=', $from],
                    ['create_date', '<=', $to],
                ]]) ?? 0;

                $won = $this->odoo->execute('crm.lead', 'search_count', [[
                    ['stage_id.is_won', '=',  true],
                    ['date_closed',     '>=', $from],
                    ['date_closed',     '<=', $to],
                ]]) ?? 0;

                $wonLeads = $this->odoo->execute('crm.lead', 'search_read', [[
                    ['stage_id.is_won', '=',  true],
                    ['date_closed',     '>=', $from],
                    ['date_closed',     '<=', $to],
                ]], ['fields' => ['expected_revenue'], 'limit' => 0]) ?? [];
                $revenue = collect($wonLeads)->sum('expected_revenue');

                $months[] = [
                    'month'   => $m,
                    'label'   => Carbon::create($year, $m)->translatedFormat('M'),
                    'leads'   => $leads,
                    'won'     => $won,
                    'revenue' => $revenue,
                ];
            }

            return $months;
        });
    }

    public function getStatsByExecutive(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "dashboard:byexec:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateFrom, $dateTo) {
            $ids = config('sales.executive_ids', []);
            if (empty($ids)) return [];

            $executives = $this->odoo->execute('res.users', 'search_read',
                [[['id', 'in', $ids]]],
                ['fields' => ['id', 'name', 'image_128'], 'limit' => 50]
            ) ?? [];

            return collect($executives)->map(function ($exec) use ($dateFrom, $dateTo) {
                $userId = $exec['id'];

                $won = $this->odoo->execute('crm.lead', 'search_count', [[
                    ['user_id',         '=',  $userId],
                    ['stage_id.is_won', '=',  true],
                    ['date_closed',     '>=', $dateFrom],
                    ['date_closed',     '<=', $dateTo],
                ]]) ?? 0;

                $leads = $this->odoo->execute('crm.lead', 'search_count', [[
                    ['user_id',     '=',  $userId],
                    ['type',        '=',  'lead'],
                    ['create_date', '>=', $dateFrom],
                    ['create_date', '<=', $dateTo],
                ]]) ?? 0;

                $wonLeads = $this->odoo->execute('crm.lead', 'search_read', [[
                    ['user_id',         '=',  $userId],
                    ['stage_id.is_won', '=',  true],
                    ['date_closed',     '>=', $dateFrom],
                    ['date_closed',     '<=', $dateTo],
                ]], ['fields' => ['expected_revenue'], 'limit' => 0]) ?? [];
                $revenue = collect($wonLeads)->sum('expected_revenue');

                $total   = $leads + $won;
                $winRate = $total > 0 ? round(($won / $total) * 100, 1) : 0;

                return [
                    'id'       => $userId,
                    'name'     => $exec['name'],
                    'image'    => $exec['image_128'] ?? null,
                    'leads'    => $leads,
                    'won'      => $won,
                    'revenue'  => $revenue,
                    'win_rate' => $winRate,
                ];
            })->sortByDesc('revenue')->values()->all();
        });
    }
}