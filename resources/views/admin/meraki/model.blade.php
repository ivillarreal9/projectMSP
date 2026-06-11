<x-app-layout>
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @endpush

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500 mb-1">
                    <a href="{{ route('admin.meraki.index') }}" class="hover:text-teal-500 transition">Meraki</a>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-gray-600 dark:text-gray-300">{{ $model }}</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $model }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $label }}</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.meraki.export.devices', ['model' => $model]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                          border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                          text-gray-600 dark:text-gray-300 rounded-lg hover:border-teal-400 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Exportar Excel
                </a>
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

            @if(session('error'))
            <div class="flex items-start gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3">
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
            @endif

            {{-- Layout superior: donut (1/3) + KPIs en grid 2x2 (2/3) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
                {{-- Donut chart --}}
                <div class="lg:col-span-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm flex flex-col items-center justify-center text-center">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-6">Estado del Dispositivo</p>

                    <div class="relative mb-6" style="width:160px;height:160px">
                        <canvas id="modelDonut"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <p class="text-3xl font-black text-gray-800 dark:text-gray-100 leading-none">
                                {{ $summary['total'] }}
                            </p>
                            <p class="text-[9px] text-gray-400 mt-1 font-bold uppercase tracking-wider">Unidades</p>
                        </div>
                    </div>

                    <div class="flex justify-center gap-6 w-full">
                        @foreach(['online' => ['bg-green-400', 'Online'], 'offline' => ['bg-red-400', 'Offline'], 'alerting' => ['bg-yellow-400', 'Alerting']] as $key => $meta)
                            <div class="flex flex-col items-center gap-1">
                                <span class="w-2.5 h-2.5 rounded-full {{ $meta[0] }} shadow-sm"></span>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">{{ $meta[1] }}</span>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-200">{{ $summary[$key] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- KPIs en grid 2x2 ocupando 2/3 --}}
                <div class="lg:col-span-2 grid grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-gray-300 dark:bg-gray-600"></div>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Total</p>
                        <h3 class="text-3xl font-black text-gray-800 dark:text-gray-100 leading-none">{{ $summary['total'] }}</h3>
                        <p class="text-[9px] text-gray-400 mt-1.5 font-bold uppercase">Dispositivos</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-green-100 dark:border-green-900/30 rounded-2xl p-5 shadow-sm flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-green-500"></div>
                        <p class="text-[9px] font-black text-green-500 uppercase tracking-widest mb-1">Online</p>
                        <h3 class="text-3xl font-black text-green-600 dark:text-green-400 leading-none">{{ $summary['online'] }}</h3>
                        <p class="text-[9px] text-green-600/70 mt-1.5 font-bold uppercase">{{ $summary['total'] > 0 ? round($summary['online']/$summary['total']*100) : 0 }}% OK</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-red-100 dark:border-red-900/30 rounded-2xl p-5 shadow-sm flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500"></div>
                        <p class="text-[9px] font-black text-red-500 uppercase tracking-widest mb-1">Offline</p>
                        <h3 class="text-3xl font-black text-red-600 dark:text-red-400 leading-none">{{ $summary['offline'] }}</h3>
                        <p class="text-[9px] text-red-600/70 mt-1.5 font-bold uppercase">{{ $summary['total'] > 0 ? round($summary['offline']/$summary['total']*100) : 0 }}% OFF</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute left-0 top-0 bottom-0 w-1 {{ $licSummary['expired'] > 0 ? 'bg-red-500' : 'bg-teal-500' }}"></div>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Licencias</p>
                        <h3 class="text-3xl font-black text-gray-800 dark:text-gray-100 leading-none">{{ $licSummary['total'] }}</h3>
                        <p class="text-[9px] mt-1.5 font-bold uppercase {{ $licSummary['expired'] > 0 ? 'text-red-500' : 'text-gray-400' }}">
                            {{ $licSummary['expired'] > 0 ? $licSummary['expired'] . ' Vencidas' : 'Activas' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Table section --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm"
                 x-data="{ search: '', status: 'all', shown: {{ $summary['total'] }} }"
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

                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100">Dispositivos</p>
                        <span class="text-xs text-gray-400"><span x-text="shown"></span> de {{ $summary['total'] }}</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model.debounce.150ms="search"
                                   placeholder="Buscar nombre, serial, org..."
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

                <div class="hidden px-6 py-10 text-center" :class="{ '!block': shown === 0 }">
                    <p class="text-sm text-gray-400">Ningún dispositivo coincide con el filtro.</p>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide w-28">Estado</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Nombre</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Licencia</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Vencimiento</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Último reporte</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach($devices as $device)
                        @php
                            $st = $device['_status']['status'] ?? 'unknown';

                            // License state
                            $lState   = strtolower($device['_licState'] ?? '');
                            $isActive  = str_contains($lState, 'active');
                            $isExpired = str_contains($lState, 'expired');
                            $licDotColor = $isExpired ? 'bg-red-400' : ($isActive ? 'bg-green-400' : 'bg-gray-300');
                            $licTextColor = $isExpired ? 'text-red-600 dark:text-red-400' : ($isActive ? 'text-green-600 dark:text-green-400' : 'text-gray-400');

                            // Expiration
                            try {
                                $expDate = !empty($device['_licExpiration'])
                                    ? \Carbon\Carbon::parse($device['_licExpiration'])
                                    : null;
                                $daysLeft = $expDate ? (int) now()->diffInDays($expDate, false) : null;
                            } catch (\Exception $e) { $expDate = null; $daysLeft = null; }

                            $expColor = 'text-gray-600 dark:text-gray-300';
                            if ($daysLeft !== null) {
                                if ($daysLeft < 0)  $expColor = 'text-red-600 dark:text-red-400';
                                elseif ($daysLeft < 30)  $expColor = 'text-red-500 dark:text-red-400';
                                elseif ($daysLeft < 90)  $expColor = 'text-yellow-600 dark:text-yellow-400';
                            }
                        @endphp
                        @php
                            $searchText = strtolower(($device['name'] ?? '') . ' ' . ($device['serial'] ?? '') . ' ' . ($device['_orgName'] ?? '') . ' ' . ($device['_licType'] ?? ''));
                        @endphp
                        <tr data-device-row
                            data-search-text="{{ $searchText }}"
                            data-status="{{ $st }}"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">

                            {{-- Estado --}}
                            <td class="px-5 py-4">
                                <x-meraki.status-badge :status="$st" />
                            </td>

                            {{-- Nombre --}}
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm">
                                    {{ $device['name'] ?? $device['serial'] ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    <a href="{{ route('admin.meraki.organization', $device['_orgId']) }}"
                                       class="hover:text-teal-500 transition">{{ $device['_orgName'] }}</a>
                                </p>
                            </td>

                            {{-- Tipo de licencia --}}
                            <td class="px-5 py-4">
                                @if($device['_licType'])
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $licDotColor }} shrink-0"></span>
                                    <span class="text-sm font-semibold {{ $licTextColor }}">{{ $device['_licType'] }}</span>
                                </div>
                                @else
                                <span class="text-xs text-gray-300 dark:text-gray-600">Sin licencia</span>
                                @endif
                            </td>

                            {{-- Vencimiento --}}
                            <td class="px-5 py-4">
                                @if($expDate)
                                <p class="text-sm font-semibold {{ $expColor }}">
                                    {{ $expDate->format('d M Y') }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    @if($daysLeft < 0)
                                        Vencida hace {{ abs($daysLeft) }} días
                                    @elseif($daysLeft === 0)
                                        Vence hoy
                                    @else
                                        Vence en {{ $daysLeft }} días
                                    @endif
                                </p>
                                @else
                                <span class="text-xs text-gray-300 dark:text-gray-600">Sin fecha</span>
                                @endif
                            </td>

                            {{-- Último reporte --}}
                            <td class="px-5 py-4">
                                <x-meraki.last-report :at="$device['_status']['lastReportedAt'] ?? null" />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const isDark = document.documentElement.classList.contains('dark');

        // --- Donut Chart ---
        new Chart(document.getElementById('modelDonut'), {
            type: 'doughnut',
            data: {
                labels: ['Online', 'Offline', 'Alerting'],
                datasets: [{
                    data: [{{ $summary['online'] }}, {{ $summary['offline'] }}, {{ $summary['alerting'] }}],
                    backgroundColor: ['#4ade80', '#f87171', '#facc15'],
                    borderColor: isDark ? '#1f2937' : '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4,
                }]
            },
            options: {
                cutout: '75%',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} dispositivos`
                        }
                    }
                }
            }
        });
    });
    </script>
</x-app-layout>
