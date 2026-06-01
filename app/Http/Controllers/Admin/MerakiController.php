<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MerakiService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Controlador del módulo Meraki.
 *
 * Gestiona la visualización y monitoreo de dispositivos de red Cisco Meraki
 * a través de la API oficial de Meraki. Los datos se obtienen mediante MerakiService,
 * que aplica caché por capas (dispositivos e inventario: 24h, estados: 5min, licencias: 48h).
 *
 * Estructura del módulo:
 *  - Dashboard global con todos los dispositivos agrupados por modelo.
 *  - Detalle por organización (redes, dispositivos, uplinks).
 *  - Detalle por red (SSIDs, eventos, alertas de salud, clientes).
 *  - Vista de licencias agrupadas por modelo de dispositivo.
 *  - Central de alertas para dispositivos offline/alerting.
 *  - Exportación a CSV de dispositivos y licencias.
 *  - Gestión de caché (flush global o por organización/red).
 *
 * Vistas:
 *   - admin.meraki.index        → Dashboard global
 *   - admin.meraki.model        → Detalle de un modelo de dispositivo
 *   - admin.meraki.organization → Detalle de una organización
 *   - admin.meraki.network      → Detalle de una red
 *   - admin.meraki.licenses     → Vista de licencias
 *   - admin.meraki.alerts       → Central de alertas
 *
 * Rutas principales (prefijo /admin/meraki):
 *   GET  /                              → index()
 *   GET  /licenses                      → licenses()
 *   GET  /alerts                        → alerts()
 *   GET  /models/{model}               → modelDetail()
 *   GET  /{orgId}                       → organization()
 *   GET  /{orgId}/networks/{networkId}  → network()
 *   GET  /export/devices                → exportDevices()
 *   GET  /export/licenses               → exportLicenses()
 *   POST /refresh-all                   → refreshAll()
 *   POST /{orgId}/refresh               → refresh()
 *
 * @see \App\Services\MerakiService  Capa de acceso a la API Meraki con caché
 */
class MerakiController extends Controller
{
    /**
     * Inyecta MerakiService vía constructor (IoC Container de Laravel).
     *
     * @param  \App\Services\MerakiService  $meraki
     */
    public function __construct(protected MerakiService $meraki) {}

    // ─── Main dashboard — all devices grouped by model ────────────────────────

    /**
     * Dashboard global con todos los dispositivos de todas las organizaciones.
     *
     * Agrupa los dispositivos por modelo exacto y calcula el resumen de estados
     * (online, offline, alerting, dormant). En caso de error de API, renderiza
     * la vista con datos vacíos y el mensaje de error para que el usuario lo vea.
     *
     * @return \Illuminate\View\View  Vista admin.meraki.index con: organizations, grouped, summary
     */
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

    /**
     * Vista de detalle de todos los dispositivos de un modelo específico en todas las organizaciones.
     *
     * Lógica de asignación de licencias:
     *  1. Filtra licencias cuyo `licenseType` comience con el prefijo raw del modelo (MR, MS, MX...).
     *  2. Asocia por número de serie (`deviceSerial`) cuando la licencia ya está asignada.
     *  3. Para licencias del pool (sin serial asignado), las distribuye secuencialmente
     *     entre los dispositivos sin licencia directa, según el orden que devuelve la API.
     *  4. Calcula el resumen de licencias: activas, vencidas, sin usar.
     *
     * @param  string  $model  Nombre exacto del modelo (ej: "MR36", "MX67")
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
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

    /**
     * Vista de detalle de una organización Meraki: redes, dispositivos, uplinks y estadísticas.
     *
     * Enriquecimiento de datos:
     *  - A cada dispositivo se le adjunta su estado (online/offline) vía `_status`
     *    y su información de uplink (WAN1/WAN2) vía `_uplink`.
     *  - A cada red se le agrega el conteo de dispositivos totales y online.
     *  - Se construye un mapa networkId → networkName para mostrarlo en la tabla de dispositivos.
     *  - Los dispositivos se agrupan por modelo con contadores de estado por grupo.
     *
     * @param  string  $orgId  ID de la organización Meraki
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
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

    /**
     * Vista de detalle de una red Meraki: dispositivos, SSIDs, eventos y alertas de salud.
     *
     * Todos los sub-recursos (clientes, SSIDs, eventos, alertas) se obtienen con
     * bloques try/catch independientes para que un fallo en uno no rompa el resto de la vista.
     * Esto es importante porque no todas las redes soportan todos los endpoints
     * (ej: SSIDs solo en redes wireless, clientes en redes con APs activos).
     *
     * Comportamiento de SSIDs: solo se muestran los SSIDs habilitados (`enabled: true`).
     * Eventos: se recuperan los últimos 30.
     *
     * @param  string  $orgId      ID de la organización (para validar pertenencia)
     * @param  string  $networkId  ID de la red Meraki
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
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

    /**
     * Vista de licencias agrupadas por modelo de dispositivo de todas las organizaciones.
     *
     * Construye un mapa serial → {model, name, orgName} a partir del inventario cacheado
     * para enriquecer cada licencia con información del dispositivo asignado.
     * Las licencias sin serial asignado se agrupan bajo el modelo "Sin asignar".
     *
     * Nota: Organizaciones con co-termination licensing no devuelven licencias individuales
     * (devuelven array vacío sin error). Ver CLAUDE.md para detalles.
     *
     * @return \Illuminate\View\View  Vista admin.meraki.licenses con: byModel, total, totalActive,
     *                                totalExpired, totalUnused
     */
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

