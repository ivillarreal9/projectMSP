<?php

namespace App\Services;

use App\Models\MspCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio principal de integración con la API REST de MSP (Managed Services Platform).
 *
 * Expone tres capas de acceso:
 *   1. Métodos públicos "SSE-friendly" que el controlador llama por separado para
 *      poder emitir eventos Server-Sent Events después de cada paso.
 *   2. Un método agregado getTickets() para flujos sin SSE (p.ej. exportación Excel).
 *   3. Métodos de búsqueda unificada por RUC (3 fases) y CRUD de clientes.
 *
 * Dependencias externas:
 *   - API MSP   : HTTP Basic Auth (SERVICES_MSP_USERNAME / PASSWORD / BASE_URL en .env)
 *   - Http::pool: solicitudes paralelas por chunks para EP2 y EP3
 *   - Cache      : custom fields de tickets cerrados cacheados 48 h (msp_cf_{ticketId})
 *   - Carbon     : normalización de fechas UTC → hora de Panamá (UTC-5)
 */
class MspService
{
    protected string $baseUrl;
    protected string $authHeader;

    /** Número de tickets por lote en las llamadas paralelas EP2 + EP3. */
    private const CHUNK_SIZE    = 25;

    /** TTL para los custom fields de tickets cerrados: 48 horas. */
    private const CF_CACHE_TTL  = 172800;

    private const CUSTOM_FIELD_IDS = [
        '3113f8e8-1d04-f011-90cd-000d3a1010e6', // Tipo de Cliente
        'cb7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Causa
        'cc7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Ubicación
        'cd7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Solución - Acción
        'ce7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Detalle - Reporte 2
        'cf7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Reporte 1
        'cabd4a41-909b-ee11-bea0-0022482ddcd2',  // Daño
        'a80fd16c-939b-ee11-bea0-0022482ddcd2',  // Solución
        'a90fd16c-939b-ee11-bea0-0022482ddcd2',  // Ubicación Cierre
        'aa0fd16c-939b-ee11-bea0-0022482ddcd2',  // Imputable a:
        '3d6ed771-f7fc-ee11-96f5-00224830661d',  // Pedido de Ventas (S0)
        '280a350f-19cc-ee11-85f7-00224835853a',  // Tipo de ticket
        'c096b751-9cd1-ee11-85f7-00224835853a',  // Provincia
        '7b1b9b04-9dd1-ee11-85f7-00224835853a',  // Teléfono
        'd8a1b573-6e98-ef11-88cf-6045bda871c2',  // Cumplimiento de agenda
    ];

    /**
     * Inicializa el servicio leyendo las credenciales MSP desde la configuración.
     *
     * @throws \Exception si USERNAME o PASSWORD no están definidos en el .env
     */
    public function __construct()
    {
        $username = config('services.msp.username');
        $password = config('services.msp.password');
        $baseUrl  = config('services.msp.base_url');

        if (!$username || !$password) {
            throw new \Exception('No hay credenciales MSP configuradas en el .env');
        }

        $this->baseUrl    = rtrim($baseUrl ?? '', '/');
        $this->authHeader = 'Basic ' . base64_encode($username . ':' . $password);
    }

    // -------------------------------------------------------------------------
    // Métodos públicos usados por el controlador SSE
    // -------------------------------------------------------------------------

    /**
     * EP1: obtiene tickets de la vista /ticketsview filtrados por rango de fechas.
     *
     * Expuesto públicamente para que el controlador SSE pueda llamarlo por separado
     * y emitir un evento al cliente antes de pasar al siguiente paso.
     *
     * @param  string $fechaInicio Fecha de inicio en cualquier formato parseable por Carbon
     * @param  string $fechaFin    Fecha de fin en cualquier formato parseable por Carbon
     * @return array               Lista de tickets con los campos de ticketsview seleccionados
     * @throws \RuntimeException   si la API MSP retorna error HTTP
     */
    public function fetchTicketsPublic(string $fechaInicio, string $fechaFin): array
    {
        $filter = $this->buildDateFilter($fechaInicio, $fechaFin);
        return $this->getTicketsFiltered($filter);
    }

