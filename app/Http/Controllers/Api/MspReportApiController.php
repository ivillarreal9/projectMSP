<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MspReport;
use App\Services\MspPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Cache;

class MspReportApiController extends Controller
{
    /**
     * GET /api/v1/reports/msp/pdf
     *
     * Parámetros:
     *   - customer  (string, requerido) — nombre exacto del cliente
     *   - periodo   (string, requerido) — ej: "April 2026"
     *
     * Autenticación: Bearer token (Sanctum)
     *
     * Respuestas:
     *   - 200: archivo PDF (application/pdf)
     *   - 404: cliente o período no encontrado
     *   - 422: parámetros faltantes
     *   - 500: error generando el PDF
     */
    /**
     * GET /api/v1/reports/msp/periodos?customer=
     *
     * Retorna los períodos disponibles para un cliente.
     */
    public function periodos(Request $request): JsonResponse
    {
        $request->validate([
            'customer' => 'required|string|max:255',
        ]);

        $customer = trim($request->input('customer'));

        $periodos = Cache::remember(
            'api_periodos_' . md5($customer),
            now()->addHours(6),
            fn () => MspReport::where('customer_name', $customer)
                ->whereNotNull('periodo')
                ->distinct()
                ->orderByDesc('periodo')
                ->pluck('periodo')
                ->values()
                ->toArray()
        );

        if (empty($periodos)) {
            return response()->json([
                'error'    => 'No se encontraron períodos para ese cliente.',
                'customer' => $customer,
            ], 404);
        }

        return response()->json([
            'customer' => $customer,
            'periodos' => $periodos,
        ]);
    }

    /**
     * GET /api/v1/reports/msp/pdf
     *
     * Parámetros:
     *   - customer  (string, requerido) — nombre exacto del cliente
     *   - periodo   (string, requerido) — ej: "April 2026"
     *
     * Autenticación: Bearer token (Sanctum)
     *
     * Respuestas:
     *   - 200: archivo PDF (application/pdf)
     *   - 404: cliente o período no encontrado
     *   - 422: parámetros faltantes
     *   - 500: error generando el PDF
     */
    public function download(Request $request): BinaryFileResponse|JsonResponse
    {
        $validated = $request->validate([
            'customer' => 'required|string|max:255',
            'periodo'  => 'required|string|max:100',
        ]);

        $customer = trim($validated['customer']);
        $periodo  = trim($validated['periodo']);

        $exists = MspReport::where('customer_name', $customer)
            ->where('periodo', $periodo)
            ->exists();

        if (!$exists) {
            return response()->json([
                'error'    => 'No se encontraron reportes para ese cliente y período.',
                'customer' => $customer,
                'periodo'  => $periodo,
            ], 404);
        }

        try {
            $pdf      = app(MspPdfService::class);
            $path     = $pdf->generate($customer, $periodo); // usa caché si existe
            $filename = $pdf->buildFilename($customer, $periodo);

            Log::info("API PDF descargado: {$filename}", [
                'user'     => $request->user()?->email,
                'customer' => $customer,
                'periodo'  => $periodo,
            ]);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Throwable $e) {
            Log::error("API PDF error [{$customer}]: " . $e->getMessage());
            return response()->json([
                'error' => 'Error al generar el PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}
