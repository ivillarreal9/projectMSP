<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class MerakiService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('meraki.base_url') ?? '', '/');
        $this->apiKey  = (string) config('meraki.api_key', '');
    }

    // ─── Organizations ────────────────────────────────────────────────────────

    public function getOrganizations(): array
    {
        return Cache::remember('meraki_organizations', now()->addHours(24), fn () =>
            $this->get('/organizations')
        );
    }

    public function getOrganization(string $orgId): array
    {
        return Cache::remember("meraki_org_{$orgId}", now()->addHours(24), fn () =>
            $this->get("/organizations/{$orgId}")
        );
    }

    // ─── Networks ─────────────────────────────────────────────────────────────

    public function getNetworks(string $orgId): array
    {
        return Cache::remember("meraki_networks_{$orgId}", now()->addHours(24), fn () =>
            $this->get("/organizations/{$orgId}/networks")
        );
    }

    public function getNetwork(string $networkId): array
    {
        return Cache::remember("meraki_network_{$networkId}", now()->addHours(24), fn () =>
            $this->get("/networks/{$networkId}")
        );
    }

    // ─── Devices ──────────────────────────────────────────────────────────────

    /** All devices in an organization. */
    public function getDevices(string $orgId): array
    {
        return Cache::remember("meraki_devices_{$orgId}", now()->addHours(24), fn () =>
            $this->get("/organizations/{$orgId}/devices")
        );
    }

    /** Per-device online/offline/alerting status for the whole org. */
    public function getDeviceStatuses(string $orgId): array
    {
        return Cache::remember("meraki_device_statuses_{$orgId}", now()->addMinutes(5), fn () =>
            $this->get("/organizations/{$orgId}/devices/statuses")
        );
    }

    /** Aggregated online/offline/alerting count — cheap summary call. */
    public function getDeviceStatusesOverview(string $orgId): array
    {
        return Cache::remember("meraki_device_statuses_overview_{$orgId}", now()->addMinutes(5), fn () =>
            $this->get("/organizations/{$orgId}/devices/statuses/overview")
        );
    }

    /** Devices belonging to a specific network. */
    public function getNetworkDevices(string $networkId): array
    {
        return Cache::remember("meraki_network_devices_{$networkId}", now()->addHours(24), fn () =>
            $this->get("/networks/{$networkId}/devices")
        );
    }

    /** Clients connected to a specific device. Timespan in seconds (default 24 h). */
    public function getDeviceClients(string $serial, int $timespan = 86400): array
    {
        return Cache::remember("meraki_device_clients_{$serial}_{$timespan}", now()->addMinutes(3), fn () =>
            $this->get("/devices/{$serial}/clients?timespan={$timespan}")
        );
    }

    /** Per-port status for a switch. */
    public function getSwitchPortStatuses(string $serial, int $timespan = 86400): array
    {
        return Cache::remember("meraki_switch_port_statuses_{$serial}", now()->addMinutes(2), fn () =>
            $this->get("/devices/{$serial}/switch/ports/statuses?timespan={$timespan}")
        );
    }

    /** Port configuration list for a switch. */
    public function getSwitchPorts(string $serial): array
    {
        return Cache::remember("meraki_switch_ports_{$serial}", now()->addHours(24), fn () =>
            $this->get("/devices/{$serial}/switch/ports")
        );
    }

    // ─── Uplinks ──────────────────────────────────────────────────────────────

    /** Org-wide uplink status (MX & MG appliances). */
    public function getUplinkStatuses(string $orgId): array
    {
        return Cache::remember("meraki_uplink_statuses_{$orgId}", now()->addMinutes(5), fn () =>
            $this->get("/organizations/{$orgId}/uplinks/statuses")
        );
    }

    // ─── Clients ──────────────────────────────────────────────────────────────

    /** All clients seen in a network during the given timespan (default 24 h). */
    public function getNetworkClients(string $networkId, int $timespan = 86400): array
    {
        return Cache::remember("meraki_network_clients_{$networkId}_{$timespan}", now()->addMinutes(3), fn () =>
            $this->get("/networks/{$networkId}/clients?timespan={$timespan}&perPage=1000")
        );
    }

    /** Aggregate client count / usage overview for a network. */
    public function getNetworkClientsOverview(string $networkId): array
    {
        return Cache::remember("meraki_network_clients_overview_{$networkId}", now()->addMinutes(3), fn () =>
            $this->get("/networks/{$networkId}/clients/overview")
        );
    }

    // ─── Wireless ─────────────────────────────────────────────────────────────

    /** SSIDs configured on a wireless network. */
    public function getWirelessSsids(string $networkId): array
    {
        return Cache::remember("meraki_wireless_ssids_{$networkId}", now()->addHours(24), fn () =>
            $this->get("/networks/{$networkId}/wireless/ssids")
        );
    }

    // ─── Events & Alerts ──────────────────────────────────────────────────────

    /** Recent events for a network. */
    public function getNetworkEvents(string $networkId, int $perPage = 50): array
    {
        return Cache::remember("meraki_network_events_{$networkId}_{$perPage}", now()->addMinutes(5), fn () =>
            $this->get("/networks/{$networkId}/events?perPage={$perPage}")
        );
    }

    /** Active health alerts for a network. */
    public function getNetworkHealthAlerts(string $networkId): array
    {
        return Cache::remember("meraki_health_alerts_{$networkId}", now()->addMinutes(5), fn () =>
            $this->get("/networks/{$networkId}/health/alerts")
        );
    }

    // ─── Licensing ────────────────────────────────────────────────────────────

    /** Summary: status, expiration date, licensed device counts per product. */
    public function getLicensesOverview(string $orgId): array
    {
        return Cache::remember("meraki_licenses_overview_{$orgId}", now()->addHours(48), fn () =>
            $this->get("/organizations/{$orgId}/licenses/overview")
        );
    }

    /** Full license list for an organization. */
    public function getLicenses(string $orgId): array
    {
        return Cache::remember("meraki_licenses_{$orgId}", now()->addHours(48), fn () =>
            $this->get("/organizations/{$orgId}/licenses")
        );
    }

    // ─── Aggregated (all orgs) ────────────────────────────────────────────────

    /**
     * All devices from all organizations with status attached.
     * Shared cache across all users — one API call benefits everyone.
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
                    \Illuminate\Support\Facades\Log::warning("Meraki getAllDevices org [{$org['id']}]: " . $e->getMessage());
                }
            }

            return $all;
        });
    }

    public function flushAllDevicesCache(): void
    {
        Cache::forget('meraki_all_devices_global');
    }

    // ─── Cache helpers ────────────────────────────────────────────────────────

    public function warmCache(): void
    {
        $organizations = $this->getOrganizations();

        foreach ($organizations as $org) {
            $orgId = $org['id'];
            $this->getDevices($orgId);
            $this->getDeviceStatuses($orgId);
            $this->getNetworks($orgId);
            $this->getUplinkStatuses($orgId);
            $this->getLicenses($orgId);
        }

        // Regenerar el caché global combinado
        Cache::forget('meraki_all_devices_global');
        $this->getAllDevicesWithStatuses();
    }

    public function flushOrgCache(string $orgId): void
    {
        Cache::forget("meraki_org_{$orgId}");
        Cache::forget("meraki_networks_{$orgId}");
        Cache::forget("meraki_devices_{$orgId}");
        Cache::forget("meraki_device_statuses_{$orgId}");
        Cache::forget("meraki_device_statuses_overview_{$orgId}");
        Cache::forget("meraki_uplink_statuses_{$orgId}");
    }

    public function flushNetworkCache(string $networkId): void
    {
        Cache::forget("meraki_network_{$networkId}");
        Cache::forget("meraki_network_devices_{$networkId}");
        Cache::forget("meraki_network_clients_{$networkId}_86400");
        Cache::forget("meraki_network_clients_overview_{$networkId}");
        Cache::forget("meraki_wireless_ssids_{$networkId}");
        Cache::forget("meraki_network_events_{$networkId}_50");
        Cache::forget("meraki_health_alerts_{$networkId}");
    }

    // ─── HTTP ─────────────────────────────────────────────────────────────────

    protected function get(string $path): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Meraki API Key no configurada. Agrega MERAKI_API_KEY en .env');
        }

        $response = Http::timeout(15)
            ->withHeaders(['X-Cisco-Meraki-API-Key' => $this->apiKey])
            ->get("{$this->baseUrl}{$path}");

        if ($response->failed()) {
            throw new Exception("Meraki API [{$path}]: HTTP {$response->status()} — {$response->body()}");
        }

        return $response->json() ?? [];
    }
}
