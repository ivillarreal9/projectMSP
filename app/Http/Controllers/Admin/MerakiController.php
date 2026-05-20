<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MerakiService;
use Illuminate\Support\Facades\Log;
use Exception;

class MerakiController extends Controller
{
    public function __construct(protected MerakiService $meraki) {}

    // ─── Main dashboard — all devices grouped by model ────────────────────────

    public function index()
    {
        try {
            $organizations = $this->meraki->getOrganizations();
            $allDevices    = $this->meraki->getAllDevicesWithStatuses();

            [$grouped, $summary] = $this->groupByModel($allDevices);

        } catch (Exception $e) {
            Log::error('Meraki index: ' . $e->getMessage());
            return view('admin.meraki.index', [
                'organizations' => [],
                'grouped'       => [],
                'summary'       => ['total' => 0, 'online' => 0, 'offline' => 0, 'alerting' => 0],
                'error'         => $e->getMessage(),
            ]);
        }

        return view('admin.meraki.index', compact('organizations', 'grouped', 'summary'));
    }

    // ─── Model detail — all devices of one model across all orgs ─────────────

    public function modelDetail(string $model)
    {
        try {
            $allDevices = $this->meraki->getAllDevicesWithStatuses();
            $devices    = array_values(array_filter($allDevices, fn ($d) => ($d['model'] ?? '') === $model));

            abort_if(empty($devices), 404);

            $prefix  = $this->modelPrefix($model);
            $label   = config("meraki.device_types.{$prefix}", $prefix);
            $summary = $this->statusSummary($devices);

            // Raw prefix for license type matching (MR, MS, MX…) — different from
            // $prefix which maps MR→AP for display categories.
            $licPrefix = $this->rawModelPrefix($model);

            // Fetch license pool for this model prefix and attach to devices
            $organizations = $this->meraki->getOrganizations();
            $orgIds        = collect($devices)->pluck('_orgId')->unique()->filter()->values();
            $licenses      = collect();

            foreach ($orgIds as $orgId) {
                try {
                    $orgLicenses = collect($this->meraki->getLicenses($orgId))
                        ->filter(fn ($l) => str_starts_with(
                            strtoupper($l['licenseType'] ?? ''), $licPrefix
                        ));
                    $licenses = $licenses->merge($orgLicenses);
                } catch (Exception $e) {
                    Log::warning("Meraki modelDetail licenses org [{$orgId}]: " . $e->getMessage());
                }
            }

            // Match by deviceSerial first, then distribute pool sequentially
            $licBySerial = $licenses->filter(fn ($l) => !empty($l['deviceSerial']))->keyBy('deviceSerial');
            $pool        = $licenses->filter(fn ($l) => empty($l['deviceSerial']))->values();
            $poolIndex   = 0;

            $devices = array_map(function ($device) use ($licBySerial, $pool, &$poolIndex) {
                $lic = $licBySerial->get($device['serial'] ?? '');
                if (!$lic && $poolIndex < $pool->count()) {
                    $lic = $pool[$poolIndex++];
                }
                $device['_licType']       = $lic['licenseType']   ?? null;
                $device['_licState']      = $lic['state']          ?? null;
                $device['_licExpiration'] = $lic['expirationDate'] ?? null;
                return $device;
            }, $devices);

            $licSummary = [
                'total'   => $licenses->count(),
                'active'  => $licenses->filter(fn ($l) => str_contains(strtolower($l['state'] ?? ''), 'active'))->count(),
                'expired' => $licenses->filter(fn ($l) => str_contains(strtolower($l['state'] ?? ''), 'expired'))->count(),
                'unused'  => $licenses->filter(fn ($l) => str_contains(strtolower($l['state'] ?? ''), 'unused'))->count(),
            ];

        } catch (Exception $e) {
            Log::error("Meraki modelDetail [{$model}]: " . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }

        return view('admin.meraki.model', compact('model', 'label', 'devices', 'summary', 'licenses', 'licSummary'));
    }

    // ─── Organization detail (devices) ───────────────────────────────────────

    public function organization(string $orgId)
    {
        try {
            $organizations = $this->meraki->getOrganizations();
            $org           = collect($organizations)->firstWhere('id', $orgId);
            abort_unless($org, 404);

            $networks = $this->meraki->getNetworks($orgId);
            $statuses = $this->meraki->getDeviceStatuses($orgId);
            $devices  = $this->meraki->getDevices($orgId);
            $uplinks  = $this->meraki->getUplinkStatuses($orgId);

            // Map statuses by serial
            $statusMap = collect($statuses)->keyBy('serial');

            // Attach status + uplink info to each device
            $uplinkMap = collect($uplinks)->keyBy('serial');
            $devices   = array_map(function ($device) use ($statusMap, $uplinkMap) {
                $device['_status'] = $statusMap->get($device['serial'] ?? '') ?? [];
                $device['_uplink'] = $uplinkMap->get($device['serial'] ?? '') ?? [];
                return $device;
            }, $devices);

            // Summary counts
            $statusCounts = collect($statuses)->groupBy('status');
            $summary = [
                'total'    => count($devices),
                'online'   => $statusCounts->get('online',   collect())->count(),
                'offline'  => $statusCounts->get('offline',  collect())->count(),
                'alerting' => $statusCounts->get('alerting', collect())->count(),
                'dormant'  => $statusCounts->get('dormant',  collect())->count(),
            ];

            // Group devices by exact model name
            $grouped = [];
            foreach ($devices as $device) {
                $model  = $device['model'] ?? 'Unknown';
                $prefix = $this->modelPrefix($model);
                $st     = $device['_status']['status'] ?? '';

                if (!isset($grouped[$model])) {
                    $grouped[$model] = [
                        'model'   => $model,
                        'prefix'  => $prefix,
                        'label'   => config("meraki.device_types.{$prefix}", $prefix),
                        'devices' => [],
                        'online'  => 0,
                        'offline' => 0,
                        'alerting'=> 0,
                    ];
                }

                $grouped[$model]['devices'][] = $device;
                if ($st === 'online')        $grouped[$model]['online']++;
                elseif ($st === 'offline')   $grouped[$model]['offline']++;
                elseif ($st === 'alerting')  $grouped[$model]['alerting']++;
            }
            ksort($grouped);

            // Networks enriched with device counts
            $devicesByNetwork = collect($devices)->groupBy(fn ($d) => $d['networkId'] ?? '');
            $networks = array_map(function ($net) use ($devicesByNetwork, $statusMap) {
                $netDevices = $devicesByNetwork->get($net['id'], collect());
                $net['_device_count']  = $netDevices->count();
                $net['_online_count']  = $netDevices->filter(fn ($d) =>
                    ($statusMap->get($d['serial'] ?? []) ?? [])['status'] ?? '' === 'online'
                )->count();
                return $net;
            }, $networks);

            // networkId → name map para mostrarlo en la tabla de dispositivos
            $networkMap = collect($networks)->pluck('name', 'id')->all();

        } catch (Exception $e) {
            Log::error("Meraki org [{$orgId}]: " . $e->getMessage());
            return back()->with('error', 'Error al cargar la organización: ' . $e->getMessage());
        }

        return view('admin.meraki.organization', compact(
            'org', 'organizations', 'networks', 'devices', 'grouped', 'summary', 'uplinks', 'networkMap'
        ));
    }

    // ─── Network detail (clients + events + SSIDs) ────────────────────────────

    public function network(string $orgId, string $networkId)
    {
        try {
            $organizations = $this->meraki->getOrganizations();
            $org           = collect($organizations)->firstWhere('id', $orgId);
            abort_unless($org, 404);

            $network      = $this->meraki->getNetwork($networkId);
            $netDevices   = $this->meraki->getNetworkDevices($networkId);
            $statuses     = $this->meraki->getDeviceStatuses($orgId);
            $statusMap    = collect($statuses)->keyBy('serial');

            // Attach status to network devices
            $netDevices = array_map(function ($d) use ($statusMap) {
                $d['_status'] = $statusMap->get($d['serial'] ?? '') ?? [];
                return $d;
            }, $netDevices);

            // Clients overview (non-fatal — not all network types support this)
            $clientsOverview = [];
            try {
                $clientsOverview = $this->meraki->getNetworkClientsOverview($networkId);
            } catch (Exception) {}

            // SSIDs (wireless only — non-fatal)
            $ssids = [];
            $productTypes = $network['productTypes'] ?? [];
            if (in_array('wireless', $productTypes)) {
                try {
                    $ssids = array_filter(
                        $this->meraki->getWirelessSsids($networkId),
                        fn ($s) => ($s['enabled'] ?? false)
                    );
                } catch (Exception) {}
            }

            // Recent events (non-fatal)
            $events = [];
            try {
                $eventsResponse = $this->meraki->getNetworkEvents($networkId, 30);
                $events = $eventsResponse['events'] ?? $eventsResponse;
            } catch (Exception) {}

            // Health alerts (non-fatal)
            $healthAlerts = [];
            try {
                $healthAlerts = $this->meraki->getNetworkHealthAlerts($networkId);
            } catch (Exception) {}

        } catch (Exception $e) {
            Log::error("Meraki network [{$networkId}]: " . $e->getMessage());
            return back()->with('error', 'Error al cargar la red: ' . $e->getMessage());
        }

        return view('admin.meraki.network', compact(
            'org', 'network', 'netDevices', 'clientsOverview', 'ssids', 'events', 'healthAlerts'
        ));
    }

    // ─── Licenses ─────────────────────────────────────────────────────────────

    public function licenses()
    {
        try {
            $organizations = $this->meraki->getOrganizations();

            // Build serial → model map from cached devices
            $allDevices  = $this->meraki->getAllDevicesWithStatuses();
            $serialToModel = collect($allDevices)->keyBy('serial')->map(fn ($d) => [
                'model'   => $d['model'] ?? 'Unknown',
                'name'    => $d['name'] ?? $d['serial'] ?? '—',
                'orgName' => $d['_orgName'] ?? '—',
                'orgId'   => $d['_orgId'] ?? null,
                'status'  => $d['_status']['status'] ?? 'unknown',
            ]);

            // Fetch all licenses from all orgs and attach model info
            $byModel = [];

            foreach ($organizations as $org) {
                try {
                    $licenses = $this->meraki->getLicenses($org['id']);

                    foreach ($licenses as $license) {
                        $serial    = $license['deviceSerial'] ?? null;
                        $device    = $serial ? $serialToModel->get($serial) : null;
                        $model     = $device['model'] ?? 'Sin asignar';

                        if (!isset($byModel[$model])) {
                            $byModel[$model] = [
                                'model'    => $model,
                                'prefix'   => $this->modelPrefix($model),
                                'licenses' => [],
                                'active'   => 0,
                                'expired'  => 0,
                                'unused'   => 0,
                            ];
                        }

                        $license['_device'] = $device;
                        $license['_org']    = $org;
                        $byModel[$model]['licenses'][] = $license;

                        $state = strtolower($license['state'] ?? '');
                        if ($state === 'active')       $byModel[$model]['active']++;
                        elseif ($state === 'expired')  $byModel[$model]['expired']++;
                        elseif ($state === 'unused')   $byModel[$model]['unused']++;
                    }

                } catch (Exception $e) {
                    Log::warning("Meraki licenses org [{$org['id']}]: " . $e->getMessage());
                }
            }

            ksort($byModel);

            $totalActive  = array_sum(array_column($byModel, 'active'));
            $totalExpired = array_sum(array_column($byModel, 'expired'));
            $totalUnused  = array_sum(array_column($byModel, 'unused'));
            $total        = $totalActive + $totalExpired + $totalUnused;

        } catch (Exception $e) {
            Log::error('Meraki licenses: ' . $e->getMessage());
            return view('admin.meraki.licenses', [
                'byModel' => [], 'total' => 0,
                'totalActive' => 0, 'totalExpired' => 0, 'totalUnused' => 0,
                'error' => $e->getMessage(),
            ]);
        }

        return view('admin.meraki.licenses', compact(
            'byModel', 'total', 'totalActive', 'totalExpired', 'totalUnused'
        ));
    }

    // ─── Alerts — dispositivos offline/alerting de todas las orgs ────────────

    public function alerts()
    {
        try {
            $allDevices = $this->meraki->getAllDevicesWithStatuses();

            $problematic = array_values(array_filter(
                $allDevices,
                fn ($d) => in_array($d['_status']['status'] ?? '', ['offline', 'alerting'])
            ));

            usort($problematic, function ($a, $b) {
                $order = ['alerting' => 0, 'offline' => 1];
                $sa = $order[$a['_status']['status'] ?? 'offline'] ?? 1;
                $sb = $order[$b['_status']['status'] ?? 'offline'] ?? 1;
                if ($sa !== $sb) return $sa - $sb;

                $la = $a['_status']['lastReportedAt'] ?? '';
                $lb = $b['_status']['lastReportedAt'] ?? '';
                return strcmp($la, $lb); // más antiguos primero
            });

            $summary = [
                'total'    => count($allDevices),
                'offline'  => count(array_filter($problematic, fn ($d) => ($d['_status']['status'] ?? '') === 'offline')),
                'alerting' => count(array_filter($problematic, fn ($d) => ($d['_status']['status'] ?? '') === 'alerting')),
            ];

        } catch (Exception $e) {
            Log::error('Meraki alerts: ' . $e->getMessage());
            return view('admin.meraki.alerts', [
                'problematic' => [], 'summary' => ['total' => 0, 'offline' => 0, 'alerting' => 0],
                'error' => $e->getMessage(),
            ]);
        }

        return view('admin.meraki.alerts', compact('problematic', 'summary'));
    }

    // ─── Exports ──────────────────────────────────────────────────────────────

    public function exportDevices()
    {
        $allDevices = $this->meraki->getAllDevicesWithStatuses();

        $headers = ['Nombre', 'Modelo', 'Serial', 'Estado', 'IP LAN', 'Organización', 'Último reporte'];
        $rows    = array_map(function ($d) {
            $lastRaw = $d['_status']['lastReportedAt'] ?? null;
            try {
                $last = $lastRaw ? \Carbon\Carbon::parse($lastRaw)->format('d/m/Y H:i') : '—';
            } catch (\Exception $e) { $last = '—'; }

            return [
                $d['name']                    ?? $d['serial'] ?? '—',
                $d['model']                   ?? '—',
                $d['serial']                  ?? '—',
                $d['_status']['status']       ?? '—',
                $d['_status']['lanIp']        ?? $d['lanIp'] ?? '—',
                $d['_orgName']                ?? '—',
                $last,
            ];
        }, $allDevices);

        return $this->streamCsv('meraki-dispositivos-' . now()->format('Y-m-d'), $headers, $rows);
    }

    public function exportLicenses()
    {
        $organizations = $this->meraki->getOrganizations();
        $allDevices    = $this->meraki->getAllDevicesWithStatuses();
        $serialToModel = collect($allDevices)->keyBy('serial')->map(fn ($d) => [
            'model'   => $d['model']   ?? '—',
            'name'    => $d['name']    ?? $d['serial'] ?? '—',
            'orgName' => $d['_orgName'] ?? '—',
        ]);

        $headers = ['Modelo', 'Dispositivo', 'Serial', 'Tipo licencia', 'Estado', 'Vencimiento', 'Organización'];
        $rows    = [];

        foreach ($organizations as $org) {
            try {
                foreach ($this->meraki->getLicenses($org['id']) as $lic) {
                    $serial = $lic['deviceSerial'] ?? null;
                    $device = $serial ? $serialToModel->get($serial) : null;
                    try {
                        $exp = !empty($lic['expirationDate'])
                            ? \Carbon\Carbon::parse($lic['expirationDate'])->format('d/m/Y')
                            : '—';
                    } catch (\Exception $e) { $exp = '—'; }

                    $rows[] = [
                        $device['model']   ?? 'Sin asignar',
                        $device['name']    ?? '—',
                        $serial            ?? '—',
                        $lic['licenseType'] ?? '—',
                        $lic['state']       ?? '—',
                        $exp,
                        $org['name'],
                    ];
                }
            } catch (Exception $e) {
                Log::warning("Meraki exportLicenses org [{$org['id']}]: " . $e->getMessage());
            }
        }

        return $this->streamCsv('meraki-licencias-' . now()->format('Y-m-d'), $headers, $rows);
    }

    private function streamCsv(string $filename, array $headers, array $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename . '.csv', [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ]);
    }

