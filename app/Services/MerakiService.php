<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio de integración con la API REST de Cisco Meraki Dashboard.
 *
 * Cubre las siguientes áreas funcionales:
 *   - Organizaciones y redes
 *   - Dispositivos (inventario, estados online/offline, clientes conectados)
 *   - Switches (puertos, configuración)
 *   - Uplinks de appliances MX/MG
 *   - Wireless SSIDs
 *   - Eventos y alertas de salud de red
 *   - Licencias (per-device y co-termination)
 *   - Caché global de todos los dispositivos (compartida entre usuarios)
 *
 * Estrategia de caché:
 *   - Datos estáticos (inventario, redes, licencias): 24 h – 48 h
 *   - Datos de estado (online/offline, uplinks, alertas): caché flexible
 *     (stale-while-revalidate): frescos 3–5 min, servibles hasta 15–30 min
 *     mientras se regeneran en segundo plano sin bloquear al usuario.
 *   - El caché global meraki_all_devices_global es compartido (no por usuario)
 *     para que una sola recarga de la API beneficie a todos los usuarios activos.
 *
 * Resiliencia HTTP:
 *   - Reintentos automáticos ante 429 (honrando Retry-After) y 5xx.
 *   - Paginación por cabecera Link en endpoints de listado (perPage=1000).
 *
 * Dependencias externas:
 *   - Cisco Meraki API v1  : MERAKI_BASE_URL + MERAKI_API_KEY en .env
 *   - Cabecera de auth     : X-Cisco-Meraki-API-Key
 */
class MerakiService
{
    protected string $baseUrl;
    protected string $apiKey;

    /**
     * Inicializa el servicio leyendo la URL base y la API Key desde config/meraki.php.
     */
    public function __construct()
    {
        $this->baseUrl = rtrim(config('meraki.base_url') ?? '', '/');
        $this->apiKey  = (string) config('meraki.api_key', '');
    }

    // ─── Organizations ────────────────────────────────────────────────────────

    /**
     * Retorna todas las organizaciones Meraki accesibles con la API Key configurada.
     *
     * @return array Lista de organizaciones con id, name, url y otros metadatos
     * @throws Exception si la API Key no está configurada o la API retorna error
     */
    public function getOrganizations(): array
    {
        return Cache::remember('meraki_organizations', now()->addHours(24), fn () =>
            $this->get('/organizations')
        );
    }

    /**
     * Retorna los datos de una organización específica por su ID.
     *
     * @param  string $orgId ID de la organización Meraki
     * @return array         Datos de la organización (id, name, url, api, licensing, cloud)
     * @throws Exception     si la API retorna error o la org no existe
     */
    public function getOrganization(string $orgId): array
    {
        return Cache::remember("meraki_org_{$orgId}", now()->addHours(24), fn () =>
            $this->get("/organizations/{$orgId}")
        );
    }

    // ─── Networks ─────────────────────────────────────────────────────────────

    /**
     * Lista todas las redes de una organización.
     *
     * @param  string $orgId ID de la organización
     * @return array         Lista de redes con id, name, type, timeZone, etc.
     * @throws Exception     si la API retorna error
     */
    public function getNetworks(string $orgId): array
    {
        return Cache::remember("meraki_networks_{$orgId}", now()->addHours(24), fn () =>
            $this->getAllPages("/organizations/{$orgId}/networks")
        );
    }

    /**
     * Retorna los detalles de una red específica.
     *
     * @param  string $networkId ID de la red Meraki
     * @return array             Detalles de la red (id, name, organizationId, type, etc.)
     * @throws Exception         si la API retorna error o la red no existe
     */
    public function getNetwork(string $networkId): array
    {
        return Cache::remember("meraki_network_{$networkId}", now()->addHours(24), fn () =>
            $this->get("/networks/{$networkId}")
        );
    }

    // ─── Devices ──────────────────────────────────────────────────────────────

