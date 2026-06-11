<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500 mb-1">
                    <a href="{{ route('admin.meraki.index') }}" class="hover:text-teal-500 transition">Meraki</a>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-gray-600 dark:text-gray-300">Alertas</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Central de Alertas</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Dispositivos offline y alerting de todas las organizaciones</p>
            </div>

            <div class="flex items-center gap-2">
                @if(!empty($problematic))
                <a href="{{ route('admin.meraki.export.alerts') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                          border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                          text-gray-600 dark:text-gray-300 rounded-lg hover:border-teal-400 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Exportar Excel
                </a>
                @endif
                <form method="POST" action="{{ route('admin.meraki.refresh.all') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                                   border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                                   text-gray-600 dark:text-gray-300 rounded-lg hover:border-teal-400 transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Actualizar
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if(isset($error))
            <div class="flex items-start gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3">
                <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
            </div>
            @endif

            {{-- KPIs --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1">Total equipos</p>
                    <p class="text-4xl font-black text-gray-800 dark:text-gray-100 leading-none">{{ $summary['total'] }}</p>
                    <p class="text-xs text-gray-400 mt-2">en todas las orgs</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-yellow-200 dark:border-yellow-900/40 rounded-2xl px-5 py-4">
                    <p class="text-xs font-semibold text-yellow-500 uppercase tracking-widest mb-1">Alerting</p>
                    <p class="text-4xl font-black text-yellow-600 dark:text-yellow-400 leading-none">{{ $summary['alerting'] }}</p>
                    <p class="text-xs text-gray-400 mt-2">requieren atención</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-red-200 dark:border-red-900/40 rounded-2xl px-5 py-4">
                    <p class="text-xs font-semibold text-red-500 uppercase tracking-widest mb-1">Offline</p>
                    <p class="text-4xl font-black text-red-600 dark:text-red-400 leading-none">{{ $summary['offline'] }}</p>
                    <p class="text-xs text-gray-400 mt-2">sin conexión</p>
                </div>
            </div>

            @if(empty($problematic))
            {{-- Todo OK --}}
            <div class="flex flex-col items-center justify-center py-24 text-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm">
                <div class="w-16 h-16 bg-green-50 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Todos los dispositivos están online</p>
                <p class="text-xs text-gray-400 mt-1">No hay alertas activas en ninguna organización.</p>
            </div>

            @else
            {{-- Tabla de dispositivos con problemas --}}
            @php
                $alertOrgs = collect($problematic)->pluck('_orgName')->filter()->unique()->values()->all();
            @endphp
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm"
                 x-data="{ search: '', status: 'all', org: 'all', shown: {{ count($problematic) }} }"
                 x-effect="
                    const q = search.toLowerCase();
                    let count = 0;
                    $el.querySelectorAll('[data-alert-row]').forEach(r => {
                        const matchSearch = !q || (r.dataset.searchText || '').includes(q);
                        const matchStatus = status === 'all' || r.dataset.status === status;
                        const matchOrg = org === 'all' || r.dataset.org === org;
                        const visible = matchSearch && matchStatus && matchOrg;
                        r.style.display = visible ? '' : 'none';
                        if (visible) count++;
                    });
                    shown = count;
                 ">

                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100">Dispositivos con problemas</p>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                     bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                            <span x-text="shown"></span> de {{ count($problematic) }}
                        </span>
                    </div>
                    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
                        <div class="relative flex-1 max-w-sm">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model.debounce.150ms="search" placeholder="Buscar nombre, serial, modelo..."
                                   class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                                          bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200
                                          focus:outline-none focus:ring-1 focus:ring-teal-400 placeholder-gray-400">
                        </div>
                        <div class="flex items-center gap-1.5">
                            @foreach(['all' => 'Todos', 'alerting' => 'Alerting', 'offline' => 'Offline'] as $val => $lbl)
                            <button type="button" @click="status = '{{ $val }}'"
                                    :class="status === '{{ $val }}' ? 'bg-teal-500 text-white border-teal-500' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700'"
                                    class="px-2.5 py-1.5 text-xs font-semibold rounded-lg border transition">{{ $lbl }}</button>
                            @endforeach
                        </div>
                        @if(count($alertOrgs) > 1)
                        <select x-model="org"
                                class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                                       bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300
                                       focus:outline-none focus:ring-1 focus:ring-teal-400">
                            <option value="all">Todas las orgs</option>
                            @foreach($alertOrgs as $orgName)
                            <option value="{{ $orgName }}">{{ $orgName }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>
                </div>

                <div class="hidden px-6 py-10 text-center" :class="{ '!block': shown === 0 }">
                    <p class="text-sm text-gray-400">Ningún dispositivo coincide con el filtro.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-700/20 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Dispositivo</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden sm:table-cell">Modelo</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden md:table-cell">Organización</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden lg:table-cell">Serial</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Último reporte</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            @foreach($problematic as $device)
                            @php
                                $st = $device['_status']['status'] ?? 'unknown';
                                $color = match($st) {
                                    'alerting' => ['dot' => 'bg-yellow-400', 'text' => 'text-yellow-600 dark:text-yellow-400', 'label' => 'Alerting', 'row' => 'bg-yellow-50/30 dark:bg-yellow-900/5'],
                                    'offline'  => ['dot' => 'bg-red-400',    'text' => 'text-red-600 dark:text-red-400',       'label' => 'Offline',  'row' => 'bg-red-50/30 dark:bg-red-900/5'],
                                    default    => ['dot' => 'bg-gray-300',   'text' => 'text-gray-400',                        'label' => $st,        'row' => ''],
                                };
                                $lastRaw = $device['_status']['lastReportedAt'] ?? null;
                                try {
                                    $lastDt  = $lastRaw ? \Carbon\Carbon::parse($lastRaw) : null;
                                    $lastAgo = $lastDt?->diffForHumans();
                                    $lastFmt = $lastDt?->format('d/m/Y H:i');
                                    $lastHrs = $lastDt ? $lastDt->diffInHours(now()) : null;
                                } catch (\Exception $e) { $lastDt = $lastAgo = $lastFmt = $lastHrs = null; }
                                $urgencyColor = match(true) {
                                    $lastHrs === null           => 'text-gray-400',
                                    $lastHrs > 72               => 'text-red-600 dark:text-red-400 font-bold',
                                    $lastHrs > 24               => 'text-red-500 dark:text-red-400',
                                    default                     => 'text-yellow-600 dark:text-yellow-400',
                                };
                            @endphp
                            <tr data-alert-row
                                data-search-text="{{ strtolower(($device['name'] ?? '') . ' ' . ($device['serial'] ?? '') . ' ' . ($device['model'] ?? '')) }}"
                                data-status="{{ $st }}"
                                data-org="{{ $device['_orgName'] ?? '' }}"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition {{ $color['row'] }}">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $color['dot'] }} shrink-0 {{ $st === 'alerting' ? 'animate-pulse' : '' }}"></span>
                                        <span class="text-xs font-semibold {{ $color['text'] }}">{{ $color['label'] }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm">
                                        {{ $device['name'] ?? $device['serial'] ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $device['serial'] ?? '' }}</p>
                                </td>
                                <td class="px-5 py-3.5 hidden sm:table-cell">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                        {{ $device['model'] ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 hidden md:table-cell">
                                    @if(!empty($device['_orgId']))
                                    <a href="{{ route('admin.meraki.organization', $device['_orgId']) }}"
                                       class="text-xs text-teal-600 dark:text-teal-400 hover:underline">
                                        {{ $device['_orgName'] ?? $device['_orgId'] }}
                                    </a>
                                    @else
                                    <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 hidden lg:table-cell font-mono text-xs text-gray-400">
                                    {{ $device['serial'] ?? '—' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($lastFmt)
                                    <p class="text-xs {{ $urgencyColor }}">{{ $lastAgo }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $lastFmt }}</p>
                                    @else
                                    <span class="text-xs text-gray-300 dark:text-gray-600">Sin datos</span>
                                    @endif
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
