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

    public function initSession(): string
    {
        if ($cached = Cache::get('glpi_session_token')) {
            $this->sessionToken = $cached;
            return $cached;
        }

        $response = Http::withoutVerifying()->withHeaders([
            
            'App-Token'     => $this->appToken,
            'Content-Type'  => 'application/json',
        ])->get("{$this->baseUrl}/initSession", [
            'user_token' => $this->userToken,
        ]);

        if ($response->failed()) {
            throw new Exception('GLPI: No se pudo iniciar sesión. ' . $response->body());
        }

        $token = $response->json('session_token');
        Cache::put('glpi_session_token', $token, now()->addMinutes(50));
        $this->sessionToken = $token;

        return $token;
    }

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

    public function getMyEntities(): array
    {
        return $this->get('/getMyEntities');
    }

    public function getActiveEntities(): array
    {
        return $this->get('/getActiveEntities');
    }

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

    public function getItem(string $itemtype, int $id): array
    {
        return $this->get("/{$itemtype}/{$id}", [
            'expand_dropdowns' => true,
            'get_hateoas'      => false,
        ]);
    }

    public function getSearchOptions(string $itemtype): array
    {
        return $this->get("/listSearchOptions/{$itemtype}");
    }

    public function searchItems(string $itemtype = 'AllAssets', array $criteria = [], array $params = []): array
    {
        $defaults = [
            'range'        => '0-49',
            'sort'         => 1,
            'order'        => 'ASC',
            'forcedisplay' => [1, 2, 3, 4, 5, 31, 45, 46],
        ];

        $query = array_merge($defaults, $params);

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

    public function addItem(string $itemtype, array $data): array
    {
        return $this->post("/{$itemtype}", ['input' => $data]);
    }

    public function updateItem(string $itemtype, int $id, array $data): array
    {
        return $this->put("/{$itemtype}/{$id}", ['input' => $data]);
    }

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

    protected function request(string $method, string $endpoint, array $query = [], array $body = [], bool $retry = true): array
    {
        $token = $this->getSessionToken();
        $http  = Http::withoutVerifying()->withHeaders($this->headers($token));
        $url   = $this->baseUrl . $endpoint;

        $response = match (strtoupper($method)) {
            'GET'  => $http->get($url, $query),
            'POST' => $http->post($url, $body),
            'PUT'  => $http->put($url, $body),
            default => throw new Exception("Método HTTP no soportado: {$method}"),
        };

        if ($response->status() === 401 && $retry) {
            Cache::forget('glpi_session_token');
            $this->sessionToken = null;
            return $this->request($method, $endpoint, $query, $body, false);
        }

        if ($response->failed()) {
            throw new Exception("GLPI API error [{$response->status()}] {$endpoint}: " . $response->body());
        }

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
