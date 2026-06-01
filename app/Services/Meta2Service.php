<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Servicio de gestión de tickets de Telefonía (módulo Meta 2).
 *
 * Orquesta un pipeline de 3 pasos para obtener y enriquecer tickets de Telefonía
 * desde la misma API MSP que usa MspService, pero con foco en un tipo específico:
 *   - Paso 1: Obtener IDs de tickets de Telefonía del mes/año → caché 24 h
 *   - Paso 2: Obtener el detalle de esos tickets por ID → caché 24 h
 *   - Paso 3: Obtener custom fields en paralelo via Http::pool → caché 48 h por ticket
 *
 * Los custom fields de tipo "código" (Causa, Ubicación, Reporte, etc.) se truncan
 * al primer token (antes del tab o del primer espacio) porque la API devuelve valores
 * en formato "CÓDIGO\tDescripción completa" y la vista solo muestra el código.
 *
 * También genera el informe PDF mensual con estadísticas de reparación por provincia
 * (% de tickets resueltos en ≤ 48 horas hábiles usando PanamaHolidays).
 *
 * Dependencias externas:
 *   - API MSP       : mismas credenciales que MspService (SERVICES_MSP_*)
 *   - Http::pool    : paralelización de EP3 en chunks de 10
 *   - PanamaHolidays: cálculo de horas laborables excluyendo feriados de Panamá
 */
class Meta2Service
{
    protected string $baseUrl;
    protected string $authHeader;

    /**
     * IDs de los campos que necesitamos del PDF
     */
    protected array $requiredFieldIds = [
        'cb7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Causa
        'cc7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Ubicación
        'ce7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Detalle - Reporte 2
        'cf7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Reporte 1
        'c096b751-9cd1-ee11-85f7-00224835853a',  // Provincia
        '7b1b9b04-9dd1-ee11-85f7-00224835853a',  // Telefonia
        'cd7251d4-ad9a-ee11-bea0-0022482ddcd2',  // Solucion - Acción
    ];

    /**
     * Campos donde solo queremos extraer el código (primera palabra)
     */
    protected array $codeFields = [
        'Reporte 1',
        'Detalle - Reporte 2',
        'Causa',
        'Ubicación',
        'Solución - Acción',
    ];

