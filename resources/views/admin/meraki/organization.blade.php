<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500 mb-1">
                    <a href="{{ route('admin.meraki.index') }}" class="hover:text-teal-500 transition">Meraki</a>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-gray-600 dark:text-gray-300">{{ $org['name'] ?? $org['id'] }}</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $org['name'] ?? 'Organización' }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Estado de redes y dispositivos</p>
            </div>

            <form method="POST" action="{{ route('admin.meraki.refresh', $org['id']) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                               bg-teal-500 hover:bg-teal-600 text-white rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Actualizar
                </button>
            </form>
        </div>
    </x-slot>

    {{-- Sticky KPI bar — stays fixed below the nav when scrolling --}}
    <div class="sticky top-14 z-20 bg-white/90 dark:bg-gray-900/90 backdrop-blur border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-6 py-2.5 overflow-x-auto">

                {{-- Org name (compact) --}}
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 shrink-0 hidden sm:block">
                    {{ Str::limit($org['name'] ?? 'Organización', 30) }}
                </p>
                <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 shrink-0 hidden sm:block"></div>

                {{-- KPI pills --}}
                <div class="flex items-center gap-4 shrink-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-gray-400">Total</span>
                        <span class="text-sm font-black text-gray-800 dark:text-gray-100">{{ $summary['total'] }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-400"></span>
                        <span class="text-sm font-black text-green-600 dark:text-green-400">{{ $summary['online'] }}</span>
                        <span class="text-xs text-gray-400">online</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-red-400"></span>
                        <span class="text-sm font-black text-red-600 dark:text-red-400">{{ $summary['offline'] }}</span>
                        <span class="text-xs text-gray-400">offline</span>
                    </div>
                    @if($summary['alerting'] > 0)
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                        <span class="text-sm font-black text-yellow-600 dark:text-yellow-400">{{ $summary['alerting'] }}</span>
                        <span class="text-xs text-gray-400">alerting</span>
                    </div>
                    @endif
                </div>

                <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 shrink-0"></div>

                {{-- Quick jump links --}}
                <div class="flex items-center gap-3 shrink-0">
                    @if(count($networks) > 0)
                    <a href="#redes" class="text-xs text-gray-400 hover:text-teal-500 transition">
                        Redes ({{ count($networks) }})
                    </a>
                    @endif
                    @if(count($uplinks) > 0)
                    <a href="#uplinks" class="text-xs text-gray-400 hover:text-teal-500 transition">
                        Uplinks ({{ count($uplinks) }})
                    </a>
                    @endif
                    @if(count($grouped) > 0)
                    <a href="#dispositivos" class="text-xs text-gray-400 hover:text-teal-500 transition">
                        Dispositivos ({{ $summary['total'] }})
                    </a>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

            @if(session('success'))
            <div class="flex items-center gap-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl px-4 py-3">
                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
            @endif

            @if(session('error'))
            <div class="flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
            @endif

            {{-- Networks --}}
            @if(count($networks) > 0)
            <div id="redes">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">
                    Redes ({{ count($networks) }})
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($networks as $net)
                    <a href="{{ route('admin.meraki.network', [$org['id'], $net['id']]) }}"
                       class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4
                              hover:border-teal-400 dark:hover:border-teal-500 hover:shadow-sm transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="w-4 h-4 text-teal-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0"/>
                                </svg>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate
                                             group-hover:text-teal-600 dark:group-hover:text-teal-400 transition">
                                    {{ $net['name'] ?? 'Sin nombre' }}
                                </span>
                            </div>
                            @if(($net['_device_count'] ?? 0) > 0)
                            <span class="text-xs text-gray-400 shrink-0 ml-2">{{ $net['_device_count'] }} disp.</span>
                            @endif
                        </div>
                        @if(!empty($net['productTypes']))
                        <div class="flex flex-wrap gap-1">
                            @foreach($net['productTypes'] as $pt)
                            <span class="px-1.5 py-0.5 text-xs bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 rounded">
                                {{ strtoupper($pt) }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Uplinks --}}
            @if(count($uplinks) > 0)
            <div id="uplinks">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">
                    Uplinks / WAN ({{ count($uplinks) }} dispositivos)
                </p>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20">
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Dispositivo</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Interfaz</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden md:table-cell">IP Pública</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden lg:table-cell">ISP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            @foreach($uplinks as $uplink)
                            @foreach($uplink['uplinks'] ?? [] as $iface)
                            @php
                                $ist    = $iface['status'] ?? 'unknown';
                                $icolor = match($ist) {
                                    'active'        => ['dot' => 'bg-green-400',  'text' => 'text-green-600 dark:text-green-400',   'label' => 'Activo'],
                                    'ready'         => ['dot' => 'bg-blue-400',   'text' => 'text-blue-600 dark:text-blue-400',     'label' => 'Listo'],
                                    'connecting'    => ['dot' => 'bg-yellow-400', 'text' => 'text-yellow-600 dark:text-yellow-400', 'label' => 'Conectando'],
                                    'failed'        => ['dot' => 'bg-red-400',    'text' => 'text-red-600 dark:text-red-400',       'label' => 'Fallo'],
                                    'not connected' => ['dot' => 'bg-red-400',    'text' => 'text-red-600 dark:text-red-400',       'label' => 'Sin conexión'],
                                    default         => ['dot' => 'bg-gray-300',   'text' => 'text-gray-400',                        'label' => $ist],
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-2.5 text-gray-800 dark:text-gray-200 font-medium text-xs">
                                    {{ $uplink['networkName'] ?? $uplink['serial'] ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $iface['interface'] ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full {{ $icolor['dot'] }} shrink-0"></span>
                                        <span class="text-xs font-medium {{ $icolor['text'] }}">{{ $icolor['label'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 hidden md:table-cell font-mono text-xs text-gray-400">
                                    {{ $iface['publicIp'] ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 hidden lg:table-cell text-xs text-gray-400">
                                    {{ $iface['provider'] ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Devices grouped by model --}}
            @if(count($grouped) > 0)
            <div id="dispositivos" class="space-y-6"
                 x-data="{ search: '' }"
                 x-effect="
                    const q = search.toLowerCase();
                    $el.querySelectorAll('[data-device-row]').forEach(r => {
                        r.style.display = !q || (r.dataset.searchText || '').includes(q) ? '' : 'none';
                    });
                    $el.querySelectorAll('[data-model-group]').forEach(g => {
                        const any = [...g.querySelectorAll('[data-device-row]')].some(r => r.style.display !== 'none');
                        g.style.display = !q || any ? '' : 'none';
                    });
                 ">

                <div class="flex items-center justify-between gap-4">
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest shrink-0">
                        Dispositivos por modelo
                    </p>
                    <div class="relative max-w-xs w-full">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text"
                               x-model.debounce.150ms="search"
                               placeholder="Buscar por nombre, serial o modelo..."
                               class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                                      bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200
                                      focus:outline-none focus:ring-1 focus:ring-teal-400 placeholder-gray-400">
                    </div>
                </div>

                @foreach($grouped as $model => $group)
                <div data-model-group>
                    <div class="flex items-center gap-3 mb-3">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ $model }}</span>
                        <span class="text-xs text-gray-400">{{ $group['label'] }}</span>
                        <div class="flex items-center gap-2 ml-auto">
                            @if($group['online'] > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>{{ $group['online'] }} online
                            </span>
                            @endif
                            @if($group['offline'] > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>{{ $group['offline'] }} offline
                            </span>
                            @endif
                            @if($group['alerting'] > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>{{ $group['alerting'] }} alerting
                            </span>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20">
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Nombre</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden md:table-cell">Serial</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden lg:table-cell">IP LAN</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden lg:table-cell">Red</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden xl:table-cell">Último reporte</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                @foreach($group['devices'] as $device)
                                @php
                                    $st    = $device['_status']['status'] ?? 'unknown';
                                    $color = match($st) {
                                        'online'   => ['dot' => 'bg-green-400',  'text' => 'text-green-600 dark:text-green-400',   'label' => 'Online'],
                                        'offline'  => ['dot' => 'bg-red-400',    'text' => 'text-red-600 dark:text-red-400',       'label' => 'Offline'],
                                        'alerting' => ['dot' => 'bg-yellow-400', 'text' => 'text-yellow-600 dark:text-yellow-400', 'label' => 'Alerting'],
                                        'dormant'  => ['dot' => 'bg-gray-400',   'text' => 'text-gray-500',                        'label' => 'Dormant'],
                                        default    => ['dot' => 'bg-gray-300',   'text' => 'text-gray-400',                        'label' => 'N/A'],
                                    };
                                @endphp
                                @php
                                    $lastRaw  = $device['_status']['lastReportedAt'] ?? null;
                                    try {
                                        $lastDt   = $lastRaw ? \Carbon\Carbon::parse($lastRaw) : null;
                                        $lastAgo  = $lastDt?->diffForHumans();
                                        $lastFmt  = $lastDt?->format('d/m/Y H:i');
                                        $lastOld  = $lastDt && $lastDt->diffInHours(now()) > 24;
                                    } catch (\Exception $e) { $lastDt = $lastAgo = $lastFmt = null; $lastOld = false; }
                                    $searchText = strtolower(($device['name'] ?? '') . ' ' . ($device['serial'] ?? '') . ' ' . ($device['model'] ?? ''));
                                @endphp
                                <tr data-device-row
                                    data-search-text="{{ $searchText }}"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $color['dot'] }} shrink-0"></span>
                                            <span class="text-xs font-medium {{ $color['text'] }}">{{ $color['label'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-gray-200">
                                        {{ $device['name'] ?? $device['serial'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 hidden md:table-cell text-gray-400 font-mono text-xs">
                                        {{ $device['serial'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 hidden lg:table-cell text-gray-400 font-mono text-xs">
                                        {{ $device['lanIp'] ?? $device['_status']['lanIp'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 hidden lg:table-cell text-gray-400 text-xs">
                                        {{ $networkMap[$device['networkId'] ?? ''] ?? $device['networkId'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 hidden xl:table-cell">
                                        @if($lastFmt)
                                        <p class="text-xs {{ $lastOld ? 'text-red-500 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }} font-medium">
                                            {{ $lastAgo }}
                                        </p>
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $lastFmt }}</p>
                                        @else
                                        <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>

            @elseif(empty($devices))
            <div class="flex flex-col items-center justify-center py-16 text-center text-gray-400 dark:text-gray-500">
                <svg class="w-10 h-10 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2v-4M9 21H5a2 2 0 01-2-2v-4m0 0h18"/>
                </svg>
                <p class="text-sm font-medium">Sin dispositivos en esta organización</p>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
