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
                    <span class="text-gray-600 dark:text-gray-300">Licencias</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Licencias</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Licenciamiento por modelo de dispositivo</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.meraki.export.licenses') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                          border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800
                          text-gray-600 dark:text-gray-300 rounded-lg hover:border-teal-400 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Exportar
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

            @if(isset($error))
            <div class="flex items-start gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3">
                <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
            </div>
            @endif

            @if(empty($byModel))
            <div class="flex flex-col items-center justify-center py-24 text-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm">
                <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/30 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Sin licencias disponibles</p>
            </div>
            @else

            {{-- ─── Top stats ───────────────────────────────────────────────────────────── --}}
            <div class="flex flex-row gap-6 items-stretch">

                {{-- Donut --}}
                <div style="width:22%;" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm flex flex-col items-center justify-between text-center shrink-0">
                    <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Estado</p>
                    <div class="relative my-6" style="width:140px;height:140px">
                        <canvas id="licDonut"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <p class="text-3xl font-black text-gray-800 dark:text-gray-100 leading-none">{{ $total }}</p>
                            <p class="text-[9px] text-gray-400 mt-1 font-bold uppercase tracking-wider">Licencias</p>
                        </div>
                    </div>
                    <div class="flex justify-center gap-4 w-full">
                        <div class="flex flex-col items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full shadow-sm" style="background-color:#4ade80"></span>
                            <span class="text-[10px] font-black text-gray-400">{{ $totalActive }}</span>
                            <span class="text-[9px] text-gray-400">Activas</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full shadow-sm" style="background-color:#60a5fa"></span>
                            <span class="text-[10px] font-black text-gray-400">{{ $totalUnused }}</span>
                            <span class="text-[9px] text-gray-400">Sin usar</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-full shadow-sm" style="background-color:#f87171"></span>
                            <span class="text-[10px] font-black text-gray-400">{{ $totalExpired }}</span>
                            <span class="text-[9px] text-gray-400">Vencidas</span>
                        </div>
                    </div>
                </div>

                {{-- KPI stack --}}
                <div style="width:15%;" class="flex flex-col gap-3 shrink-0">
                    <div class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-3 shadow-sm flex items-center justify-between overflow-hidden relative">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-green-500"></div>
                        <div class="pl-1">
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Activas</p>
                            <h3 class="text-lg font-black text-gray-800 dark:text-gray-100 mt-0.5">{{ $totalActive }}</h3>
                        </div>
                        <div class="w-7 h-7 bg-green-50 dark:bg-green-900/20 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-3 shadow-sm flex items-center justify-between overflow-hidden relative">
                        <div class="absolute left-0 top-0 bottom-0 w-1" style="background-color:#3b82f6"></div>
                        <div class="pl-1">
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Sin usar</p>
                            <h3 class="text-lg font-black text-gray-800 dark:text-gray-100 mt-0.5">{{ $totalUnused }}</h3>
                        </div>
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background-color:#eff6ff">
                            <svg class="w-4 h-4" style="color:#3b82f6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-3 shadow-sm flex items-center justify-between overflow-hidden relative">
                        <div class="absolute left-0 top-0 bottom-0 w-1" style="background-color:#ef4444"></div>
                        <div class="pl-1">
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Vencidas</p>
                            <h3 class="text-lg font-black text-gray-800 dark:text-gray-100 mt-0.5">{{ $totalExpired }}</h3>
                        </div>
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background-color:#fef2f2">
                            <svg class="w-4 h-4" style="color:#ef4444" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Bar chart por modelo --}}
                <div style="width:63%;" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm flex flex-col shrink-0">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-4">Distribución por Modelo</p>
                    <div class="flex-1 min-h-[200px]">
                        <canvas id="licModelsBar"></canvas>
                    </div>
                </div>
            </div>

            {{-- ─── Model cards + detail panel ──────────────────────────────────────────── --}}
            <div x-data="licencias()" class="space-y-6">

                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Licencias por Modelo</h3>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-widest">{{ count($byModel) }} modelos</p>
                </div>

                {{-- Cards grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    @foreach($byModel as $model => $group)
                    @php
                        $total_m   = count($group['licenses']);
                        $pct       = $total_m > 0 ? round($group['active'] / $total_m * 100) : 0;
                        $hasExpired = $group['expired'] > 0;
                        $cardBorder = $hasExpired
                            ? 'border-red-200 dark:border-red-900/40'
                            : ($group['unused'] > 0 ? 'border-blue-200 dark:border-blue-900/40' : 'border-gray-200 dark:border-gray-700');
                        $barColor  = $pct === 100 ? 'bg-green-400' : ($pct >= 70 ? 'bg-yellow-400' : 'bg-red-400');
                        $pctColor  = $pct === 100 ? 'text-green-600 dark:text-green-400' : ($pct >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
                        $modelKey  = Str::slug($model);
                        $licenses_json = json_encode($group['licenses']);
                    @endphp

                    <button
                        type="button"
                        @click="toggle('{{ $modelKey }}')"
                        :class="active === '{{ $modelKey }}' ? 'ring-2 ring-teal-400 border-teal-400 dark:border-teal-400' : '{{ $cardBorder }}'"
                        class="group text-left bg-white dark:bg-gray-800 border rounded-2xl p-5 shadow-sm
                               hover:shadow-md hover:border-teal-400 dark:hover:border-teal-400/50
                               transition-all duration-300 flex flex-col w-full focus:outline-none">

                        <div class="flex items-start justify-between mb-4">
                            <div class="min-w-0">
                                <h4 class="text-base font-bold text-gray-800 dark:text-gray-100 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors truncate">
                                    {{ $model }}
                                </h4>
                                <p class="text-xs text-gray-400 mt-0.5">{{ strtoupper($group['prefix'] ?? '') }}</p>
                            </div>
                            <div class="w-8 h-8 bg-gray-50 dark:bg-gray-700/50 rounded-lg flex items-center justify-center shrink-0 ml-2">
                                @if($hasExpired)
                                <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                @else
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-teal-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                @endif
                            </div>
                        </div>

                        <div class="mt-auto space-y-4">
                            <div class="flex items-end justify-between">
                                <div>
                                    <p class="text-2xl font-black text-gray-800 dark:text-gray-100">{{ $total_m }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Licencias</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold {{ $pctColor }}">{{ $pct }}%</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Activas</p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700/50 rounded-full overflow-hidden">
                                    <div class="h-1.5 rounded-full transition-all duration-700 {{ $barColor }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-tighter">
                                    @if($group['active'] > 0)
                                        <span class="text-green-500">{{ $group['active'] }} activas</span>
                                    @endif
                                    @if($group['unused'] > 0)
                                        <span class="text-blue-500">{{ $group['unused'] }} sin usar</span>
                                    @endif
                                    @if($group['expired'] > 0)
                                        <span class="text-red-500">{{ $group['expired'] }} vencidas</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </button>
                    @endforeach
                </div>

                {{-- ─── Detail panel ──────────────────────────────────────────────────────── --}}
                @foreach($byModel as $model => $group)
                @php $modelKey = Str::slug($model); @endphp
                <div x-show="active === '{{ $modelKey }}'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-2"
                     class="bg-white dark:bg-gray-800 border border-teal-200 dark:border-teal-900/50 rounded-2xl overflow-hidden shadow-sm"
                     style="display:none;">

                    {{-- Panel header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-teal-50 dark:bg-teal-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $model }}</h4>
                                <p class="text-xs text-gray-400">{{ count($group['licenses']) }} {{ count($group['licenses']) === 1 ? 'licencia' : 'licencias' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            @if($group['active'] > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                {{ $group['active'] }} activas
                            </span>
                            @endif
                            @if($group['unused'] > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                {{ $group['unused'] }} sin usar
                            </span>
                            @endif
                            @if($group['expired'] > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                {{ $group['expired'] }} vencidas
                            </span>
                            @endif

                            <button @click="active = null" type="button"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- ── Tabla especial para Sin asignar (data cruda de la API) ── --}}
                    @if($model === 'Sin asignar')
                    <div class="px-6 py-3 bg-amber-50/50 dark:bg-amber-900/10 border-b border-amber-100 dark:border-amber-900/30 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-amber-700 dark:text-amber-400">
                            Estas licencias no tienen un dispositivo asociado en el inventario. Se muestra la data cruda devuelta por la API de Meraki.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-700/20 border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Para</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Tipo de licencia</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Clave</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">N° Orden</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Reclamo</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Activación</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Vencimiento</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Organización</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                @foreach($group['licenses'] as $license)
                                @php
                                    $lState = strtolower($license['state'] ?? '');
                                    $lColor = match($lState) {
                                        'active'  => ['dot' => 'bg-green-400', 'text' => 'text-green-600 dark:text-green-400',  'label' => 'Activa'],
                                        'expired' => ['dot' => 'bg-red-400',   'text' => 'text-red-600 dark:text-red-400',      'label' => 'Vencida'],
                                        'unused'  => ['dot' => 'bg-blue-400',  'text' => 'text-blue-600 dark:text-blue-400',    'label' => 'Sin usar'],
                                        default   => ['dot' => 'bg-gray-300',  'text' => 'text-gray-400',                       'label' => ucfirst($lState ?: '—')],
                                    };
                                    try {
                                        $lExp      = !empty($license['expirationDate']) ? \Carbon\Carbon::parse($license['expirationDate'])->format('d M Y') : null;
                                        $lDays     = !empty($license['expirationDate']) ? (int) now()->diffInDays(\Carbon\Carbon::parse($license['expirationDate']), false) : null;
                                        $lClaim    = !empty($license['claimDate'])      ? \Carbon\Carbon::parse($license['claimDate'])->format('d M Y')      : null;
                                        $lActivate = !empty($license['activationDate']) ? \Carbon\Carbon::parse($license['activationDate'])->format('d M Y') : null;
                                    } catch (\Exception $e) {
                                        $lExp = $lDays = $lClaim = $lActivate = null;
                                    }
                                    $expColor = match(true) {
                                        $lDays !== null && $lDays < 0  => 'text-red-600 dark:text-red-400',
                                        $lDays !== null && $lDays < 30 => 'text-red-500 dark:text-red-400',
                                        $lDays !== null && $lDays < 90 => 'text-yellow-600 dark:text-yellow-400',
                                        default                        => 'text-gray-700 dark:text-gray-300',
                                    };

                                    // Extraer prefijo del tipo de licencia → marca del equipo
                                    $licType   = strtoupper($license['licenseType'] ?? '');
                                    $devPrefix = match(true) {
                                        str_starts_with($licType, 'MR') => ['prefix' => 'MR', 'label' => 'Access Point',        'color' => 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400'],
                                        str_starts_with($licType, 'MS') => ['prefix' => 'MS', 'label' => 'Switch',              'color' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'],
                                        str_starts_with($licType, 'MX') => ['prefix' => 'MX', 'label' => 'Firewall / Security', 'color' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400'],
                                        str_starts_with($licType, 'MG') => ['prefix' => 'MG', 'label' => 'Cellular Gateway',   'color' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400'],
                                        str_starts_with($licType, 'MV') => ['prefix' => 'MV', 'label' => 'Cámara',             'color' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400'],
                                        str_starts_with($licType, 'MT') => ['prefix' => 'MT', 'label' => 'Sensor',             'color' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400'],
                                        default                         => ['prefix' => '—',  'label' => 'Desconocido',         'color' => 'bg-gray-100 dark:bg-gray-700 text-gray-500'],
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                    {{-- Estado --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $lColor['dot'] }} shrink-0"></span>
                                            <span class="text-xs font-medium {{ $lColor['text'] }}">{{ $lColor['label'] }}</span>
                                        </div>
                                    </td>
                                    {{-- Para (marca/tipo de equipo) --}}
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-xs font-semibold {{ $devPrefix['color'] }}">
                                            <span class="font-mono font-bold">{{ $devPrefix['prefix'] }}</span>
                                            <span class="text-[10px] font-normal opacity-80">{{ $devPrefix['label'] }}</span>
                                        </span>
                                    </td>
                                    {{-- Tipo de licencia --}}
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono
                                                     bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            {{ $license['licenseType'] ?? '—' }}
                                        </span>
                                    </td>
                                    {{-- Clave --}}
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-xs text-gray-600 dark:text-gray-300 select-all">
                                            {{ $license['licenseKey'] ?? '—' }}
                                        </span>
                                    </td>
                                    {{-- N° Orden --}}
                                    <td class="px-4 py-3">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $license['orderNumber'] ?? '—' }}
                                        </span>
                                    </td>
                                    {{-- Fecha reclamo --}}
                                    <td class="px-4 py-3">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $lClaim ?? '—' }}</span>
                                    </td>
                                    {{-- Activación --}}
                                    <td class="px-4 py-3">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $lActivate ?? '—' }}</span>
                                    </td>
                                    {{-- Vencimiento --}}
                                    <td class="px-4 py-3">
                                        @if($lExp)
                                        <p class="text-xs font-semibold {{ $expColor }}">{{ $lExp }}</p>
                                        @if($lDays !== null)
                                        <p class="text-[10px] text-gray-400 mt-0.5">
                                            {{ $lDays > 0 ? 'en ' . $lDays . 'd' : 'hace ' . abs($lDays) . 'd' }}
                                        </p>
                                        @endif
                                        @else
                                        <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    {{-- Org --}}
                                    <td class="px-4 py-3">
                                        @if(!empty($license['_org']))
                                        <a href="{{ route('admin.meraki.organization', $license['_org']['id']) }}"
                                           class="text-xs text-teal-600 dark:text-teal-400 hover:underline">
                                            {{ $license['_org']['name'] }}
                                        </a>
                                        @else
                                        <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ── Tabla normal para modelos con dispositivo asignado ── --}}
                    @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-700/20 border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Dispositivo</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Tipo de licencia</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide hidden md:table-cell">Organización</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                @foreach($group['licenses'] as $license)
                                @php
                                    $lState = strtolower($license['state'] ?? '');
                                    $lColor = match($lState) {
                                        'active'  => ['dot' => 'bg-green-400', 'text' => 'text-green-600 dark:text-green-400',  'label' => 'Activa'],
                                        'expired' => ['dot' => 'bg-red-400',   'text' => 'text-red-600 dark:text-red-400',      'label' => 'Vencida'],
                                        'unused'  => ['dot' => 'bg-blue-400',  'text' => 'text-blue-600 dark:text-blue-400',    'label' => 'Sin usar'],
                                        default   => ['dot' => 'bg-gray-300',  'text' => 'text-gray-400',                       'label' => ucfirst($lState)],
                                    };
                                    $device = $license['_device'] ?? null;
                                    try {
                                        $lExp  = isset($license['expirationDate']) ? \Carbon\Carbon::parse($license['expirationDate'])->format('d M Y') : null;
                                        $lDays = isset($license['expirationDate']) ? (int) now()->diffInDays(\Carbon\Carbon::parse($license['expirationDate']), false) : null;
                                    } catch (\Exception $e) {
                                        $lExp = $license['expirationDate'] ?? null;
                                        $lDays = null;
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $lColor['dot'] }} shrink-0"></span>
                                            <span class="text-xs font-medium {{ $lColor['text'] }}">{{ $lColor['label'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($device)
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $device['name'] }}</p>
                                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $license['deviceSerial'] ?? '—' }}</p>
                                        @else
                                        <span class="text-xs text-gray-400 italic">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono
                                                     bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            {{ $license['licenseType'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 hidden md:table-cell">
                                        @if(!empty($license['_org']))
                                        <a href="{{ route('admin.meraki.organization', $license['_org']['id']) }}"
                                           class="text-xs text-teal-600 dark:text-teal-400 hover:underline">
                                            {{ $license['_org']['name'] }}
                                        </a>
                                        @else
                                        <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($lExp)
                                        <p class="text-xs font-semibold
                                            {{ $lDays !== null && $lDays < 30 ? 'text-red-600 dark:text-red-400' : ($lDays !== null && $lDays < 90 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-700 dark:text-gray-300') }}">
                                            {{ $lExp }}
                                        </p>
                                        @if($lDays !== null)
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $lDays > 0 ? 'Vence en ' . $lDays . ' días' : 'Vencida hace ' . abs($lDays) . ' días' }}
                                        </p>
                                        @endif
                                        @else
                                        <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                @endforeach

            </div>{{-- /x-data --}}

            @endif
        </div>
    </div>

    @if(!empty($byModel))
    <script>
    function licencias() {
        return {
            active: null,
            toggle(key) {
                this.active = this.active === key ? null : key;
                this.$nextTick(() => {
                    if (this.active) {
                        const panel = document.querySelector(`[x-show="active === '${this.active}'"]`);
                        if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const isDark = document.documentElement.classList.contains('dark');
        const gray400 = '#9ca3af';

        new Chart(document.getElementById('licDonut'), {
            type: 'doughnut',
            data: {
                labels: ['Activas', 'Sin usar', 'Vencidas'],
                datasets: [{
                    data: [{{ $totalActive }}, {{ $totalUnused }}, {{ $totalExpired }}],
                    backgroundColor: ['#4ade80', '#60a5fa', '#f87171'],
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
                        callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` }
                    }
                }
            }
        });

        @php
            $barLabels  = array_keys($byModel);
            $barActive  = array_column($byModel, 'active');
            $barUnused  = array_column($byModel, 'unused');
            $barExpired = array_column($byModel, 'expired');
        @endphp

        new Chart(document.getElementById('licModelsBar'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($barLabels) !!},
                datasets: [
                    {
                        label: 'Activas',
                        data: {!! json_encode($barActive) !!},
                        backgroundColor: '#4ade80',
                        borderRadius: 4,
                        barThickness: 18,
                    },
                    {
                        label: 'Sin usar',
                        data: {!! json_encode($barUnused) !!},
                        backgroundColor: '#60a5fa',
                        borderRadius: 4,
                        barThickness: 18,
                    },
                    {
                        label: 'Vencidas',
                        data: {!! json_encode($barExpired) !!},
                        backgroundColor: '#f87171',
                        borderRadius: 4,
                        barThickness: 18,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: gray400,
                            font: { size: 10, weight: 'bold' },
                            boxWidth: 10,
                            padding: 12,
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y}`
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: false,
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
                            stepSize: 1
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