    // ─── Cache flush ──────────────────────────────────────────────────────────

    public function refreshAll()
    {
        $this->meraki->flushAllDevicesCache();
        return redirect()->route('admin.meraki.index')->with('success', 'Datos actualizados.');
    }

    public function refresh(string $orgId)
    {
        $this->meraki->flushOrgCache($orgId);
        $this->meraki->flushAllDevicesCache();
        return back()->with('success', 'Cache de organización actualizado.');
    }

    public function refreshNetwork(string $orgId, string $networkId)
    {
        $this->meraki->flushNetworkCache($networkId);
        return back()->with('success', 'Cache de red actualizado.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected function modelPrefix(string $model): string
    {
        foreach (['MX', 'MS', 'MG', 'MV', 'MT', 'MR'] as $prefix) {
            if (str_starts_with(strtoupper($model), $prefix)) {
                return $prefix === 'MR' ? 'AP' : $prefix;
            }
        }
        return 'AP';
    }

    /** Raw Meraki prefix from model name — used for license type matching. */
    protected function rawModelPrefix(string $model): string
    {
        foreach (['MX', 'MS', 'MG', 'MV', 'MT', 'MR'] as $prefix) {
            if (str_starts_with(strtoupper($model), $prefix)) {
                return $prefix;
            }
        }
        return strtoupper(substr($model, 0, 2));
    }

    protected function groupByModel(array $devices): array
    {
        $grouped = [];
        foreach ($devices as $device) {
            $model  = $device['model'] ?? 'Unknown';
            $prefix = $this->modelPrefix($model);
            $st     = $device['_status']['status'] ?? '';

            if (!isset($grouped[$model])) {
                $grouped[$model] = [
                    'model'    => $model,
                    'prefix'   => $prefix,
                    'label'    => config("meraki.device_types.{$prefix}", $prefix),
                    'devices'  => [],
                    'online'   => 0,
                    'offline'  => 0,
                    'alerting' => 0,
                ];
            }

            $grouped[$model]['devices'][] = $device;
            if ($st === 'online')       $grouped[$model]['online']++;
            elseif ($st === 'offline')  $grouped[$model]['offline']++;
            elseif ($st === 'alerting') $grouped[$model]['alerting']++;
        }
        ksort($grouped);

        $summary = $this->statusSummary($devices);

        return [$grouped, $summary];
    }

    protected function statusSummary(array $devices): array
    {
        $online = $offline = $alerting = 0;
        foreach ($devices as $d) {
            $st = $d['_status']['status'] ?? '';
            if ($st === 'online')       $online++;
            elseif ($st === 'offline')  $offline++;
            elseif ($st === 'alerting') $alerting++;
        }
        return ['total' => count($devices), 'online' => $online, 'offline' => $offline, 'alerting' => $alerting];
    }
}
