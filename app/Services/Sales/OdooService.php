<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OdooService
{
    private string $url;
    private string $db;
    private string $username;
    private string $apiKey;

    public function __construct()
    {
        $this->url      = config('services.odoo.url')      ?? throw new \RuntimeException('ODOO_URL no está configurado.');
        $this->db       = config('services.odoo.db')       ?? throw new \RuntimeException('ODOO_DB no está configurado.');
        $this->username = config('services.odoo.username') ?? throw new \RuntimeException('ODOO_USERNAME no está configurado.');
        $this->apiKey   = config('services.odoo.api_key')  ?? throw new \RuntimeException('ODOO_API_KEY no está configurado.');
    }

    private function call(string $service, string $method, array $args): mixed
    {
        $response = Http::timeout(30)->post($this->url, [
            'jsonrpc' => '2.0',
            'method'  => 'call',
            'id'      => now()->timestamp,
            'params'  => [
                'service' => $service,
                'method'  => $method,
                'args'    => $args,
            ],
        ]);

        if ($response->failed()) return null;
        $json = $response->json();
        if (isset($json['error'])) return null;
        return $json['result'] ?? null;
    }

    public function login(): ?int
    {
        // Sin caché — Odoo sessions expiran rápido
        return $this->call('common', 'login', [
            $this->db, $this->username, $this->apiKey
        ]);
    }

    private function execute(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        $uid = $this->login();
        if (!$uid) return null;

        return $this->call('object', 'execute_kw', [
            $this->db, $uid, $this->apiKey,
            $model, $method, $args, $kwargs
        ]);
    }

    // ── KPIs ──────────────────────────────────────────────────

    public function getKpis(): array
    {
        $leads         = $this->execute('crm.lead', 'search_count', [[['type', '=', 'lead'], ['active', '=', true]]]) ?? 0;
        $opportunities = $this->execute('crm.lead', 'search_count', [[['type', '=', 'opportunity'], ['active', '=', true]]]) ?? 0;
        $quotations    = $this->execute('sale.order', 'search_count', [[['state', 'in', ['draft', 'sent']]]]) ?? 0;
        $won           = $this->execute('crm.lead', 'search_count', [[['type', '=', 'opportunity'], ['stage_id.is_won', '=', true]]]) ?? 0;

        // Clientes en riesgo — sin factura en más de 60 días
        $cutoff     = now()->subDays(60)->format('Y-m-d');
        try {
            $atRisk = $this->execute('res.partner', 'search_count', [[
                ['customer_rank', '>', 0],
                ['is_company', '=', true],
                ['date_last_invoice', '<', $cutoff],
            ]]) ?? 0;
        } catch (\Throwable $e) {
            $atRisk = 0;
        }

        // Monto total pipeline
        $pipeline = $this->execute('sale.order', 'search_read',
            [[['state', 'in', ['draft', 'sent']]]],
            ['fields' => ['amount_total']]
        ) ?? [];

        $pipelineTotal = collect($pipeline)->sum('amount_total');

        return compact('leads', 'opportunities', 'quotations', 'won', 'atRisk', 'pipelineTotal');
    }

    // ── Pipeline ──────────────────────────────────────────────

    public function getPipeline(string $userId = '', string $state = '', int $limit = 50, int $offset = 0): array
    {
        $domain = [['state', 'in', ['draft', 'sent']]];
    
        if ($userId !== '') {
            $domain[] = ['user_id', '=', (int) $userId];
        }
        if ($state !== '') {
            $domain[] = ['state', '=', $state];
        }
    
        $kwargs = [
            'fields' => ['name', 'partner_id', 'user_id', 'amount_total', 'state', 'date_order', 'validity_date'],
            'order'  => 'date_order desc',
        ];
    
        // limit 0 = sin límite (para export CSV)
        if ($limit > 0) {
            $kwargs['limit']  = $limit;
            $kwargs['offset'] = $offset;
        }
    
        return $this->execute('sale.order', 'search_read', [$domain], $kwargs) ?? [];
    }

    // ── Clientes ──────────────────────────────────────────────

    public function getClients(): array
    {
        return $this->execute('res.partner', 'search_read',
            [[['customer_rank', '>', 0], ['is_company', '=', true]]],
            [
                'fields' => ['name', 'user_id', 'activity_date_deadline', 'date_last_invoice', 'customer_rank'],
                'order'  => 'date_last_invoice asc',
                'limit'  => 100,
            ]
        ) ?? [];
    }

    // ── Ejecutivas ────────────────────────────────────────────

    public function getExecutives(): array
    {
        $ids = config('sales.executive_ids', []);
    
        if (empty($ids)) return [];
    
        return $this->execute('res.users', 'search_read',
            [[['id', 'in', $ids]]],
            [
                'fields' => [
                    'id', 'name', 'email',
                    'sale_team_id',
                    'phone', 'mobile',
                    'image_128',
                ],
                'order' => 'name asc',
                'limit' => 50,
            ]
        ) ?? [];
    }
 

    public function getMetricsByExecutive(int $userId): array
    {
        $leads      = $this->execute('crm.lead', 'search_count', [[['user_id', '=', $userId], ['type', '=', 'lead']]]) ?? 0;
        $won        = $this->execute('crm.lead', 'search_count', [[['user_id', '=', $userId], ['stage_id.is_won', '=', true]]]) ?? 0;
        $pipeline   = $this->execute('sale.order', 'search_count', [[['user_id', '=', $userId], ['state', 'in', ['draft', 'sent']]]]) ?? 0;
        $noContact  = $this->execute('res.partner', 'search_count', [[['user_id', '=', $userId], ['customer_rank', '>', 0], ['activity_date_deadline', '=', false]]]) ?? 0;

        return compact('leads', 'won', 'pipeline', 'noContact');
    }

    public function getClientsForReassign(int $days = 60): array
    {
        return $this->execute('res.partner', 'search_read',
            [[
                ['is_ovni_client', '=', true],
                ['user_id', '!=', false],
            ]],
            [
                'fields' => ['name', 'user_id', 'customer_rank', 
                            'activity_date_deadline', 'creation_date'],
                'order'  => 'creation_date asc',
                'limit'  => 2000,
            ]
        ) ?? [];
    }

    public function countPipeline(string $userId = '', string $state = ''): int
    {
        $domain = [['state', 'in', ['draft', 'sent']]];
    
        if ($userId !== '') {
            $domain[] = ['user_id', '=', (int) $userId];
        }
        if ($state !== '') {
            $domain[] = ['state', '=', $state];
        }
    
        return $this->execute('sale.order', 'search_count', [$domain]) ?? 0;
    }
    
    // ── Solo montos para la gráfica (siempre sin filtros) ────────
    
    public function getPipelineForChart(): array
    {
        return $this->execute('sale.order', 'search_read',
            [[['state', 'in', ['draft', 'sent']]]],
            [
                'fields' => ['state', 'amount_total'],
                'limit'  => 0,
            ]
        ) ?? [];
    }


}