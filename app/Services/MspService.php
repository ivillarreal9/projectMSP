<?php

namespace App\Services;

use App\Models\MspCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class MspService
{
    protected string $baseUrl;
    protected string $authHeader;

    public function __construct()
    {
        $cred = MspCredential::latest()->first();

        if (!$cred) {
            throw new \Exception('No hay credenciales MSP configuradas.');
        }

        $this->baseUrl    = $cred->base_url;
        $this->authHeader = 'Basic ' . base64_encode($cred->username . ':' . $cred->password);
    }

    protected function get(string $endpoint, int $timeout = 60): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader,
        ])->timeout($timeout)->get($this->baseUrl . $endpoint);

        if ($response->failed()) return [];

        return $response->json('value') ?? [];
    }

    public function getTickets(string $fechaInicio, string $fechaFin): array
    {
        // Construir filtro OData para fechas
        $filterOData = $this->buildDateFilter($fechaInicio, $fechaFin);
        
        // Obtener datos con filtros aplicados en la API
        $tickets      = $this->getTicketsFiltered($filterOData);
        $timeEntries  = $this->getTimeEntries();
        $customFields = $this->getCustomFields();

        if (empty($tickets)) {
            return [];
        }

        // Indexar datos por TicketId
        $entriesMap = $this->indexByTicketId($timeEntries);
        $customMap  = $this->indexCustomFieldsByTicketId($customFields);

        // Transformar y combinar datos
        $result = [];
        foreach ($tickets as $ticket) {
            $ticketId = $ticket['TicketId'] ?? '';
            $base = $this->transformTicket($ticket);

            // Agregar custom fields
            $base = array_merge($base, $this->extractCustomFields($customMap, $ticketId));

            // Combinar con time entries
            if (!empty($entriesMap[$ticketId])) {
                foreach ($entriesMap[$ticketId] as $entry) {
                    $result[] = array_merge($base, $this->transformTimeEntry($entry));
                }
            } else {
                $result[] = $base;
            }
        }

        return $result;
    }

    protected function buildDateFilter(string $fechaInicio, string $fechaFin): string
    {
        try {
            $inicio = \Carbon\Carbon::parse($fechaInicio)->startOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');
            $fin    = \Carbon\Carbon::parse($fechaFin)->endOfDay()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');
            
            return "CompletedDate ge datetime'$inicio' and CompletedDate le datetime'$fin'";
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function getTicketsFiltered(string $filterOData): array
    {
        $filter = $filterOData ? "&\$filter=$filterOData" : '';
        $endpoint = "/ticketsview?{$filter}&\$orderby=TicketNumber desc&\$select=TicketId,TicketNumber,TicketTitle,TicketIssueTypeName,TicketSubIssueTypeName,CustomerName,LocationName,CreatedDate,CompletedDate,DueDate,UpdatedDate";
        
        return $this->get($endpoint);
    }

    protected function getTimeEntries(): array
    {
        return $this->get("/tickettimeentriesview?\$top=3000&\$orderby=TicketNumber desc&\$select=TicketId,WorkType,CustomWorkType,StartTime,EndTime,UserFirstName,UserLastName", 90);
    }

    protected function getCustomFields(): array
    {
        // Limitar a los últimos 5000 registros para evitar timeout
        return $this->get("/ticketscustomfields?\$top=5000&\$orderby=TicketId desc&\$select=TicketId,Name,Value", 90);
    }

    protected function indexByTicketId(array $entries): array
    {
        $map = [];
        foreach ($entries as $entry) {
            $ticketId = $entry['TicketId'] ?? '';
            if ($ticketId) {
                $map[$ticketId][] = $entry;
            }
        }
        return $map;
    }

    protected function indexCustomFieldsByTicketId(array $customFields): array
    {
        $map = [];
        foreach ($customFields as $cf) {
            $ticketId = $cf['TicketId'] ?? '';
            if ($ticketId) {
                $map[$ticketId][] = [
                    'nombre'      => $cf['Name'] ?? '',
                    'descripcion' => $cf['Value'] ?? '',
                ];
            }
        }
        return $map;
    }

    protected function transformTicket(array $ticket): array
    {
        return [
            'ticket_id'              => $ticket['TicketId'] ?? '',
            'ticket_number'          => $ticket['TicketNumber'] ?? '',
            'ticket_title'           => $ticket['TicketTitle'] ?? '',
            'ticket_issue_type'      => $ticket['TicketIssueTypeName'] ?? '',
            'ticket_sub_issue_type'  => $ticket['TicketSubIssueTypeName'] ?? '',
            'customer_name'          => $ticket['CustomerName'] ?? '',
            'location_name'          => $ticket['LocationName'] ?? '',
            'created_date'           => $this->formatDate($ticket['CreatedDate'] ?? ''),
            'completed_date'         => $this->formatDate($ticket['CompletedDate'] ?? ''),
            'due_date'               => $this->formatDate($ticket['DueDate'] ?? ''),
        ];
    }

    protected function transformTimeEntry(array $entry): array
    {
        return [
            'work_type'       => $entry['WorkType'] ?? '',
            'custom_work_type' => $entry['CustomWorkType'] ?? '',
            'start_time'      => $this->formatDate($entry['StartTime'] ?? ''),
            'end_time'        => $this->formatDate($entry['EndTime'] ?? ''),
            'user_first_name' => $entry['UserFirstName'] ?? '',
            'user_last_name'  => $entry['UserLastName'] ?? '',
        ];
    }

    protected function extractCustomFields(array $customMap, string $ticketId): array
    {
        $fields = [];
        foreach ($customMap[$ticketId] ?? [] as $cf) {
            $fields[$cf['nombre']] = $cf['descripcion'];
        }
        return $fields;
    }

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