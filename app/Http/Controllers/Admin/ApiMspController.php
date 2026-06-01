<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MspService;
use App\Exports\ApiMspExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador del módulo API MSP (consulta directa de tickets por rango de fechas).
 *
 * Permite consultar tickets de soporte técnico desde la API externa MSP en tiempo real,
 * con progreso en vivo mediante Server-Sent Events (SSE). Los resultados se almacenan
 * temporalmente en caché Redis/cache para ser consumidos por la tabla de resultados y
 * el chat IA sin repetir la consulta al servidor externo.
 *
 * Flujo de uso:
 *  1. Usuario selecciona rango de fechas en la vista index.
 *  2. El frontend abre un EventSource a stream() que emite eventos SSE de progreso.
 *  3. Al recibir el evento 'done', el frontend llama a results() con el cache_key.
 *  4. Opcionalmente el usuario puede chatear con los datos vía chat().
 *  5. O exportar los resultados a Excel vía export().
 *
 * TTL de caché:
 *  - Rango de fechas pasadas (fechaFin en el pasado): 8 horas (datos no cambian).
 *  - Rango activo (incluye hoy): 30 minutos (pueden llegar tickets nuevos).
 *
 * Vistas:
 *   - admin.api-msp.index → Formulario de consulta y tabla de resultados
 *
 * Rutas principales (prefijo /admin/api-msp):
 *   GET  /          → index()
 *   GET  /stream    → stream()   [SSE]
 *   GET  /results   → results()  [AJAX]
 *   POST /chat      → chat()     [AJAX]
 *   GET  /export    → export()   [descarga Excel]
 *
 * @see \App\Services\MspService  Servicio de integración con la API MSP externa
 */
class ApiMspController extends Controller
{
    /**
     * Vista principal del módulo API MSP.
     *
     * Muestra el formulario de búsqueda por rango de fechas y una indicación
     * del estado de las credenciales MSP configuradas en .env.
     * Las fechas por defecto son: inicio del mes actual hasta hoy.
     *
     * @param  \Illuminate\Http\Request  $request  Parámetros opcionales: fecha_inicio, fecha_fin
     * @return \Illuminate\View\View               Vista admin.api-msp.index
     */
    public function index(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin    = $request->get('fecha_fin', now()->format('Y-m-d'));

        // Verificar que las credenciales están configuradas en .env
        $credencialesOk = !empty(config('services.msp.username')) && !empty(config('services.msp.password'));

        return view('admin.api-msp.index', compact('fechaInicio', 'fechaFin', 'credencialesOk'));
    }

    /**
     * Endpoint SSE (Server-Sent Events) que transmite el progreso de la consulta MSP en tiempo real.
     *
     * Emite los siguientes tipos de eventos SSE al cliente:
     *  - 'status'   → Cambio de paso mayor (paso 0: inicio, paso 1: tickets base, paso 2: extra data, paso 3: combinado)
     *  - 'progress' → Progreso granular del procesamiento paralelo (lote X / total Y)
     *  - 'done'     → Consulta finalizada, incluye cache_key para recuperar resultados
     *  - 'error'    → Error irrecuperable durante la consulta
     *
     * Técnicas requeridas para SSE en PHP:
     *  - session()->save() antes de iniciar el stream (libera bloqueo de sesión).
     *  - set_time_limit(300) para permitir consultas largas sin timeout de PHP.
     *  - Limpiar todos los buffers de salida con ob_end_clean() para envío inmediato.
     *  - Headers: Content-Type: text/event-stream, X-Accel-Buffering: no (nginx).
     *
     * El cache_key tiene formato: msp_result_{user_id}_{md5(fechaInicio+fechaFin)},
     * garantizando que cada usuario tenga su propio espacio en caché y que el mismo
     * rango de fechas reutilice el resultado almacenado.
     *
     * @param  \Illuminate\Http\Request  $request  Parámetros: fecha_inicio, fecha_fin
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream(Request $request): StreamedResponse
    {
        session()->save();

        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin    = $request->get('fecha_fin', now()->format('Y-m-d'));
        $cacheKey    = 'msp_result_' . auth()->id() . '_' . md5($fechaInicio . $fechaFin);

        return response()->stream(function () use ($fechaInicio, $fechaFin, $cacheKey) {

            set_time_limit(300);
            ini_set('max_execution_time', 300);

            while (ob_get_level() > 0) ob_end_clean();

            $this->sendEvent('status', [
                'step'    => 0,
                'message' => 'Iniciando consulta...',
                'percent' => 5,
            ]);

            try {
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

                // Fechas pasadas no cambian: cache de 8h. Rango activo: 30 min.
                $esPasado = \Carbon\Carbon::parse($fechaFin)->endOfDay()->isPast();
                $ttl      = $esPasado ? now()->addHours(8) : now()->addMinutes(30);
                Cache::put($cacheKey, $result, $ttl);

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

    /**
     * Devuelve los tickets almacenados en caché tras completarse el stream SSE.
     *
     * Endpoint AJAX llamado por el frontend una vez recibe el evento 'done' del SSE.
     * Si la caché expiró (TTL vencido), devuelve 404 con instrucción de repetir consulta.
     *
     * @param  \Illuminate\Http\Request  $request  Parámetro requerido: cache_key
     * @return \Illuminate\Http\JsonResponse        {tickets: array, total: int} o error
     */
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