    /**
     * Retorna todos los dispositivos del inventario de una organización.
     *
     * @param  string $orgId ID de la organización
     * @return array         Lista de dispositivos con serial, model, name, networkId, etc.
     * @throws Exception     si la API retorna error
     */
    public function getDevices(string $orgId): array
    {
        return Cache::remember("meraki_devices_{$orgId}", now()->addHours(24), fn () =>
            $this->getAllPages("/organizations/{$orgId}/devices")
        );
    }

    /**
     * Retorna el estado online/offline/alerting de cada dispositivo de la organización.
     *
     * Caché flexible (stale-while-revalidate): fresco 5 min, servible hasta 30 min
     * mientras se regenera en segundo plano — el usuario nunca espera a la API.
     *
     * @param  string $orgId ID de la organización
     * @return array         Lista de estados con serial, status, lastReportedAt, etc.
     * @throws Exception     si la API retorna error
     */
    public function getDeviceStatuses(string $orgId): array
    {
        return Cache::flexible("meraki_device_statuses_{$orgId}", [300, 1800], fn () =>
            $this->getAllPages("/organizations/{$orgId}/devices/statuses")
        );
    }

    /**
     * Retorna el conteo agregado de dispositivos por estado (online/offline/alerting).
     *
     * Más barato que getDeviceStatuses() — útil para mostrar totales en el dashboard
     * sin necesidad de iterar sobre todos los dispositivos en PHP.
     *
     * @param  string $orgId ID de la organización
     * @return array         Totales: {'counts': {'online': N, 'offline': N, 'alerting': N}}
     * @throws Exception     si la API retorna error
     */
    public function getDeviceStatusesOverview(string $orgId): array
    {
        return Cache::flexible("meraki_device_statuses_overview_{$orgId}", [300, 1800], fn () =>
            $this->get("/organizations/{$orgId}/devices/statuses/overview")
        );
    }

    /**
     * Lista los dispositivos físicamente asociados a una red específica.
     *
     * @param  string $networkId ID de la red Meraki
     * @return array             Lista de dispositivos de la red
     * @throws Exception         si la API retorna error
     */
    public function getNetworkDevices(string $networkId): array
    {
        return Cache::remember("meraki_network_devices_{$networkId}", now()->addHours(24), fn () =>
            $this->get("/networks/{$networkId}/devices")
        );
    }

    /**
     * Lista los clientes conectados a un dispositivo específico en el período dado.
     *
     * @param  string $serial   Número de serie del dispositivo Meraki
     * @param  int    $timespan Ventana temporal en segundos (default 86400 = 24 h)
     * @return array            Lista de clientes con mac, ip, description, usage, etc.
     * @throws Exception        si la API retorna error
     */
    public function getDeviceClients(string $serial, int $timespan = 86400): array
    {
        return Cache::remember("meraki_device_clients_{$serial}_{$timespan}", now()->addMinutes(3), fn () =>
            $this->get("/devices/{$serial}/clients?timespan={$timespan}")
        );
    }

    /**
     * Retorna el estado de cada puerto de un switch Meraki en el período dado.
     *
     * TTL corto (2 min) porque los estados de puerto cambian con cada conexión/desconexión.
     *
     * @param  string $serial   Número de serie del switch
     * @param  int    $timespan Ventana temporal en segundos (default 86400 = 24 h)
     * @return array            Lista de estados de puerto con portId, enabled, status, etc.
     * @throws Exception        si la API retorna error
     */
    public function getSwitchPortStatuses(string $serial, int $timespan = 86400): array
    {
        return Cache::remember("meraki_switch_port_statuses_{$serial}", now()->addMinutes(2), fn () =>
            $this->get("/devices/{$serial}/switch/ports/statuses?timespan={$timespan}")
        );
    }

    /**
     * Retorna la configuración de todos los puertos de un switch.
     *
     * Datos estáticos (VLAN, nombre, tipo) — TTL largo de 24 h.
     *
     * @param  string $serial Número de serie del switch
     * @return array          Lista de configuraciones de puerto con portId, name, vlan, type, etc.
     * @throws Exception      si la API retorna error
     */
    public function getSwitchPorts(string $serial): array
    {
        return Cache::remember("meraki_switch_ports_{$serial}", now()->addHours(24), fn () =>
            $this->get("/devices/{$serial}/switch/ports")
        );
    }

