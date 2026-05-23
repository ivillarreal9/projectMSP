<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MspReport;
use App\Models\MspClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class MspReportApiController extends Controller
{
    /**
     * GET /api/v1/reports/msp/pdf
     *
     * Parámetros:
     *   - customer  (string, requerido) — nombre exacto del cliente
     *   - periodo   (string, requerido) — ej: "Mayo 2026"
     *
     * Autenticación: Bearer token (Sanctum)
     *
     * Respuestas:
     *   - 200: archivo PDF (application/pdf)
     *   - 404: cliente o período no encontrado
     *   - 422: parámetros faltantes
     *   - 500: error generando el PDF
     */
    public function download(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'customer' => 'required|string|max:255',
            'periodo'  => 'required|string|max:100',
        ]);

        $customer = trim($validated['customer']);
        $periodo  = trim($validated['periodo']);

        // Verificar que existen datos para ese cliente y período
        $exists = MspReport::where('customer_name', $customer)
            ->where('periodo', $periodo)
            ->exists();

        if (!$exists) {
            return response()->json([
                'error'   => 'No se encontraron reportes para ese cliente y período.',
                'customer' => $customer,
                'periodo'  => $periodo,
            ], 404);
        }

        try {
            $stats       = MspReport::statsForCustomer($customer, $periodo);
            $logoUrl     = $this->resolveLogoUrl($customer);
            $ovnicomLogo = $this->getOvnicomLogo();

            $html = view('admin.reports.msp.pdf_template',
                compact('customer', 'stats', 'periodo', 'logoUrl', 'ovnicomLogo')
            )->render();

            $filename = $this->buildFilename($customer, $periodo);
            $path     = storage_path("app/public/msp_pdfs/{$filename}");

            $this->generatePdf($html, $path);

            Log::info("API PDF generado: {$filename}", [
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

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function generatePdf(string $html, string $outputPath): void
    {
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        Browsershot::html($html)
            ->setChromePath(env('BROWSERSHOT_CHROME_PATH', '/usr/bin/chromium'))
            ->setNodeBinary(env('BROWSERSHOT_NODE_PATH', '/usr/bin/node'))
            ->setNpmBinary(env('BROWSERSHOT_NPM_PATH', '/usr/bin/npm'))
            ->noSandbox()
            ->addChromiumArguments([
                'disable-dev-shm-usage',
                'disable-gpu',
            ])
            ->format('A4')
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->save($outputPath);
    }

    private function resolveLogoUrl(string $customer): ?string
    {
        $cliente = MspClient::where('customer_name', $customer)->first();
        if ($cliente?->logo_path && file_exists(public_path('storage/' . $cliente->logo_path))) {
            return asset('storage/' . $cliente->logo_path);
        }
        return null;
    }

    private function getOvnicomLogo(): string
    {
        $path = public_path('images/ovnicom-logo.png');
        if (!file_exists($path)) return '';
        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }

    private function buildFilename(string $customer, string $periodo): string
    {
        $safeCustomer = str_replace([' ', '/', ',', '\\'], '-', $customer);
        $safePeriodo  = str_replace(' ', '-', $periodo);
        return "MSP-{$safeCustomer}-{$safePeriodo}.pdf";
    }
}
