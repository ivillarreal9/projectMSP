<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class GlpiService
{
    protected string $baseUrl;
    protected string $appToken;
    protected string $userToken;
    protected ?string $sessionToken = null;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('glpi.base_url'), '/');
        $this->appToken  = config('glpi.app_token');
        $this->userToken = config('glpi.user_token');
    }

    // ─────────────────────────────────────────────
    // SESSION
    // ─────────────────────────────────────────────

    /**
     * curl: GET /initSession
     * Inicia sesión y cachea el token por 50 minutos.
     */
    public function initSession(): string
    {
        if ($cached = Cache::get('glpi_session_token')) {
            $this->sessionToken = $cached;
            return $cached;
        }

        $response = Http::withHeaders([
            'Authorization' => "user_token {$this->userToken}",
            'App-Token'     => $this->appToken,
            'Content-Type'  => 'application/json',
        ])->get("{$this->baseUrl}/initSession");

        if ($response->failed()) {
            throw new Exception('GLPI: No se pudo iniciar sesión. ' . $response->body());
        }

        $token = $response->json('session_token');
        Cache::put('glpi_session_token', $token, now()->addMinutes(50));
        $this->sessionToken = $token;

        return $token;
    }

    /**
     * curl: GET /killSession
     * Mata la sesión activa.
     */
    public function killSession(): void
    {
        $token = Cache::get('glpi_session_token');
        if (!$token) return;

        Http::withHeaders([
            'App-Token'     => $this->appToken,
            'Session-Token' => $token,
            'Content-Type'  => 'application/json',
        ])->get("{$this->baseUrl}/killSession");

        Cache::forget('glpi_session_token');
        $this->sessionToken = null;
    }

    // ─────────────────────────────────────────────
    // ENTITIES
    // ─────────────────────────────────────────────

    /**
     * curl: GET /getMyEntities
     * Devuelve las entidades del usuario autenticado.
     */
    public function getMyEntities(): array
    {
        return $this->get('/getMyEntities');
    }

    /**
     * curl: GET /getActiveEntities
     * Devuelve las entidades activas en la sesión actual.
     */
    public function getActiveEntities(): array
    {
        return $this->get('/getActiveEntities');
    }

    // ─────────────────────────────────────────────
    // ITEMS (ACTIVOS)
    // ─────────────────────────────────────────────

    /**
     * curl: GET /{itemtype}
     * Ej: GET /phone  → getAllItems('Phone')
     *
     * Lista todos los items de un tipo con paginación.
     */
    public function getAllItems(string $itemtype, array $params = []): array
    {
        $defaults = [
            'range'            => '0-49',
            'sort'             => 'name',
            'order'            => 'ASC',
            'expand_dropdowns' => true,
            'get_hateoas'      => false,
        ];

        $query    = array_merge($defaults, $params);
        $response = $this->request('GET', "/{$itemtype}", $query);

        return [
            'items' => $response['data'] ?? [],
            'total' => $response['total'] ?? 0,
        ];
    }

    /**
     * curl: GET /{itemtype}/{id}
     * Ej: GET /networkequipment/1660
     *
     * Obtiene un item específico por ID.
     */
    public function getItem(string $itemtype, int $id): array
    {
        return $this->get("/{$itemtype}/{$id}", [
            'expand_dropdowns' => true,
            'get_hateoas'      => false,
        ]);
    }

    /**
     * curl: GET /listSearchOptions/{itemtype}
     * Ej: GET /listSearchOptions/networkequipment
     *
     * Devuelve los campos disponibles para búsqueda de un tipo.
     */
    public function getSearchOptions(string $itemtype): array
    {
        return $this->get("/listSearchOptions/{$itemtype}");
    }

    /**
     * curl: GET /search/AllAssets/?criteria[0][field]=31&...
     *
     * Búsqueda avanzada. Soporta 'AllAssets' y cualquier itemtype.
     *
     * Ejemplo de $criteria:
     * [
     *   ['field' => 31, 'searchtype' => 'contains', 'value' => 'En De'],
     *   ['field' => 49, 'searchtype' => 'contains', 'value' => 'Servicios Especiales'],
     * ]
     */
    public function searchItems(string $itemtype = 'AllAssets', array $criteria = [], array $params = []): array
    {
        $defaults = [
            'range'        => '0-49',
            'sort'         => 1,
            'order'        => 'ASC',
            'forcedisplay' => [1, 2, 3, 4, 5, 31, 45, 46],
        ];

        $query = array_merge($defaults, $params);

        // Construir criteria como query params planos
        // Ej: criteria[0][field]=31&criteria[0][searchtype]=contains&criteria[0][value]=En De
        foreach ($criteria as $i => $criterion) {
            $query["criteria[{$i}][field]"]      = $criterion['field'];
            $query["criteria[{$i}][searchtype]"] = $criterion['searchtype'] ?? 'contains';
            $query["criteria[{$i}][value]"]      = $criterion['value'];
        }

        $response = $this->request('GET', "/search/{$itemtype}", $query);

        return [
            'items' => $response['data']['data']       ?? [],
            'total' => $response['data']['totalcount'] ?? 0,
            'count' => $response['data']['count']      ?? 0,
        ];
    }

    // ─────────────────────────────────────────────
    // WRITE (POST / PUT)
    // ─────────────────────────────────────────────

    /**
     * curl: POST /{itemtype}  (Add Items)
     * Crea un nuevo item.
     */
    public function addItem(string $itemtype, array $data): array
    {
        return $this->post("/{$itemtype}", ['input' => $data]);
    }

    /**
     * curl: PUT /{itemtype}/{id}  (Update Items)
     * Actualiza un item existente.
     */
    public function updateItem(string $itemtype, int $id, array $data): array
    {
        return $this->put("/{$itemtype}/{$id}", ['input' => $data]);
    }

    // ─────────────────────────────────────────────
    // HELPERS INTERNOS
    // ─────────────────────────────────────────────

    protected function headers(?string $token = null): array
    {
        return [
            'App-Token'     => $this->appToken,
            'Session-Token' => $token ?? $this->getSessionToken(),
            'Content-Type'  => 'application/json',
        ];
    }

    protected function getSessionToken(): string
    {
        if ($this->sessionToken) return $this->sessionToken;
        return $this->initSession();
    }

    protected function get(string $endpoint, array $query = []): array
    {
        $response = $this->request('GET', $endpoint, $query);
        return $response['data'] ?? [];
    }

    protected function post(string $endpoint, array $body = []): array
    {
        $response = $this->request('POST', $endpoint, [], $body);
        return $response['data'] ?? [];
    }

    protected function put(string $endpoint, array $body = []): array
    {
        $response = $this->request('PUT', $endpoint, [], $body);
        return $response['data'] ?? [];
    }

    /**
     * Ejecuta la request HTTP.
     * Reintenta una vez automáticamente si el token expiró (401).
     */
    protected function request(
        string $method,
        string $endpoint,
        array  $query = [],
        array  $body  = [],
        bool   $retry = true
    ): array {
        $token = $this->getSessionToken();

        $http = Http::withHeaders($this->headers($token));
        $url  = $this->baseUrl . $endpoint;

        $response = match (strtoupper($method)) {
            'GET'  => $http->get($url, $query),
            'POST' => $http->post($url, $body),
            'PUT'  => $http->put($url, $body),
            default => throw new Exception("Método HTTP no soportado: {$method}"),
        };

        // Token expirado → refrescar y reintentar una vez
        if ($response->status() === 401 && $retry) {
            Cache::forget('glpi_session_token');
            $this->sessionToken = null;
            return $this->request($method, $endpoint, $query, $body, false);
        }

        if ($response->failed()) {
            throw new Exception(
                "GLPI API error [{$response->status()}] {$endpoint}: " . $response->body()
            );
        }

        // Extraer total del header Content-Range si existe (formato: "0-49/150")
        $total = 0;
        if ($contentRange = $response->header('Content-Range')) {
            if (preg_match('/\/(\d+)$/', $contentRange, $m)) {
                $total = (int) $m[1];
            }
        }

        return [
            'data'  => $response->json(),
            'total' => $total,
        ];
    }
}