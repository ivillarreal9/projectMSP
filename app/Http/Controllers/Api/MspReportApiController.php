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

/**
 * Controlador para descarga de reportes MSP en PDF desde la aplicación móvil.
 *
 * Expone dos endpoints: uno para consultar los períodos disponibles de un cliente
 * y otro para descargar el PDF del reporte correspondiente. La generación de PDF
 * se delega a MspPdfService, que aplica caché de 48 horas para evitar regenerar
 * el mismo documento. El listado de períodos también se almacena en caché por 6 horas.
 *
 * Ruta base: /api/v1/reports/msp
 * Autenticación: Bearer token (Sanctum)
 */
class MspReportApiController extends Controller
{
    /**
     * Lista los períodos con reportes disponibles para un cliente MSP.
     *
     * Consulta la base de datos local (tabla msp_reports) y retorna los períodos
     * distintos ordenados de más reciente a más antiguo. Cada período se incluye
     * con su valor original en inglés (ej: "April 2026") y su etiqueta traducida
     * al español (ej: "Abril 2026"). Los resultados se cachean 6 horas por cliente.
     *
     * GET /api/v1/reports/msp/periodos?customer={customer}
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request  Requiere: customer (string, max:255) — nombre exacto del cliente
     * @return JsonResponse      200 con { customer: string, periodos: [{ value, label }] }
     *                           | 404 si no hay períodos para ese cliente
     *                           | 422 validación fallida
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
                ->map(fn($p) => [
                    'value' => $p,
                    'label' => MspReport::translatePeriodo($p),
                ])
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
     * Descarga el PDF del reporte MSP de un cliente para un período específico.
     *
     * Verifica primero que exista al menos un reporte en la base de datos local
     * para la combinación customer + periodo antes de intentar generar el PDF.
     * La generación se delega a MspPdfService, que reutiliza el archivo si ya
     * existe en caché (48h), evitando regeneraciones costosas en Chromium.
     * El nombre del archivo descargado se construye automáticamente según
     * la nomenclatura interna del proyecto.
     *
     * GET /api/v1/reports/msp/pdf?customer={customer}&periodo={periodo}
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request  Requiere: customer (string, max:255) — nombre exacto del cliente
     *                                     periodo  (string, max:100)  — ej: "April 2026"
     * @return BinaryFileResponse|JsonResponse
     *         200 application/pdf con el archivo adjunto para descarga
     *         | 404 si no hay reportes para esa combinación cliente/período
     *         | 422 validación fallida
     *         | 500 error al generar el PDF (Chromium/Browsershot)
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
