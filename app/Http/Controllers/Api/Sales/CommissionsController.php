<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionsController extends Controller
{
    public function __construct(private CommissionService $commissions) {}

    public function month(Request $request, int $vendedor_id): JsonResponse
    {
        $validated = $request->validate([
            'year'  => ['sometimes', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        $year  = (int) ($validated['year']  ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        $data = $this->commissions->getMonthly($vendedor_id, $year, $month);

        if (!$data) {
            return response()->json(
                ['message' => 'Vendedor no encontrado o sin comisiones en este período.'],
                404
            );
        }

        return response()->json($data);
    }

    public function year(Request $request, int $vendedor_id): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);

        $data = $this->commissions->getYearly($vendedor_id, $year);

        if (!$data) {
            return response()->json(
                ['message' => 'Vendedor no encontrado o sin comisiones en este período.'],
                404
            );
        }

        return response()->json($data);
    }
}
