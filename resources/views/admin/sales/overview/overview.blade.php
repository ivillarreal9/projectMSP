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
            <span class="text-gray-700 dark:text-gray-200 font-medium">Overview</span>
        </div>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @include('admin.sales.partials.nav')

            @php
                $isCurrentPeriod = ($year == now()->year && $month == now()->month);
                $periodoLabel    = \Carbon\Carbon::create($year, $month)->translatedFormat('F Y');

                // Mapa de imágenes por nombre de ejecutiva
                $execImageMap = collect($byExecutive ?? [])->keyBy('name')->map(
                    fn($e) => (!empty($e['image']) && !str_starts_with($e['image'], 'PD94'))
                        ? 'data:image/png;base64,'.$e['image']
                        : null
                )->all();

                $toJsComision = fn($col, string $tipo) =>
                    collect($col)
                        ->sortByDesc($tipo)
                        ->values()
                        ->map(fn($v) => [
                            'name'    => $v['vendedor_name'],
                            'short'   => collect(explode(' ', $v['vendedor_name']))->first(),
                            'initials'=> collect(explode(' ', $v['vendedor_name']))->take(2)
                                            ->map(fn($w) => strtoupper(substr($w,0,1)))->join(''),
                            'revenue' => round($v[$tipo], 2),
                            'cantidad'=> $v['cantidad'],
                            'image'   => $execImageMap[$v['vendedor_name']] ?? null,
                        ])
                        ->toJson();

                $jsOtf = isset($commissionData) ? $toJsComision($commissionData['by_vendedor'], 'total_otf') : '[]';
                $jsMrc = isset($commissionData) ? $toJsComision($commissionData['by_vendedor'], 'total_mrc') : '[]';

                $totalOtf    = $commissionData['total_otf']  ?? 0;
                $totalMrc    = $commissionData['total_mrc']  ?? 0;
                $totalComis  = $commissionData['total']      ?? 0;
                $cantidadOrd = $commissionData['cantidad']   ?? 0;

                $periodoComisiones = isset($commissionPeriod)
                    ? $commissionPeriod->translatedFormat('F Y')
                    : \Carbon\Carbon::create($year, $month, 1)->subMonth()->translatedFormat('F Y');
            @endphp

            {{-- ── TÍTULO + SELECTOR ───────────────────────── --}}
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Competencia de Ventas</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Ovnicom · comisiones de <span class="font-medium text-gray-700 dark:text-gray-200">{{ $periodoComisiones }}</span> · actualizado {{ now()->format('d/m/Y H:i') }}
                    </p>
                </div>
                <form method="GET" action="{{ route('admin.sales.overview') }}" class="flex items-center gap-2 flex-wrap">
                    <div class="relative">
                        <select name="month" onchange="this.form.submit()" class="appearance-none bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-200 rounded-lg pl-3 pr-8 py-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}" {{ $month==$m?'selected':'' }}>{{ \Carbon\Carbon::create(null,$m)->translatedFormat('F') }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center"><svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></div>
                    </div>
                    <div class="relative">
                        <select name="year" onchange="this.form.submit()" class="appearance-none bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-200 rounded-lg pl-3 pr-8 py-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($availableYears as $y)
                                <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center"><svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium {{ $isCurrentPeriod ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' }}">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $periodoLabel }}{{ $isCurrentPeriod?' · actual':'' }}
                    </span>
                    @if(!$isCurrentPeriod)
                        <a href="{{ route('admin.sales.overview') }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Hoy
                        </a>
                    @endif
                </form>
            </div>

            {{-- ── KPI CARDS — COMISIONES ───────────────────── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total OTF</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-500/10">
                            <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">${{ number_format($totalOtf, 2, '.', ',') }}</p>
                    <p class="text-xs text-gray-400 -mt-2">One-Time Fee · {{ $periodoComisiones }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total MRC</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-teal-50 dark:bg-teal-500/10">
                            <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-teal-600 dark:text-teal-400">${{ number_format($totalMrc, 2, '.', ',') }}</p>
                    <p class="text-xs text-gray-400 -mt-2">Monthly Recurring · {{ $periodoComisiones }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total comisiones</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-500/10">
                            <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-violet-600 dark:text-violet-400">${{ number_format($totalComis, 2, '.', ',') }}</p>
                    <p class="text-xs text-gray-400 -mt-2">OTF + MRC · {{ $periodoComisiones }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Órdenes</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/10">
                            <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($cantidadOrd) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">Con comisión registrada · {{ $periodoComisiones }}</p>
                </div>
            </div>

            {{-- ── PODIOS OTF + MRC ─────────────────────────── --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Podio OTF --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 border border-orange-200 dark:border-orange-500/20">OTF</span>
                                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">One-Time Fee</h2>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Comisiones por cargo único · {{ $periodoComisiones }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">Total</p>
                            <p class="text-base font-bold text-orange-600 dark:text-orange-400">${{ number_format($totalOtf, 2, '.', ',') }}</p>
                        </div>
                    </div>
                    @if(($totalOtf + $totalMrc) > 0)
                    <div class="flex items-center gap-2 mb-5">
                        <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-orange-400 h-1.5 rounded-full transition-all" style="width:{{ round($totalOtf / ($totalOtf + $totalMrc) * 100) }}%"></div>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ round($totalOtf / ($totalOtf + $totalMrc) * 100) }}% del total</span>
                    </div>
                    @endif
                    <div id="podio-otf-content"></div>
                </div>

                {{-- Podio MRC --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-400 border border-teal-200 dark:border-teal-500/20">MRC</span>
                                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Monthly Recurring</h2>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Comisiones recurrentes mensuales · {{ $periodoComisiones }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">Total</p>
                            <p class="text-base font-bold text-teal-600 dark:text-teal-400">${{ number_format($totalMrc, 2, '.', ',') }}</p>
                        </div>
                    </div>
                    @if(($totalOtf + $totalMrc) > 0)
                    <div class="flex items-center gap-2 mb-5">
                        <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-teal-400 h-1.5 rounded-full transition-all" style="width:{{ round($totalMrc / ($totalOtf + $totalMrc) * 100) }}%"></div>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ round($totalMrc / ($totalOtf + $totalMrc) * 100) }}% del total</span>
                    </div>
                    @endif
                    <div id="podio-mrc-content"></div>
                </div>

            </div>

            {{-- ── GRÁFICAS COMISIONES ──────────────────────── --}}
            <div class="grid grid-cols-2 gap-6">

                {{-- Stacked bar H: OTF + MRC por vendedor --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">OTF + MRC por vendedor</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $periodoComisiones }} · barras apiladas</p>
                    </div>
                    <div class="flex items-center gap-4 mb-3 text-xs text-gray-400">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#f97316"></span>OTF</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#14b8a6"></span>MRC</span>
                    </div>
                    <div class="relative" style="height:240px"><canvas id="chartStacked"></canvas></div>
                </div>

                {{-- Donut: OTF vs MRC total --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Distribución OTF vs MRC</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $periodoComisiones }} · participación por tipo</p>
                    </div>
                    <div class="flex items-center gap-6" style="height:200px">
                        <div class="relative flex-shrink-0" style="width:180px;height:180px;">
                            <canvas id="chartDona" style="width:180px;height:180px;"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <p class="text-xs text-gray-400" id="dona-label">Total</p>
                                <p class="text-base font-bold text-gray-900 dark:text-gray-100" id="dona-total">${{ number_format($totalComis, 2, '.', ',') }}</p>
                            </div>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm flex-shrink-0" style="background:#f97316"></span><span class="text-sm text-gray-600 dark:text-gray-300">OTF</span></div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${{ number_format($totalOtf, 2, '.', ',') }}</p>
                                    @if($totalComis > 0)<p class="text-xs text-gray-400">{{ round($totalOtf / $totalComis * 100, 1) }}%</p>@endif
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm flex-shrink-0" style="background:#14b8a6"></span><span class="text-sm text-gray-600 dark:text-gray-300">MRC</span></div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${{ number_format($totalMrc, 2, '.', ',') }}</p>
                                    @if($totalComis > 0)<p class="text-xs text-gray-400">{{ round($totalMrc / $totalComis * 100, 1) }}%</p>@endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-2 gap-6">

                {{-- Bar OTF ranking --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Ranking OTF por vendedor</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $periodoComisiones }} · comisión one-time fee</p>
                    </div>
                    <div class="relative h-56"><canvas id="chartOtfBar"></canvas></div>
                </div>

                {{-- Bar MRC ranking --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Ranking MRC por vendedor</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $periodoComisiones }} · comisión recurrente mensual</p>
                    </div>
                    <div class="relative h-56"><canvas id="chartMrcBar"></canvas></div>
                </div>

            </div>

            <div class="grid grid-cols-2 gap-6">

                {{-- Total comisiones por vendedor --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Total comisiones por vendedor</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $periodoComisiones }} · OTF + MRC combinado</p>
                    </div>
                    <div class="relative h-56"><canvas id="chartTotal"></canvas></div>
                </div>

                {{-- Órdenes por vendedor --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Órdenes por vendedor</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $periodoComisiones }} · cantidad de órdenes con comisión</p>
                    </div>
                    <div class="relative h-56"><canvas id="chartOrders"></canvas></div>
                </div>

            </div>

        </div>
    </div>

    <script>
    const palette      = ['#7F77DD','#378ADD','#1D9E75','#EF9F27','#E24B4A','#06b6d4','#6366f1','#f97316','#ec4899','#84cc16','#a855f7'];
    const dataOtf      = {!! $jsOtf !!};
    const dataMrc      = {!! $jsMrc !!};

    const isDark       = document.documentElement.classList.contains('dark');
    const gridColor    = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const labelColor   = isDark ? '#9ca3af' : '#6b7280';
    Chart.defaults.font.family = 'ui-sans-serif,system-ui,sans-serif';
    Chart.defaults.font.size   = 11;

    // ── Formatters ────────────────────────────────────────────
    function fmtFull(n) {
        return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtK(n) {
        if (n >= 1000000) return '$' + (n / 1000000).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 'M';
        if (n >= 1000)    return '$' + (n / 1000).toLocaleString('en-US',    { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 'k';
        return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ── Merge OTF + MRC por vendedor ──────────────────────────
    function getMerged() {
        const names = [...new Set([...dataOtf.map(e => e.name), ...dataMrc.map(e => e.name)])];
        return names.map(name => {
            const o = dataOtf.find(e => e.name === name) || { revenue: 0, cantidad: 0, short: name.split(' ')[0], initials: '', image: null };
            const m = dataMrc.find(e => e.name === name) || { revenue: 0, cantidad: 0 };
            return {
                name,
                short:    o.short    || name.split(' ')[0],
                initials: o.initials || '',
                image:    o.image    || null,
                otf:      o.revenue,
                mrc:      m.revenue,
                total:    o.revenue + m.revenue,
                cantidad: o.cantidad + m.cantidad,
            };
        }).sort((a, b) => b.total - a.total);
    }

    const merged = getMerged();

    // ── Avatar ────────────────────────────────────────────────
    function avatarHtmlIdx(e, sz, fontSize, idx) {
        if (e.image) return `<img src="${e.image}" style="width:${sz}px;height:${sz}px;border-radius:50%;object-fit:cover;display:block;" alt="${e.name}">`;
        return `<div style="width:${sz}px;height:${sz}px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:${fontSize};font-weight:500;background:${palette[idx % palette.length]}">${e.initials}</div>`;
    }

    // ── Podio Comisiones ──────────────────────────────────────
    function renderPodioComision(data, containerId, accent, accentBg, accentTxt) {
        const container = document.getElementById(containerId);
        if (!container) return;
        if (!data || data.length === 0) {
            container.innerHTML = '<p style="font-size:12px;color:var(--color-text-tertiary);text-align:center;padding:24px 0">Sin datos para este período</p>';
            return;
        }
        const podioColors = [
            { bg: accentBg,  txt: accentTxt, ht: 80 },
            { bg: '#F1EFE8', txt: '#5F5E5A', ht: 56 },
            { bg: '#FAECE7', txt: '#993C1D', ht: 44 },
        ];
        const lbls  = ['1er lugar', '2do lugar', '3er lugar'];
        const order = data.length >= 3 ? [1, 0, 2] : (data.length === 2 ? [1, 0] : [0]);

        let html = '<div style="display:flex;align-items:flex-end;justify-content:center;gap:12px;margin-bottom:1rem">';
        order.forEach(idx => {
            const e = data[idx]; if (!e) return;
            const isFirst = idx === 0;
            const sz = isFirst ? 52 : 40;
            const fs = isFirst ? '15px' : '12px';
            const pc = podioColors[idx] || podioColors[2];
            html += `<div style="display:flex;flex-direction:column;align-items:center;gap:5px;flex:1;max-width:160px">
                <div style="position:relative">
                    ${isFirst ? `<span style="position:absolute;top:-18px;left:50%;transform:translateX(-50%);font-size:16px;color:${accentTxt}">★</span>` : ''}
                    ${avatarHtmlIdx(e, sz, fs, idx)}
                </div>
                <div style="font-size:12px;font-weight:500;color:var(--color-text-primary);text-align:center;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${e.short}</div>
                <div style="font-size:${isFirst ? '17px' : '13px'};font-weight:600;color:${pc.txt};text-align:center">${fmtFull(e.revenue)}</div>
                <div style="font-size:10px;color:var(--color-text-tertiary);text-align:center">${e.cantidad} orden${e.cantidad !== 1 ? 'es' : ''}</div>
                <div style="width:100%;border-radius:5px 5px 0 0;background:${pc.bg};height:${pc.ht}px;display:flex;align-items:flex-end;justify-content:center;padding-bottom:6px">
                    <span style="font-size:10px;font-weight:500;padding:1px 8px;border-radius:999px;background:${pc.bg};color:${pc.txt}">${lbls[idx]}</span>
                </div>
            </div>`;
        });
        html += '</div>';

        if (data.length > 3) {
            html += '<div style="display:flex;flex-direction:column;gap:6px;margin-top:4px">';
            data.slice(3).forEach((e, j) => {
                const i   = j + 3;
                const pct = data[0].revenue > 0 ? ((e.revenue / data[0].revenue) * 100).toFixed(0) : 0;
                html += `<div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:10px;color:var(--color-text-tertiary);width:16px;text-align:right;flex-shrink:0">${i + 1}</span>
                    ${avatarHtmlIdx(e, 24, '9px', i)}
                    <span style="font-size:12px;color:var(--color-text-primary);min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1">${e.short}</span>
                    <div style="flex:2;background:var(--color-background-secondary);border-radius:3px;overflow:hidden;height:4px">
                        <div style="width:${pct}%;height:4px;background:${accent};border-radius:3px;transition:width .4s"></div>
                    </div>
                    <span style="font-size:11px;font-weight:500;color:var(--color-text-secondary);flex-shrink:0">${fmtFull(e.revenue)}</span>
                </div>`;
            });
            html += '</div>';
        }
        container.innerHTML = html;
    }

    // ── Gráficas ──────────────────────────────────────────────
    let charts = {};
    function destroyChart(id) { if (charts[id]) { charts[id].destroy(); delete charts[id]; } }

    function renderCharts() {
        // 1. Stacked bar H — OTF + MRC por vendedor
        destroyChart('stacked');
        charts.stacked = new Chart(document.getElementById('chartStacked'), {
            type: 'bar',
            data: {
                labels: merged.map(e => e.short),
                datasets: [
                    { label: 'OTF', data: merged.map(e => e.otf), backgroundColor: 'rgba(249,115,22,0.8)', borderRadius: 3 },
                    { label: 'MRC', data: merged.map(e => e.mrc), backgroundColor: 'rgba(20,184,166,0.8)',  borderRadius: 3 },
                ]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${fmtFull(ctx.raw)}` } }
                },
                scales: {
                    x: { stacked: true, grid: { color: gridColor }, ticks: { color: labelColor, callback: v => fmtK(v) }, beginAtZero: true },
                    y: { stacked: true, grid: { display: false }, ticks: { color: labelColor } }
                }
            }
        });

        // 2. Donut — OTF vs MRC
        const totalComis = {!! $totalComis !!};
        const totalOtf   = {!! $totalOtf !!};
        const totalMrc   = {!! $totalMrc !!};
        destroyChart('dona');
        charts.dona = new Chart(document.getElementById('chartDona'), {
            type: 'doughnut',
            data: {
                labels: ['OTF', 'MRC'],
                datasets: [{ data: [totalOtf, totalMrc], backgroundColor: ['rgba(249,115,22,0.85)', 'rgba(20,184,166,0.85)'], borderWidth: 3, borderColor: isDark ? '#1f2937' : '#ffffff', hoverOffset: 8 }]
            },
            options: {
                responsive: false, maintainAspectRatio: false, cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${fmtFull(ctx.raw)} (${totalComis > 0 ? ((ctx.raw / totalComis) * 100).toFixed(1) : 0}%)` } }
                },
                onHover: (e, els) => {
                    const lbl  = document.getElementById('dona-label');
                    const tot  = document.getElementById('dona-total');
                    if (els.length) {
                        const i = els[0].index;
                        lbl.textContent = ['OTF', 'MRC'][i];
                        tot.textContent = fmtFull([totalOtf, totalMrc][i]);
                    } else {
                        lbl.textContent = 'Total';
                        tot.textContent = fmtFull(totalComis);
                    }
                }
            }
        });

        // 3. Bar OTF ranking
        destroyChart('otfBar');
        const otfSorted = [...dataOtf].sort((a, b) => b.revenue - a.revenue);
        charts.otfBar = new Chart(document.getElementById('chartOtfBar'), {
            type: 'bar',
            data: {
                labels: otfSorted.map(e => e.short),
                datasets: [{ label: 'OTF', data: otfSorted.map(e => e.revenue), backgroundColor: 'rgba(249,115,22,0.8)', borderRadius: 4 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${fmtFull(ctx.raw)}` } } },
                scales: { x: { grid: { color: gridColor }, ticks: { color: labelColor } }, y: { grid: { color: gridColor }, ticks: { color: labelColor, callback: v => fmtK(v) }, beginAtZero: true } }
            }
        });

        // 4. Bar MRC ranking
        destroyChart('mrcBar');
        const mrcSorted = [...dataMrc].sort((a, b) => b.revenue - a.revenue);
        charts.mrcBar = new Chart(document.getElementById('chartMrcBar'), {
            type: 'bar',
            data: {
                labels: mrcSorted.map(e => e.short),
                datasets: [{ label: 'MRC', data: mrcSorted.map(e => e.revenue), backgroundColor: 'rgba(20,184,166,0.8)', borderRadius: 4 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${fmtFull(ctx.raw)}` } } },
                scales: { x: { grid: { color: gridColor }, ticks: { color: labelColor } }, y: { grid: { color: gridColor }, ticks: { color: labelColor, callback: v => fmtK(v) }, beginAtZero: true } }
            }
        });

        // 5. Bar total comisiones
        destroyChart('total');
        charts.total = new Chart(document.getElementById('chartTotal'), {
            type: 'bar',
            data: {
                labels: merged.map(e => e.short),
                datasets: [{ label: 'Total', data: merged.map(e => e.total), backgroundColor: palette.slice(0, merged.length), borderRadius: 4 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${fmtFull(ctx.raw)}` } } },
                scales: { x: { grid: { color: gridColor }, ticks: { color: labelColor } }, y: { grid: { color: gridColor }, ticks: { color: labelColor, callback: v => fmtK(v) }, beginAtZero: true } }
            }
        });

        // 6. Bar órdenes por vendedor
        destroyChart('orders');
        charts.orders = new Chart(document.getElementById('chartOrders'), {
            type: 'bar',
            data: {
                labels: merged.map(e => e.short),
                datasets: [{ label: 'Órdenes', data: merged.map(e => e.cantidad), backgroundColor: palette.slice(0, merged.length), borderRadius: 4 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.raw} órdenes` } } },
                scales: { x: { grid: { color: gridColor }, ticks: { color: labelColor } }, y: { grid: { color: gridColor }, ticks: { color: labelColor }, beginAtZero: true } }
            }
        });
    }

    // ── Init ──────────────────────────────────────────────────
    renderPodioComision([...dataOtf].sort((a,b) => b.revenue - a.revenue), 'podio-otf-content', '#f97316', '#FFF7ED', '#c2410c');
    renderPodioComision([...dataMrc].sort((a,b) => b.revenue - a.revenue), 'podio-mrc-content', '#14b8a6', '#F0FDFA', '#0f766e');
    renderCharts();
    </script>

</x-app-layout>