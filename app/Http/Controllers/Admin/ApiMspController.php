<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MspCredential;
use App\Services\MspService;
use App\Exports\ApiMspExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiMspController extends Controller
{
    // -------------------------------------------------------------------------
    // Vista principal
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $credential  = MspCredential::latest()->first();
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin    = $request->get('fecha_fin', now()->format('Y-m-d'));

        return view('admin.api-msp.index', compact(
            'credential', 'fechaInicio', 'fechaFin'
        ));
    }

    // -------------------------------------------------------------------------
    // SSE: stream de progreso — NO envía los tickets, solo el progreso
    // Al terminar guarda los tickets en Cache con una clave única
    // -------------------------------------------------------------------------

    public function stream(Request $request): StreamedResponse
    {
        session()->save();

        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin    = $request->get('fecha_fin', now()->format('Y-m-d'));
        $cacheKey    = 'msp_result_' . auth()->id() . '_' . md5($fechaInicio . $fechaFin);

        return response()->stream(function () use ($fechaInicio, $fechaFin, $cacheKey) {

            set_time_limit(300);
            ini_set('max_execution_time', 300);

            // Limpiar todos los buffers
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $this->sendEvent('status', [
                'step'    => 0,
                'message' => 'Iniciando consulta...',
                'percent' => 5,
            ]);

            try {
                $cred = MspCredential::latest()->first();
                if (!$cred) {
                    $this->sendEvent('error', ['message' => 'No hay credenciales configuradas.']);
                    return;
                }

                $service = new MspService();

                // ----- EP1 -----
                $this->sendEvent('status', [
                    'step'    => 1,
                    'message' => 'Consultando tickets por rango de fecha...',
                    'percent' => 10,
                ]);

                $tickets = $service->fetchTicketsPublic($fechaInicio, $fechaFin);
                $total   = count($tickets);

                $this->sendEvent('status', [
                    'step'          => 1,
                    'message'       => "EP1 completado — {$total} tickets encontrados",
                    'percent'       => 30,
                    'tickets_found' => $total,
                ]);

                if ($total === 0) {
                    $this->sendEvent('done', [
                        'cache_key' => $cacheKey,
                        'total'     => 0,
                        'percent'   => 100,
                        'message'   => 'No se encontraron tickets',
                    ]);
                    return;
                }

                // ----- EP2 + EP3 -----
                $this->sendEvent('status', [
                    'step'    => 2,
                    'message' => "Procesando {$total} tickets en paralelo...",
                    'percent' => 35,
                ]);

                $extraData = $service->fetchExtraDataPublic(
                    $tickets,
                    function (int $done, int $totalCount) {
                        $percent = 35 + (int)(($done / $totalCount) * 55);
                        $this->sendEvent('progress', [
                            'step'    => 2,
                            'message' => "Procesando lote: {$done} / {$totalCount} tickets",
                            'percent' => min($percent, 90),
                            'done'    => $done,
                            'total'   => $totalCount,
                        ]);
                    }
                );

                // ----- Combinar -----
                $this->sendEvent('status', [
                    'step'    => 3,
                    'message' => 'Combinando y formateando resultados...',
                    'percent' => 95,
                ]);

                $result = $service->combinePublic($tickets, $extraData);

                // Guardar en Cache por 30 minutos (NO en sesión — demasiado grande)
                Cache::put($cacheKey, $result, now()->addMinutes(30));

                // Enviar solo la clave, NO los datos
                $this->sendEvent('done', [
                    'cache_key' => $cacheKey,
                    'total'     => count($result),
                    'percent'   => 100,
                    'message'   => 'Consulta completada',
                ]);

            } catch (\Throwable $e) {
                $this->sendEvent('error', ['message' => $e->getMessage()]);
            }

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api-msp/results?cache_key=xxx
    // El JS llama esto después de recibir el evento "done"
    // -------------------------------------------------------------------------

    public function results(Request $request)
    {
        $cacheKey = $request->get('cache_key');

        if (!$cacheKey) {
            return response()->json(['error' => 'cache_key requerido'], 400);
        }

        $tickets = Cache::get($cacheKey);

        if ($tickets === null) {
            return response()->json(['error' => 'Resultados expirados, vuelve a filtrar'], 404);
        }

        return response()->json([
            'tickets' => $tickets,
            'total'   => count($tickets),
        ]);
    }

    // -------------------------------------------------------------------------
    // Guardar credenciales
    // -------------------------------------------------------------------------

    public function saveCredential(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'base_url' => 'nullable|url',
        ]);

        MspCredential::truncate();

        MspCredential::create([
            'username' => $request->username,
            'password' => $request->password,
            'base_url' => $request->base_url ?? 'https://api.mspmanager.com/odata',
        ]);

        return back()->with('success', 'Credenciales guardadas correctamente.');
    }

    // -------------------------------------------------------------------------
    // Export Excel — usa el cache si existe, sino vuelve a consultar
    // -------------------------------------------------------------------------

    public function export(Request $request)
    {
        set_time_limit(300);

        $cacheKey = $request->get('cache_key');
        $tickets  = $cacheKey ? Cache::get($cacheKey) : null;

        if (!$tickets) {
            try {
                $service   = new MspService();
                $rawTickets = $service->fetchTicketsPublic(
                    $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d')),
                    $request->get('fecha_fin', now()->format('Y-m-d'))
                );
                $extraData = $service->fetchExtraDataPublic($rawTickets);
                $tickets   = $service->combinePublic($rawTickets, $extraData);
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return Excel::download(
            new ApiMspExport($tickets),
            'tickets-msp-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // -------------------------------------------------------------------------
    // Helper SSE
    // -------------------------------------------------------------------------

    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}