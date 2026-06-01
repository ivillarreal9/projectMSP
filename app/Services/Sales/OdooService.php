<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de integración con Odoo via JSON-RPC (protocolo nativo de Odoo).
 *
 * Provee acceso a los modelos CRM, Ventas y Socios para el módulo de Ventas:
 *   - KPIs globales del dashboard (leads, oportunidades, pipeline, clientes en riesgo)
 *   - Pipeline de órdenes de venta (cotizaciones y pedidos)
 *   - Clientes y clientes para reasignación (sin factura reciente)
 *   - Ejecutivas de venta y sus métricas individuales
 *   - Comisiones (via CommissionService que depende de este servicio)
 *   - Sincronización de partners para el módulo de merge de clientes
 *
 * Protocolo: JSON-RPC 2.0 sobre HTTP POST al endpoint /web/dataset/call_kw de Odoo.
 * Autenticación: login con API Key → UID → execute_kw con (db, uid, api_key, model, method).
 * El UID se cachea 4 minutos y se renueva automáticamente si una llamada falla.
 *
 * Dependencias externas:
 *   - Odoo : ODOO_URL, ODOO_DB, ODOO_USERNAME, ODOO_API_KEY en .env
 *   - config/sales.php : executive_ids (lista de IDs de usuarios ejecutivos)
 */
class OdooService
{
    private string $url;
    private string $db;
    private string $username;
    private string $apiKey;

    /** TTL de caché para KPIs y métricas: 24 horas. */
    const CACHE_KPI        = 86400;
    /** TTL de caché para lista de ejecutivas: 24 horas. */
    const CACHE_EXECUTIVES = 86400;
    /** TTL de caché para datos de clientes: 24 horas. */
    const CACHE_CLIENTS    = 86400;
    /** TTL de caché para el pipeline de ventas: 24 horas. */
    const CACHE_PIPELINE   = 86400;
    /** TTL de caché para sincronización de partners: 30 días. */
    const CACHE_MONTH      = 2592000;

    /**
     * Inicializa el servicio leyendo la configuración de Odoo.
     *
     * @throws \RuntimeException si cualquiera de las 4 variables de Odoo no está configurada
     */
    public function __construct()
    {
        $this->url      = config('services.odoo.url')      ?? throw new \RuntimeException('ODOO_URL no está configurado.');
        $this->db       = config('services.odoo.db')       ?? throw new \RuntimeException('ODOO_DB no está configurado.');
        $this->username = config('services.odoo.username') ?? throw new \RuntimeException('ODOO_USERNAME no está configurado.');
        $this->apiKey   = config('services.odoo.api_key')  ?? throw new \RuntimeException('ODOO_API_KEY no está configurado.');
    }

    // ── Transporte ────────────────────────────────────────────

    /**
     * Ejecuta una llamada JSON-RPC al servidor Odoo.
     *
     * Envuelve el request en el formato {"jsonrpc":"2.0","method":"call","params":{...}}
     * requerido por Odoo. Retorna null si la HTTP request falla o si la respuesta
     * contiene un campo 'error' (error de negocio de Odoo).
     *
     * @param  string $service Servicio Odoo: 'common' (autenticación) o 'object' (datos)
     * @param  string $method  Método del servicio: 'login' o 'execute_kw'
     * @param  array  $args    Argumentos posicionales para el método
     * @return mixed           Valor del campo 'result' de la respuesta, o null si hay error
     */
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

    /**
     * Autentica en Odoo y retorna el UID del usuario.
     *
     * El UID se cachea 4 minutos — suficiente para una sesión de trabajo normal
     * sin llamadas de login repetidas en cada request de la aplicación.
     *
     * @return int|null UID del usuario autenticado, o null si las credenciales fallan
     */
    public function login(): ?int
    {
        return Cache::remember('odoo:session:uid', 240, fn() =>
            $this->call('common', 'login', [
                $this->db, $this->username, $this->apiKey
            ])
        );
    }

