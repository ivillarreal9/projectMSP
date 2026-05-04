<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('admin.sales.index') }}" class="hover:text-gray-600 transition">Ventas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Ejecutivas</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @include('admin.sales.partials.nav')

            {{-- ── TÍTULO + SELECTOR DE PERÍODO ───────────── --}}
            @php
                $isCurrentPeriod = ($year == now()->year && $month == now()->month);
                $periodoLabel    = \Carbon\Carbon::create($year, $month)->translatedFormat('F Y');
            @endphp

            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Ejecutivas de Ventas</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Ovnicom · {{ count($executives) }} ejecutivas · actualizado {{ now()->format('d/m/Y H:i') }}
                    </p>
                </div>

                {{-- Selector mes + año --}}
                <form method="GET" action="{{ route('admin.sales.executives') }}"
                      class="flex items-center gap-2 flex-wrap">

                    {{-- Mes --}}
                    <div class="relative">
                        <select name="month" onchange="this.form.submit()"
                                class="appearance-none bg-white dark:bg-gray-800
                                       border border-gray-200 dark:border-gray-700
                                       text-sm text-gray-700 dark:text-gray-200
                                       rounded-lg pl-3 pr-8 py-2 cursor-pointer
                                       focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Año --}}
                    <div class="relative">
                        <select name="year" onchange="this.form.submit()"
                                class="appearance-none bg-white dark:bg-gray-800
                                       border border-gray-200 dark:border-gray-700
                                       text-sm text-gray-700 dark:text-gray-200
                                       rounded-lg pl-3 pr-8 py-2 cursor-pointer
                                       focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($availableYears as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Chip período activo --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                 {{ $isCurrentPeriod
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                                    : 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' }}">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ $periodoLabel }}{{ $isCurrentPeriod ? ' · actual' : '' }}
                    </span>

                    {{-- Botón reset al mes actual --}}
                    @if(!$isCurrentPeriod)
                        <a href="{{ route('admin.sales.executives') }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium
                                  text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600
                                  hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Hoy
                        </a>
                    @endif

                </form>
            </div>

            {{-- ── KPI CARDS DEL EQUIPO ────────────────────── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Leads totales</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/10">
                            <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalLeads) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">En todo el equipo</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Ganadas</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalWon) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">Oportunidades cerradas</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Win Rate equipo</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-500/10">
                            <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $teamWinRate }}%</p>
                    <p class="text-xs text-gray-400 -mt-2">Promedio del equipo</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Sin contacto</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/10">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalNoContact) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">Clientes sin actividad</p>
                </div>

            </div>

            {{-- ── TARJETAS INDIVIDUALES ───────────────────── --}}
            @php
            $avatarColors = [
                'bg-violet-500', 'bg-blue-500', 'bg-emerald-500',
                'bg-amber-500',  'bg-rose-500', 'bg-sky-500',
                'bg-indigo-500', 'bg-teal-500', 'bg-pink-500',
                'bg-cyan-500',   'bg-orange-500',
            ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($executives as $index => $exec)
                @php
                    $initials = collect(explode(' ', $exec['name']))
                        ->take(2)->map(fn($w) => strtoupper(substr($w, 0, 1)))->join('');
                    $color    = $avatarColors[$index % count($avatarColors)];
                    $wr       = $exec['win_rate'] ?? 0;
                    $wrColor  = $wr >= 30 ? 'text-emerald-500' : ($wr >= 15 ? 'text-amber-500' : 'text-red-500');
                    $wrBg     = $wr >= 30 ? 'bg-emerald-500'   : ($wr >= 15 ? 'bg-amber-500'   : 'bg-red-500');
                    $nc       = $exec['noContact'] ?? 0;
                    $hasPhoto = !empty($exec['image_128']) && !str_starts_with($exec['image_128'], 'PD94');
                    $execId   = $exec['id'] ?? $exec['odoo_id'] ?? $index;
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-4">

                    {{-- Header --}}
                    <div class="flex items-center gap-3">
                        @if($hasPhoto)
                            <img src="data:image/png;base64,{{ $exec['image_128'] }}"
                                 class="w-11 h-11 rounded-full object-cover flex-shrink-0"
                                 alt="{{ $exec['name'] }}">
                        @else
                            <div class="w-11 h-11 rounded-full {{ $color }} flex items-center justify-center
                                        text-white text-sm font-bold flex-shrink-0">
                                {{ $initials }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $exec['name'] }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $exec['email'] ?? '' }}</p>
                            @if(!empty($exec['mobile']) && $exec['mobile'] !== false)
                                <p class="text-xs text-gray-400">{{ $exec['mobile'] }}</p>
                            @elseif(!empty($exec['phone']) && $exec['phone'] !== false)
                                <p class="text-xs text-gray-400">{{ $exec['phone'] }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Métricas en grid --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <p class="text-xs text-gray-400 mb-0.5">Leads</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($exec['leads'] ?? 0) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <p class="text-xs text-gray-400 mb-0.5">Ganadas</p>
                            <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($exec['won'] ?? 0) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <p class="text-xs text-gray-400 mb-0.5">Pipeline</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($exec['pipeline'] ?? 0) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <p class="text-xs text-gray-400 mb-0.5">Sin contacto</p>
                            <p class="text-xl font-bold {{ $nc > 10 ? 'text-red-500' : 'text-gray-900 dark:text-gray-100' }}">
                                {{ number_format($nc) }}
                            </p>
                        </div>
                    </div>

                    {{-- Win Rate bar --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs text-gray-400">Win Rate</span>
                            <span class="text-sm font-bold {{ $wrColor }}">{{ $wr }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $wrBg }}" style="width: {{ min($wr, 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ number_format($exec['won'] ?? 0) }} de {{ number_format($exec['total_oport'] ?? 0) }} oportunidades
                        </p>
                    </div>

                    {{-- Botón Más información — pasa el período --}}
                    <a href="{{ route('admin.sales.executives.show', $execId) }}?year={{ $year }}&month={{ $month }}"
                       class="flex items-center justify-center gap-2 w-full px-4 py-2.5
                              rounded-lg border border-gray-200 dark:border-gray-600
                              text-xs font-semibold text-gray-600 dark:text-gray-300
                              hover:bg-gray-50 dark:hover:bg-gray-700/50
                              hover:border-gray-300 dark:hover:border-gray-500
                              transition-all duration-150 group">
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 transition"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Más información
                        <svg class="w-3 h-3 text-gray-300 group-hover:text-gray-500 dark:group-hover:text-gray-300 transition ml-auto"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                </div>
                @empty
                <div class="col-span-3 py-16 text-center text-sm text-gray-400">
                    No se encontraron ejecutivas.
                </div>
                @endforelse
            </div>

            {{-- ── TABLA COMPARATIVA ───────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Comparativa del equipo</h2>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ $periodoLabel }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-6 py-3 text-left   text-xs font-semibold text-gray-400 uppercase tracking-wider">Ejecutiva</th>
                                <th class="px-6 py-3 text-right  text-xs font-semibold text-gray-400 uppercase tracking-wider">Leads</th>
                                <th class="px-6 py-3 text-right  text-xs font-semibold text-gray-400 uppercase tracking-wider">Ganadas</th>
                                <th class="px-6 py-3 text-right  text-xs font-semibold text-gray-400 uppercase tracking-wider">Pipeline</th>
                                <th class="px-6 py-3 text-right  text-xs font-semibold text-gray-400 uppercase tracking-wider">Sin contacto</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Win Rate</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                            @foreach($executives as $index => $exec)
                            @php
                                $wr       = $exec['win_rate'] ?? 0;
                                $wrColor  = $wr >= 30 ? 'text-emerald-500' : ($wr >= 15 ? 'text-amber-500' : 'text-red-500');
                                $wrBg     = $wr >= 30 ? 'bg-emerald-500'   : ($wr >= 15 ? 'bg-amber-500'   : 'bg-red-500');
                                $nc       = $exec['noContact'] ?? 0;
                                $color    = $avatarColors[$index % count($avatarColors)];
                                $ini      = collect(explode(' ', $exec['name']))->take(2)->map(fn($w) => strtoupper(substr($w,0,1)))->join('');
                                $hasPhoto = !empty($exec['image_128']) && !str_starts_with($exec['image_128'], 'PD94');
                                $execId   = $exec['id'] ?? $exec['odoo_id'] ?? $index;
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($hasPhoto)
                                            <img src="data:image/png;base64,{{ $exec['image_128'] }}"
                                                 class="w-7 h-7 rounded-full object-cover flex-shrink-0"
                                                 alt="{{ $exec['name'] }}">
                                        @else
                                            <div class="w-7 h-7 rounded-full {{ $color }} flex items-center justify-center
                                                        text-white text-xs font-bold flex-shrink-0">{{ $ini }}</div>
                                        @endif
                                        <div>
                                            <p class="text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $exec['name'] }}</p>
                                            <p class="text-xs text-gray-400">{{ $exec['email'] ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format($exec['leads'] ?? 0) }}
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($exec['won'] ?? 0) }}
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format($exec['pipeline'] ?? 0) }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="text-sm font-semibold {{ $nc > 10 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ number_format($nc) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-20 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $wrBg }}"
                                                 style="width: {{ min($wr, 100) }}%"></div>
                                        </div>
                                        <span class="text-sm font-bold {{ $wrColor }} w-10 text-right">{{ $wr }}%</span>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-3 text-center">
                                    <a href="{{ route('admin.sales.executives.show', $execId) }}?year={{ $year }}&month={{ $month }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5
                                              rounded-lg border border-gray-200 dark:border-gray-600
                                              text-xs font-medium text-gray-500 dark:text-gray-400
                                              hover:bg-gray-50 dark:hover:bg-gray-700/50 transition group">
                                        Ver detalle
                                        <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</x-app-layout>