    /**
     * Central de alertas: lista todos los dispositivos con problemas (offline o alerting).
     *
     * Ordena los dispositivos problemáticos con la siguiente prioridad:
     *  1. Estado: alerting primero, luego offline.
     *  2. Dentro de cada estado: los más antiguos primero (lastReportedAt ascendente),
     *     ya que los dispositivos sin reporte reciente son más críticos.
     *
     * @return \Illuminate\View\View  Vista admin.meraki.alerts con: problematic, summary
     */
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

    /**
     * Exporta todos los dispositivos Meraki a un archivo CSV con BOM UTF-8.
     *
     * Incluye: nombre, modelo, serial, estado, IP LAN, organización y última conexión.
     * La fecha se formatea en d/m/Y H:i para legibilidad en español.
     * El BOM UTF-8 (0xEF 0xBB 0xBF) garantiza que Excel lo abra correctamente en Windows.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse  Descarga CSV
     */
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

    /**
     * Exporta todas las licencias Meraki de todas las organizaciones a CSV con BOM UTF-8.
     *
     * Incluye: modelo del dispositivo, nombre, serial, tipo de licencia, estado,
     * fecha de vencimiento y organización. Los errores por organización se loguean
     * como warning sin detener la exportación de las demás organizaciones.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse  Descarga CSV
     */
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

    /**
     * Genera una respuesta CSV con streaming para evitar cargar todo el archivo en memoria.
     *
     * Escribe directamente a php://output mediante fputcsv para manejar eficientemente
     * grandes volúmenes de datos. Incluye BOM UTF-8 al inicio para compatibilidad con Excel.
     *
     * @param  string  $filename  Nombre del archivo sin extensión (se agrega .csv)
     * @param  array   $headers   Array de strings con los encabezados de columna
     * @param  array   $rows      Array de arrays con los datos de cada fila
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
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

    /**
     * Invalida la caché global de todos los dispositivos y redirige al dashboard.
     *
     * Útil cuando se sabe que hubo cambios en la infraestructura Meraki
     * y se necesita forzar la recarga sin esperar el TTL de 24h.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshAll()
    {
        $this->meraki->flushAllDevicesCache();
        return redirect()->route('admin.meraki.index')->with('success', 'Datos actualizados.');
    }

    /**
     * Invalida la caché de una organización específica y la caché global de dispositivos.
     *
     * Se limpia también la caché global porque el inventario total incluye
     * los dispositivos de esta organización.
     *
     * @param  string  $orgId  ID de la organización Meraki
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refresh(string $orgId)
    {
        $this->meraki->flushOrgCache($orgId);
        $this->meraki->flushAllDevicesCache();
        return back()->with('success', 'Cache de organización actualizado.');
    }

    /**
     * Invalida la caché de una red específica.
     *
     * @param  string  $orgId      ID de la organización (requerido por la ruta, no usado directamente)
     * @param  string  $networkId  ID de la red Meraki cuya caché se limpia
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshNetwork(string $orgId, string $networkId)
    {
        $this->meraki->flushNetworkCache($networkId);
        return back()->with('success', 'Cache de red actualizado.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Deriva el prefijo de categoría de visualización a partir del nombre del modelo.
     *
     * Mapeo de prefijos raw de Meraki → categorías de visualización:
     *  - MX → MX (Security Appliance / Firewall)
     *  - MS → MS (Switch)
     *  - MG → MG (Cellular Gateway)
     *  - MV → MV (Security Camera)
     *  - MT → MT (Sensor)
     *  - MR → AP (Access Point — renombrado para mostrar "AP" en la UI)
     *
     * @param  string  $model  Nombre del modelo (ej: "MR36", "MX67", "MS120-8")
     * @return string          Prefijo de categoría (MX, MS, MG, MV, MT o AP)
     */
    protected function modelPrefix(string $model): string
    {
        foreach (['MX', 'MS', 'MG', 'MV', 'MT', 'MR'] as $prefix) {
            if (str_starts_with(strtoupper($model), $prefix)) {
                return $prefix === 'MR' ? 'AP' : $prefix;
            }
        }
        return 'AP';
    }

    /**
     * Obtiene el prefijo raw del modelo Meraki sin mapeo de visualización.
     *
     * A diferencia de modelPrefix(), este método devuelve el prefijo original de Meraki
     * (MR en lugar de AP) para usarlo en comparaciones con el campo `licenseType` de la API,
     * que sigue la convención original de Meraki (ej: "MR-ENT-1D").
     *
     * @param  string  $model  Nombre del modelo Meraki
     * @return string          Prefijo raw en mayúsculas (MX, MS, MG, MV, MT, MR o primeras 2 letras)
     */
    protected function rawModelPrefix(string $model): string
    {
        foreach (['MX', 'MS', 'MG', 'MV', 'MT', 'MR'] as $prefix) {
            if (str_starts_with(strtoupper($model), $prefix)) {
                return $prefix;
            }
        }
        return strtoupper(substr($model, 0, 2));
    }

    /**
     * Agrupa un array de dispositivos por modelo exacto y calcula contadores de estado.
     *
     * Devuelve una tupla [grouped, summary]:
     *  - `grouped`: array indexado por nombre de modelo, cada entrada con 'devices', 'online', 'offline', 'alerting'.
     *  - `summary`: contadores globales para mostrar en el header del dashboard.
     * Los grupos se ordenan alfabéticamente por modelo (ksort).
     *
     * @param  array  $devices  Array de dispositivos con campo `_status` adjunto
     * @return array            [grouped: array, summary: array]
     */
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

    /**
     * Calcula el resumen de estados (online, offline, alerting) de un conjunto de dispositivos.
     *
     * @param  array  $devices  Dispositivos con el campo `_status.status` adjunto
     * @return array            {total: int, online: int, offline: int, alerting: int}
     */
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