    /**
     * Ejecuta una operación sobre un modelo Odoo vía execute_kw.
     *
     * Si el UID cacheado ya expiró en Odoo (result == null), invalida la caché,
     * obtiene un UID fresco y reintenta exactamente una vez para recuperarse
     * de sesiones vencidas sin intervención del usuario.
     *
     * @param  string $model   Modelo Odoo (p.ej. 'crm.lead', 'sale.order', 'res.partner')
     * @param  string $method  Método del modelo: 'search_read', 'search_count', 'write', etc.
     * @param  array  $args    Argumentos posicionales (normalmente [domain])
     * @param  array  $kwargs  Argumentos por nombre (fields, limit, offset, order, etc.)
     * @return mixed           Resultado de Odoo (array, int o bool) o null si falla
     */
    public function execute(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        $uid = $this->login();

        // Si el UID cacheado ya expiró en Odoo, reintenta una vez con sesión nueva
        if (!$uid) return null;

        $result = $this->call('object', 'execute_kw', [
            $this->db, $uid, $this->apiKey,
            $model, $method, $args, $kwargs,
        ]);

        if ($result === null) {
            Cache::forget('odoo:session:uid');
            $uid = $this->login();
            if (!$uid) return null;
        } else {
            return $result;
        }

        return $this->call('object', 'execute_kw', [
            $this->db, $uid, $this->apiKey,
            $model, $method, $args, $kwargs
        ]);
    }

    // ── KPIs ──────────────────────────────────────────────────

    /**
     * Obtiene los KPIs globales para el dashboard de ventas.
     *
     * Ejecuta múltiples llamadas a Odoo para calcular:
     *   - leads: leads activos en CRM
     *   - opportunities: oportunidades activas
     *   - quotations: cotizaciones en estado draft o sent
     *   - won: oportunidades ganadas
     *   - atRisk: clientes empresa sin factura en los últimos 60 días
     *   - pipelineTotal: monto total de cotizaciones activas
     *
     * @return array Mapa con claves: leads, opportunities, quotations, won, atRisk, pipelineTotal
     */
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

    /**
     * Obtiene el pipeline de órdenes de venta (cotizaciones) con filtros opcionales.
     *
     * @param  string $userId   ID de ejecutiva para filtrar (vacío = todos)
     * @param  string $state    Estado de la orden ('draft'|'sent'|'' para ambos)
     * @param  int    $limit    Registros por página (0 = sin límite)
     * @param  int    $offset   Desplazamiento para paginación
     * @return array            Lista de órdenes con name, partner_id, user_id, amount_total, state, etc.
     */
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

    /**
     * Cuenta el total de órdenes en el pipeline con los filtros dados.
     *
     * Usado para construir la paginación del listado de pipeline.
     *
     * @param  string $userId ID de ejecutiva para filtrar (vacío = todos)
     * @param  string $state  Estado de la orden (vacío = draft y sent)
     * @return int            Total de registros que coinciden con el filtro
     */
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

    /**
     * Obtiene el pipeline completo con solo state y amount_total para el gráfico.
     *
     * Versión ligera sin paginación ni campos innecesarios — optimizada para
     * alimentar el gráfico de distribución de pipeline por estado.
     *
     * @return array Lista de órdenes con solo state y amount_total
     */
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

    /**
     * Retorna los IDs de partners que tienen al menos una factura publicada desde la fecha dada.
     *
     * Usado para identificar clientes "activos" (con compras recientes) y excluirlos
     * del cálculo de clientes "en riesgo" en los KPIs.
     *
     * @param  string $since Fecha de corte en formato 'Y-m-d' (p.ej. '2024-01-01')
     * @return array         Lista de partner IDs (int) con facturas desde $since
     */
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

    /**
     * Retorna la fecha de la última factura por partner ID.
     *
     * Obtiene todas las facturas de los partners dados y construye un mapa
     * con la fecha más reciente por partner. Se usa en la vista de clientes
     * para mostrar cuándo fue la última compra de cada cliente.
     *
     * @param  array $partnerIds Lista de partner IDs (int)
     * @return array             Mapa [partnerId => 'Y-m-d'] con la fecha de última factura
     */
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

