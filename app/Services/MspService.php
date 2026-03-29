<?php

namespace App\Services;

use App\Models\MspCredential;
use Illuminate\Support\Facades\Http;

class MspService
{
    protected string $baseUrl;
    protected string $authHeader;

    private const CHUNK_SIZE = 100;

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

    public function __construct()
    {
        $cred = MspCredential::latest()->first();

        if (!$cred) {
            throw new \Exception('No hay credenciales MSP configuradas.');
        }

        $this->baseUrl    = rtrim($cred->base_url, '/');
        $this->authHeader = 'Basic ' . base64_encode($cred->username . ':' . $cred->password);
    }

    // -------------------------------------------------------------------------
    // Métodos públicos usados por el controlador SSE
    // -------------------------------------------------------------------------

    /**
     * EP1: obtener tickets filtrados por fecha.
     * Expuesto públicamente para que el controlador lo llame por separado
     * y pueda enviar el evento SSE después de completarse.
     */
    public function fetchTicketsPublic(string $fechaInicio, string $fechaFin): array
    {
        $filter = $this->buildDateFilter($fechaInicio, $fechaFin);
        return $this->getTicketsFiltered($filter);
    }

    /**
     * EP2 + EP3: obtener time entries y custom fields en paralelo por chunks.
     * Acepta un callback opcional que se llama después de cada lote
     * para reportar progreso al SSE.
     *
     * @param array         $tickets
     * @param callable|null $onChunkDone  fn(int $done, int $total)
     */
    public function fetchExtraDataPublic(array $tickets, ?callable $onChunkDone = null): array
    {
        return $this->fetchEP2andEP3InParallel($tickets, $onChunkDone);
    }

    /**
     * Combinar tickets con sus datos extra.
     * Expuesto públicamente para que el controlador lo llame por separado.
     */
    public function combinePublic(array $tickets, array $extraData): array
    {
        return $this->combineResults($tickets, $extraData);
    }

    // -------------------------------------------------------------------------
    // Método principal (uso directo sin SSE, ej: export)
    // -------------------------------------------------------------------------

    public function getTickets(string $fechaInicio, string $fechaFin): array
    {
        $tickets   = $this->fetchTicketsPublic($fechaInicio, $fechaFin);
        $extraData = $this->fetchExtraDataPublic($tickets);
        return $this->combinePublic($tickets, $extraData);
    }

    // -------------------------------------------------------------------------
    // EP1: ticketsview
    // -------------------------------------------------------------------------

    protected function buildDateFilter(string $fechaInicio, string $fechaFin): string
    {
        $inicio = \Carbon\Carbon::parse($fechaInicio)->startOfDay()->utc()->format('Y-m-d\TH:i:s\Z');
        $fin    = \Carbon\Carbon::parse($fechaFin)->endOfDay()->utc()->format('Y-m-d\TH:i:s\Z');

        return "CompletedDate ge {$inicio} and CompletedDate lt {$fin}";
    }

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

    protected function fetchEP2andEP3InParallel(array $tickets, ?callable $onChunkDone = null): array
    {
        $fieldFilter = implode(' or ', array_map(
            fn($id) => "ticketTypeFieldId eq {$id}",
            self::CUSTOM_FIELD_IDS
        ));

        $extraData  = [];
        $chunks     = array_chunk($tickets, self::CHUNK_SIZE);
        $totalDone  = 0;
        $totalCount = count($tickets);

        foreach ($chunks as $chunk) {

            $responses = Http::pool(function ($pool) use ($chunk, $fieldFilter) {
                $requests = [];

                foreach ($chunk as $ticket) {
                    $ticketId = $ticket['TicketId'];

                    // EP2: time entry
                    $urlEP2 = $this->baseUrl
                        . '/tickettimeentriesview'
                        . '?$filter='  . rawurlencode("TicketId eq {$ticketId}")
                        . '&$orderby=TicketNumber desc'
                        . '&$select='  . rawurlencode('TicketId,WorkType,CustomWorkType')
                        . '&$top=1';

                    $requests[] = $pool
                        ->as("te_{$ticketId}")
                        ->withHeaders(['Authorization' => $this->authHeader])
                        ->timeout(30)
                        ->get($urlEP2);

                    // EP3: custom fields
                    $urlEP3 = $this->baseUrl
                        . '/tickets/' . $ticketId . '/customfields'
                        . '?$select=' . rawurlencode('ticketId,name,value')
                        . '&$filter=' . rawurlencode($fieldFilter);

                    $requests[] = $pool
                        ->as("cf_{$ticketId}")
                        ->withHeaders(['Authorization' => $this->authHeader])
                        ->timeout(30)
                        ->get($urlEP3);
                }

                return $requests;
            });

            // Procesar respuestas del lote
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
                } catch (\Throwable $e) {}

                // EP3
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
                    }
                } catch (\Throwable $e) {}

                $extraData[$ticketId] = [
                    'timeEntry'    => $timeEntry,
                    'customFields' => $customFields,
                ];
            }

            // Reportar progreso después de cada lote
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

    protected function transformTimeEntry(array $entry): array
    {
        return [
            'WorkType'       => $entry['WorkType']       ?? '',
            'CustomWorkType' => $entry['CustomWorkType'] ?? '',
        ];
    }

    protected function emptyTimeEntry(): array
    {
        return ['WorkType' => '', 'CustomWorkType' => ''];
    }

    // -------------------------------------------------------------------------
    // HTTP Helper
    // -------------------------------------------------------------------------

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