    /**
     * Chat IA sobre los tickets del rango de fechas consultado.
     *
     * Usa los tickets almacenados en caché (identificados por cache_key) para construir
     * un contexto de texto plano que se pasa al agente MspChatAgent. El contexto incluye:
     *  - Total de tickets
     *  - Top 10 clientes por volumen
     *  - Top 10 Work Types
     *  - Top 10 tipos de issue
     *  - Lista completa de tickets (número, título, cliente, work type, fecha completado)
     *
     * Si la caché expiró, devuelve un mensaje de error amigable sin excepciones.
     *
     * @param  \Illuminate\Http\Request  $request  Campos: message (pregunta del usuario), cache_key
     * @return \Illuminate\Http\JsonResponse        {reply: string}
     */
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

    /**
     * Exporta los tickets a Excel (.xlsx) usando Maatwebsite Excel.
     *
     * Intenta primero usar los tickets del caché (cache_key). Si no están disponibles
     * (caché expirada o no se ha consultado), realiza una nueva consulta completa a la API MSP.
     * Esto permite exportar incluso si el usuario refrescó la página.
     *
     * @param  \Illuminate\Http\Request  $request  Parámetros: cache_key (opcional),
     *                                             fecha_inicio, fecha_fin (fallback si no hay caché)
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
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

    /**
     * Construye el contexto de texto plano para el LLM a partir de los tickets.
     *
     * Genera estadísticas agregadas (top clientes, work types, tipos de issue) y
     * la lista completa de tickets en formato legible. Se limita a los top 10 en cada
     * categoría para no exceder el contexto del modelo IA.
     *
     * @param  array  $tickets  Array de tickets combinados (resultado de MspService::combinePublic)
     * @return string           Contexto formateado como texto plano para el agente IA
     */
    private function buildAiContext(array $tickets): string
    {
        $total       = count($tickets);
        $porCliente  = [];
        $porTipo     = [];
        $porWorkType = [];

        foreach ($tickets as $t) {
            $cliente  = $t['CustomerName']       ?? 'Sin cliente';
            $tipo     = $t['TicketIssueTypeName'] ?? 'Sin tipo';
            $workType = $t['WorkType']            ?? 'Sin WorkType';

            $porCliente[$cliente]   = ($porCliente[$cliente]   ?? 0) + 1;
            $porTipo[$tipo]         = ($porTipo[$tipo]         ?? 0) + 1;
            $porWorkType[$workType] = ($porWorkType[$workType] ?? 0) + 1;
        }

        arsort($porCliente);
        arsort($porTipo);
        arsort($porWorkType);

        $topClientes  = array_slice($porCliente,  0, 10, true);
        $topTipos     = array_slice($porTipo,     0, 10, true);
        $topWorkTypes = array_slice($porWorkType, 0, 10, true);

        $listaTickets = array_map(fn($t) => sprintf(
            '[%s] %s | Cliente: %s | WorkType: %s | Completado: %s',
            $t['TicketNumber']  ?? 'N/A',
            $t['TicketTitle']   ?? 'Sin título',
            $t['CustomerName']  ?? 'N/A',
            $t['WorkType']      ?? 'N/A',
            $t['CompletedDate'] ?? 'N/A'
        ), $tickets);

        $context  = "TOTAL DE TICKETS: {$total}\n\n";
        $context .= "TOP CLIENTES:\n";
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

    /**
     * Emite un evento SSE (Server-Sent Events) al cliente conectado.
     *
     * Formato del protocolo SSE:
     *   event: {nombre}\n
     *   data: {json}\n\n
     *
     * Llama a ob_flush() + flush() para enviar los datos inmediatamente al cliente
     * sin esperar a que PHP llene el buffer de salida.
     *
     * @param  string  $event  Nombre del evento SSE (status, progress, done, error)
     * @param  array   $data   Datos a serializar como JSON en el campo 'data' del evento
     * @return void
     */
    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }
}