    /**
     * Retorna todos los IDs de clientes empresa, opcionalmente filtrados por ejecutiva.
     *
     * Optimizado para paginación: obtiene solo los IDs primero, luego los detalles
     * por página para no cargar todos los campos de todos los clientes de una vez.
     *
     * @param  string|null $ejecutivaId ID de usuario ejecutiva para filtrar (null o '' = todos)
     * @return array                    Lista de IDs de partners (int)
     */
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

    /**
     * Retorna una página de clientes empresa con nombre y ejecutiva asignada.
     *
     * @param  string|null $ejecutivaId ID de ejecutiva para filtrar (null o '' = todos)
     * @param  int         $limit       Registros por página
     * @param  int         $offset      Desplazamiento para paginación
     * @return array                    Lista de partners con name, user_id, customer_rank
     */
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

    /**
     * Cuenta los clientes empresa, opcionalmente filtrados por ejecutiva.
     *
     * @param  string|null $ejecutivaId ID de ejecutiva para filtrar (null o '' = todos)
     * @return int                      Total de clientes que coinciden con el filtro
     */
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

    /**
     * Cuenta los clientes inactivos (sin factura en el período) candidatos a reasignación.
     *
     * Un cliente es candidato si es empresa, tiene ejecutiva asignada, y NO tiene
     * facturas publicadas en los últimos $days días.
     *
     * @param  int         $days        Días de inactividad para considerar reasignación (default 60)
     * @param  string|null $ejecutivaId ID de ejecutiva para filtrar (null o '' = todos)
     * @return int                      Total de clientes inactivos candidatos
     */
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

    /**
     * Retorna una página de clientes inactivos candidatos a reasignación de ejecutiva.
     *
     * @param  int         $days        Días de inactividad para considerar reasignación (default 60)
     * @param  string|null $ejecutivaId ID de ejecutiva para filtrar (null o '' = todos)
     * @param  int         $limit       Registros por página
     * @param  int         $offset      Desplazamiento para paginación
     * @return array                    Lista de partners con name, user_id, customer_rank
     */
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

    /**
     * Retorna la primera página (50 registros) de clientes candidatos a reasignación.
     *
     * Método de conveniencia que envuelve getClientsForReassignPaginated() con los
     * valores por defecto. Útil para exportaciones y vistas sin paginación explícita.
     *
     * @param  int         $days        Días de inactividad (default 60)
     * @param  string|null $ejecutivaId ID de ejecutiva para filtrar (null o '' = todos)
     * @return array                    Primera página de clientes candidatos
     */
    public function getClientsForReassign(int $days = 60, ?string $ejecutivaId = ''): array
    {
        return $this->getClientsForReassignPaginated($days, $ejecutivaId, 50, 0);
    }

    // ── Ejecutivas ────────────────────────────────────────────

    /**
     * Retorna el listado de ejecutivas de venta configuradas en sales.executive_ids.
     *
     * Los IDs de ejecutivas se configuran en config/sales.php para independizarlos
     * del código. Incluye imagen (image_128) para mostrar avatares en la UI.
     *
     * @return array Lista de usuarios ejecutivos con id, name, email, phone, image_128, etc.
     */
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

