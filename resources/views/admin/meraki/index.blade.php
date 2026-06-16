<x-app-layout>
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @endpush

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Meraki</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Estado de dispositivos por modelo</p>
            </div>

            <div class="flex items-center gap-2">
                @if(!empty($summary) && ($summary['offline'] + $summary['alerting']) > 0)
                <a href="{{ route('admin.meraki.alerts') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                          border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/20
                          text-red-600 dark:text-red-400 rounded-lg hover:border-red-400 transition">
                    <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span>
                    {{ $summary['offline'] + $summary['alerting'] }} alertas
                </a>
                @endif

                <a href="{{ route('admin.meraki.licenses') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                          border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                          text-gray-600 dark:text-gray-300 rounded-lg hover:border-teal-400 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Licencias
                </a>

                <a href="{{ route('admin.meraki.export.devices') }}"
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

                @if(!empty($organizations))
                <div x-data="{ open: false, orgSearch: '' }" class="relative">
                    <button @click="open = !open; if(open) $nextTick(() => $refs.orgSearch.focus())"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                                   border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                                   text-gray-600 dark:text-gray-300 rounded-lg hover:border-teal-400 transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                        </svg>
                        Organizaciones
                        <span class="text-[10px] text-gray-400">({{ count($organizations) }})</span>
                        <svg class="w-3 h-3 transition-transform duration-150" :class="open ? 'rotate-180' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="open = false; orgSearch = ''" x-transition
                         class="absolute right-0 mt-1 w-72 bg-white dark:bg-gray-800 border border-gray-200
                                dark:border-gray-700 rounded-xl shadow-lg z-10 flex flex-col" style="display:none;">

                        {{-- Buscador sticky --}}
                        <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                            <div class="relative">
                                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" x-ref="orgSearch" x-model="orgSearch"
                                       @keydown.escape.stop="open = false; orgSearch = ''"
                                       placeholder="Buscar organización..."
                                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                                              bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200
                                              focus:outline-none focus:ring-1 focus:ring-teal-400 placeholder-gray-400">
                            </div>
                        </div>

                        {{-- Lista scrollable --}}
                        @php
                            $orgNamesLower = collect($organizations)->map(fn($o) => strtolower($o['name']))->values()->all();
                        @endphp
                        <div class="overflow-y-auto py-1" style="max-height:18rem; overscroll-behavior:contain;">
                            @foreach($organizations as $org)
                            <a href="{{ route('admin.meraki.organization', $org['id']) }}"
                               x-show="!orgSearch || @js(strtolower($org['name'])).includes(orgSearch.toLowerCase())"
                               class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300
                                      hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-teal-400 shrink-0"></span>
                                <span class="truncate">{{ $org['name'] }}</span>
                            </a>
                            @endforeach

                            {{-- Sin resultados --}}
                            <p x-show="orgSearch && !@js($orgNamesLower).some(n => n.includes(orgSearch.toLowerCase()))"
                               class="px-3 py-3 text-xs text-gray-400 text-center" style="display:none;">
                                Sin coincidencias
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if(session('success'))
            <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl px-4 py-3 shadow-sm">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
            </div>
            @endif

            @if(session('error') || isset($error))
            <div class="flex items-start gap-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl px-4 py-3 shadow-sm">
                <svg class="w-4 h-4 text-rose-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-rose-700 dark:text-rose-300">{{ session('error') ?? $error }}</p>
            </div>
            @endif

            @if(empty($grouped))
            <div class="flex flex-col items-center justify-center py-24 text-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm">
                <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/30 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Sin dispositivos</p>
                <p class="text-xs text-gray-400 mt-1">Verifica que <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">MERAKI_API_KEY</code> esté configurado.</p>
            </div>
            @else

            <div class="flex flex-row gap-6 items-stretch">
                {{-- Column 1: Donut chart (Exact 25%) --}}
                <div style="width: 25%;" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm flex flex-col items-center justify-between text-center shrink-0">
                    <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Estado</p>
                    
                    <div class="relative my-6" style="width:150px;height:150px">
                        <canvas id="merakiDonut"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <p class="text-3xl font-black text-gray-800 dark:text-gray-100 leading-none">
                                {{ $summary['total'] }}
                            </p>
                            <p class="text-[9px] text-gray-400 mt-1 font-bold uppercase tracking-wider">Equipos</p>
                        </div>
                    </div>

                    <div class="flex justify-center gap-4 w-full">
                        @foreach(['online' => 'bg-green-400', 'offline' => 'bg-red-400', 'alerting' => 'bg-yellow-400'] as $key => $color)
                            <div class="flex flex-col items-center gap-1">
                                <span class="w-2.5 h-2.5 rounded-full {{ $color }} shadow-sm"></span>
                                <span class="text-[10px] font-black text-gray-400">{{ $summary[$key] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Column 2: Stacked KPIs (Exact 15%) --}}
                <div style="width: 15%;" class="flex flex-col gap-3 shrink-0">
                    {{-- Online KPI --}}
                    <div class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-3 shadow-sm flex items-center justify-between group overflow-hidden relative">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-green-500"></div>
                        <div class="z-10 pl-1">
                            <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Online</p>
                            <h3 class="text-lg font-black text-gray-800 dark:text-gray-100 mt-0.5">{{ $summary['online'] }}</h3>
                        </div>
                        <div class="w-7 h-7 bg-green-50 dark:bg-green-900/20 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Offline KPI --}}
                    <div class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-3 shadow-sm flex items-center justify-between group overflow-hidden relative">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500"></div>
                        <div class="z-10 pl-1">
                            <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Offline</p>
                            <h3 class="text-lg font-black text-gray-800 dark:text-gray-100 mt-0.5">{{ $summary['offline'] }}</h3>
                        </div>
                        <div class="w-7 h-7 bg-red-50 dark:bg-red-900/20 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Alerting KPI --}}
                    <div class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-3 shadow-sm flex items-center justify-between group overflow-hidden relative">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-yellow-500"></div>
                        <div class="z-10 pl-1">
                            <p class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Alerting</p>
                            <h3 class="text-lg font-black text-gray-800 dark:text-gray-100 mt-0.5">{{ $summary['alerting'] }}</h3>
                        </div>
                        <div class="w-7 h-7 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Column 3: Bar Chart (Exact 60%) --}}
                <div style="width: 60%;" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm flex flex-col shrink-0">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-4">Distribución por Modelo</p>
                    <div class="flex-1 min-h-[220px]">
                        <canvas id="merakiModelsBar"></canvas>
                    </div>
                </div>
            </div>

            {{-- Model cards grid --}}
            @php
                $categories = collect($grouped)->pluck('prefix')->unique()->values()->all();
            @endphp
            <div class="space-y-4"
                 x-data="{ search: '', category: 'all', shown: {{ count($grouped) }} }"
                 x-effect="
                    const q = search.toLowerCase();
                    let count = 0;
                    $el.querySelectorAll('[data-model-card]').forEach(c => {
                        const matchSearch = !q || (c.dataset.searchText || '').includes(q);
                        const matchCat = category === 'all' || c.dataset.category === category;
                        const visible = matchSearch && matchCat;
                        c.style.display = visible ? '' : 'none';
                        if (visible) count++;
                    });
                    shown = count;
                 ">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Dispositivos por Modelo</h3>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-widest">
                        <span x-text="shown"></span> de {{ count($grouped) }} modelos
                    </p>
                </div>

                {{-- Filtros --}}
                <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                    <div class="relative flex-1 max-w-sm">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model.debounce.150ms="search"
                               placeholder="Buscar modelo o categoría..."
                               class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700
                                      bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200
                                      focus:outline-none focus:ring-1 focus:ring-teal-400 placeholder-gray-400">
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button type="button" @click="category = 'all'"
                                :class="category === 'all' ? 'bg-teal-500 text-white border-teal-500' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700'"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition">Todos</button>
                        @foreach($categories as $cat)
                        <button type="button" @click="category = '{{ $cat }}'"
                                :class="category === '{{ $cat }}' ? 'bg-teal-500 text-white border-teal-500' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700'"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition">
                            {{ config("meraki.device_types.{$cat}", $cat) }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <div class="hidden py-16 text-center" :class="{ '!block': shown === 0 }">
                    <p class="text-sm text-gray-400">Ningún modelo coincide con el filtro.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($grouped as $model => $group)
                    @php
                        $total    = count($group['devices']);
                        $pct      = $total > 0 ? round($group['online'] / $total * 100) : 0;
                        $cardBorder = $group['offline'] > 0
                            ? ($pct >= 80 ? 'border-yellow-200 dark:border-yellow-900/40' : 'border-red-200 dark:border-red-900/40')
                            : 'border-gray-200 dark:border-gray-700';
                        $barColor = $pct === 100 ? 'bg-green-400' : ($pct >= 80 ? 'bg-yellow-400' : 'bg-red-400');
                        $pctColor = $pct === 100 ? 'text-green-600 dark:text-green-400' : ($pct >= 80 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
                    @endphp
                    <a href="{{ route('admin.meraki.model', $model) }}"
                       data-model-card
                       data-search-text="{{ strtolower($model . ' ' . $group['label'] . ' ' . $group['prefix']) }}"
                       data-category="{{ $group['prefix'] }}"
                       class="group bg-white dark:bg-gray-800 border {{ $cardBorder }} rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-teal-400 dark:hover:border-teal-400/50 transition-all duration-300 flex flex-col">

                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-base font-bold text-gray-800 dark:text-gray-100 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                                    {{ $model }}
                                </h4>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $group['label'] }}</p>
                            </div>
                            <div class="w-8 h-8 bg-gray-50 dark:bg-gray-700/50 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-teal-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>

                        <div class="mt-auto space-y-4">
                            <div class="flex items-end justify-between">
                                <div>
                                    <p class="text-2xl font-black text-gray-800 dark:text-gray-100">{{ $total }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Unidades</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold {{ $pctColor }}">{{ $pct }}%</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Online</p>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700/50 rounded-full overflow-hidden">
                                    <div class="h-1.5 rounded-full transition-all duration-700 {{ $barColor }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-tighter">
                                    <span class="text-green-500">{{ $group['online'] }} ON</span>
                                    @if($group['offline'] > 0)
                                        <span class="text-red-500">{{ $group['offline'] }} OFF</span>
                                    @endif
                                    @if($group['alerting'] > 0)
                                        <span class="text-yellow-500">{{ $group['alerting'] }} ALERT</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>

            @endif
        </div>
    </div>

    @if(!empty($grouped))
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const isDark = document.documentElement.classList.contains('dark');
        const gray400 = '#9ca3af';

        // --- Donut Chart ---
        new Chart(document.getElementById('merakiDonut'), {
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

        // --- Models Bar Chart ---
        @php
            $labels = array_keys($grouped);
            $counts = array_map(fn($g) => count($g['devices']), $grouped);
        @endphp

        new Chart(document.getElementById('merakiModelsBar'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($labels) !!},
                datasets: [{
                    label: 'Unidades',
                    data: {!! json_encode($counts) !!},
                    backgroundColor: '#2dd4bf', // teal-400
                    borderRadius: 4,
                    barThickness: 20,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y} unidades`
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: gray400,
                            font: { size: 9, weight: 'bold' },
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: gray400,
                            font: { size: 9, weight: 'bold' },
                            precision: 0
                        },
                        grid: { 
                            color: isDark ? '#374151' : '#f3f4f6',
                            drawBorder: false
                        }
                    }
                }
            }
        });
    });
    </script>
    @endif
</x-app-layout>