    // ─── Uplinks ──────────────────────────────────────────────────────────────

    /**
     * Retorna el estado de los uplinks WAN de los appliances MX y MG de la organización.
     *
     * Incluye estado de cada interface (active/ready/failed), IP, gateway y proveedor.
     * TTL corto (5 min) porque refleja conectividad WAN en tiempo casi real.
     *
     * @param  string $orgId ID de la organización
     * @return array         Lista de uplinks por dispositivo con serial, networkId, uplinks[]
     * @throws Exception     si la API retorna error
     */
    public function getUplinkStatuses(string $orgId): array
    {
        return Cache::flexible("meraki_uplink_statuses_{$orgId}", [300, 1800], fn () =>
            $this->getAllPages("/organizations/{$orgId}/uplinks/statuses")
        );
    }

    // ─── Clients ──────────────────────────────────────────────────────────────

    /**
     * Lista todos los clientes vistos en una red durante el período indicado.
     *
     * Se solicitan hasta 1 000 clientes por página (perPage=1000). Si la red
     * tiene más clientes activos, se devolverán solo los primeros 1 000.
     *
     * @param  string $networkId ID de la red
     * @param  int    $timespan  Ventana en segundos (default 86400 = 24 h)
     * @return array             Lista de clientes con mac, ip, description, usage, etc.
     * @throws Exception         si la API retorna error
     */
    public function getNetworkClients(string $networkId, int $timespan = 86400): array
    {
        return Cache::remember("meraki_network_clients_{$networkId}_{$timespan}", now()->addMinutes(3), fn () =>
            $this->getAllPages("/networks/{$networkId}/clients?timespan={$timespan}")
        );
    }

    /**
     * Retorna el resumen agregado de clientes de una red (totales y uso).
     *
     * Más eficiente que getNetworkClients() para mostrar solo el conteo de clientes.
     *
     * @param  string $networkId ID de la red
     * @return array             Totales: counts (online, offline) + usage (sent, recv)
     * @throws Exception         si la API retorna error
     */
    public function getNetworkClientsOverview(string $networkId): array
    {
        return Cache::flexible("meraki_network_clients_overview_{$networkId}", [180, 900], fn () =>
            $this->get("/networks/{$networkId}/clients/overview")
        );
    }

    // ─── Wireless ─────────────────────────────────────────────────────────────

    /**
     * Lista los SSIDs configurados en una red inalámbrica Meraki.
     *
     * Meraki siempre devuelve 15 SSIDs (habilitados y deshabilitados).
     * La vista filtra los que tienen enabled=true para mostrar solo los activos.
     *
     * @param  string $networkId ID de la red inalámbrica
     * @return array             Lista de 15 SSIDs con number, name, enabled, authMode, etc.
     * @throws Exception         si la API retorna error
     */
    public function getWirelessSsids(string $networkId): array
    {
        return Cache::remember("meraki_wireless_ssids_{$networkId}", now()->addHours(24), fn () =>
            $this->get("/networks/{$networkId}/wireless/ssids")
        );
    }

    // ─── Events & Alerts ──────────────────────────────────────────────────────

    /**
     * Retorna los eventos recientes de una red (conexiones, desconexiones, errores, etc.).
     *
     * @param  string $networkId ID de la red
     * @param  int    $perPage   Número de eventos a retornar (default 50)
     * @return array             Estructura con events[] y pageStartAt
     * @throws Exception         si la API retorna error
     */
    public function getNetworkEvents(string $networkId, int $perPage = 50): array
    {
        return Cache::flexible("meraki_network_events_{$networkId}_{$perPage}", [300, 1800], fn () =>
            $this->get("/networks/{$networkId}/events?perPage={$perPage}")
        );
    }

