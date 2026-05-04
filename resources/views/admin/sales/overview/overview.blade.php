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
                $prevMonth       = \Carbon\Carbon::create($year, $month, 1)->subMonth();
                $prevLabel       = $prevMonth->translatedFormat('F Y');

                // Incluye image en el JSON — se valida para no pasar SVGs vacíos
                $toJs = fn($col) => collect($col)->sortByDesc('revenue')->values()->map(fn($e) => [
                    'name'    => $e['name'],
                    'short'   => collect(explode(' ',$e['name']))->first(),
                    'initials'=> collect(explode(' ',$e['name']))->take(2)->map(fn($w)=>strtoupper(substr($w,0,1)))->join(''),
                    'leads'   => $e['leads'], 'won' => $e['won'],
                    'revenue' => round($e['revenue'],2), 'wr' => $e['win_rate'],
                    'image'   => (!empty($e['image']) && !str_starts_with($e['image'], 'PD94'))
                                    ? 'data:image/png;base64,'.$e['image']
                                    : null,
                ])->toJson();

                $jsMonth   = $toJs($byExecutive);
                $jsYear    = $toJs($byExecutiveYear ?? $byExecutive);
                $jsPrev    = collect($byExecutivePrev ?? [])->count()
                    ? collect($byExecutivePrev)->map(fn($e)=>[
                        'name'=>$e['name'],'revenue'=>round($e['revenue'],2),
                        'won'=>$e['won'],'leads'=>$e['leads'],'wr'=>$e['win_rate'],
                      ])->toJson()
                    : '[]';
                $jsMonthly = collect($monthlyData ?? [])->map(fn($m)=>[
                    'label'=>$m['label'],'leads'=>$m['leads'],
                    'won'=>$m['won'],'revenue'=>round($m['revenue'],2),
                ])->toJson();
            @endphp

            {{-- ── TÍTULO + SELECTOR ───────────────────────── --}}
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Competencia de Ventas</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ovnicom · actualizado {{ now()->format('d/m/Y H:i') }}</p>
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

            {{-- ── TABS GLOBALES ───────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-3 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Vista:</span>
                </div>
                <div class="flex gap-2 flex-1 justify-center sm:justify-start">
                    <button onclick="setView('month')" id="gtab-month" class="gtab px-4 py-2 rounded-lg text-sm font-medium border transition bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600">Mes actual</button>
                    <button onclick="setView('vs')"    id="gtab-vs"    class="gtab px-4 py-2 rounded-lg text-sm font-medium border transition text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50">vs Mes anterior</button>
                    <button onclick="setView('year')"  id="gtab-year"  class="gtab px-4 py-2 rounded-lg text-sm font-medium border transition text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50">Acumulado {{ $year }}</button>
                </div>
                <p class="text-xs text-gray-400" id="global-period-label">{{ $periodoLabel }}</p>
            </div>

            {{-- ── KPI CARDS ───────────────────────────────── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between"><span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Leads</span><span class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/10"><svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span></div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100" id="kpi-leads">—</p>
                    <p class="text-xs text-gray-400 -mt-2" id="kpi-leads-sub">—</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between"><span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Ganadas</span><span class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10"><svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span></div>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400" id="kpi-won">—</p>
                    <p class="text-xs text-gray-400 -mt-2">Oportunidades cerradas</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between"><span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Win Rate</span><span class="w-9 h-9 flex items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-500/10"><svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg></span></div>
                    <p class="text-3xl font-bold text-sky-600 dark:text-sky-400" id="kpi-wr">—</p>
                    <p class="text-xs text-gray-400 -mt-2" id="kpi-wr-sub">Del período</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between"><span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Monto ganado</span><span class="w-9 h-9 flex items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-500/10"><svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span></div>
                    <p class="text-3xl font-bold text-violet-600 dark:text-violet-400" id="kpi-revenue">—</p>
                    <p class="text-xs text-gray-400 -mt-2">Ingreso esperado (CRM)</p>
                </div>
            </div>

            {{-- ── PODIO ───────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Ranking de ejecutivas</h2>
                    <p class="text-xs text-gray-400" id="podio-sub">—</p>
                </div>
                <div id="podio-content"></div>
            </div>

            {{-- ── DASHBOARDS 2x2 ──────────────────────────── --}}
            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4"><h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Monto ganado por ejecutiva</h2><p class="text-xs text-gray-400 mt-0.5" id="sub-barH">—</p></div>
                    <div class="relative" style="height:232px"><canvas id="chartBarH"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4"><h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Participación en monto</h2><p class="text-xs text-gray-400 mt-0.5" id="sub-dona">—</p></div>
                    <div class="flex items-center gap-4" style="height:200px">
                        {{-- Dona con centro --}}
                        <div class="relative flex-shrink-0" style="width:180px;height:180px;" id="dona-wrap">
                            <canvas id="chartDona" style="width:180px;height:180px;"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <p class="text-xs text-gray-400" id="dona-label">Total</p>
                                <p class="text-base font-bold text-gray-900 dark:text-gray-100" id="dona-total">$0</p>
                            </div>
                        </div>
                        {{-- Leyenda al lado derecho --}}
                        <div class="flex-1 min-w-0 space-y-2 overflow-y-auto" style="max-height:180px" id="donaLegend"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div><h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Tendencia mensual</h2><p class="text-xs text-gray-400 mt-0.5">Leads vs ganadas por mes</p></div>
                        <div class="flex items-center gap-3 text-xs text-gray-400">
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 opacity-70 inline-block"></span>Leads</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 opacity-70 inline-block"></span>Ganadas</span>
                        </div>
                    </div>
                    <div class="relative h-56"><canvas id="chartTrend"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4"><h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Win Rate por ejecutiva</h2><p class="text-xs text-gray-400 mt-0.5" id="sub-wr">—</p></div>
                    <div class="relative h-56"><canvas id="chartWR"></canvas></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4"><h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Oportunidades ganadas</h2><p class="text-xs text-gray-400 mt-0.5" id="sub-won">—</p></div>
                    <div class="relative h-56"><canvas id="chartWon"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <div class="mb-4"><h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Monto mensual {{ $year }}</h2><p class="text-xs text-gray-400 mt-0.5">Ingreso esperado · línea de área</p></div>
                    <div class="relative h-56"><canvas id="chartRevLine"></canvas></div>
                </div>
            </div>

        </div>
    </div>

    <script>
    const palette=['#7F77DD','#378ADD','#1D9E75','#EF9F27','#E24B4A','#06b6d4','#6366f1','#14b8a6','#f97316','#ec4899','#84cc16'];
    const dataMonth  ={!! $jsMonth !!};
    const dataYear   ={!! $jsYear !!};
    const dataPrev   ={!! $jsPrev !!};
    const dataMonthly={!! $jsMonthly !!};
    const PERIODO='{{ $periodoLabel }}';
    const PREV   ='{{ $prevLabel }}';
    const YEAR   ={{ $year }};

    const isDark    =document.documentElement.classList.contains('dark');
    const gridColor =isDark?'rgba(255,255,255,0.06)':'rgba(0,0,0,0.06)';
    const labelColor=isDark?'#9ca3af':'#6b7280';
    Chart.defaults.font.family='ui-sans-serif,system-ui,sans-serif';
    Chart.defaults.font.size=11;

    function fmtFull(n){return '$'+Math.round(n).toLocaleString();}
    function fmtK(n){return n>=1000000?'$'+(n/1000000).toFixed(1)+'M':n>=1000?'$'+(n/1000).toFixed(0)+'k':'$'+Math.round(n);}
    function wrColor(wr){return wr>=50?'#1D9E75':wr>=25?'#EF9F27':'#E24B4A';}
    function signStr(d){return d>0?'+':d<0?'−':'';}
    function deltaCol(d){return d>0?'#1D9E75':d<0?'#E24B4A':'#9ca3af';}

    // Avatar: imagen real o fallback con iniciales
    function avatarHTML(e, sz, fontSize) {
        if (e.image) {
            return `<img src="${e.image}" style="width:${sz}px;height:${sz}px;border-radius:50%;object-fit:cover;display:block;" alt="${e.name}">`;
        }
        return `<div style="width:${sz}px;height:${sz}px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:${fontSize};font-weight:500;background:${palette[0]}">${e.initials}</div>`;
    }
    function avatarHtmlIdx(e, sz, fontSize, idx) {
        if (e.image) {
            return `<img src="${e.image}" style="width:${sz}px;height:${sz}px;border-radius:50%;object-fit:cover;display:block;" alt="${e.name}">`;
        }
        return `<div style="width:${sz}px;height:${sz}px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:${fontSize};font-weight:500;background:${palette[idx]}">${e.initials}</div>`;
    }

    let currentView='month';
    let charts={};

    function destroyChart(id){if(charts[id]){charts[id].destroy();delete charts[id];}}

    function getActiveData(){
        if(currentView==='year') return [...dataYear].sort((a,b)=>b.revenue-a.revenue);
        if(currentView==='vs'){
            return [...dataMonth].map(e=>{
                const p=dataPrev.find(x=>x.name===e.name)||{revenue:0,won:0,leads:0,wr:0};
                return{...e,prevRevenue:p.revenue,prevWon:p.won,prevLeads:p.leads,prevWr:p.wr,
                    deltaRev:e.revenue-p.revenue,deltaWon:e.won-p.won};
            }).sort((a,b)=>b.revenue-a.revenue);
        }
        return [...dataMonth].sort((a,b)=>b.revenue-a.revenue);
    }

    function getPeriodLabel(){
        if(currentView==='year') return 'Acumulado '+YEAR;
        if(currentView==='vs') return PERIODO+' vs '+PREV;
        return PERIODO;
    }

    function updateKpis(data){
        const totLeads=data.reduce((s,e)=>s+e.leads,0);
        const totWon=data.reduce((s,e)=>s+e.won,0);
        const totRev=data.reduce((s,e)=>s+e.revenue,0);
        const wr=((totLeads+totWon)>0?(totWon/(totLeads+totWon)*100):0).toFixed(1);
        document.getElementById('kpi-leads').textContent=totLeads.toLocaleString();
        document.getElementById('kpi-won').textContent=totWon.toLocaleString();
        document.getElementById('kpi-wr').textContent=wr+'%';
        document.getElementById('kpi-revenue').textContent=fmtFull(totRev);
        document.getElementById('kpi-leads-sub').textContent='Creados · '+getPeriodLabel();
        document.getElementById('kpi-wr-sub').textContent=getPeriodLabel();
    }

    function renderPodio(data){
        const bgP={0:'#FAEEDA',1:'#F1EFE8',2:'#FAECE7'};
        const txtC={0:'#BA7517',1:'#5F5E5A',2:'#993C1D'};
        const lbls=['1er lugar','2do lugar','3er lugar'];
        const hts={0:96,1:68,2:52};
        const order=[1,0,2];
        document.getElementById('podio-sub').textContent=getPeriodLabel();

        let html='<div style="display:flex;align-items:flex-end;justify-content:center;gap:16px;margin-bottom:1.5rem">';
        order.forEach(idx=>{
            const e=data[idx]; if(!e) return;
            const sz=idx===0?64:52;
            const fs=idx===0?'18px':'14px';
            let dHtml='';
            if(currentView==='vs'&&e.deltaRev!==undefined){
                const col=deltaCol(e.deltaRev);
                dHtml=`<div style="font-size:11px;font-weight:500;color:${col};text-align:center">${signStr(e.deltaRev)}${fmtK(Math.abs(e.deltaRev))}</div>`;
            }
            html+=`<div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;max-width:180px">
                <div style="position:relative">
                    ${idx===0?'<span style="position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:18px;color:#BA7517;z-index:1">★</span>':''}
                    ${avatarHtmlIdx(e,sz,fs,idx)}
                </div>
                <div style="font-size:13px;font-weight:500;color:var(--color-text-primary);text-align:center">${e.name.split(' ')[0]}</div>
                <div style="font-size:${idx===0?'20px':'15px'};font-weight:500;color:${txtC[idx]};text-align:center">${fmtFull(e.revenue)}</div>
                ${dHtml}
                <div style="font-size:11px;color:var(--color-text-tertiary);text-align:center">${e.won} ganada${e.won!==1?'s':''}</div>
                <div style="width:100%;border-radius:6px 6px 0 0;background:${bgP[idx]};height:${hts[idx]}px;display:flex;align-items:flex-end;justify-content:center;padding-bottom:8px">
                    <span style="font-size:11px;font-weight:500;padding:2px 10px;border-radius:999px;background:${bgP[idx]};color:${txtC[idx]}">${lbls[idx]}</span>
                </div>
            </div>`;
        });
        html+='</div>';

        // Mini cards resto con imagen
        html+='<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px">';
        data.slice(3).forEach((e,j)=>{
            const i=j+3;
            let dSpan='';
            if(currentView==='vs'&&e.deltaRev!==undefined)
                dSpan=`<span style="font-size:10px;color:${deltaCol(e.deltaRev)};font-weight:500">${signStr(e.deltaRev)}${fmtK(Math.abs(e.deltaRev))}</span>`;
            html+=`<div style="background:var(--color-background-secondary);border-radius:var(--border-radius-md);padding:10px;display:flex;align-items:center;gap:8px">
                <div style="flex-shrink:0">${avatarHtmlIdx(e,30,'10px',i)}</div>
                <div style="min-width:0"><div style="font-size:11px;font-weight:500;color:var(--color-text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${e.name.split(' ')[0]}</div>
                <div style="font-size:11px;color:var(--color-text-secondary)">${fmtFull(e.revenue)} ${dSpan}</div></div>
            </div>`;
        });
        html+='</div>';
        document.getElementById('podio-content').innerHTML=html;
    }

    function renderBarH(data){
        destroyChart('barH');
        document.getElementById('sub-barH').textContent=getPeriodLabel()+' · ingreso esperado CRM';
        const isVs=currentView==='vs';
        const datasets=isVs?[
            {label:'Mes actual',  data:data.map(e=>e.revenue),        backgroundColor:palette.slice(0,data.length),          borderRadius:4},
            {label:'Mes anterior',data:data.map(e=>e.prevRevenue||0), backgroundColor:data.map((_,i)=>palette[i]+'55'),      borderRadius:4},
        ]:[{label:'Monto',data:data.map(e=>e.revenue),backgroundColor:palette.slice(0,data.length),borderRadius:4}];
        charts.barH=new Chart(document.getElementById('chartBarH'),{
            type:'bar',data:{labels:data.map(e=>e.short),datasets},
            options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,
                plugins:{legend:{display:isVs,position:'top',labels:{color:labelColor,boxWidth:10,padding:10}},
                    tooltip:{callbacks:{label:ctx=>' '+fmtFull(ctx.raw)}}},
                scales:{x:{grid:{color:gridColor},ticks:{color:labelColor,callback:v=>fmtK(v)},beginAtZero:true},
                        y:{grid:{display:false},ticks:{color:labelColor}}}}
        });
    }

    function renderDona(data){
        destroyChart('dona');
        document.getElementById('sub-dona').textContent=getPeriodLabel();
        const filtered=data.filter(e=>e.revenue>0);
        const totalRev=filtered.reduce((s,e)=>s+e.revenue,0);
        document.getElementById('dona-label').textContent='Total';
        document.getElementById('dona-total').textContent=fmtK(totalRev);
        charts.dona=new Chart(document.getElementById('chartDona'),{
            type:'doughnut',
            data:{labels:filtered.map(e=>e.name.split(' ')[0]),datasets:[{
                data:filtered.map(e=>e.revenue),backgroundColor:palette,
                borderWidth:3,borderColor:isDark?'#1f2937':'#ffffff',hoverOffset:8
            }]},
            options:{responsive:false,maintainAspectRatio:false,cutout:'72%',
                plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>` ${fmtFull(ctx.raw)} (${totalRev>0?((ctx.raw/totalRev)*100).toFixed(1):0}%)`}}},
                onHover:(e,els)=>{
                    if(els.length){const i=els[0].index;document.getElementById('dona-label').textContent=filtered[i].name.split(' ')[0];document.getElementById('dona-total').textContent=fmtFull(filtered[i].revenue);}
                    else{document.getElementById('dona-label').textContent='Total';document.getElementById('dona-total').textContent=fmtK(totalRev);}
                }
            }
        });
        const leg=document.getElementById('donaLegend');
        leg.innerHTML='';
        filtered.forEach((e,i)=>{
            const pct=totalRev>0?((e.revenue/totalRev)*100).toFixed(1):0;
            let dSpan='';
            if(currentView==='vs'&&e.deltaRev!==undefined)
                dSpan=`<span style="font-size:10px;color:${deltaCol(e.deltaRev)};font-weight:500;margin-left:2px">${signStr(e.deltaRev)}${fmtK(Math.abs(e.deltaRev))}</span>`;
            leg.innerHTML+=`<div style="display:flex;align-items:center;justify-content:space-between;gap:4px">
                <div style="display:flex;align-items:center;gap:5px;min-width:0">
                    ${e.image?`<img src="${e.image}" style="width:16px;height:16px;border-radius:50%;object-fit:cover;flex-shrink:0">`:`<span style="width:8px;height:8px;border-radius:50%;background:${palette[i]};flex-shrink:0;display:inline-block"></span>`}
                    <span style="font-size:12px;color:var(--color-text-secondary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${e.name.split(' ')[0]}</span>
                </div>
                <div style="display:flex;align-items:center;gap:3px;flex-shrink:0">
                    <span style="font-size:11px;color:var(--color-text-tertiary)">${pct}%</span>
                    <span style="font-size:12px;font-weight:500;color:var(--color-text-primary)">${fmtK(e.revenue)}</span>
                    ${dSpan}
                </div>
            </div>`;
        });
    }

    function renderTrend(){
        destroyChart('trend');
        charts.trend=new Chart(document.getElementById('chartTrend'),{
            type:'bar',
            data:{labels:dataMonthly.map(m=>m.label),datasets:[
                {label:'Leads',  data:dataMonthly.map(m=>m.leads),backgroundColor:'rgba(59,130,246,0.65)',borderRadius:3},
                {label:'Ganadas',data:dataMonthly.map(m=>m.won),  backgroundColor:'rgba(16,185,129,0.65)',borderRadius:3},
            ]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
                scales:{x:{grid:{color:gridColor},ticks:{color:labelColor}},y:{grid:{color:gridColor},ticks:{color:labelColor},beginAtZero:true}}}
        });
    }

    function renderWR(data){
        destroyChart('wr');
        document.getElementById('sub-wr').textContent=getPeriodLabel()+' · verde ≥50% · ámbar ≥25%';
        const s=[...data].sort((a,b)=>b.wr-a.wr);
        const isVs=currentView==='vs';
        const datasets=isVs?[
            {label:'Mes actual',  data:s.map(e=>e.wr),      backgroundColor:s.map(e=>e.wr>=50?'rgba(16,185,129,0.75)':e.wr>=25?'rgba(239,159,39,0.75)':'rgba(226,75,74,0.75)'),borderRadius:4},
            {label:'Mes anterior',data:s.map(e=>e.prevWr||0),backgroundColor:s.map(()=>'rgba(148,163,184,0.4)'),borderRadius:4},
        ]:[{label:'WR %',data:s.map(e=>e.wr),backgroundColor:s.map(e=>e.wr>=50?'rgba(16,185,129,0.75)':e.wr>=25?'rgba(239,159,39,0.75)':'rgba(226,75,74,0.75)'),borderRadius:4}];
        charts.wr=new Chart(document.getElementById('chartWR'),{
            type:'bar',data:{labels:s.map(e=>e.short),datasets},
            options:{responsive:true,maintainAspectRatio:false,
                plugins:{legend:{display:isVs,position:'top',labels:{color:labelColor,boxWidth:10,padding:10}},tooltip:{callbacks:{label:ctx=>` ${ctx.dataset.label}: ${ctx.raw}%`}}},
                scales:{x:{grid:{color:gridColor},ticks:{color:labelColor}},y:{grid:{color:gridColor},ticks:{color:labelColor,callback:v=>v+'%'},beginAtZero:true,max:100}}}
        });
    }

    function renderWon(data){
        destroyChart('won');
        document.getElementById('sub-won').textContent=getPeriodLabel();
        const isVs=currentView==='vs';
        const datasets=isVs?[
            {label:'Mes actual',  data:data.map(e=>e.won),        backgroundColor:palette.slice(0,data.length),        borderRadius:4},
            {label:'Mes anterior',data:data.map(e=>e.prevWon||0), backgroundColor:data.map((_,i)=>palette[i]+'55'),   borderRadius:4},
        ]:[{label:'Ganadas',data:data.map(e=>e.won),backgroundColor:palette.slice(0,data.length),borderRadius:4}];
        charts.won=new Chart(document.getElementById('chartWon'),{
            type:'bar',data:{labels:data.map(e=>e.short),datasets},
            options:{responsive:true,maintainAspectRatio:false,
                plugins:{legend:{display:isVs,position:'top',labels:{color:labelColor,boxWidth:10,padding:10}}},
                scales:{x:{grid:{color:gridColor},ticks:{color:labelColor}},y:{grid:{color:gridColor},ticks:{color:labelColor},beginAtZero:true}}}
        });
    }

    function renderRevLine(){
        destroyChart('revLine');
        charts.revLine=new Chart(document.getElementById('chartRevLine'),{
            type:'line',
            data:{labels:dataMonthly.map(m=>m.label),datasets:[{
                label:'Monto ($)',data:dataMonthly.map(m=>m.revenue),
                borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,0.12)',
                borderWidth:2,pointRadius:4,pointBackgroundColor:'#8b5cf6',tension:0.4,fill:true
            }]},
            options:{responsive:true,maintainAspectRatio:false,
                plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>' '+fmtFull(ctx.raw)}}},
                scales:{x:{grid:{color:gridColor},ticks:{color:labelColor}},y:{grid:{color:gridColor},ticks:{color:labelColor,callback:v=>fmtK(v)},beginAtZero:true}}}
        });
    }

    function renderAll(){
        const data=getActiveData();
        document.getElementById('global-period-label').textContent=getPeriodLabel();
        updateKpis(data);
        renderPodio(data);
        renderBarH(data);
        renderDona(data);
        renderTrend();
        renderWR(data);
        renderWon(data);
        renderRevLine();
    }

    function setView(v){
        currentView=v;
        document.querySelectorAll('.gtab').forEach(t=>{
            const a=t.id==='gtab-'+v;
            t.className=`gtab px-4 py-2 rounded-lg text-sm font-medium border transition ${a
                ?'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600'
                :'text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50'}`;
        });
        renderAll();
    }

    renderAll();
    </script>

</x-app-layout>