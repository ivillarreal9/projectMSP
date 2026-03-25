<?php

namespace App\Services;

use App\Models\MspCredential;
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
    ];

    public function __construct()
    {
        $cred = MspCredential::latest()->first();

        if (!$cred) {
            throw new \Exception('No hay credenciales MSP configuradas.');
        }

        $this->baseUrl    = $cred->base_url;
        $this->authHeader = 'Basic ' . base64_encode($cred->username . ':' . $cred->password);
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
     * Paso 3 — Custom fields en paralelo con Http::pool
     * Hace todas las peticiones al mismo tiempo en vez de una por una
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
                    // ✅ Sin wrapper 'value'
                    $rawFields = $response->json() ?? [];
                }

                $flat = ['ticketId' => $ticketId];
                foreach ($rawFields as $field) {
                    // ✅ Name y Value con mayúscula
                    $name = trim($field['Name'] ?? $field['name'] ?? '');
                    if ($name) $flat[$name] = $field['Value'] ?? $field['value'] ?? '';
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

        // Paso 1 — IDs del período
        $ids = $this->getTelefoniaIds($month, $year);

        if (empty($ids)) return [];

        // Paso 2 — detalle de tickets con esos IDs
        $tickets = $this->getTicketsByIds($ids);

        // Filtro de búsqueda local sobre resultados
        if ($search) {
            $s       = strtolower($search);
            $tickets = array_values(array_filter($tickets, fn($t) =>
                str_contains(strtolower($t['TicketNumber'] ?? ''), $s) ||
                str_contains(strtolower($t['TicketIssueTypeName'] ?? ''), $s)
            ));
        }

        if (empty($tickets)) return [];

        // Paso 3 — custom fields en paralelo
        $ticketIds    = array_column($tickets, 'TicketId');
        $customFields = $this->getCustomFieldsPool($ticketIds);

        // Combinar tickets con sus custom fields
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

                // ✅ Solo contar si tiene provincia real
                if ($prov !== '') {
                    $pendingByProv[$prov] = ($pendingByProv[$prov] ?? 0) + 1;
                }
            }
        }

        // Agrupar completados por provincia
        $byProvince = [];

        foreach ($completedTickets as $ticket) {
            $prov = trim($ticket['customFields']['Provincia'] ?? '');

            // ✅ Ignorar tickets sin provincia — data dormida
            if ($prov === '') continue;

            $byProvince[$prov][] = $ticket;
        }

        // Calcular resumen — solo provincias con data real
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
            // ✅ Name y Value con mayúscula
            $name  = trim($field['Name'] ?? $field['name'] ?? '');
            $value = $field['Value'] ?? $field['value'] ?? '';
            if ($name) {
                $result['aplanado'][$name] = $value;
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

        // Devuelve el JSON completo sin buscar 'value'
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

}