    /**
     * Retorna las alertas de salud activas de una red.
     *
     * Las alertas incluyen dispositivos con alta latencia, pérdida de paquetes,
     * interfaces degradadas, etc. TTL corto (5 min) para mantener relevancia.
     *
     * @param  string $networkId ID de la red
     * @return array             Lista de alertas con type, severity, scope, etc.
     * @throws Exception         si la API retorna error
     */
    public function getNetworkHealthAlerts(string $networkId): array
    {
        return Cache::flexible("meraki_health_alerts_{$networkId}", [300, 1800], fn () =>
            $this->get("/networks/{$networkId}/health/alerts")
        );
    }

    // ─── Licensing ────────────────────────────────────────────────────────────

    /**
     * Retorna el resumen de licenciamiento de la organización.
     *
     * Incluye estado (OK/License Required), fecha de expiración y conteos por producto.
     * TTL largo (48 h) porque las licencias cambian raramente.
     *
     * @param  string $orgId ID de la organización
     * @return array         Resumen con status, expirationDate, licensedDeviceCounts, etc.
     * @throws Exception     si la API retorna error
     */
    public function getLicensesOverview(string $orgId): array
    {
        return Cache::remember("meraki_licenses_overview_{$orgId}", now()->addHours(48), fn () =>
            $this->get("/organizations/{$orgId}/licenses/overview")
        );
    }

    /**
     * Retorna la lista completa de licencias per-device de una organización.
     *
     * Las organizaciones con modelo co-termination no tienen licencias individuales —
     * la API retorna HTTP 400. En ese caso se captura la excepción, se registra
     * en el log como info (no error) y se devuelve [] para no bloquear el flujo.
     *
     * @param  string $orgId ID de la organización
     * @return array         Lista de licencias con licenseType, licenseKey, deviceSerial, etc.
     *                       Puede ser [] si la org usa co-termination licensing.
     * @throws Exception     si el error de la API NO es por co-termination
     */
    public function getLicenses(string $orgId): array
    {
        return Cache::remember("meraki_licenses_{$orgId}", now()->addHours(48), function () use ($orgId) {
            try {
                return $this->getAllPages("/organizations/{$orgId}/licenses");
            } catch (Exception $e) {
                // 400 = org usa co-termination, no per-device licensing → sin licencias individuales
                if (str_contains($e->getMessage(), '400') || str_contains($e->getMessage(), 'per-device')) {
                    Log::info("Meraki org [{$orgId}] usa co-termination licensing, se omiten licencias individuales.");
                    return [];
                }
                throw $e;
            }
        });
    }

    // ─── Aggregated (all orgs) ────────────────────────────────────────────────

    /**
     * Retorna todos los dispositivos de todas las organizaciones con su estado adjunto.
     *
     * Combina getDevices() + getDeviceStatuses() por organización y agrega a cada
     * dispositivo las claves _status, _orgName y _orgId para facilitar la vista.
     * El caché global (meraki_all_devices_global) es compartido entre todos los usuarios
     * — si un usuario refresca, todos se benefician del dato actualizado.
     * Los errores por org se logean como warnings y no detienen el proceso.
     *
     * @return array Lista completa de dispositivos de todas las orgs con estado incrustado
     */
    public function getAllDevicesWithStatuses(): array
    {
        return Cache::remember('meraki_all_devices_global', now()->addHours(24), function () {
            $organizations = $this->getOrganizations();
            $all = [];

            foreach ($organizations as $org) {
                try {
                    $devices   = $this->getDevices($org['id']);
                    $statusMap = collect($this->getDeviceStatuses($org['id']))->keyBy('serial');

                    foreach ($devices as &$device) {
                        $device['_status']  = $statusMap->get($device['serial'] ?? '') ?? [];
                        $device['_orgName'] = $org['name'];
                        $device['_orgId']   = $org['id'];
                    }
                    unset($device);

                    $all = array_merge($all, $devices);
                } catch (Exception $e) {
                    Log::warning("Meraki getAllDevices org [{$org['id']}]: " . $e->getMessage());
                }
            }

            return $all;
        });
    }

