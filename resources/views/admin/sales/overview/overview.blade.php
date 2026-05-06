<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.sales.index') }}" class="hover:text-gray-600 transition">Ventas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Overview</span>
        </div>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @include('admin.sales.partials.nav')

            {{-- ── TÍTULO + BOTONES ────────────────────────── --}}
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Competencia de Ventas</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Ovnicom · comisiones de <span id="label-periodo" class="font-medium text-gray-700 dark:text-gray-200">—</span> · actualizado {{ now()->format('d/m/Y H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button id="btn-mes"
                        onclick="setMode('mes')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition border bg-violet-600 text-white border-violet-600">
                        Mes anterior
                    </button>
                    <button id="btn-acumulado"
                        onclick="setMode('acumulado')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition border bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Acumulado {{ now()->year }}
                    </button>
                </div>
            </div>

            {{-- ── KPI CARDS ────────────────────────────────── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="kpi-cards">
                @for($i = 0; $i < 4; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3 animate-pulse">
                    <div class="flex items-center justify-between">
                        <div class="h-3 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="w-9 h-9 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>
                    <div class="h-8 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-3 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
                @endfor
            </div>

            {{-- ── PODIOS ───────────────────────────────────── --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 border border-orange-200 dark:border-orange-500/20">OTF</span>
                                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">One-Time Fee</h2>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Comisiones por cargo único · <span class="periodo-label">—</span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">Total</p>
                            <p class="text-base font-bold text-orange-600 dark:text-orange-400" id="kpi-total-otf-header">—</p>
                        </div>
                    </div>
                    <div id="podio-otf-bar" class="hidden mb-5">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-orange-400 h-1.5 rounded-full transition-all" id="bar-otf-pct" style="width:0%"></div>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0" id="bar-otf-txt">0%</span>
                        </div>
                    </div>
                    <div id="podio-otf-content">
                        <div class="animate-pulse flex justify-center gap-6 items-end h-32">
                            <div class="w-16 h-20 bg-gray-200 dark:bg-gray-700 rounded-t-lg"></div>
                            <div class="w-16 h-28 bg-gray-200 dark:bg-gray-700 rounded-t-lg"></div>
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-t-lg"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-400 border border-teal-200 dark:border-teal-500/20">MRC</span>
                                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Monthly Recurring</h2>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Comisiones recurrentes mensuales · <span class="periodo-label">—</span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">Total</p>
                            <p class="text-base font-bold text-teal-600 dark:text-teal-400" id="kpi-total-mrc-header">—</p>
                        </div>
                    </div>
                    <div id="podio-mrc-bar" class="hidden mb-5">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-teal-400 h-1.5 rounded-full transition-all" id="bar-mrc-pct" style="width:0%"></div>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0" id="bar-mrc-txt">0%</span>
                        </div>
                    </div>
                    <div id="podio-mrc-content">
                        <div class="animate-pulse flex justify-center gap-6 items-end h-32">
                            <div class="w-16 h-20 bg-gray-200 dark:bg-gray-700 rounded-t-lg"></div>
                            <div class="w-16 h-28 bg-gray-200 dark:bg-gray-700 rounded-t-lg"></div>
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-t-lg"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── GRÁFICAS ─────────────────────────────────── --}}
            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">OTF + MRC por vendedor</h2>
                        <p class="text-xs text-gray-400 mt-0.5 periodo-label">—</p>
                    </div>
                    <div class="flex items-center gap-4 mb-3 text-xs text-gray-400">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#f97316"></span>OTF</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#14b8a6"></span>MRC</span>
                    </div>
                    <div class="relative" style="height:240px">
                        <div id="skel-stacked" class="animate-pulse h-full flex items-end gap-2 px-2">
                            @for($i=0;$i<5;$i++)<div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-t" style="height:{{ 40+$i*15 }}%"></div>@endfor
                        </div>
                        <canvas id="chartStacked" class="hidden"></canvas>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Distribución OTF vs MRC</h2>
                        <p class="text-xs text-gray-400 mt-0.5 periodo-label">—</p>
                    </div>
                    <div class="flex items-center gap-6" style="height:200px">
                        <div class="relative flex-shrink-0 animate-pulse" id="skel-dona" style="width:180px;height:180px;">
                            <div class="w-full h-full rounded-full bg-gray-200 dark:bg-gray-700"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-24 h-24 rounded-full bg-white dark:bg-gray-800"></div>
                            </div>
                        </div>
                        <div class="relative flex-shrink-0 hidden" style="width:180px;height:180px;">
                            <canvas id="chartDona" style="width:180px;height:180px;"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <p class="text-xs text-gray-400" id="dona-label">Total</p>
                                <p class="text-base font-bold text-gray-900 dark:text-gray-100" id="dona-total">—</p>
                            </div>
                        </div>
                        <div class="flex-1 space-y-3" id="dona-legend">
                            <div class="animate-pulse space-y-3">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                @foreach([['chartOtfBar','skel-otfBar','Ranking OTF por vendedor'],['chartMrcBar','skel-mrcBar','Ranking MRC por vendedor']] as [$canvasId,$skelId,$title])
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5 periodo-label">—</p>
                    </div>
                    <div class="relative h-56">
                        <div id="{{ $skelId }}" class="animate-pulse h-full flex items-end gap-2 px-2">
                            @for($i=0;$i<5;$i++)<div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-t" style="height:{{ 30+$i*14 }}%"></div>@endfor
                        </div>
                        <canvas id="{{ $canvasId }}" class="hidden w-full h-full"></canvas>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="grid grid-cols-2 gap-6">
                @foreach([['chartTotal','skel-total','Total comisiones por vendedor'],['chartOrders','skel-orders','Órdenes por vendedor']] as [$canvasId,$skelId,$title])
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5 periodo-label">—</p>
                    </div>
                    <div class="relative h-56">
                        <div id="{{ $skelId }}" class="animate-pulse h-full flex items-end gap-2 px-2">
                            @for($i=0;$i<5;$i++)<div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-t" style="height:{{ 25+$i*13 }}%"></div>@endfor
                        </div>
                        <canvas id="{{ $canvasId }}" class="hidden w-full h-full"></canvas>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>

    <script>
    // ── Config ────────────────────────────────────────────────
    const palette   = ['#7F77DD','#378ADD','#1D9E75','#EF9F27','#E24B4A','#06b6d4','#6366f1','#f97316','#ec4899','#84cc16','#a855f7'];
    const isDark    = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const lblColor  = isDark ? '#9ca3af' : '#6b7280';
    Chart.defaults.font.family = 'ui-sans-serif,system-ui,sans-serif';
    Chart.defaults.font.size   = 11;

    let currentMode = 'mes';

    // ── Formatters ────────────────────────────────────────────
    const fmtFull = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtK    = n => {
        if (n >= 1e6) return '$' + (n/1e6).toFixed(2) + 'M';
        if (n >= 1e3) return '$' + (n/1e3).toFixed(2) + 'k';
        return '$' + Number(n).toFixed(2);
    };

    // ── Helpers ───────────────────────────────────────────────
    function showCanvas(canvasId, skelId) {
        document.getElementById(skelId)?.classList.add('hidden');
        document.getElementById(canvasId)?.classList.remove('hidden');
    }

    function swapDona() {
        document.getElementById('skel-dona')?.classList.add('hidden');
        document.getElementById('chartDona')?.closest('div.relative')?.classList.remove('hidden');
    }

    let charts = {};
    function destroyChart(id) { if (charts[id]) { charts[id].destroy(); delete charts[id]; } }

    // ── Avatar ────────────────────────────────────────────────
    function avatarHtml(e, sz, fontSize, idx) {
        if (e.image) return `<img src="${e.image}" style="width:${sz}px;height:${sz}px;border-radius:50%;object-fit:cover;display:block;" alt="${e.name}">`;
        return `<div style="width:${sz}px;height:${sz}px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:${fontSize};font-weight:500;background:${palette[idx % palette.length]}">${e.initials}</div>`;
    }

    // ── Podio ─────────────────────────────────────────────────
    function renderPodio(data, containerId, accent, accentBg, accentTxt) {
        const container = document.getElementById(containerId);
        if (!container) return;
        if (!data || data.length === 0) {
            container.innerHTML = '<p style="font-size:12px;color:#9ca3af;text-align:center;padding:24px 0">Sin datos para este período</p>';
            return;
        }
        const podioColors = [
            { bg: accentBg, txt: accentTxt, ht: 80 },
            { bg: '#F1EFE8', txt: '#5F5E5A', ht: 56 },
            { bg: '#FAECE7', txt: '#993C1D', ht: 44 },
        ];
        const lbls  = ['1er lugar', '2do lugar', '3er lugar'];
        const order = data.length >= 3 ? [1,0,2] : (data.length === 2 ? [1,0] : [0]);
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
                    ${avatarHtml(e, sz, fs, idx)}
                </div>
                <div style="font-size:12px;font-weight:500;text-align:center;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${e.short}</div>
                <div style="font-size:${isFirst?'17px':'13px'};font-weight:600;color:${pc.txt};text-align:center">${fmtFull(e.revenue)}</div>
                <div style="font-size:10px;color:#9ca3af;text-align:center">${e.cantidad} orden${e.cantidad!==1?'es':''}</div>
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
                    <span style="font-size:10px;color:#9ca3af;width:16px;text-align:right;flex-shrink:0">${i+1}</span>
                    ${avatarHtml(e, 24, '9px', i)}
                    <span style="font-size:12px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1">${e.short}</span>
                    <div style="flex:2;background:${isDark?'#374151':'#f3f4f6'};border-radius:3px;overflow:hidden;height:4px">
                        <div style="width:${pct}%;height:4px;background:${accent};border-radius:3px;transition:width .4s"></div>
                    </div>
                    <span style="font-size:11px;font-weight:500;flex-shrink:0">${fmtFull(e.revenue)}</span>
                </div>`;
            });
            html += '</div>';
        }
        container.innerHTML = html;
    }

    // ── Render ────────────────────────────────────────────────
    function renderAll(d) {
        const { dataOtf, dataMrc, totalOtf, totalMrc, totalComis, cantidadOrd, periodoComisiones } = d;

        document.getElementById('label-periodo').textContent = periodoComisiones;
        document.querySelectorAll('.periodo-label').forEach(el => el.textContent = periodoComisiones);

        document.getElementById('kpi-cards').innerHTML = `
            ${kpiCard('Total OTF', fmtFull(totalOtf), 'One-Time Fee · '+periodoComisiones, 'orange', `<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>`)}
            ${kpiCard('Total MRC', fmtFull(totalMrc), 'Monthly Recurring · '+periodoComisiones, 'teal', `<path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>`)}
            ${kpiCard('Total comisiones', fmtFull(totalComis), 'OTF + MRC · '+periodoComisiones, 'violet', `<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>`)}
            ${kpiCard('Órdenes', cantidadOrd, 'Con comisión registrada · '+periodoComisiones, 'blue', `<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>`, true)}
        `;

        document.getElementById('kpi-total-otf-header').textContent = fmtFull(totalOtf);
        document.getElementById('kpi-total-mrc-header').textContent = fmtFull(totalMrc);

        if ((totalOtf + totalMrc) > 0) {
            const otfPct = Math.round(totalOtf / (totalOtf + totalMrc) * 100);
            const mrcPct = Math.round(totalMrc / (totalOtf + totalMrc) * 100);
            document.getElementById('podio-otf-bar').classList.remove('hidden');
            document.getElementById('bar-otf-pct').style.width = otfPct + '%';
            document.getElementById('bar-otf-txt').textContent  = otfPct + '% del total';
            document.getElementById('podio-mrc-bar').classList.remove('hidden');
            document.getElementById('bar-mrc-pct').style.width = mrcPct + '%';
            document.getElementById('bar-mrc-txt').textContent  = mrcPct + '% del total';
        }

        renderPodio([...dataOtf].sort((a,b)=>b.revenue-a.revenue), 'podio-otf-content', '#f97316', '#FFF7ED', '#c2410c');
        renderPodio([...dataMrc].sort((a,b)=>b.revenue-a.revenue), 'podio-mrc-content', '#14b8a6', '#F0FDFA', '#0f766e');

        const names  = [...new Set([...dataOtf.map(e=>e.name),...dataMrc.map(e=>e.name)])];
        const merged = names.map(name => {
            const o = dataOtf.find(e=>e.name===name) || {revenue:0,cantidad:0,short:name.split(' ')[0],initials:'',image:null};
            const m = dataMrc.find(e=>e.name===name) || {revenue:0,cantidad:0};
            return { name, short:o.short, initials:o.initials, image:o.image, otf:o.revenue, mrc:m.revenue, total:o.revenue+m.revenue, cantidad:o.cantidad+m.cantidad };
        }).sort((a,b)=>b.total-a.total);

        document.getElementById('dona-legend').innerHTML = `
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm flex-shrink-0" style="background:#f97316"></span><span class="text-sm text-gray-600 dark:text-gray-300">OTF</span></div>
                <div class="text-right"><p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${fmtFull(totalOtf)}</p>${totalComis>0?`<p class="text-xs text-gray-400">${(totalOtf/totalComis*100).toFixed(1)}%</p>`:''}</div>
            </div>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm flex-shrink-0" style="background:#14b8a6"></span><span class="text-sm text-gray-600 dark:text-gray-300">MRC</span></div>
                <div class="text-right"><p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${fmtFull(totalMrc)}</p>${totalComis>0?`<p class="text-xs text-gray-400">${(totalMrc/totalComis*100).toFixed(1)}%</p>`:''}</div>
            </div>`;

        document.getElementById('dona-total').textContent = fmtFull(totalComis);

        showCanvas('chartStacked', 'skel-stacked');
        showCanvas('chartOtfBar',  'skel-otfBar');
        showCanvas('chartMrcBar',  'skel-mrcBar');
        showCanvas('chartTotal',   'skel-total');
        showCanvas('chartOrders',  'skel-orders');
        swapDona();

        const barOpts = () => ({
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${fmtFull(ctx.raw)}` } } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: lblColor } },
                y: { grid: { color: gridColor }, ticks: { color: lblColor, callback: v => fmtK(v) }, beginAtZero: true }
            }
        });

        destroyChart('stacked');
        charts.stacked = new Chart(document.getElementById('chartStacked'), {
            type: 'bar',
            data: { labels: merged.map(e=>e.short), datasets: [
                { label:'OTF', data: merged.map(e=>e.otf), backgroundColor:'rgba(249,115,22,0.8)', borderRadius:3 },
                { label:'MRC', data: merged.map(e=>e.mrc), backgroundColor:'rgba(20,184,166,0.8)',  borderRadius:3 },
            ]},
            options: { indexAxis:'y', responsive:true, maintainAspectRatio:false,
                plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: ctx=>` ${ctx.dataset.label}: ${fmtFull(ctx.raw)}` }} },
                scales: {
                    x: { stacked:true, grid:{color:gridColor}, ticks:{color:lblColor,callback:v=>fmtK(v)}, beginAtZero:true },
                    y: { stacked:true, grid:{display:false}, ticks:{color:lblColor} }
                }
            }
        });

        destroyChart('dona');
        charts.dona = new Chart(document.getElementById('chartDona'), {
            type: 'doughnut',
            data: { labels:['OTF','MRC'], datasets:[{ data:[totalOtf,totalMrc], backgroundColor:['rgba(249,115,22,0.85)','rgba(20,184,166,0.85)'], borderWidth:3, borderColor: isDark?'#1f2937':'#ffffff', hoverOffset:8 }] },
            options: { responsive:false, maintainAspectRatio:false, cutout:'72%',
                plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: ctx=>` ${ctx.label}: ${fmtFull(ctx.raw)}` }} },
                onHover: (e,els) => {
                    const lbl=document.getElementById('dona-label'), tot=document.getElementById('dona-total');
                    if(els.length){ lbl.textContent=['OTF','MRC'][els[0].index]; tot.textContent=fmtFull([totalOtf,totalMrc][els[0].index]); }
                    else{ lbl.textContent='Total'; tot.textContent=fmtFull(totalComis); }
                }
            }
        });

        const otfSorted = [...dataOtf].sort((a,b)=>b.revenue-a.revenue);
        destroyChart('otfBar');
        charts.otfBar = new Chart(document.getElementById('chartOtfBar'), {
            type:'bar', data:{ labels:otfSorted.map(e=>e.short), datasets:[{ label:'OTF', data:otfSorted.map(e=>e.revenue), backgroundColor:'rgba(249,115,22,0.8)', borderRadius:4 }] },
            options: { ...barOpts() }
        });

        const mrcSorted = [...dataMrc].sort((a,b)=>b.revenue-a.revenue);
        destroyChart('mrcBar');
        charts.mrcBar = new Chart(document.getElementById('chartMrcBar'), {
            type:'bar', data:{ labels:mrcSorted.map(e=>e.short), datasets:[{ label:'MRC', data:mrcSorted.map(e=>e.revenue), backgroundColor:'rgba(20,184,166,0.8)', borderRadius:4 }] },
            options: { ...barOpts() }
        });

        destroyChart('total');
        charts.total = new Chart(document.getElementById('chartTotal'), {
            type:'bar', data:{ labels:merged.map(e=>e.short), datasets:[{ label:'Total', data:merged.map(e=>e.total), backgroundColor:palette.slice(0,merged.length), borderRadius:4 }] },
            options: { ...barOpts() }
        });

        destroyChart('orders');
        charts.orders = new Chart(document.getElementById('chartOrders'), {
            type:'bar', data:{ labels:merged.map(e=>e.short), datasets:[{ label:'Órdenes', data:merged.map(e=>e.cantidad), backgroundColor:palette.slice(0,merged.length), borderRadius:4 }] },
            options: { responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: ctx=>` ${ctx.raw} órdenes` }} },
                scales:{ x:{grid:{color:gridColor},ticks:{color:lblColor}}, y:{grid:{color:gridColor},ticks:{color:lblColor},beginAtZero:true} }
            }
        });
    }

    // ── KPI card HTML ─────────────────────────────────────────
    function kpiCard(label, value, sub, color, iconPath, isCount=false) {
        const colors = {
            orange: { bg:'bg-orange-50 dark:bg-orange-500/10', txt:'text-orange-600 dark:text-orange-400', icon:'text-orange-500' },
            teal:   { bg:'bg-teal-50 dark:bg-teal-500/10',     txt:'text-teal-600 dark:text-teal-400',     icon:'text-teal-500' },
            violet: { bg:'bg-violet-50 dark:bg-violet-500/10', txt:'text-violet-600 dark:text-violet-400', icon:'text-violet-500' },
            blue:   { bg:'bg-blue-50 dark:bg-blue-500/10',     txt:'text-blue-600 dark:text-blue-400',     icon:'text-blue-500' },
        }[color];
        return `<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">${label}</span>
                <span class="w-9 h-9 flex items-center justify-center rounded-lg ${colors.bg}">
                    <svg class="w-4 h-4 ${colors.icon}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">${iconPath}</svg>
                </span>
            </div>
            <p class="text-3xl font-bold ${colors.txt}">${isCount ? Number(value).toLocaleString() : value}</p>
            <p class="text-xs text-gray-400 -mt-2">${sub}</p>
        </div>`;
    }

    // ── Modo ──────────────────────────────────────────────────
    function setMode(mode) {
        currentMode = mode;
        const active   = 'px-4 py-2 rounded-lg text-sm font-medium transition border bg-violet-600 text-white border-violet-600';
        const inactive = 'px-4 py-2 rounded-lg text-sm font-medium transition border bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700';
        document.getElementById('btn-mes').className       = mode === 'mes'       ? active : inactive;
        document.getElementById('btn-acumulado').className = mode === 'acumulado' ? active : inactive;
        loadData();
    }

    // ── Fetch ─────────────────────────────────────────────────
    function loadData() {
        document.getElementById('kpi-cards').innerHTML = `
            ${[0,1,2,3].map(() => `
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3 animate-pulse">
                <div class="flex items-center justify-between">
                    <div class="h-3 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="w-9 h-9 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                </div>
                <div class="h-8 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-3 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>`).join('')}
        `;

        fetch(`{{ route('admin.sales.overview.commissions') }}?mode=${currentMode}`)
            .then(r => r.json())
            .then(renderAll)
            .catch(() => {
                document.getElementById('kpi-cards').innerHTML =
                    '<p class="col-span-4 text-center text-sm text-red-500 py-8">Error cargando datos. Recarga la página.</p>';
            });
    }

    // ── Init ──────────────────────────────────────────────────
    setMode('mes');
    </script>
</x-app-layout>