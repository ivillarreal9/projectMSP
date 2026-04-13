<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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

    public function __construct()
    {
        $username = config('services.msp.username');
        $password = config('services.msp.password');
        $baseUrl  = config('services.msp.base_url');

        if (!$username || !$password) {
            throw new \Exception('No hay credenciales MSP configuradas.');
        }

        $this->baseUrl    = $baseUrl;
        $this->authHeader = 'Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * Petición GET simple a la API
     */
    protected function get(string $endpoint, int $timeout = 60): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader,
        ])->timeout($timeout)->get($this->baseUrl . $endpoint);

        if ($response->failed()) return [];

        return $response->json('value') ?? [];
    }

    /**
     * Paso 1 — Obtener solo los IDs de tickets de Telefonía del mes/año
     */
    public function getTelefoniaIds(int $month, int $year): array
    {
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)
            ->startOfDay()->format('Y-m-d\TH:i:s\Z');

        $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)
            ->endOfMonth()->endOfDay()->format('Y-m-d\TH:i:s\Z');

        $filter = "TicketIssueTypeName eq 'Telefonía'" .
                  " and CompletedDate ge {$startDate}" .
                  " and CompletedDate le {$endDate}";

        $tickets = $this->get("/ticketsview?\$filter={$filter}&\$select=TicketId");

        return array_column($tickets, 'TicketId');
    }

    /**
     * Paso 2 — Con los IDs, traer el detalle de cada ticket
     */
    protected function getTicketsByIds(array $ids): array
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
     * Determinar si un campo debe mostrar solo el código
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
     * Paso 3 — Custom fields en paralelo con Http::pool
     */
    protected function getCustomFieldsPool(array $ticketIds): array
    {
        if (empty($ticketIds)) return [];

        $result      = [];
        $authHeader  = $this->authHeader;
        $baseUrl     = $this->baseUrl;
        $fieldFilter = $this->buildFieldFilter();

        $chunks = array_chunk($ticketIds, 10);

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

                $result[$ticketId] = $flat;
            }

            if (count($chunks) > 1) {
                usleep(200000);
            }
        }

        return $result;
    }

    /**
     * Método principal — orquesta los 3 pasos
     */
    public function getTelefoniaTickets(?string $search = null, ?int $month = null, ?int $year = null): array
    {
        if (!$month || !$year) return [];

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
    }

    /**
     * Transformar datos al formato de la vista
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
     * Formatear fecha subiendo -5 horas (UTC → Panamá)
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
     * Preparar datos completos para el PDF del informe
     */
    public function getPdfReportData(int $month, int $year): array
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
     * Todos los tickets de Telefonía sin filtro de fecha
     */
    protected function getAllTelefoniaTickets(): array
    {
        return $this->get(
            "/ticketsview?\$filter=TicketIssueTypeName eq 'Telefonía'" .
            "&\$select=TicketId,TicketNumber,CompletedDate,CreatedDate"
        );
    }

    /**
     * Tickets por IDs con fechas completas
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
     * Petición GET que devuelve el JSON directamente (sin wrapper 'value')
     */
    protected function getRaw(string $endpoint, int $timeout = 60): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader,
        ])->timeout($timeout)->get($this->baseUrl . $endpoint);

        if ($response->failed()) return [];

        return $response->json() ?? [];
    }

    protected function buildFieldFilter(): string
    {
        $conditions = array_map(
            fn($id) => "TicketTypeFieldId eq {$id}",
            $this->requiredFieldIds
        );

        return implode(' or ', $conditions);
    }

    /**
     * Extraer solo el código del valor (antes del \t o del primer espacio)
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
     * Paso 2 público — para el stream SSE
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
     * Paso 3 público — adjunta custom fields y transforma
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