    /**
     * Inicializa el servicio con las credenciales MSP compartidas con MspService.
     *
     * @throws \Exception si USERNAME o PASSWORD no están definidos en el .env
     */
    public function __construct()
    {
        $username = config('services.msp.username');
        $password = config('services.msp.password');
        $baseUrl  = config('services.msp.base_url');

        if (!$username || !$password) {
            throw new \Exception('No hay credenciales MSP configuradas.');
        }

        $this->baseUrl    = (string) $baseUrl;
        $this->authHeader = 'Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * Ejecuta una petición GET autenticada y retorna el array 'value' de la respuesta OData.
     *
     * Retorna [] en caso de error HTTP para no interrumpir el pipeline de 3 pasos.
     *
     * @param  string $endpoint Ruta relativa + query string (ya codificada)
     * @param  int    $timeout  Tiempo límite en segundos (default 60)
     * @return array            Array de registros del campo 'value', o [] si hay error
     */
    protected function get(string $endpoint, int $timeout = 60): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader,
        ])->timeout($timeout)->get($this->baseUrl . $endpoint);

        if ($response->failed()) return [];

        return $response->json('value') ?? [];
    }

    private const CACHE_IDS     = 86400;  // 24 horas — IDs de un mes cerrado no cambian
    private const CACHE_TICKETS = 86400;  // 24 horas — detalle de tickets
    private const CACHE_CF      = 172800; // 48 horas — custom fields de tickets cerrados
    private const CACHE_PDF     = 172800; // 48 horas — datos del PDF (histórico, no cambia)
    private const CACHE_ALL     = 86400;  // 24 horas — listado completo de Telefonía

    /**
     * Paso 1 — Obtiene únicamente los IDs de tickets de Telefonía completados en el mes/año dado.
     *
     * Se consultan solo los TicketId (campo mínimo) para minimizar el payload de la respuesta.
     * Los IDs se cachean 24 h — los tickets de un mes cerrado no cambian.
     *
     * @param  int $month Número del mes (1–12)
     * @param  int $year  Año con 4 dígitos
     * @return array      Array plano de UUIDs de ticket (TicketId)
     */
    public function getTelefoniaIds(int $month, int $year): array
    {
        return Cache::remember("meta2:ids:{$year}:{$month}", self::CACHE_IDS, function () use ($month, $year) {
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)
                ->startOfDay()->format('Y-m-d\TH:i:s\Z');

            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)
                ->endOfMonth()->endOfDay()->format('Y-m-d\TH:i:s\Z');

            $filter = "TicketIssueTypeName eq 'Telefonía'" .
                      " and CompletedDate ge {$startDate}" .
                      " and CompletedDate le {$endDate}";

            $tickets = $this->get("/ticketsview?\$filter={$filter}&\$select=TicketId");

            return array_column($tickets, 'TicketId');
        });
    }

    /**
     * Paso 2 — Obtiene el detalle de los tickets dados sus IDs.
     *
     * Usa el operador OData "in" para obtener todos los tickets en una sola llamada.
     * La clave de caché es el md5 de los IDs para soportar distintos subconjuntos.
     *
     * @param  array $ids Lista de UUIDs de tickets a consultar
     * @return array      Lista de tickets con TicketId, TicketNumber, TicketIssueTypeName, CreatedDate, CompletedDate
     */
    protected function getTicketsByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $cacheKey = 'meta2:tickets:' . md5(implode(',', $ids));

        return Cache::remember($cacheKey, self::CACHE_TICKETS, function () use ($ids) {
            $idList = implode(',', $ids);

            return $this->get(
                "/ticketsview?\$filter=TicketId in ({$idList})" .
                "&\$orderby=TicketNumber desc" .
                "&\$select=TicketId,TicketNumber,TicketIssueTypeName,CreatedDate,CompletedDate"
            );
        });
    }

    /**
     * Determina si un campo custom debe mostrarse solo con su código (primer token).
     *
     * Los valores de estos campos en la API MSP tienen formato "CÓDIGO\tDescripción"
     * o "CÓDIGO descripción". La vista solo necesita el código para los campos
     * de clasificación técnica. El fallback con str_starts_with cubre variaciones
     * con/sin tilde en los nombres del campo.
     *
     * @param  string $name Nombre del campo custom field
     * @return bool         true si el campo debe mostrarse solo con el código
     */
    protected function isCodeField(string $name): bool
    {
        if (in_array($name, $this->codeFields)) {
            return true;
        }

        // Fallback: captura variaciones de tildes/guiones
        $lower = mb_strtolower($name);
        return str_starts_with($lower, 'soluc')
            || str_starts_with($lower, 'causa')
            || str_starts_with($lower, 'ubicac')
            || str_starts_with($lower, 'reporte')
            || str_starts_with($lower, 'detalle');
    }

    /**
     * Paso 3 — Obtiene los custom fields de múltiples tickets en paralelo.
     *
     * Estrategia de optimización:
     *   - Pre-carga desde caché los tickets ya conocidos (caché 48 h).
     *   - Los no cacheados se consultan en chunks de 10 via Http::pool.
     *   - Entre chunks (si hay más de uno) espera 200 ms para no saturar la API.
     *   - Los campos "código" se truncan con extractCode() antes de cachear.
     *
     * @param  array $ticketIds Lista de UUIDs de tickets a enriquecer
     * @return array            Mapa [ticketId => ['ticketId' => ..., 'Causa' => ..., 'Provincia' => ..., ...]]
     */
    protected function getCustomFieldsPool(array $ticketIds): array
    {
        if (empty($ticketIds)) return [];

        // Pre-cargar los que ya están en caché
        $result    = [];
        $uncached  = [];

        foreach ($ticketIds as $id) {
            $cached = Cache::get("meta2:cf:{$id}");
            if ($cached !== null) {
                $result[$id] = $cached;
            } else {
                $uncached[] = $id;
            }
        }

        if (empty($uncached)) return $result;

        $authHeader  = $this->authHeader;
        $baseUrl     = $this->baseUrl;
        $fieldFilter = $this->buildFieldFilter();
        $chunks      = array_chunk($uncached, 10);

        foreach ($chunks as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk, $baseUrl, $authHeader, $fieldFilter) {
                return array_map(
                    fn($id) => $pool
                        ->withHeaders(['Authorization' => $authHeader])
                        ->timeout(30)
                        ->get("{$baseUrl}/tickets/{$id}/customfields", [
                            '$select' => 'ticketId,name,value,ticketTypeFieldId',
                            '$filter' => $fieldFilter,
                        ]),
                    $chunk
                );
            });

            foreach ($chunk as $index => $ticketId) {
                $response  = $responses[$index];
                $rawFields = [];

                if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                    $rawFields = $response->json() ?? [];
                }

                $flat = ['ticketId' => $ticketId];
                foreach ($rawFields as $field) {
                    $name  = trim($field['Name'] ?? $field['name'] ?? '');
                    $value = $field['Value'] ?? $field['value'] ?? '';

                    if ($name) {
                        $flat[$name] = $this->isCodeField($name)
                            ? $this->extractCode((string)$value)
                            : ($value ?? '');
                    }
                }

                Cache::put("meta2:cf:{$ticketId}", $flat, self::CACHE_CF);
                $result[$ticketId] = $flat;
            }

            if (count($chunks) > 1) {
                usleep(200000);
            }
        }

        return $result;
    }

    /**
     * Método principal — orquesta los 3 pasos para la vista de listado de Telefonía.
     *
     * Con búsqueda activa el TTL se reduce a 1 h (datos filtrados no se reutilizan tanto).
     * Sin búsqueda el resultado completo se cachea 24 h.
     *
     * @param  string|null $search Término de búsqueda por número o tipo de ticket (null = sin filtro)
     * @param  int|null    $month  Mes (1–12); si es null retorna []
     * @param  int|null    $year   Año; si es null retorna []
     * @return array               Lista de tickets transformados al formato de la vista
     */
    public function getTelefoniaTickets(?string $search = null, ?int $month = null, ?int $year = null): array
    {
        if (!$month || !$year) return [];

        // Con búsqueda activa no cacheamos el resultado filtrado, pero sí los datos base
        $cacheKey = "meta2:view:{$year}:{$month}:" . md5($search ?? '');
        $ttl      = $search ? 3600 : self::CACHE_TICKETS; // 1h con búsqueda activa, 24h sin

        return Cache::remember($cacheKey, $ttl, function () use ($search, $month, $year) {
            $ids = $this->getTelefoniaIds($month, $year);
            if (empty($ids)) return [];

            $tickets = $this->getTicketsByIds($ids);

            if ($search) {
                $s       = strtolower($search);
                $tickets = array_values(array_filter($tickets, fn($t) =>
                    str_contains(strtolower($t['TicketNumber'] ?? ''), $s) ||
                    str_contains(strtolower($t['TicketIssueTypeName'] ?? ''), $s)
                ));
            }

            if (empty($tickets)) return [];

            $ticketIds    = array_column($tickets, 'TicketId');
            $customFields = $this->getCustomFieldsPool($ticketIds);

            foreach ($tickets as &$ticket) {
                $ticket['customFields'] = $customFields[$ticket['TicketId']] ?? ['ticketId' => $ticket['TicketId']];
            }
            unset($ticket);

            return $this->transformTickets($tickets);
        });
    }

    /**
     * Transforma el array raw de tickets al formato estandarizado de la vista.
     *
     * Las fechas se convierten de UTC a hora de Panamá (UTC-5) con formatDate().
     * Los custom_fields se adjuntan como sub-array al ticket transformado.
     *
     * @param  array $tickets Array de tickets enriquecidos con customFields adjuntos
     * @return array          Lista de tickets con claves: ticket_id, ticket_number, issue_type,
     *                        created_date, completed_date, custom_fields
     */
    protected function transformTickets(array $tickets): array
    {
        $result = [];

        foreach ($tickets as $ticket) {
            $result[] = [
                'ticket_id'      => $ticket['TicketId']            ?? '',
                'ticket_number'  => $ticket['TicketNumber']        ?? '',
                'issue_type'     => $ticket['TicketIssueTypeName'] ?? '',
                'created_date'   => $this->formatDate($ticket['CreatedDate']   ?? ''),
                'completed_date' => $this->formatDate($ticket['CompletedDate'] ?? ''),
                'custom_fields'  => $ticket['customFields']        ?? [],
            ];
        }

        return $result;
    }

    /**
     * Convierte una fecha UTC de la API MSP a hora de Panamá (UTC-5).
     *
     * Panamá no observa horario de verano — el ajuste es siempre fijo de -5 h.
     * Retorna '—' si la cadena está vacía, y la cadena original si Carbon no puede parsearla.
     *
     * @param  string $date Fecha ISO 8601 en UTC (p.ej. "2024-03-15T14:30:00Z")
     * @return string       Fecha formateada como "d/m/Y H:i" en UTC-5, o '—' si vacía
     */
    protected function formatDate(string $date): string
    {
        if (!$date) return '—';
        try {
            return \Carbon\Carbon::parse($date)->subHours(5)->format('d/m/Y H:i');
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Obtiene los datos completos para el PDF del informe mensual de Telefonía.
     *
     * Cachea 48 h (datos históricos de un mes cerrado no cambian).
     * Delega la construcción real a buildPdfReportData().
     *
     * @param  int $month Mes (1–12)
     * @param  int $year  Año
     * @return array      Estructura del informe: ['month' => ..., 'year' => ..., 'summary' => [...]]
     */
    public function getPdfReportData(int $month, int $year): array
    {
        return Cache::remember("meta2:pdf:{$year}:{$month}", self::CACHE_PDF, fn () =>
            $this->buildPdfReportData($month, $year)
        );
    }

    /**
     * Construye el informe mensual de Telefonía agrupado por provincia.
     *
     * Para cada provincia calcula:
     *   - Reparados: tickets completados en el mes con Provincia definida.
     *   - Pendientes: tickets SIN CompletedDate que tengan Provincia asignada.
     *   - Porcentaje en ≤ 48 h hábiles: usando PanamaHolidays::workingHoursBetween().
     *
     * Los tickets sin CompletedDate se obtienen de getAllTelefoniaTickets() (sin filtro
     * de fecha) porque aún están abiertos y no aparecen en getTelefoniaIds().
     *
     * @param  int $month Mes (1–12)
     * @param  int $year  Año
     * @return array      Estructura: ['month' => string, 'year' => int, 'summary' => [
     *                      ['provincia' => ..., 'pendientes' => ..., 'reparados' => ...,
     *                       'porcentaje' => ..., 'tickets' => [...]], ...
     *                    ]]
     */
    protected function buildPdfReportData(int $month, int $year): array
    {
        $completedIds = $this->getTelefoniaIds($month, $year);

        if (empty($completedIds)) {
            return [
                'month'   => \Carbon\Carbon::createFromDate($year, $month, 1)->locale('es')->monthName,
                'year'    => $year,
                'summary' => [],
            ];
        }

        $completedTickets = $this->getTicketsByIdsWithDates($completedIds);
        $ticketIds        = array_column($completedTickets, 'TicketId');
        $customFields     = $this->getCustomFieldsPool($ticketIds);

        foreach ($completedTickets as &$ticket) {
            $ticket['customFields'] = $customFields[$ticket['TicketId']] ?? [];
        }
        unset($ticket);

        // Tickets pendientes sin CompletedDate
        $allTickets    = $this->getAllTelefoniaTickets();
        $pendingByProv = [];

        foreach ($allTickets as $t) {
            if (empty($t['CompletedDate'])) {
                $cf   = $customFields[$t['TicketId']] ?? [];
                $prov = trim($cf['Provincia'] ?? '');

                if ($prov !== '') {
                    $pendingByProv[$prov] = ($pendingByProv[$prov] ?? 0) + 1;
                }
            }
        }

        // Agrupar completados por provincia
        $byProvince = [];

        foreach ($completedTickets as $ticket) {
            $prov = trim($ticket['customFields']['Provincia'] ?? '');

            if ($prov === '') continue;

            $byProvince[$prov][] = $ticket;
        }

        // Calcular resumen
        $summary = [];

        foreach ($byProvince as $prov => $tickets) {
            $total = count($tickets);
            $en48h = 0;

            foreach ($tickets as $ticket) {
                if (!empty($ticket['CreatedDate']) && !empty($ticket['CompletedDate'])) {
                    $created   = \Carbon\Carbon::parse($ticket['CreatedDate'])->subHours(5);
                    $completed = \Carbon\Carbon::parse($ticket['CompletedDate'])->subHours(5);
                    $hours     = \App\Support\PanamaHolidays::workingHoursBetween($created, $completed);
                    if ($hours <= 48) $en48h++;
                }
            }

            $summary[] = [
                'provincia'  => $prov,
                'pendientes' => $pendingByProv[$prov] ?? 0,
                'reparados'  => $total,
                'porcentaje' => $total > 0 ? round(($en48h / $total) * 100) . '%' : '0%',
                'tickets'    => $tickets,
            ];
        }

        return [
            'month'   => \Carbon\Carbon::createFromDate($year, $month, 1)->locale('es')->monthName,
            'year'    => $year,
            'summary' => $summary,
        ];
    }

    /**
     * Retorna todos los tickets de Telefonía sin filtro de fecha (abiertos y cerrados).
     *
     * Se usa en buildPdfReportData() para identificar tickets pendientes (sin CompletedDate)
     * que no aparecerían en getTelefoniaIds() que filtra por mes completado.
     *
     * @return array Lista de tickets con TicketId, TicketNumber, CompletedDate, CreatedDate
     */
    protected function getAllTelefoniaTickets(): array
    {
        return Cache::remember('meta2:all_tickets', self::CACHE_ALL, fn () =>
            $this->get(
                "/ticketsview?\$filter=TicketIssueTypeName eq 'Telefonía'" .
                "&\$select=TicketId,TicketNumber,CompletedDate,CreatedDate"
            )
        );
    }

    /**
     * Obtiene tickets por IDs incluyendo CreatedDate y CompletedDate completas.
     *
     * Variante de getTicketsByIds() sin caché, usada en buildPdfReportData()
     * donde se necesita frescura de datos y las fechas completas para el cálculo
     * de horas laborables.
     *
     * @param  array $ids Lista de UUIDs de tickets
     * @return array      Lista de tickets con TicketId, TicketNumber, TicketIssueTypeName,
     *                    CreatedDate, CompletedDate ordenados por TicketNumber desc
     */
    protected function getTicketsByIdsWithDates(array $ids): array
    {
        if (empty($ids)) return [];

        $idList = implode(',', $ids);

        return $this->get(
            "/ticketsview?\$filter=TicketId in ({$idList})" .
            "&\$orderby=TicketNumber desc" .
            "&\$select=TicketId,TicketNumber,TicketIssueTypeName,CreatedDate,CompletedDate"
        );
    }

    /**
     * Retorna los custom fields raw y aplanados de un ticket para diagnóstico.
     *
     * Útil durante desarrollo para descubrir los nombres exactos de campos
     * que devuelve la API MSP y verificar la lógica de extractCode().
     * No cachea el resultado — siempre consulta la API en tiempo real.
     *
     * @param  string $ticketId UUID del ticket a inspeccionar
     * @return array            Mapa con 'ticketId', 'raw' (campos originales) y 'aplanado' (procesados)
     */
    public function debugCustomFields(string $ticketId): array
    {
        $fieldFilter = $this->buildFieldFilter();

        $fields = $this->getRaw(
            "/tickets/{$ticketId}/customfields" .
            "?\$select=ticketId,name,value,ticketTypeFieldId" .
            "&\$filter=" . urlencode($fieldFilter)
        );

        $result = ['ticketId' => $ticketId, 'raw' => $fields, 'aplanado' => []];

        foreach ($fields as $field) {
            $name  = trim($field['Name'] ?? $field['name'] ?? '');
            $value = $field['Value'] ?? $field['value'] ?? '';

            if ($name) {
                $result['aplanado'][$name] = $this->isCodeField($name)
                    ? $this->extractCode((string)$value)
                    : ($value ?? '');
            }
        }

        return $result;
    }

    /**
     * Ejecuta una petición GET y retorna el JSON completo sin extraer 'value'.
     *
     * Necesario para el endpoint /customfields que en algunas versiones de la API
     * devuelve el array directamente en la raíz (sin wrapper OData).
     *
     * @param  string $endpoint Ruta relativa + query string
     * @param  int    $timeout  Tiempo límite en segundos (default 60)
     * @return array            JSON completo de la respuesta, o [] si hay error HTTP
     */
    protected function getRaw(string $endpoint, int $timeout = 60): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader,
        ])->timeout($timeout)->get($this->baseUrl . $endpoint);

        if ($response->failed()) return [];

        return $response->json() ?? [];
    }

    /**
     * Construye el filtro OData para solicitar solo los custom fields requeridos.
     *
     * Genera una cadena "TicketTypeFieldId eq {id1} or TicketTypeFieldId eq {id2} ..."
     * que se aplica al endpoint /tickets/{id}/customfields para evitar traer todos
     * los campos (pueden ser decenas) cuando solo necesitamos ~7.
     *
     * @return string Fragmento de filtro OData para los IDs en $requiredFieldIds
     */
    protected function buildFieldFilter(): string
    {
        $conditions = array_map(
            fn($id) => "TicketTypeFieldId eq {$id}",
            $this->requiredFieldIds
        );

        return implode(' or ', $conditions);
    }

    /**
     * Extrae el código del valor de un campo clasificatorio.
     *
     * Los valores de la API tienen formato "CÓDIGO\tDescripción" o "CÓDIGO descripción".
     * Solo necesitamos el código para mostrar en la tabla del informe.
     * El tab tiene prioridad sobre el espacio para manejar ambos formatos.
     *
     * @param  string $value Valor raw del campo custom field
     * @return string        Solo el código (primera palabra o token antes del tab)
     */
    protected function extractCode(string $value): string
    {
        if (!$value) return '';

        // Si tiene tabulación, tomar lo que está antes
        if (str_contains($value, "\t")) {
            return trim(explode("\t", $value)[0]);
        }

        // Si no tiene tabulación, tomar primera palabra
        return trim(explode(' ', $value)[0]);
    }

    /**
     * Paso 2 público — retorna tickets por IDs aplicando filtro de búsqueda opcional.
     *
     * Expuesto como público para que el controlador SSE pueda llamarlo como segundo
     * evento separado y reportar progreso al cliente antes de cargar los custom fields.
     *
     * @param  array  $ids    Lista de UUIDs de tickets
     * @param  string $search Término de búsqueda (vacío = sin filtro)
     * @return array          Lista de tickets filtrados por número o tipo
     */
    public function getTicketsByIdsPublic(array $ids, string $search = ''): array
    {
        $tickets = $this->getTicketsByIds($ids);

        if ($search) {
            $s = strtolower($search);
            $tickets = array_values(array_filter($tickets, fn($t) =>
                str_contains(strtolower($t['TicketNumber'] ?? ''), $s) ||
                str_contains(strtolower($t['TicketIssueTypeName'] ?? ''), $s)
            ));
        }

        return $tickets;
    }

    /**
     * Paso 3 público — adjunta custom fields a los tickets y aplica la transformación final.
     *
     * Expuesto como público para que el controlador SSE lo llame como tercer evento
     * y pueda emitir el evento "done" con el dataset completo al finalizar.
     *
     * @param  array $tickets Lista de tickets (output de getTicketsByIdsPublic)
     * @return array          Lista transformada con custom_fields incrustados, lista para la vista
     */
    public function attachCustomFields(array $tickets): array
    {
        if (empty($tickets)) return [];

        $ticketIds    = array_column($tickets, 'TicketId');
        $customFields = $this->getCustomFieldsPool($ticketIds);

        foreach ($tickets as &$ticket) {
            $ticket['customFields'] = $customFields[$ticket['TicketId']]
                ?? ['ticketId' => $ticket['TicketId']];
        }
        unset($ticket);

        return $this->transformTickets($tickets);
    }
}