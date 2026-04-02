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
use Laravel\Ai\Ai;

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
    // SSE: stream de progreso
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

                $this->sendEvent('status', [
                    'step'    => 3,
                    'message' => 'Combinando y formateando resultados...',
                    'percent' => 95,
                ]);

                $result = $service->combinePublic($tickets, $extraData);

                Cache::put($cacheKey, $result, now()->addMinutes(30));

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
    // Resultados desde cache
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
    // Chat con Laravel AI
    // POST /admin/api-msp/chat
    // -------------------------------------------------------------------------

    public function chat(Request $request)
    {
        $request->validate([
            'message'   => 'required|string|max:1000',
            'cache_key' => 'required|string',
        ]);

        $tickets = Cache::get($request->cache_key);

        if (!$tickets) {
            return response()->json([
                'reply' => 'Los datos han expirado. Por favor vuelve a filtrar los tickets.',
            ]);
        }

        $context = $this->buildAiContext($tickets);

        try {
            $response = \App\Ai\Agents\MspChatAgent::make()
                ->setTicketContext($context)
                ->prompt($request->message);

            return response()->json(['reply' => (string) $response]);

        } catch (\Throwable $e) {
            return response()->json([
                'reply' => 'Error al procesar tu consulta: ' . $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Construir contexto compacto para el AI
    // -------------------------------------------------------------------------

    private function buildAiContext(array $tickets): string
    {
        $total = count($tickets);

        // Estadísticas por cliente
        $porCliente = [];
        $porTecnico = [];
        $porTipo    = [];
        $porWorkType = [];

        foreach ($tickets as $t) {
            $cliente  = $t['CustomerName']        ?? 'Sin cliente';
            $tecnico  = trim(($t['UserFirstName'] ?? '') . ' ' . ($t['UserLastName'] ?? '')) ?: ($t['WorkType'] ?? 'Sin técnico');
            $tipo     = $t['TicketIssueTypeName']  ?? 'Sin tipo';
            $workType = $t['WorkType']             ?? 'Sin WorkType';

            $porCliente[$cliente]   = ($porCliente[$cliente]  ?? 0) + 1;
            $porTecnico[$tecnico]   = ($porTecnico[$tecnico]  ?? 0) + 1;
            $porTipo[$tipo]         = ($porTipo[$tipo]        ?? 0) + 1;
            $porWorkType[$workType] = ($porWorkType[$workType] ?? 0) + 1;
        }

        arsort($porCliente);
        arsort($porTecnico);
        arsort($porTipo);
        arsort($porWorkType);

        // Top 10 de cada categoría
        $topClientes  = array_slice($porCliente,  0, 10, true);
        $topTecnicos  = array_slice($porTecnico,  0, 10, true);
        $topTipos     = array_slice($porTipo,     0, 10, true);
        $topWorkTypes = array_slice($porWorkType, 0, 10, true);

        // Lista de tickets resumida (número, título, cliente, worktype, fechas)
        $listaTickets = array_map(fn($t) => sprintf(
            '[%s] %s | Cliente: %s | WorkType: %s | Completado: %s',
            $t['TicketNumber']  ?? 'N/A',
            $t['TicketTitle']   ?? 'Sin título',
            $t['CustomerName']  ?? 'N/A',
            $t['WorkType']      ?? 'N/A',
            $t['CompletedDate'] ?? 'N/A'
        ), $tickets);

        $context  = "TOTAL DE TICKETS: {$total}\n\n";

        $context .= "TOP CLIENTES CON MÁS TICKETS:\n";
        foreach ($topClientes as $nombre => $count) {
            $context .= "  - {$nombre}: {$count} tickets\n";
        }

        $context .= "\nTOP WORK TYPES:\n";
        foreach ($topWorkTypes as $wt => $count) {
            $context .= "  - {$wt}: {$count} tickets\n";
        }

        $context .= "\nTOP TIPOS DE ISSUE:\n";
        foreach ($topTipos as $tipo => $count) {
            $context .= "  - {$tipo}: {$count} tickets\n";
        }

        $context .= "\nLISTA COMPLETA DE TICKETS:\n";
        $context .= implode("\n", $listaTickets);

        return $context;
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
    // Export Excel
    // -------------------------------------------------------------------------

    public function export(Request $request)
    {
        set_time_limit(300);

        $cacheKey = $request->get('cache_key');
        $tickets  = $cacheKey ? Cache::get($cacheKey) : null;

        if (!$tickets) {
            try {
                $service    = new MspService();
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