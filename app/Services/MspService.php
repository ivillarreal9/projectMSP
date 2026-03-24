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

    protected function get(string $endpoint): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader,
        ])->timeout(30)->get($this->baseUrl . $endpoint);

        if ($response->failed()) return [];

        return $response->json('value') ?? [];
    }

    public function getTickets(string $fechaInicio, string $fechaFin): array
    {
        $tickets      = $this->get("/ticketsview?\$orderby=TicketNumber desc&\$select=TicketId,TicketNumber,TicketTitle,TicketIssueTypeName,TicketSubIssueTypeName,CustomerName,LocationName,CreatedDate,CompletedDate,DueDate,UpdatedDate");
        $timeEntries  = $this->get("/tickettimeentriesview?\$top=3000&\$orderby=TicketNumber desc&\$select=TicketId,WorkType,CustomWorkType,StartTime,EndTime,UserFirstName,UserLastName");
        $customFields = $this->get("/ticketscustomfields?\$select=TicketId,Name,Value");

        // Indexar timeEntries por TicketId
        $entriesMap = [];
        foreach ($timeEntries as $entry) {
            $entriesMap[$entry['TicketId']][] = $entry;
        }

        // Indexar customFields por TicketId
        $customMap = [];
        foreach ($customFields as $cf) {
            $customMap[$cf['TicketId']][] = [
                'nombre'      => $cf['Name'],
                'descripcion' => $cf['Value'],
            ];
        }

        $inicio = strtotime($fechaInicio);
        $fin    = strtotime($fechaFin . ' 23:59:59');

        $result = [];

        foreach ($tickets as $ticket) {
            $completedTs = strtotime($ticket['CompletedDate'] ?? '');

            if ($completedTs < $inicio || $completedTs > $fin) continue;

            $base = [
                'TicketId'              => $ticket['TicketId'] ?? '',
                'TicketNumber'          => $ticket['TicketNumber'] ?? '',
                'TicketTitle'           => $ticket['TicketTitle'] ?? '',
                'TicketIssueTypeName'   => $ticket['TicketIssueTypeName'] ?? '',
                'TicketSubIssueTypeName'=> $ticket['TicketSubIssueTypeName'] ?? '',
                'CustomerName'          => $ticket['CustomerName'] ?? '',
                'LocationName'          => $ticket['LocationName'] ?? '',
                'CreatedDate'           => $this->formatDate($ticket['CreatedDate'] ?? ''),
                'CompletedDate'         => $this->formatDate($ticket['CompletedDate'] ?? ''),
                'DueDate'               => $this->formatDate($ticket['DueDate'] ?? ''),
            ];

            // Agregar custom fields
            foreach ($customMap[$ticket['TicketId']] ?? [] as $cf) {
                $base[$cf['nombre']] = $cf['descripcion'];
            }

            // Combinar con time entries
            if (!empty($entriesMap[$ticket['TicketId']])) {
                foreach ($entriesMap[$ticket['TicketId']] as $entry) {
                    $result[] = array_merge($base, [
                        'WorkType'       => $entry['WorkType'] ?? '',
                        'CustomWorkType' => $entry['CustomWorkType'] ?? '',
                        'StartTime'      => $this->formatDate($entry['StartTime'] ?? ''),
                        'EndTime'        => $this->formatDate($entry['EndTime'] ?? ''),
                        'UserFirstName'  => $entry['UserFirstName'] ?? '',
                        'UserLastName'   => $entry['UserLastName'] ?? '',
                    ]);
                }
            } else {
                $result[] = $base;
            }
        }

        return $result;
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