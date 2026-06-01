<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Servicio de integración con la API REST de GLPI (Gestión Libre del Parc Informatique).
 *
 * Gestiona el inventario de activos IT: computadoras, equipos de red, impresoras
 * y otros tipos definidos en config/glpi.php (asset_types).
 *
 * Autenticación:
 *   - Doble token: App-Token (identifica la aplicación) + Session-Token (por sesión de usuario).
 *   - La sesión se inicia con initSession() usando el User-Token y se cachea 50 min.
 *   - Si un request recibe HTTP 401 (sesión expirada), el método request() renueva
 *     automáticamente la sesión y reintenta una vez.
 *
 * Caché:
 *   - Session token: 50 min (glpi_session_token)
 *   - Items/inventario: 24 h (glpi_items_{itemtype}_{md5})
 *   - Las claves incluyen un md5 de los parámetros para soportar múltiples rangos/filtros.
 *
 * Dependencias externas:
 *   - GLPI API REST : GLPI_BASE_URL, GLPI_APP_TOKEN, GLPI_USER_TOKEN en .env
 *   - SSL: se deshabilita la verificación (withoutVerifying) por posibles certs auto-firmados.
 *   - Redirects: configurados con CURLOPT_UNRESTRICTED_AUTH para preservar el header
 *     Authorization al seguir redirects HTTP→HTTPS que ocurren en algunas instalaciones GLPI.
 */
class GlpiService
{
    protected string $baseUrl;
    protected string $appToken;
    protected string $userToken;
    protected ?string $sessionToken = null;

    /**
     * Inicializa el servicio leyendo la URL base y los tokens desde config/glpi.php.
     */
    public function __construct()
    {
        $this->baseUrl   = rtrim(config('glpi.base_url') ?? '', '/');
        $this->appToken  = (string) config('glpi.app_token', '');
        $this->userToken = (string) config('glpi.user_token', '');
    }

