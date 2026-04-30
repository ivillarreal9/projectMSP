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

    const CACHE_KPI        = 300;
    const CACHE_EXECUTIVES = 3600;
    const CACHE_CLIENTS    = 600;
    const CACHE_PIPELINE   = 300;

    public function __construct()
    {
        $this->url      = config('services.odoo.url')      ?? throw new \RuntimeException('ODOO_URL no está configurado.');
        $this->db       = config('services.odoo.db')       ?? throw new \RuntimeException('ODOO_DB no está configurado.');
        $this->username = config('services.odoo.username') ?? throw new \RuntimeException('ODOO_USERNAME no está configurado.');
        $this->apiKey   = config('services.odoo.api_key')  ?? throw new \RuntimeException('ODOO_API_KEY no está configurado.');
    }

    // ── Transporte ────────────────────────────────────────────

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
        return $this->call('common', 'login', [
            $this->db, $this->username, $this->apiKey
        ]);
    }

    public function execute(string $model, string $method, array $args = [], array $kwargs = []): mixed
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
        return Cache::remember('odoo:kpis', self::CACHE_KPI, function () {
            $leads         = $this->execute('crm.lead', 'search_count',
                [[['type', '=', 'lead'], ['active', '=', true]]]) ?? 0;
            $opportunities = $this->execute('crm.lead', 'search_count',
                [[['type', '=', 'opportunity'], ['active', '=', true]]]) ?? 0;
            $quotations    = $this->execute('sale.order', 'search_count',
                [[['state', 'in', ['draft', 'sent']]]]) ?? 0;
            $won           = $this->execute('crm.lead', 'search_count',
                [[['type', '=', 'opportunity'], ['stage_id.is_won', '=', true]]]) ?? 0;

            $cutoff    = now()->subDays(60)->format('Y-m-d');
            $recentIds = $this->getPartnerIdsWithInvoiceSince($cutoff);

            $domainRisk = [
                ['customer_rank', '>', 0],
                ['is_company',    '=', true],
            ];
            if (!empty($recentIds)) {
                $domainRisk[] = ['id', 'not in', $recentIds];
            }
            $atRisk = $this->execute('res.partner', 'search_count', [$domainRisk]) ?? 0;

            $pipeline      = $this->execute('sale.order', 'search_read',
                [[['state', 'in', ['draft', 'sent']]]],
                ['fields' => ['amount_total'], 'limit' => 0]
            ) ?? [];
            $pipelineTotal = collect($pipeline)->sum('amount_total');

            return compact('leads', 'opportunities', 'quotations', 'won', 'atRisk', 'pipelineTotal');
        });
    }

    // ── Pipeline ──────────────────────────────────────────────

    public function getPipeline(string $userId = '', string $state = '', int $limit = 50, int $offset = 0): array
    {
        $cacheKey = "odoo:pipeline:{$userId}:{$state}:{$limit}:{$offset}";

        return Cache::remember($cacheKey, self::CACHE_PIPELINE, function () use ($userId, $state, $limit, $offset) {
            $domain = [['state', 'in', ['draft', 'sent']]];
            if ($userId !== '') $domain[] = ['user_id', '=', (int) $userId];
            if ($state  !== '') $domain[] = ['state',   '=', $state];

            $kwargs = [
                'fields' => ['name', 'partner_id', 'user_id', 'amount_total', 'state', 'date_order', 'validity_date'],
                'order'  => 'date_order desc',
            ];
            if ($limit > 0) {
                $kwargs['limit']  = $limit;
                $kwargs['offset'] = $offset;
            }

            return $this->execute('sale.order', 'search_read', [$domain], $kwargs) ?? [];
        });
    }

    public function countPipeline(string $userId = '', string $state = ''): int
    {
        $cacheKey = "odoo:pipeline:count:{$userId}:{$state}";

        return Cache::remember($cacheKey, self::CACHE_PIPELINE, function () use ($userId, $state) {
            $domain = [['state', 'in', ['draft', 'sent']]];
            if ($userId !== '') $domain[] = ['user_id', '=', (int) $userId];
            if ($state  !== '') $domain[] = ['state',   '=', $state];
            return $this->execute('sale.order', 'search_count', [$domain]) ?? 0;
        });
    }

    public function getPipelineForChart(): array
    {
        return Cache::remember('odoo:pipeline:chart', self::CACHE_PIPELINE, function () {
            return $this->execute('sale.order', 'search_read',
                [[['state', 'in', ['draft', 'sent']]]],
                ['fields' => ['state', 'amount_total'], 'limit' => 0]
            ) ?? [];
        });
    }

    // ── Clientes — account.move ───────────────────────────────

    public function getPartnerIdsWithInvoiceSince(string $since): array
    {
        $cacheKey = "odoo:invoice:partners:{$since}";

        return Cache::remember($cacheKey, self::CACHE_CLIENTS, function () use ($since) {
            $invoices = $this->execute('account.move', 'search_read',
                [[
                    ['move_type',    '=',  'out_invoice'],
                    ['state',        '=',  'posted'],
                    ['invoice_date', '>=', $since],
                ]],
                ['fields' => ['partner_id'], 'limit' => 0]
            ) ?? [];

            return collect($invoices)
                ->pluck('partner_id.0')
                ->filter()
                ->unique()
                ->values()
                ->all();
        });
    }

    public function getLastInvoiceDateByPartners(array $partnerIds): array
    {
        if (empty($partnerIds)) return [];

        $cacheKey = 'odoo:lastinvoice:' . md5(implode(',', $partnerIds));

        return Cache::remember($cacheKey, self::CACHE_CLIENTS, function () use ($partnerIds) {
            $invoices = $this->execute('account.move', 'search_read',
                [[
                    ['move_type',  '=',  'out_invoice'],
                    ['state',      '=',  'posted'],
                    ['partner_id', 'in', $partnerIds],
                ]],
                [
                    'fields' => ['partner_id', 'invoice_date'],
                    'order'  => 'invoice_date desc',
                    'limit'  => 0,
                ]
            ) ?? [];

            $map = [];
            foreach ($invoices as $inv) {
                $pid  = is_array($inv['partner_id']) ? (int) $inv['partner_id'][0] : (int) $inv['partner_id'];
                $date = $inv['invoice_date'] ?? null;
                if (!$date) continue;
                if (!isset($map[$pid]) || $date > $map[$pid]) {
                    $map[$pid] = $date;
                }
            }

            return $map;
        });
    }

    public function getAllClientIds(?string $ejecutivaId = ''): array
    {
        $ejecutivaId = $ejecutivaId ?? '';
        $cacheKey    = "odoo:clients:ids:{$ejecutivaId}";

        return Cache::remember($cacheKey, self::CACHE_CLIENTS, function () use ($ejecutivaId) {
            $domain = [
                ['customer_rank', '>', 0],
                ['is_company',    '=', true],
            ];
            if ($ejecutivaId !== '') {
                $domain[] = ['user_id', '=', (int) $ejecutivaId];
            }

            $results = $this->execute('res.partner', 'search_read',
                [$domain],
                ['fields' => ['id'], 'limit' => 0]
            ) ?? [];

            return collect($results)->pluck('id')->map(fn($id) => (int)$id)->values()->all();
        });
    }

    public function getClientsPaginated(?string $ejecutivaId = '', int $limit = 50, int $offset = 0): array
    {
        $ejecutivaId = $ejecutivaId ?? '';
        $cacheKey    = "odoo:clients:pag:{$ejecutivaId}:{$limit}:{$offset}";

        return Cache::remember($cacheKey, self::CACHE_CLIENTS, function () use ($ejecutivaId, $limit, $offset) {
            $domain = [
                ['customer_rank', '>', 0],
                ['is_company',    '=', true],
            ];
            if ($ejecutivaId !== '') {
                $domain[] = ['user_id', '=', (int) $ejecutivaId];
            }

            return $this->execute('res.partner', 'search_read',
                [$domain],
                [
                    'fields' => ['name', 'user_id', 'customer_rank'],
                    'order'  => 'name asc',
                    'limit'  => $limit,
                    'offset' => $offset,
                ]
            ) ?? [];
        });
    }

    public function countClients(?string $ejecutivaId = ''): int
    {
        $ejecutivaId = $ejecutivaId ?? '';
        $cacheKey    = "odoo:clients:count:{$ejecutivaId}";

        return Cache::remember($cacheKey, self::CACHE_CLIENTS, function () use ($ejecutivaId) {
            $domain = [
                ['customer_rank', '>', 0],
                ['is_company',    '=', true],
            ];
            if ($ejecutivaId !== '') {
                $domain[] = ['user_id', '=', (int) $ejecutivaId];
            }
            return $this->execute('res.partner', 'search_count', [$domain]) ?? 0;
        });
    }

    // ── Clientes para reasignación ────────────────────────────

    public function countClientsForReassign(int $days = 60, ?string $ejecutivaId = ''): int
    {
        $ejecutivaId = $ejecutivaId ?? '';
        $cacheKey    = "odoo:reassign:count:{$days}:{$ejecutivaId}";

        return Cache::remember($cacheKey, self::CACHE_CLIENTS, function () use ($days, $ejecutivaId) {
            $since     = now()->subDays($days)->format('Y-m-d');
            $activeIds = $this->getPartnerIdsWithInvoiceSince($since);

            $domain = [
                ['is_company',    '=',  true],
                ['customer_rank', '>',  0],
                ['user_id',       '!=', false],
            ];
            if (!empty($activeIds)) {
                $domain[] = ['id', 'not in', $activeIds];
            }
            if ($ejecutivaId !== '') {
                $domain[] = ['user_id', '=', (int) $ejecutivaId];
            }

            return $this->execute('res.partner', 'search_count', [$domain]) ?? 0;
        });
    }

    public function getClientsForReassignPaginated(
        int     $days        = 60,
        ?string $ejecutivaId = '',
        int     $limit       = 50,
        int     $offset      = 0
    ): array {
        $ejecutivaId = $ejecutivaId ?? '';
        $cacheKey    = "odoo:reassign:{$days}:{$ejecutivaId}:{$limit}:{$offset}";

        return Cache::remember($cacheKey, self::CACHE_CLIENTS, function () use ($days, $ejecutivaId, $limit, $offset) {
            $since     = now()->subDays($days)->format('Y-m-d');
            $activeIds = $this->getPartnerIdsWithInvoiceSince($since);

            $domain = [
                ['is_company',    '=',  true],
                ['customer_rank', '>',  0],
                ['user_id',       '!=', false],
            ];
            if (!empty($activeIds)) {
                $domain[] = ['id', 'not in', $activeIds];
            }
            if ($ejecutivaId !== '') {
                $domain[] = ['user_id', '=', (int) $ejecutivaId];
            }

            return $this->execute('res.partner', 'search_read',
                [$domain],
                [
                    'fields' => ['name', 'user_id', 'customer_rank'],
                    'order'  => 'name asc',
                    'limit'  => $limit,
                    'offset' => $offset,
                ]
            ) ?? [];
        });
    }

    public function getClientsForReassign(int $days = 60, ?string $ejecutivaId = ''): array
    {
        return $this->getClientsForReassignPaginated($days, $ejecutivaId, 50, 0);
    }

    // ── Ejecutivas ────────────────────────────────────────────

    public function getExecutives(): array
    {
        return Cache::remember('odoo:executives', self::CACHE_EXECUTIVES, function () {
            $ids = config('sales.executive_ids', []);
            if (empty($ids)) return [];

            return $this->execute('res.users', 'search_read',
                [[['id', 'in', $ids]]],
                [
                    'fields' => ['id', 'name', 'email', 'sale_team_id', 'phone', 'mobile', 'image_128'],
                    'order'  => 'name asc',
                    'limit'  => 50,
                ]
            ) ?? [];
        });
    }

    public function getMetricsByExecutive(int $userId): array
    {
        $cacheKey = "odoo:metrics:exec:{$userId}";

        return Cache::remember($cacheKey, self::CACHE_KPI, function () use ($userId) {
            $leads     = $this->execute('crm.lead', 'search_count',
                [[['user_id', '=', $userId], ['type', '=', 'lead']]]) ?? 0;
            $won       = $this->execute('crm.lead', 'search_count',
                [[['user_id', '=', $userId], ['stage_id.is_won', '=', true]]]) ?? 0;
            $pipeline  = $this->execute('sale.order', 'search_count',
                [[['user_id', '=', $userId], ['state', 'in', ['draft', 'sent']]]]) ?? 0;
            $noContact = $this->execute('res.partner', 'search_count',
                [[['user_id', '=', $userId], ['customer_rank', '>', 0], ['activity_date_deadline', '=', false]]]) ?? 0;

            return compact('leads', 'won', 'pipeline', 'noContact');
        });
    }

    // ── Detalle ejecutiva — oportunidades CRM ─────────────────

    public function getOpportunitiesByExecutive(int $userId, int $limit = 20): array
    {
        $cacheKey = "odoo:opportunities:exec:{$userId}";

        return Cache::remember($cacheKey, self::CACHE_KPI, function () use ($userId, $limit) {
            return $this->execute('crm.lead', 'search_read',
                [[
                    ['user_id', '=', $userId],
                    ['type',    '=', 'opportunity'],
                    ['active',  '=', true],
                ]],
                [
                    'fields' => [
                        'name',
                        'partner_id',
                        'stage_id',
                        'expected_revenue',
                        'probability',
                        'date_deadline',
                    ],
                    'order' => 'probability desc',
                    'limit' => $limit,
                ]
            ) ?? [];
        });
    }

    // ── Detalle ejecutiva — actividades recientes ─────────────

    public function getActivitiesByExecutive(int $userId, int $limit = 15): array
    {
        $cacheKey = "odoo:activities:exec:{$userId}";

        return Cache::remember($cacheKey, self::CACHE_KPI, function () use ($userId, $limit) {
            $raw = $this->execute('mail.activity', 'search_read',
                [[
                    ['user_id', '=', $userId],
                ]],
                [
                    'fields' => [
                        'summary',
                        'activity_type_id',
                        'date_deadline',
                        'note',
                        'res_name',
                    ],
                    'order' => 'date_deadline desc',
                    'limit' => $limit,
                ]
            ) ?? [];

            // Normalizar activity_type para que la vista lo encuentre en $act['activity_type']
            return collect($raw)->map(function ($act) {
                $act['activity_type'] = is_array($act['activity_type_id'])
                    ? $act['activity_type_id'][1]
                    : ($act['activity_type_id'] ?? 'Actividad');
                return $act;
            })->all();
        });
    }

    // ── Invalidar caché ───────────────────────────────────────

    public function clearCache(): void
    {
        $keys = [
            'odoo:kpis',
            'odoo:clients',
            'odoo:executives',
            'odoo:pipeline:chart',
        ];
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}