<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\CommissionService;
use Carbon\Carbon;

class SalesOverviewController extends Controller
{
    public function index()
    {
        $mode           = request('mode', 'mes');
        $availableYears = range(now()->year, now()->year - 3);

        return view('admin.sales.overview.overview', compact('mode', 'availableYears'));
    }

    public function commissions(CommissionService $commissions)
    {
        $mode = request('mode', 'mes');
        $year = (string) now()->year;

        if ($mode === 'acumulado') {
            $data  = $commissions->getByYear($year);
            $label = 'Acumulado ' . $year;
        } elseif ($mode === 'mes_actual') {
            $period = Carbon::now();
            $data   = $commissions->getByPeriod((string) $period->year, (string) $period->month);
            $label  = $period->translatedFormat('F Y');
        } else {
            $period = Carbon::now()->subMonth();
            $data   = $commissions->getByPeriod((string) $period->year, (string) $period->month);
            $label  = $period->translatedFormat('F Y');
        }

        $vendedorIds = collect($data['by_vendedor'] ?? [])
            ->pluck('vendedor_id')
            ->filter(fn($id) => is_int($id) && $id > 0)
            ->values()
            ->all();

        $userImages = [];
        if (!empty($vendedorIds)) {
            $users = $commissions->odoo()->execute('res.users', 'search_read',
                [[['id', 'in', $vendedorIds]]],
                ['fields' => ['id', 'image_128'], 'limit' => 0]
            ) ?? [];

            foreach ($users as $u) {
                $img = $u['image_128'] ?? null;
                $userImages[$u['id']] = ($img && !str_starts_with($img, 'PD94'))
                    ? 'data:image/png;base64,' . $img
                    : null;
            }
        }

        $toJs = function ($byVendedor, string $tipo) use ($userImages): array {
            return collect($byVendedor)
                ->sortByDesc($tipo)
                ->values()
                ->map(fn($v) => [
                    'name'     => $v['vendedor_name'],
                    'short'    => collect(explode(' ', $v['vendedor_name']))->first(),
                    'initials' => collect(explode(' ', $v['vendedor_name']))->take(2)
                                    ->map(fn($w) => strtoupper(substr($w, 0, 1)))->join(''),
                    'revenue'  => $v[$tipo],
                    'cantidad' => $v['cantidad'],
                    'image'    => $userImages[$v['vendedor_id']] ?? null,
                ])
                ->all();
        };

        return response()->json([
            'periodoComisiones' => $label,
            'totalOtf'          => $data['total_otf'] ?? 0,
            'totalMrc'          => $data['total_mrc'] ?? 0,
            'totalComis'        => $data['total']     ?? 0,
            'cantidadOrd'       => $data['cantidad']  ?? 0,
            'dataOtf'           => $toJs($data['by_vendedor'] ?? [], 'total_otf'),
            'dataMrc'           => $toJs($data['by_vendedor'] ?? [], 'total_mrc'),
        ]);
    }
}