    /**
     * EP2 + EP3: obtiene time entries y custom fields en paralelo por chunks de 25 tickets.
     *
     * Acepta un callback opcional que se invoca tras procesar cada chunk para
     * reportar el progreso al controlador SSE (p.ej. emitir evento "progress").
     *
     * @param  array         $tickets      Lista de tickets devuelta por fetchTicketsPublic()
     * @param  callable|null $onChunkDone  Función fn(int $done, int $total) llamada después de cada lote
     * @return array                       Mapa [TicketId => ['timeEntry' => ..., 'customFields' => ...]]
     * @throws \RuntimeException           si una petición del pool falla de forma no recuperable
     */
    public function fetchExtraDataPublic(array $tickets, ?callable $onChunkDone = null): array
    {
        return $this->fetchEP2andEP3InParallel($tickets, $onChunkDone);
    }

    /**
     * Combina la lista de tickets con sus datos extra (time entries + custom fields).
     *
     * Expuesto públicamente para que el controlador SSE lo llame como tercer paso
     * separado y pueda emitir el evento final antes de retornar la respuesta.
     *
     * @param  array $tickets   Lista original de tickets (fetchTicketsPublic)
     * @param  array $extraData Mapa de datos extra (fetchExtraDataPublic)
     * @return array            Lista final de tickets enriquecidos y normalizados
     */
    public function combinePublic(array $tickets, array $extraData): array
    {
        return $this->combineResults($tickets, $extraData);
    }

    // -------------------------------------------------------------------------
    // Método principal (uso directo sin SSE, ej: export)
    // -------------------------------------------------------------------------

    /**
     * Orquesta los tres pasos (EP1 → EP2+EP3 → combinación) en una sola llamada.
     *
     * Usado en flujos síncronos donde no se necesita SSE, como la exportación a Excel.
     * Para streaming SSE usar fetchTicketsPublic / fetchExtraDataPublic / combinePublic.
     *
     * @param  string $fechaInicio Fecha de inicio del período
     * @param  string $fechaFin    Fecha de fin del período
     * @return array               Lista de tickets enriquecidos listos para exportar o mostrar
     * @throws \RuntimeException   si cualquiera de los pasos HTTP falla
     */
    public function getTickets(string $fechaInicio, string $fechaFin): array
    {
        $tickets   = $this->fetchTicketsPublic($fechaInicio, $fechaFin);
        $extraData = $this->fetchExtraDataPublic($tickets);
        return $this->combinePublic($tickets, $extraData);
    }

    // -------------------------------------------------------------------------
    // EP1: ticketsview
    // -------------------------------------------------------------------------

    /**
     * Construye el filtro OData de CompletedDate para el rango dado.
     *
     * Convierte las fechas a UTC (inicio del día / fin del día) porque la API MSP
     * almacena CompletedDate en UTC y rechaza filtros sin el sufijo Z.
     *
     * @param  string $fechaInicio Fecha de inicio (cualquier formato Carbon-parseable)
     * @param  string $fechaFin    Fecha de fin (cualquier formato Carbon-parseable)
     * @return string              Fragmento de filtro OData listo para url-encode
     */
    protected function buildDateFilter(string $fechaInicio, string $fechaFin): string
    {
        $inicio = \Carbon\Carbon::parse($fechaInicio)->startOfDay()->utc()->format('Y-m-d\TH:i:s\Z');
        $fin    = \Carbon\Carbon::parse($fechaFin)->endOfDay()->utc()->format('Y-m-d\TH:i:s\Z');

        return "CompletedDate ge {$inicio} and CompletedDate lt {$fin}";
    }