    /**
     * Inicia una sesión en la API de GLPI y cachea el token resultante 50 minutos.
     *
     * Si ya existe un token en caché (sesión vigente), lo reutiliza sin llamar a la API.
     * CURLOPT_UNRESTRICTED_AUTH es necesario para que el header Authorization se
     * preserve cuando GLPI redirige HTTP → HTTPS internamente.
     *
     * @return string Token de sesión GLPI listo para usar en cabecera Session-Token
     * @throws Exception si la API no puede autenticar con el User-Token configurado
     */
    public function initSession(): string
    {
        if ($cached = Cache::get('glpi_session_token')) {
            $this->sessionToken = $cached;
            return $cached;
        }

        $response = Http::withoutVerifying()
            ->withOptions([
                'allow_redirects' => [
                    'max'             => 5,
                    'protocols'       => ['http', 'https'],
                    'strict'          => true,   // keep method (GET stays GET)
                    'referer'         => false,
                    'track_redirects' => false,
                ],
                'curl' => [
                    CURLOPT_UNRESTRICTED_AUTH => true, // preserve Authorization on redirect
                ],
            ])
            ->withHeaders([
                'Authorization' => 'user_token ' . $this->userToken,
                'App-Token'     => $this->appToken,
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
     * Cierra la sesión activa en GLPI e invalida el token en caché.
     *
     * Si no hay token en caché (sesión ya cerrada), retorna sin hacer nada.
     * Los errores de la llamada HTTP se ignoran — el objetivo es liberar el token local.
     *
     * @return void
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

    /**
     * Retorna las entidades GLPI accesibles para el usuario autenticado.
     *
     * @return array Lista de entidades con id y name
     * @throws Exception si la sesión expiró y no se pudo renovar
     */
    public function getMyEntities(): array
    {
        return $this->get('/getMyEntities');
    }

    /**
     * Retorna las entidades GLPI actualmente activas en la sesión.
     *
     * @return array Entidad activa con is_recursive y otros metadatos
     * @throws Exception si la sesión expiró y no se pudo renovar
     */
    public function getActiveEntities(): array
    {
        return $this->get('/getActiveEntities');
    }

    /** TTL de caché para el inventario de items: 24 horas. */
    private const ITEMS_CACHE_TTL = 86400;

    /**
     * Obtiene items de un tipo de activo GLPI con paginación y opciones configurables.
     *
     * El resultado se cachea 24 h usando una clave que incluye el md5 de todos los
     * parámetros — así distintas combinaciones de range/sort/dropdowns tienen su
     * propia entrada de caché sin colisiones.
     *
     * @param  string $itemtype Tipo de item GLPI (p.ej. 'Computer', 'NetworkEquipment', 'Printer')
     * @param  array  $params   Parámetros opcionales que sobreescriben los defaults:
     *                           - range: '0-49' (paginación, p.ej. '0-499')
     *                           - sort: campo de ordenamiento (default 'name')
     *                           - order: 'ASC'|'DESC'
     *                           - expand_dropdowns: bool (resuelve IDs a nombres)
     *                           - get_hateoas: bool
     * @return array            Mapa ['items' => [...], 'total' => int]
     * @throws Exception        si la API GLPI retorna error
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
        $cacheKey = 'glpi_items_' . $itemtype . '_' . md5(serialize($query));

        return Cache::remember($cacheKey, self::ITEMS_CACHE_TTL, function () use ($itemtype, $query) {
            $response = $this->request('GET', "/{$itemtype}", $query);

            return [
                'items' => $response['data'] ?? [],
                'total' => $response['total'] ?? 0,
            ];
        });
    }

    /**
     * Pre-carga en caché los datos de inventario GLPI para todos los tipos de activo.
     *
     * Diseñado para ejecutarse via el comando artisan glpi:warm-cache cada 30 minutos.
     * Pre-carga tres conjuntos de datos por tipo:
     *   1. Conteo rápido (range 0-0) para el index/dashboard
     *   2. Lista completa de NetworkEquipment (range 0-4999, el tipo con más registros)
     *   3. Lista paginada de cada tipo (range 0-499) para las vistas de listado
     *
     * @return void
     */
    public function warmCache(): void
    {
        $this->initSession();

        $assetTypes = array_keys(config('glpi.asset_types', []));

        // Para el index: conteo de cada tipo (range 0-0)
        foreach ($assetTypes as $type) {
            $this->getAllItems($type, ['range' => '0-0']);
        }

        // Para el index NetworkEquipment: lista completa con dropdowns
        $this->getAllItems('NetworkEquipment', [
            'range'            => '0-4999',
            'expand_dropdowns' => true,
            'get_hateoas'      => false,
        ]);

        // Para la vista de items de cada tipo (range 0-499)
        foreach ($assetTypes as $type) {
            $this->getAllItems($type, [
                'range'            => '0-499',
                'expand_dropdowns' => true,
                'get_hateoas'      => false,
            ]);
        }
    }

    /**
     * Invalida las entradas de caché conocidas para un tipo de activo.
     *
     * Elimina las claves generadas con los conjuntos de parámetros estándar
     * que usa warmCache() y la UI. Para NetworkEquipment incluye también
     * el rango 0-4999 que se usa solo para ese tipo.
     *
     * @param  string $itemtype Tipo de item GLPI cuyas entradas de caché se eliminarán
     * @return void
     */
    public function forgetItemsCache(string $itemtype): void
    {
        // Borra todas las entradas conocidas para el tipo dado
        $paramSets = [
            ['range' => '0-0'],
            ['range' => '0-499', 'expand_dropdowns' => true, 'get_hateoas' => false],
        ];

        if ($itemtype === 'NetworkEquipment') {
            $paramSets[] = ['range' => '0-4999', 'expand_dropdowns' => true, 'get_hateoas' => false];
        }

        $defaults = ['range' => '0-49', 'sort' => 'name', 'order' => 'ASC', 'expand_dropdowns' => true, 'get_hateoas' => false];

        foreach ($paramSets as $params) {
            $query = array_merge($defaults, $params);
            Cache::forget('glpi_items_' . $itemtype . '_' . md5(serialize($query)));
        }
    }

    /**
     * Obtiene el detalle completo de un activo individual.
     *
     * @param  string $itemtype Tipo de item GLPI (p.ej. 'Computer')
     * @param  int    $id       ID numérico del item en GLPI
     * @return array            Todos los campos del item con dropdowns expandidos
     * @throws Exception        si el item no existe o la sesión expiró
     */
    public function getItem(string $itemtype, int $id): array
    {
        return $this->get("/{$itemtype}/{$id}", [
            'expand_dropdowns' => true,
            'get_hateoas'      => false,
        ]);
    }

    /**
     * Retorna las opciones de búsqueda disponibles para un tipo de item.
     *
     * Devuelve el mapa de IDs de campo → metadatos que necesita searchItems()
     * para construir criterios de búsqueda tipados.
     *
     * @param  string $itemtype Tipo de item GLPI
     * @return array            Mapa de opciones de búsqueda indexado por ID numérico de campo
     * @throws Exception        si la sesión expiró y no se pudo renovar
     */
    public function getSearchOptions(string $itemtype): array
    {
        return $this->get("/listSearchOptions/{$itemtype}");
    }

    /**
     * Ejecuta una búsqueda avanzada de items usando el motor de búsqueda de GLPI.
     *
     * El endpoint /search/{itemtype} de GLPI usa IDs numéricos de campos en lugar de nombres
     * — usar getSearchOptions() para descubrir los IDs disponibles por tipo.
     *
     * @param  string $itemtype  Tipo de item (default 'AllAssets' para todos los tipos)
     * @param  array  $criteria  Criterios de búsqueda, cada uno con 'field' (int), 'searchtype' y 'value'
     * @param  array  $params    Parámetros adicionales: range, sort, order, forcedisplay
     * @return array             Mapa ['items' => [...], 'total' => int, 'count' => int]
     * @throws Exception         si la API GLPI retorna error
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

    /**
     * Crea un nuevo item en GLPI.
     *
     * La API GLPI requiere que los datos se envíen bajo la clave 'input'.
     *
     * @param  string $itemtype Tipo de item a crear
     * @param  array  $data     Campos del nuevo item (ver documentación GLPI por tipo)
     * @return array            Respuesta de la API con el ID del item creado y estado
     * @throws Exception        si la validación de GLPI falla o la sesión expiró
     */
    public function addItem(string $itemtype, array $data): array
    {
        return $this->post("/{$itemtype}", ['input' => $data]);
    }

    /**
     * Actualiza un item existente en GLPI.
     *
     * La API GLPI requiere que los datos se envíen bajo la clave 'input'.
     *
     * @param  string $itemtype Tipo de item a actualizar
     * @param  int    $id       ID numérico del item en GLPI
     * @param  array  $data     Campos a actualizar (no es necesario enviar todos)
     * @return array            Respuesta de la API con confirmación de actualización
     * @throws Exception        si el item no existe, la validación falla o la sesión expiró
     */
    public function updateItem(string $itemtype, int $id, array $data): array
    {
        return $this->put("/{$itemtype}/{$id}", ['input' => $data]);
    }

    /**
     * Construye el array de cabeceras HTTP para autenticación GLPI.
     *
     * @param  string|null $token Token de sesión; si es null se obtiene via getSessionToken()
     * @return array              Cabeceras App-Token, Session-Token y Content-Type
     */
    protected function headers(?string $token = null): array
    {
        return [
            'App-Token'     => $this->appToken,
            'Session-Token' => $token ?? $this->getSessionToken(),
            'Content-Type'  => 'application/json',
        ];
    }

    /**
     * Retorna el token de sesión activo, iniciando una nueva sesión si es necesario.
     *
     * @return string Token de sesión GLPI vigente
     * @throws Exception si no se puede iniciar sesión
     */
    protected function getSessionToken(): string
    {
        if ($this->sessionToken) return $this->sessionToken;
        return $this->initSession();
    }

    /**
     * Ejecuta una petición GET y retorna solo el array de datos (sin metadata).
     *
     * @param  string $endpoint Ruta relativa al baseUrl
     * @param  array  $query    Parámetros de query string
     * @return array            Array de datos del campo 'data' de la respuesta
     * @throws Exception        si la API retorna error
     */
    protected function get(string $endpoint, array $query = []): array
    {
        $response = $this->request('GET', $endpoint, $query);
        return $response['data'] ?? [];
    }

    /**
     * Ejecuta una petición POST y retorna el array de datos de la respuesta.
     *
     * @param  string $endpoint Ruta relativa al baseUrl
     * @param  array  $body     Cuerpo JSON de la petición
     * @return array            Array de datos del campo 'data' de la respuesta
     * @throws Exception        si la API retorna error
     */
    protected function post(string $endpoint, array $body = []): array
    {
        $response = $this->request('POST', $endpoint, [], $body);
        return $response['data'] ?? [];
    }

    /**
     * Ejecuta una petición PUT y retorna el array de datos de la respuesta.
     *
     * @param  string $endpoint Ruta relativa al baseUrl
     * @param  array  $body     Cuerpo JSON de la petición
     * @return array            Array de datos del campo 'data' de la respuesta
     * @throws Exception        si la API retorna error
     */
    protected function put(string $endpoint, array $body = []): array
    {
        $response = $this->request('PUT', $endpoint, [], $body);
        return $response['data'] ?? [];
    }

    /**
     * Ejecuta una petición HTTP a la API GLPI con renovación automática de sesión.
     *
     * Si la API retorna HTTP 401 (sesión expirada), invalida el token en caché,
     * inicia una nueva sesión y reintenta exactamente una vez ($retry = false en el
     * segundo intento para evitar bucles infinitos).
     * El total de items disponibles se extrae del header Content-Range (formato "0-49/total").
     *
     * @param  string $method   Método HTTP: 'GET', 'POST' o 'PUT'
     * @param  string $endpoint Ruta relativa al baseUrl
     * @param  array  $query    Parámetros de query string (solo GET)
     * @param  array  $body     Cuerpo JSON (POST/PUT)
     * @param  bool   $retry    Si true, permite un reintento tras 401 (default true)
     * @return array            Mapa ['data' => mixed, 'total' => int]
     * @throws Exception        si el método no es soportado, o si la API retorna error tras el reintento
     */
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