    /**
     * Invalida el caché global de todos los dispositivos.
     *
     * Fuerza que la próxima llamada a getAllDevicesWithStatuses() vuelva a consultar
     * la API. Útil después de cambios en el inventario de dispositivos.
     *
     * @return void
     */
    public function flushAllDevicesCache(): void
    {
        Cache::forget('meraki_all_devices_global');
    }

    // ─── Cache helpers ────────────────────────────────────────────────────────

    /**
     * Pre-carga en caché todos los datos de todas las organizaciones.
     *
     * Diseñado para ejecutarse via el comando artisan meraki:warm-cache cada 30 minutos
     * (configurado en el scheduler de routes/console.php). Al finalizar, invalida y
     * regenera también el caché global combinado de dispositivos.
     * Los errores por organización se logean como warnings y no detienen el warm-up.
     *
     * @return void
     */
    public function warmCache(): void
    {
        $organizations = $this->getOrganizations();

        foreach ($organizations as $org) {
            $orgId = $org['id'];
            try { $this->getDevices($orgId); }        catch (Exception $e) { Log::warning("warmCache devices [{$orgId}]: " . $e->getMessage()); }
            try { $this->getDeviceStatuses($orgId); } catch (Exception $e) { Log::warning("warmCache statuses [{$orgId}]: " . $e->getMessage()); }
            try { $this->getNetworks($orgId); }       catch (Exception $e) { Log::warning("warmCache networks [{$orgId}]: " . $e->getMessage()); }
            try { $this->getUplinkStatuses($orgId); } catch (Exception $e) { Log::warning("warmCache uplinks [{$orgId}]: " . $e->getMessage()); }
            try { $this->getLicenses($orgId); }       catch (Exception $e) { Log::warning("warmCache licenses [{$orgId}]: " . $e->getMessage()); }
        }

        // Regenerar el caché global combinado
        Cache::forget('meraki_all_devices_global');
        $this->getAllDevicesWithStatuses();
    }

    /**
     * Invalida todas las entradas de caché relacionadas con una organización específica.
     *
     * Usado por el botón "Refrescar organización" en la UI para forzar datos frescos
     * sin invalidar el caché de las demás organizaciones.
     *
     * @param  string $orgId ID de la organización a invalidar
     * @return void
     */
    public function flushOrgCache(string $orgId): void
    {
        Cache::forget("meraki_org_{$orgId}");
        Cache::forget("meraki_networks_{$orgId}");
        Cache::forget("meraki_devices_{$orgId}");
        Cache::forget("meraki_device_statuses_{$orgId}");
        Cache::forget("meraki_device_statuses_overview_{$orgId}");
        Cache::forget("meraki_uplink_statuses_{$orgId}");
    }

    /**
     * Invalida todas las entradas de caché relacionadas con una red específica.
     *
     * Invalida con los parámetros por defecto usados en cada método (timespan 86400,
     * perPage 50) para asegurar que las claves generadas coincidan exactamente.
     *
     * @param  string $networkId ID de la red a invalidar
     * @return void
     */
    public function flushNetworkCache(string $networkId): void
    {
        Cache::forget("meraki_network_{$networkId}");
        Cache::forget("meraki_network_devices_{$networkId}");
        Cache::forget("meraki_network_clients_{$networkId}_86400");
        Cache::forget("meraki_network_clients_overview_{$networkId}");
        Cache::forget("meraki_wireless_ssids_{$networkId}");
        Cache::forget("meraki_network_events_{$networkId}_30"); // perPage usado por la vista de red
        Cache::forget("meraki_network_events_{$networkId}_50"); // perPage por defecto
        Cache::forget("meraki_health_alerts_{$networkId}");
    }

    // ─── HTTP ─────────────────────────────────────────────────────────────────