    /**
     * Ejecuta la consulta a /ticketsview con el filtro OData indicado.
     *
     * Limita a 5 000 registros con $top para evitar respuestas excesivamente grandes.
     * Los campos seleccionados son los mínimos necesarios — EP2/EP3 aportan el resto.
     *
     * @param  string $filter Fragmento de filtro OData (ya construido por buildDateFilter)
     * @return array          Lista de tickets con campos base de ticketsview
     * @throws \RuntimeException si la API responde con error HTTP
     */
    protected function getTicketsFiltered(string $filter): array
    {
        $select = implode(',', [
            'TicketId', 'TicketNumber', 'TicketTitle',
            'TicketIssueTypeName', 'TicketSubIssueTypeName',
            'CustomerName', 'LocationName',
            'CreatedDate', 'CompletedDate', 'DueDate',
        ]);

        $endpoint = '/ticketsview'
            . '?$filter='  . rawurlencode($filter)
            . '&$orderby=TicketNumber desc'
            . '&$select='  . rawurlencode($select)
            . '&$top=5000';

        return $this->get($endpoint);
    }

    // -------------------------------------------------------------------------
    // EP2 + EP3: pool en chunks con callback de progreso
    // -------------------------------------------------------------------------

    /**
     * Obtiene time entries (EP2) y custom fields (EP3) en paralelo para todos los tickets.
     *
     * Estrategia de caché:
     *   - EP3 (custom fields) se cachea 48 h por ticket (tickets cerrados no cambian).
     *   - EP2 (time entries) siempre se consulta porque puede modificarse.
     *
     * Los tickets se procesan en chunks de CHUNK_SIZE (25) para no saturar la API MSP
     * con cientos de peticiones simultáneas. Tras cada chunk se llama $onChunkDone
     * si se proporcionó, para reportar progreso al SSE.
     *
     * @param  array         $tickets      Lista de tickets de EP1
     * @param  callable|null $onChunkDone  fn(int $done, int $total) — reporta progreso al SSE
     * @return array                       Mapa [TicketId => ['timeEntry' => array|null, 'customFields' => array]]
     */
    protected function fetchEP2andEP3InParallel(array $tickets, ?callable $onChunkDone = null): array
    {
        $fieldFilter = implode(' or ', array_map(
            fn($id) => "ticketTypeFieldId eq {$id}",
            self::CUSTOM_FIELD_IDS
        ));

        $extraData  = [];
        $totalDone  = 0;
        $totalCount = count($tickets);

        // Pre-cargar EP3 desde cache — EP2 siempre se consulta (puede cambiar)
        $cfCache = [];
        foreach ($tickets as $ticket) {
            $ticketId = $ticket['TicketId'];
            $cached   = Cache::get("msp_cf_{$ticketId}");
            if ($cached !== null) {
                $cfCache[$ticketId] = $cached;
            }
        }

        $cacheHits = count($cfCache);
        $chunks    = array_chunk($tickets, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {

            $responses = Http::pool(function ($pool) use ($chunk, $fieldFilter, $cfCache) {
                $requests = [];

                foreach ($chunk as $ticket) {
                    $ticketId = $ticket['TicketId'];

                    // EP2: time entry — siempre se consulta (puede cambiar)
                    $urlEP2 = $this->baseUrl
                        . '/tickettimeentriesview'
                        . '?$filter='  . rawurlencode("TicketId eq {$ticketId}")
                        . '&$orderby=TicketNumber desc'
                        . '&$select='  . rawurlencode('TicketId,WorkType,CustomWorkType')
                        . '&$top=1';

                    $requests[] = $pool
                        ->as("te_{$ticketId}")
                        ->withHeaders(['Authorization' => $this->authHeader])
                        ->timeout(15)
                        ->get($urlEP2);

                    // EP3: custom fields — solo si no está en cache
                    if (!isset($cfCache[$ticketId])) {
                        $urlEP3 = $this->baseUrl
                            . '/tickets/' . $ticketId . '/customfields'
                            . '?$select=' . rawurlencode('ticketId,name,value')
                            . '&$filter=' . rawurlencode($fieldFilter);

                        $requests[] = $pool
                            ->as("cf_{$ticketId}")
                            ->withHeaders(['Authorization' => $this->authHeader])
                            ->timeout(15)
                            ->get($urlEP3);
                    }
                }

                return $requests;
            });

            foreach ($chunk as $ticket) {
                $ticketId = $ticket['TicketId'];

                // EP2
                $timeEntry = null;
                try {
                    $teResp = $responses["te_{$ticketId}"] ?? null;
                    if ($teResp && method_exists($teResp, 'failed') && !$teResp->failed()) {
                        $entries   = $teResp->json('value') ?? [];
                        $timeEntry = !empty($entries) ? $entries[0] : null;
                    }
                } catch (\Throwable $e) {
                    Log::warning("MSP EP2 parse error [{$ticketId}]: " . $e->getMessage());
                }

                // EP3 — desde cache o desde respuesta del pool
                if (isset($cfCache[$ticketId])) {
                    $customFields = $cfCache[$ticketId];
                } else {
                    $customFields = [];
                    try {
                        $cfResp = $responses["cf_{$ticketId}"] ?? null;
                        if ($cfResp && method_exists($cfResp, 'failed') && !$cfResp->failed()) {
                            $raw = $cfResp->json() ?? [];
                            if (isset($raw['value'])) $raw = $raw['value'];
                            foreach ($raw as $field) {
                                $name  = trim($field['name']  ?? $field['Name']  ?? '');
                                $value = $field['value'] ?? $field['Value'] ?? '';
                                if ($name !== '') $customFields[$name] = $value ?? '';
                            }
                            Cache::put("msp_cf_{$ticketId}", $customFields, self::CF_CACHE_TTL);
                        }
                    } catch (\Throwable $e) {
                        Log::warning("MSP EP3 parse error [{$ticketId}]: " . $e->getMessage());
                    }
                }

                $extraData[$ticketId] = [
                    'timeEntry'    => $timeEntry,
                    'customFields' => $customFields,
                ];
            }

            $totalDone += count($chunk);
            if ($onChunkDone) {
                $onChunkDone($totalDone, $totalCount);
            }
        }

        return $extraData;
    }

    // -------------------------------------------------------------------------
    // Combinar resultados
    // -------------------------------------------------------------------------

    /**
     * Une cada ticket de EP1 con sus time entries (EP2) y custom fields (EP3).
     *
     * Si un ticket no tiene time entry se rellena con emptyTimeEntry() para
     * garantizar que siempre existan las claves WorkType y CustomWorkType.
     *
     * @param  array $tickets   Lista original de ticketsview
     * @param  array $extraData Mapa devuelto por fetchEP2andEP3InParallel()
     * @return array            Lista de tickets con todos los campos combinados en un array plano
     */
    protected function combineResults(array $tickets, array $extraData): array
    {
        $result = [];

        foreach ($tickets as $ticket) {
            $ticketId  = $ticket['TicketId'] ?? '';
            $base      = $this->transformTicket($ticket);
            $extra     = $extraData[$ticketId] ?? [];
            $timeEntry = $extra['timeEntry']    ?? null;

            $base = array_merge($base, $timeEntry
                ? $this->transformTimeEntry($timeEntry)
                : $this->emptyTimeEntry()
            );

            $base = array_merge($base, $extra['customFields'] ?? []);
            $result[] = $base;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Transformadores
    // -------------------------------------------------------------------------

    /**
     * Normaliza un registro raw de ticketsview al esquema interno de la aplicación.
     *
     * Las fechas se convierten de UTC a hora de Panamá (UTC-5) mediante formatDate().
     *
     * @param  array $ticket Ticket raw del endpoint /ticketsview
     * @return array         Ticket con claves normalizadas y fechas en UTC-5
     */
    protected function transformTicket(array $ticket): array
    {
        return [
            'TicketId'               => $ticket['TicketId']               ?? '',
            'TicketNumber'           => $ticket['TicketNumber']            ?? '',
            'TicketTitle'            => $ticket['TicketTitle']             ?? '',
            'TicketIssueTypeName'    => $ticket['TicketIssueTypeName']     ?? '',
            'TicketSubIssueTypeName' => $ticket['TicketSubIssueTypeName']  ?? '',
            'CustomerName'           => $ticket['CustomerName']            ?? '',
            'LocationName'           => $ticket['LocationName']            ?? '',
            'CreatedDate'            => $this->formatDate($ticket['CreatedDate']   ?? ''),
            'CompletedDate'          => $this->formatDate($ticket['CompletedDate'] ?? ''),
            'DueDate'                => $this->formatDate($ticket['DueDate']       ?? ''),
        ];
    }

    /**
     * Extrae los campos de trabajo de un registro de time entry.
     *
     * @param  array $entry Primer registro de /tickettimeentriesview para el ticket
     * @return array        Array con claves WorkType y CustomWorkType
     */
    protected function transformTimeEntry(array $entry): array
    {
        return [
            'WorkType'       => $entry['WorkType']       ?? '',
            'CustomWorkType' => $entry['CustomWorkType'] ?? '',
        ];
    }

    /**
     * Retorna un time entry vacío para tickets sin entradas de tiempo.
     *
     * Garantiza que combineResults() produzca siempre las mismas claves
     * independientemente de si el ticket tiene o no time entries.
     *
     * @return array Array con claves WorkType y CustomWorkType en vacío
     */
    protected function emptyTimeEntry(): array
    {
        return ['WorkType' => '', 'CustomWorkType' => ''];
    }

    // -------------------------------------------------------------------------
    // Customers
    // -------------------------------------------------------------------------

    /**
     * Obtiene todos los clientes de la API MSP con paginación automática (OData nextLink).
     *
     * El resultado se cachea 30 días porque la lista de clientes raramente cambia.
     * updateCustomer() invalida esta caché tras cada modificación para mantener consistencia.
     *
     * @return array Lista completa de clientes con CustomerId, CustomerName, ReferenceId, RmReferenceId
     * @throws \RuntimeException si la API MSP retorna error HTTP en cualquier página
     */
    public function fetchCustomers(): array
    {
        return Cache::remember('msp:customers:sync', 2592000, function () {
            $all      = [];
            $endpoint = '/customers?$top=1000&$select=CustomerId,CustomerName,ReferenceId,RmReferenceId';

            while ($endpoint) {
                $response = Http::withHeaders([
                    'Authorization' => $this->authHeader,
                ])->timeout(60)->get($this->baseUrl . $endpoint);

                if ($response->failed()) {
                    throw new \RuntimeException(
                        "Error MSP API [{$response->status()}] en /customers: " . $response->body()
                    );
                }

                $body     = $response->json();
                $all      = array_merge($all, $body['value'] ?? []);
                $next     = $body['@odata.nextLink'] ?? null;
                $endpoint = $next ? str_replace($this->baseUrl, '', $next) : null;
            }

            return $all;
        });
    }

    // -------------------------------------------------------------------------
    // Tickets por cliente (Fase 2a)
    // -------------------------------------------------------------------------

    /**
     * Obtiene tickets y usuarios de tickets para un cliente específico en paralelo.
     *
     * Fase 2a del flujo de búsqueda unificada. Las dos llamadas al pool son:
     *   - /ticketsview       → detalle de cada ticket del cliente
     *   - /ticketusersview   → técnicos/contactos asociados a cada ticket
     *
     * @param  string $customerId UUID del cliente en MSP
     * @return array              Mapa con claves 'data' (tickets + ticket_users) y 'ticketIds' (UUIDs únicos)
     */
    public function fetchTicketsByCustomer(string $customerId): array
    {
        $ticketsSelect = implode(',', [
            'TicketNumber', 'TicketTitle', 'TicketDescription',
            'TicketPriorityName', 'TicketStatusName', 'TicketCustomStatusName',
            'ServiceItemName', 'ServiceItemTypeName',
            'TicketIssueTypeName', 'TicketSubIssueTypeName',
            'CustomerName',
            'CreatedByEmailAddress', 'CreatedByFirstName', 'CreatedByLastName',
            'UpdatedDate',
            'UpdatedByEmailAddress', 'UpdatedByFirstName', 'UpdatedByLastName',
            'CompletedDate', 'IsBillable', 'IsTaxable',
        ]);

        $usersSelect = implode(',', [
            'UserEmailAddress', 'UserFirstName', 'UserLastName',
            'TicketPriorityName', 'TicketStatusName', 'TicketCustomStatusName',
            'TicketNumber', 'TicketId',
        ]);

        $filter = rawurlencode("CustomerId eq {$customerId}");

        $responses = Http::pool(fn ($pool) => [
            $pool->as('tickets')
                ->withHeaders(['Authorization' => $this->authHeader])
                ->timeout(30)
                ->get($this->baseUrl . '/ticketsview?$filter=' . $filter
                    . '&$orderby=TicketNumber desc'
                    . '&$select=' . rawurlencode($ticketsSelect)),

            $pool->as('ticket_users')
                ->withHeaders(['Authorization' => $this->authHeader])
                ->timeout(30)
                ->get($this->baseUrl . '/ticketusersview?$filter=' . $filter
                    . '&$orderby=TicketNumber desc'
                    . '&$select=' . rawurlencode($usersSelect)),
        ]);

        $tickets     = !$responses['tickets']->failed()      ? ($responses['tickets']->json('value')      ?? []) : [];
        $ticketUsers = !$responses['ticket_users']->failed() ? ($responses['ticket_users']->json('value') ?? []) : [];

        $ticketIds = collect($ticketUsers)
            ->pluck('TicketId')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return [
            'data'      => compact('tickets', 'ticket_users'),
            'ticketIds' => $ticketIds,
        ];
    }

    // -------------------------------------------------------------------------
    // Detalle de tickets — responses + SLAs (Fase 2b)
    // -------------------------------------------------------------------------

    /**
     * Obtiene las respuestas y datos SLA de múltiples tickets en paralelo.
     *
     * Fase 2b del flujo de búsqueda unificada. Por cada ticketId se lanzan dos
     * peticiones al pool: una a /ticketsresponses y otra a /ticketslas.
     * Los IDs se sanean con preg_replace para evitar inyección OData.
     *
     * @param  array $ticketIds Lista de UUIDs de tickets (puede estar vacía)
     * @return array            Mapa ['responses' => [ticketId => [...]], 'slas' => [ticketId => {...}|null]]
     */
    public function fetchTicketDetails(array $ticketIds): array
    {
        if (empty($ticketIds)) {
            return ['responses' => [], 'slas' => []];
        }

        $responses = Http::pool(function ($pool) use ($ticketIds) {
            $requests = [];

            foreach ($ticketIds as $id) {
                $safeId = preg_replace('/[^a-zA-Z0-9\-]/', '', $id);

                $requests[] = $pool
                    ->as("responses_{$safeId}")
                    ->withHeaders(['Authorization' => $this->authHeader])
                    ->timeout(15)
                    ->get($this->baseUrl . '/ticketsresponses'
                        . '?$filter=' . rawurlencode("TicketId eq {$safeId}")
                        . '&$select=' . rawurlencode('Description,FromAddress,FromDisplay,CreatedDate,UpdatedDate'));

                $requests[] = $pool
                    ->as("slas_{$safeId}")
                    ->withHeaders(['Authorization' => $this->authHeader])
                    ->timeout(15)
                    ->get($this->baseUrl . '/ticketslas'
                        . '?$filter=' . rawurlencode("TicketId eq {$safeId}")
                        . '&$select=' . rawurlencode(implode(',', [
                            'CurrentPercentage', 'MarkedAssignedIn', 'MarkedInProgressIn',
                            'MarkedCompletedIn', 'AssignedThreshold', 'InProgressThreshold',
                            'CompletedThreshold', 'CurrentHours',
                        ])));
            }

            return $requests;
        });

        $result = ['responses' => [], 'slas' => []];

        foreach ($ticketIds as $id) {
            $safeId = preg_replace('/[^a-zA-Z0-9\-]/', '', $id);

            $respKey = "responses_{$safeId}";
            $slaKey  = "slas_{$safeId}";

            $result['responses'][$safeId] = isset($responses[$respKey]) && !$responses[$respKey]->failed()
                ? ($responses[$respKey]->json('value') ?? [])
                : [];

            $slaValues = isset($responses[$slaKey]) && !$responses[$slaKey]->failed()
                ? ($responses[$slaKey]->json('value') ?? [])
                : [];

            $result['slas'][$safeId] = $slaValues[0] ?? null;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Búsqueda unificada por RUC (3 fases)
    // -------------------------------------------------------------------------

    /**
     * Búsqueda completa de un cliente por RUC con sus tickets, respuestas y SLAs.
     *
     * Orquesta las 3 fases en secuencia:
     *   - Fase 1: findCustomerByRuc()         → localizar el cliente
     *   - Fase 2a: fetchTicketsByCustomer()   → tickets y usuarios
     *   - Fase 2b: fetchTicketDetails()       → respuestas y SLAs
     *
     * Retorna el mismo esquema de mapa en todos los casos (incluso cuando no hay datos)
     * para facilitar el manejo uniforme en el controlador.
     *
     * @param  string $ruc RUC o fragmento de RUC a buscar
     * @return array       Mapa con claves: customer, message, tickets, ticket_users, responses, slas
     */
    public function unifiedSearch(string $ruc): array
    {
        $customers = $this->findCustomerByRuc($ruc);

        if (empty($customers)) {
            return [
                'customer'     => null,
                'message'      => 'Cliente no encontrado',
                'tickets'      => [],
                'ticket_users' => [],
                'responses'    => [],
                'slas'         => [],
            ];
        }

        $customer   = $customers[0];
        $customerId = $customer['CustomerId'] ?? null;

        if (!$customerId) {
            return [
                'customer'     => $customer,
                'message'      => 'CustomerId no disponible',
                'tickets'      => [],
                'ticket_users' => [],
                'responses'    => [],
                'slas'         => [],
            ];
        }

        $phase2a   = $this->fetchTicketsByCustomer($customerId);
        $phase2b   = $this->fetchTicketDetails($phase2a['ticketIds']);

        return [
            'customer'     => $customer,
            'message'      => 'OK',
            'tickets'      => $phase2a['data']['tickets']      ?? [],
            'ticket_users' => $phase2a['data']['ticket_users'] ?? [],
            'responses'    => $phase2b['responses'],
            'slas'         => $phase2b['slas'],
        ];
    }

    /**
     * Busca clientes en la API MSP filtrando por RUC (ReferenceId).
     *
     * Usa contains() de OData para búsqueda parcial — un RUC parcial puede
     * retornar múltiples clientes. Usar RUC completo para resultado exacto.
     *
     * @param  string $ruc  RUC o fragmento del RUC a buscar
     * @return array        Lista de clientes con CustomerName, CustomerId, ReferenceId, PhoneMain, EmailDomain
     * @throws \RuntimeException si la API MSP retorna error HTTP
     */
    public function findCustomerByRuc(string $ruc): array
    {
        $filter   = "contains(ReferenceId,'{$ruc}')";
        $select   = 'CustomerName,PhoneMain,EmailDomain,ReferenceId,CustomerId';
        $endpoint = '/customers?$filter=' . rawurlencode($filter) . '&$select=' . rawurlencode($select);

        return $this->get($endpoint);
    }

    /**
     * Busca un cliente específico por su CustomerId (UUID).
     *
     * @param  string $customerId UUID del cliente en MSP
     * @return array              Lista con el cliente encontrado (vacía si no existe)
     * @throws \RuntimeException  si la API MSP retorna error HTTP
     */
    public function findCustomerById(string $customerId): array
    {
        $filter   = "CustomerId eq '{$customerId}'";
        $select   = 'CustomerName,PhoneMain,EmailDomain,ReferenceId,CustomerId';
        $endpoint = '/customers?$filter=' . rawurlencode($filter) . '&$select=' . rawurlencode($select);

        return $this->get($endpoint);
    }

    /**
     * Actualiza el nombre y el RUC de un cliente en la API MSP.
     *
     * La API exige PUT completo — ambos campos (CustomerName y ReferenceId) son
     * obligatorios aunque solo se modifique uno. Tras la actualización se invalida
     * la caché msp:customers:sync para que la siguiente consulta refleje el cambio.
     *
     * @param  string $customerId   UUID del cliente a actualizar
     * @param  string $customerName Nombre del cliente (campo obligatorio del PUT)
     * @param  string $referenceId  RUC actualizado
     * @return void
     * @throws \RuntimeException    si la API MSP retorna error HTTP
     */
    public function updateCustomer(string $customerId, string $customerName, string $referenceId): void
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader,
            'Accept'        => 'application/json;odata.metadata=minimal;odata.streaming=true',
            'Content-Type'  => 'application/json;odata.metadata=minimal;odata.streaming=true',
        ])->timeout(30)->put($this->baseUrl . '/customers/' . $customerId, [
            'CustomerName' => $customerName,  // PUT requiere todos los campos obligatorios
            'referenceId'  => $referenceId,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Error MSP update [{$response->status()}] cliente {$customerId}: " . $response->body()
            );
        }

        // Invalidar caché para que el siguiente buildFilas() muestre el estado real
        Cache::forget('msp:customers:sync');
    }

    // -------------------------------------------------------------------------
    // HTTP Helper
    // -------------------------------------------------------------------------

    /**
     * Ejecuta una petición GET autenticada a la API MSP y retorna el array 'value'.
     *
     * La API MSP envuelve sus colecciones en {"value": [...]} según el estándar OData.
     * Este helper extrae automáticamente el array value para simplificar los llamadores.
     *
     * @param  string $endpoint Ruta relativa al baseUrl, incluyendo query string (p.ej. '/ticketsview?$filter=...')
     * @param  int    $timeout  Tiempo límite en segundos (default 60)
     * @return array            Array de registros del campo 'value' de la respuesta OData
     * @throws \RuntimeException si la API MSP retorna estado HTTP de error
     */
    protected function get(string $endpoint, int $timeout = 60): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader,
        ])->timeout($timeout)->get($this->baseUrl . $endpoint);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Error MSP API [{$response->status()}] en {$endpoint}: " . $response->body()
            );
        }

        return $response->json('value') ?? [];
    }

    /**
     * Convierte una fecha UTC de la API MSP a hora de Panamá (UTC-5).
     *
     * La API MSP devuelve todas las fechas en UTC. Panamá no observa horario de
     * verano, por lo que el ajuste es siempre -5 horas fijo. Retorna la cadena
     * original sin modificar si Carbon no puede parsearla.
     *
     * @param  string $date Fecha en formato ISO 8601 UTC (p.ej. "2024-03-15T14:30:00Z")
     * @return string       Fecha formateada como "Y-m-d H:i:s" en UTC-5, o cadena original si inválida
     */
    protected function formatDate(string $date): string
    {
        if (!$date) return '';
        try {
            return \Carbon\Carbon::parse($date)->subHours(5)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $date;
        }
    }
}