    /**
     * Obtiene las métricas individuales de una ejecutiva para el período dado.
     *
     * Ejecuta 4 llamadas a Odoo para: leads creados, oportunidades ganadas,
     * cotizaciones en pipeline, y clientes sin actividad programada.
     * La clave de caché incluye las fechas para que cada período tenga su propia entrada.
     *
     * @param  int         $userId   ID del usuario ejecutiva en Odoo
     * @param  string|null $dateFrom Fecha de inicio (null = sin filtro de fecha)
     * @param  string|null $dateTo   Fecha de fin (null = sin filtro de fecha)
     * @return array                 Mapa: leads, won, pipeline, noContact
     */
    public function getMetricsByExecutive(int $userId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        // La clave incluye las fechas para que cada período tenga su propio caché
        $cacheKey = "odoo:metrics:exec:{$userId}:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, self::CACHE_KPI, function () use ($userId, $dateFrom, $dateTo) {

            // Leads — filtro por create_date
            $domainLeads = [['user_id', '=', $userId], ['type', '=', 'lead']];
            if ($dateFrom) $domainLeads[] = ['create_date', '>=', $dateFrom];
            if ($dateTo)   $domainLeads[] = ['create_date', '<=', $dateTo];
            $leads = $this->execute('crm.lead', 'search_count', [$domainLeads]) ?? 0;

            // Ganadas — filtro por date_closed
            $domainWon = [['user_id', '=', $userId], ['stage_id.is_won', '=', true]];
            if ($dateFrom) $domainWon[] = ['date_closed', '>=', $dateFrom];
            if ($dateTo)   $domainWon[] = ['date_closed', '<=', $dateTo];
            $won = $this->execute('crm.lead', 'search_count', [$domainWon]) ?? 0;

            // Pipeline — filtro por date_order
            $domainPipeline = [['user_id', '=', $userId], ['state', 'in', ['draft', 'sent']]];
            if ($dateFrom) $domainPipeline[] = ['date_order', '>=', $dateFrom];
            if ($dateTo)   $domainPipeline[] = ['date_order', '<=', $dateTo];
            $pipeline = $this->execute('sale.order', 'search_count', [$domainPipeline]) ?? 0;

            // Sin contacto — siempre estado actual, sin filtro de fecha
            $noContact = $this->execute('res.partner', 'search_count',
                [[
                    ['user_id',                '=',    $userId],
                    ['customer_rank',          '>',    0],
                    ['activity_date_deadline', '=',    false],
                ]]) ?? 0;

            return compact('leads', 'won', 'pipeline', 'noContact');
        });
    }

    // ── Métricas bulk (1 call por métrica para todos los ejecutivos) ──

    /**
     * Obtiene las métricas de todas las ejecutivas en 4 llamadas a Odoo (en lugar de N*4).
     *
     * Optimización clave: en lugar de llamar getMetricsByExecutive() por cada ejecutiva
     * (que generaría 4*N llamadas a Odoo), hace 4 llamadas bulk con 'user_id in [...]'
     * y agrupa los resultados en PHP con groupBy. El resultado se cachea por el md5 de
     * todos los IDs + fechas para invalidar cuando cambia el conjunto de ejecutivas.
     *
     * @param  array       $userIds  Lista de IDs de usuarios ejecutivos
     * @param  string|null $dateFrom Fecha de inicio del período (null = sin filtro)
     * @param  string|null $dateTo   Fecha de fin del período (null = sin filtro)
     * @return array                 Mapa [userId => ['leads' => N, 'won' => N, 'pipeline' => N, 'noContact' => N]]
     */
    public function getMetricsForAllExecutives(array $userIds, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if (empty($userIds)) return [];

        $cacheKey = 'odoo:metrics:bulk:' . md5(implode(',', $userIds) . $dateFrom . $dateTo);

        return Cache::remember($cacheKey, self::CACHE_KPI, function () use ($userIds, $dateFrom, $dateTo) {
            // Leads — 1 sola llamada para todos los usuarios
            $domainLeads = [['user_id', 'in', $userIds], ['type', '=', 'lead']];
            if ($dateFrom) $domainLeads[] = ['create_date', '>=', $dateFrom];
            if ($dateTo)   $domainLeads[] = ['create_date', '<=', $dateTo];
            $leadsRaw = $this->execute('crm.lead', 'search_read', [$domainLeads], ['fields' => ['user_id'], 'limit' => 0]) ?? [];

            // Ganadas — 1 sola llamada
            $domainWon = [['user_id', 'in', $userIds], ['stage_id.is_won', '=', true]];
            if ($dateFrom) $domainWon[] = ['date_closed', '>=', $dateFrom];
            if ($dateTo)   $domainWon[] = ['date_closed', '<=', $dateTo];
            $wonRaw = $this->execute('crm.lead', 'search_read', [$domainWon], ['fields' => ['user_id'], 'limit' => 0]) ?? [];

            // Pipeline — 1 sola llamada
            $domainPipeline = [['user_id', 'in', $userIds], ['state', 'in', ['draft', 'sent']]];
            if ($dateFrom) $domainPipeline[] = ['date_order', '>=', $dateFrom];
            if ($dateTo)   $domainPipeline[] = ['date_order', '<=', $dateTo];
            $pipelineRaw = $this->execute('sale.order', 'search_read', [$domainPipeline], ['fields' => ['user_id'], 'limit' => 0]) ?? [];

            // Sin contacto — 1 sola llamada
            $noContactRaw = $this->execute('res.partner', 'search_read',
                [[
                    ['user_id',                'in',   $userIds],
                    ['customer_rank',          '>',    0],
                    ['activity_date_deadline', '=',    false],
                ]],
                ['fields' => ['user_id'], 'limit' => 0]
            ) ?? [];

            // Agrupar por user_id en PHP
            $extractUserId = fn($row) => is_array($row['user_id']) ? (int) $row['user_id'][0] : (int) $row['user_id'];

            $leads     = collect($leadsRaw)->groupBy($extractUserId)->map->count();
            $won       = collect($wonRaw)->groupBy($extractUserId)->map->count();
            $pipeline  = collect($pipelineRaw)->groupBy($extractUserId)->map->count();
            $noContact = collect($noContactRaw)->groupBy($extractUserId)->map->count();

            $result = [];
            foreach ($userIds as $id) {
                $result[$id] = [
                    'leads'     => $leads->get($id, 0),
                    'won'       => $won->get($id, 0),
                    'pipeline'  => $pipeline->get($id, 0),
                    'noContact' => $noContact->get($id, 0),
                ];
            }

            return $result;
        });
    }

    // ── Detalle ejecutiva — oportunidades CRM ─────────────────

    /**
     * Obtiene las oportunidades CRM activas de una ejecutiva, ordenadas por probabilidad.
     *
     * @param  int $userId ID de la ejecutiva en Odoo
     * @param  int $limit  Número máximo de oportunidades a retornar (default 20)
     * @return array       Lista de oportunidades con name, partner_id, stage_id,
     *                     expected_revenue, probability, date_deadline
     */
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

    /**
     * Obtiene las actividades recientes programadas para una ejecutiva.
     *
     * Normaliza activity_type_id (que Odoo retorna como [id, nombre]) al campo
     * activity_type (string) para facilitar el acceso en la vista Blade.
     *
     * @param  int $userId ID de la ejecutiva en Odoo
     * @param  int $limit  Número máximo de actividades (default 15)
     * @return array       Lista de actividades con summary, activity_type, date_deadline, res_name
     */
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

    // ── Sync: todos los partners empresa con nombre y número de cuenta ──

    /**
     * Obtiene todos los partners empresa para el proceso de sincronización de clientes.
     *
     * Excluye residenciales y partners cancelados para el módulo de merge de clientes
     * MSP ↔ Odoo. Usa TTL de 30 días porque esta sincronización es poco frecuente.
     * Los campos x_studio_tipo_de_cliente y partner_state son campos personalizados
     * de la instancia Odoo de Ovnicom.
     *
     * @return array Lista de partners con id, complete_name, account_no ordenados por nombre
     */
    public function fetchAllPartnersForSync(): array
    {
        return Cache::remember('odoo:sync:partners', self::CACHE_MONTH, function () {
            return $this->execute('res.partner', 'search_read',
                [[['is_company', '=', true], ['partner_state', '!=', 'cancel'], ['x_studio_tipo_de_cliente', '!=', 'Residencial']]],
                [
                    'fields' => ['complete_name', 'account_no'],
                    'order'  => 'complete_name asc',
                    'limit'  => 0,
                ]
            ) ?? [];
        });
    }

    // ── Invalidar caché ───────────────────────────────────────

    /**
     * Invalida las entradas de caché principales del dashboard de ventas.
     *
     * Útil para el botón "Refrescar datos" en la UI. No invalida el caché de
     * clientes paginados ni el de metrics por ejecutiva (tienen sus propias claves
     * parametrizadas que no se pueden borrar sin iterar todas las combinaciones).
     *
     * @return void
     */
    public function clearCache(): void
    {
        
        $keys = [
            'odoo:kpis',
            'odoo:clients',
            'odoo:executives',
            'odoo:pipeline:chart',
            'odoo:sync:partners',
        ];
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}