    /**
     * Ejecuta una petición GET autenticada a la API de Cisco Meraki.
     *
     * La autenticación usa la cabecera X-Cisco-Meraki-API-Key (no Bearer).
     * Retorna el JSON completo parseado — la estructura varía por endpoint
     * (algunos devuelven array, otros objeto raíz).
     *
     * @param  string $path Ruta relativa al baseUrl (p.ej. '/organizations')
     * @return array        JSON de la respuesta parseado como array
     * @throws Exception    si la API Key no está configurada
     * @throws Exception    si la API retorna cualquier estado HTTP de error
     */
    protected function get(string $path): array
    {
        return $this->getResponse($path)->json() ?? [];
    }

    /**
     * Obtiene todas las páginas de un endpoint paginado de la API Meraki.
     *
     * Meraki pagina mediante la cabecera `Link` (rel=next) con un máximo de
     * 1000 ítems por página. Sin esto, organizaciones con más de 1000
     * dispositivos/licencias quedarían truncadas silenciosamente.
     *
     * @param  string $path    Ruta relativa al baseUrl (puede incluir query string)
     * @param  int    $perPage Ítems por página (máx. 1000 según Meraki)
     * @return array           Todos los ítems de todas las páginas concatenados
     * @throws Exception       si la API retorna error
     */
    protected function getAllPages(string $path, int $perPage = 1000): array
    {
        $separator = str_contains($path, '?') ? '&' : '?';
        $url = "{$this->baseUrl}{$path}{$separator}perPage={$perPage}";
        $all = [];

        // Tope de 50 páginas (50k ítems) como salvaguarda ante un Link circular
        for ($page = 0; $page < 50 && $url; $page++) {
            $response = $this->getResponse($url);
            $all      = array_merge($all, $response->json() ?? []);
            $url      = $this->nextPageUrl($response->header('Link'));
        }

        return $all;
    }

    /**
     * Extrae la URL de la siguiente página desde la cabecera Link de Meraki.
     *
     * Formato: `<https://...>; rel=first, <https://...>; rel=next, ...`
     *
     * @param  string|null $linkHeader Valor de la cabecera Link
     * @return string|null             URL absoluta de la siguiente página, o null si no hay más
     */
    protected function nextPageUrl(?string $linkHeader): ?string
    {
        if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="?next"?/', $linkHeader, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Ejecuta la petición HTTP con reintentos automáticos.
     *
     * Política de reintentos (hasta 3 intentos):
     *  - 429 (rate limit de Meraki: ~10 req/s por org) → espera el Retry-After indicado.
     *  - 5xx / errores de conexión → backoff incremental (500ms, 1000ms).
     *  - 4xx distintos de 429 fallan de inmediato (no tiene sentido reintentar).
     *
     * @param  string $pathOrUrl Ruta relativa al baseUrl o URL absoluta (paginación)
     * @return Response          Respuesta exitosa
     * @throws Exception         si la API Key no está configurada o la API retorna error
     */
    protected function getResponse(string $pathOrUrl): Response
    {
        if (empty($this->apiKey)) {
            throw new Exception('Meraki API Key no configurada. Agrega MERAKI_API_KEY en .env');
        }

        $url = str_starts_with($pathOrUrl, 'http') ? $pathOrUrl : "{$this->baseUrl}{$pathOrUrl}";

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->withHeaders(['X-Cisco-Meraki-API-Key' => $this->apiKey])
            ->retry(
                3,
                function (int $attempt, Exception $e) {
                    if ($e instanceof RequestException && $e->response->status() === 429) {
                        return max(1000, (int) $e->response->header('Retry-After') * 1000);
                    }
                    return $attempt * 500;
                },
                function (Exception $e) {
                    if ($e instanceof ConnectionException) {
                        return true;
                    }
                    return $e instanceof RequestException
                        && in_array($e->response->status(), [429, 500, 502, 503, 504], true);
                },
                throw: false,
            )
            ->get($url);

        if ($response->failed()) {
            throw new Exception("Meraki API [{$pathOrUrl}]: HTTP {$response->status()} — {$response->body()}");
        }

        return $response;
    }
}
