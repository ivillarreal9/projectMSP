<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500 mb-1">
                    <a href="{{ route('admin.meraki.index') }}" class="hover:text-teal-500 transition">Meraki</a>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <a href="{{ route('admin.meraki.organization', $org['id']) }}" class="hover:text-teal-500 transition">{{ $org['name'] }}</a>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-gray-600 dark:text-gray-300">{{ $network['name'] ?? $network['id'] }}</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $network['name'] ?? 'Red' }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Detalle de red — dispositivos, clientes y eventos</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.meraki.export.devices', ['network' => $network['id']]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                          border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                          text-gray-600 dark:text-gray-300 rounded-lg hover:border-teal-400 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Exportar Excel
                </a>
                <form method="POST" action="{{ route('admin.meraki.network.refresh', [$org['id'], $network['id']]) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-teal-500 hover:bg-teal-600 text-white rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Actualizar
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Flash --}}
            @if(session('success'))
            <div class="flex items-center gap-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl px-4 py-3">
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
            @endif
            @if(session('error'))
            <div class="flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3">
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
            @endif

            {{-- Network info + client summary --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-2">Información</p>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-400">ID</dt>
                            <dd class="font-mono text-xs text-gray-600 dark:text-gray-300">{{ $network['id'] }}</dd>
                        </div>
                        @if(!empty($network['timeZone']))
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Zona horaria</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $network['timeZone'] }}</dd>
                        </div>
                        @endif
                        @if(!empty($network['productTypes']))
                        <div class="flex justify-between items-start">
                            <dt class="text-gray-400 shrink-0 mr-2">Productos</dt>
                            <dd class="flex flex-wrap gap-1 justify-end">
                                @foreach($network['productTypes'] as $pt)
                                <span class="px-1.5 py-0.5 text-xs bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 rounded">{{ strtoupper($pt) }}</span>
                                @endforeach
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>

                {{-- Clients overview --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-2">Clientes (24 h)</p>
                    @if(!empty($clientsOverview['counts']))
                    @php $counts = $clientsOverview['counts']; @endphp
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $counts['total'] ?? '—' }}</p>
                            <p class="text-xs text-gray-400">Total</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $counts['withHeavyUsage'] ?? '—' }}</p>
                            <p class="text-xs text-gray-400">Alto uso</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $counts['withNormalUsage'] ?? '—' }}</p>
                            <p class="text-xs text-gray-400">Normal</p>
                        </div>
                    </div>
                    @else
                    <p class="text-sm text-gray-400 dark:text-gray-500">No disponible para esta red.</p>
                    @endif
                </div>

                {{-- Health alerts --}}
                <div class="bg-white dark:bg-gray-800 border rounded-xl p-4 {{ count($healthAlerts) > 0 ? 'border-yellow-200 dark:border-yellow-700' : 'border-gray-200 dark:border-gray-700' }}">
                    <p class="text-xs font-medium uppercase tracking-wide mb-2 {{ count($healthAlerts) > 0 ? 'text-yellow-500' : 'text-gray-400' }}">
                        Alertas de salud ({{ count($healthAlerts) }})
                    </p>
                    @if(count($healthAlerts) > 0)
                    <ul class="space-y-1">
                        @foreach(array_slice($healthAlerts, 0, 4) as $alert)
                        <li class="text-xs text-yellow-700 dark:text-yellow-400 flex items-start gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 mt-1 shrink-0"></span>
                            {{ $alert['type'] ?? ($alert['category'] ?? 'Alerta') }}
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-sm text-green-600 dark:text-green-400 font-medium">Sin alertas activas</p>
                    @endif
                </div>
            </div>

            {{-- SSIDs --}}
            @if(count($ssids) > 0)
            <div>
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">
                    SSIDs activos ({{ count($ssids) }})
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($ssids as $ssid)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-start gap-3">
                        <div class="w-8 h-8 bg-teal-50 dark:bg-teal-900/30 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $ssid['name'] }}</p>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                @if(!empty($ssid['authMode']))
                                <span class="px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded">{{ $ssid['authMode'] }}</span>
                                @endif
                                @if(!empty($ssid['bandSelection']))
                                <span class="px-1.5 py-0.5 text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-500 rounded">{{ $ssid['bandSelection'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Devices in this network --}}
            @if(count($netDevices) > 0)
            <div x-data="{ search: '', status: 'all', shown: {{ count($netDevices) }} }"
                 x-effect="
                    const q = search.toLowerCase();
                    let count = 0;
                    $el.querySelectorAll('[data-device-row]').forEach(r => {
                        const matchSearch = !q || (r.dataset.searchText || '').includes(q);
                        const matchStatus = status === 'all' || r.dataset.status === status;
                        const visible = matchSearch && matchStatus;
                        r.style.display = visible ? '' : 'none';
                        if (visible) count++;
                    });
                    shown = count;
                 ">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-3">
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                        Dispositivos en esta red (<span x-text="shown"></span>/{{ count($netDevices) }})
                    </p>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model.debounce.150ms="search" placeholder="Buscar dispositivo..."
                                   class="w-full sm:w-56 pl-9 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                                          bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200
                                          focus:outline-none focus:ring-1 focus:ring-teal-400 placeholder-gray-400">
                        </div>
                        <div class="flex items-center gap-1.5">
                            @foreach(['all' => 'Todos', 'online' => 'Online', 'offline' => 'Offline', 'alerting' => 'Alerting'] as $val => $lbl)
                            <button type="button" @click="status = '{{ $val }}'"
                                    :class="status === '{{ $val }}' ? 'bg-teal-500 text-white border-teal-500' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700'"
                                    class="px-2.5 py-1.5 text-xs font-semibold rounded-lg border transition">{{ $lbl }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Nombre</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden sm:table-cell">Modelo</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden md:table-cell">Serial</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden lg:table-cell">IP LAN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            @foreach($netDevices as $device)
                            @php
                                $st = $device['_status']['status'] ?? 'unknown';
                                $searchText = strtolower(($device['name'] ?? '') . ' ' . ($device['model'] ?? '') . ' ' . ($device['serial'] ?? ''));
                            @endphp
                            <tr data-device-row
                                data-search-text="{{ $searchText }}"
                                data-status="{{ $st }}"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-2.5">
                                    <x-meraki.status-badge :status="$st" />
                                </td>
                                <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $device['name'] ?? $device['serial'] ?? '—' }}</td>
                                <td class="px-4 py-2.5 hidden sm:table-cell text-gray-500 dark:text-gray-400">{{ $device['model'] ?? '—' }}</td>
                                <td class="px-4 py-2.5 hidden md:table-cell text-gray-400 font-mono text-xs">{{ $device['serial'] ?? '—' }}</td>
                                <td class="px-4 py-2.5 hidden lg:table-cell text-gray-400 font-mono text-xs">{{ $device['lanIp'] ?? $device['_status']['lanIp'] ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Recent events --}}
            @if(count($events) > 0)
            <div x-data="{ search: '', shown: {{ count($events) }} }"
                 x-effect="
                    const q = search.toLowerCase();
                    let count = 0;
                    $el.querySelectorAll('[data-event-row]').forEach(r => {
                        const visible = !q || (r.dataset.searchText || '').includes(q);
                        r.style.display = visible ? '' : 'none';
                        if (visible) count++;
                    });
                    shown = count;
                 ">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                        Eventos recientes (<span x-text="shown"></span>/{{ count($events) }})
                    </p>
                    <div class="relative max-w-xs w-full">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model.debounce.150ms="search" placeholder="Buscar tipo, dispositivo o descripción..."
                               class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                                      bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200
                                      focus:outline-none focus:ring-1 focus:ring-teal-400 placeholder-gray-400">
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Hora</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Tipo</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden sm:table-cell">Dispositivo</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden lg:table-cell">Descripción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            @foreach($events as $event)
                            <tr data-event-row
                                data-search-text="{{ strtolower(($event['type'] ?? '') . ' ' . ($event['deviceName'] ?? $event['deviceSerial'] ?? '') . ' ' . ($event['description'] ?? '')) }}"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-2 text-xs text-gray-400 font-mono whitespace-nowrap">
                                    {{ isset($event['occurredAt']) ? rescue(fn () => \Carbon\Carbon::parse($event['occurredAt'])->format('d/m H:i'), '—', false) : '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-1.5 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        {{ $event['type'] ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 hidden sm:table-cell text-xs text-gray-500 dark:text-gray-400">
                                    {{ $event['deviceName'] ?? $event['deviceSerial'] ?? '—' }}
                                </td>
                                <td class="px-4 py-2 hidden lg:table-cell text-xs text-gray-400 max-w-xs truncate">
                                    {{ $event['description'] ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
