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
            <span class="text-gray-700 dark:text-gray-200 font-medium">Pipeline</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @include('admin.sales.partials.nav')

            {{-- ── TÍTULO ──────────────────────────────────── --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Pipeline de Cotizaciones</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Ovnicom · actualizado {{ now()->format('d/m/Y H:i') }}
                </p>
            </div>

            {{-- ── KPI CARDS ───────────────────────────────── --}}
            <div class="grid grid-cols-2 gap-4">

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Cotizaciones</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-500/10">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($kpis['quotations']) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">Abiertas / enviadas</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Pipeline</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-500/10">
                            <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    @php $total = $kpis['pipelineTotal']; @endphp
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        ${{ $total >= 1e6 ? number_format($total/1e6, 1).'M' : number_format($total, 0) }}
                    </p>
                    <p class="text-xs text-gray-400 -mt-2">Monto total abierto</p>
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
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($kpis['won']) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">Cerradas ganadas</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Win Rate</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-500/10">
                            <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </span>
                    </div>
                    @php
                        $totalOport = ($kpis['opportunities'] ?? 0) + ($kpis['won'] ?? 0);
                        $rate = $totalOport > 0 ? round(($kpis['won'] / $totalOport) * 100, 1) : 0;
                    @endphp
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $rate }}%</p>
                    <p class="text-xs text-gray-400 -mt-2">{{ number_format($kpis['won']) }} de {{ number_format($totalOport) }} oport.</p>
                </div>

            </div>

            {{-- ── GRÁFICA ─────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Pipeline por etapa</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Monto total en cotizaciones abiertas</p>
                </div>
                <div style="position:relative; height:220px; width:100%;">
                    <canvas id="pipelineChart"></canvas>
                </div>
            </div>

            {{-- ── FILTROS (server-side) ───────────────────── --}}
            <form method="GET" action="{{ route('admin.sales.pipeline') }}" id="filter-form">
                <input type="hidden" name="page" value="1">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-4 py-3">
                    <div class="flex flex-wrap items-center gap-3">

                        <select name="ejecutiva"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                                       text-gray-800 dark:text-gray-200 text-sm rounded-lg px-3 py-2
                                       focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">Todas las ejecutivas</option>
                            @foreach($ejecutivas as $ej)
                                <option value="{{ $ej['id'] }}" {{ $userId == $ej['id'] ? 'selected' : '' }}>
                                    {{ $ej['name'] }}
                                </option>
                            @endforeach
                        </select>

                        <select name="etapa"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                                       text-gray-800 dark:text-gray-200 text-sm rounded-lg px-3 py-2
                                       focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">Todas las etapas</option>
                            <option value="draft" {{ $state === 'draft' ? 'selected' : '' }}>Borrador</option>
                            <option value="sent"  {{ $state === 'sent'  ? 'selected' : '' }}>Enviada</option>
                        </select>

                        <button type="submit"
                                class="flex items-center gap-1.5 bg-purple-600 hover:bg-purple-500 text-white
                                       text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                            </svg>
                            Filtrar
                        </button>

                        <a href="{{ route('admin.sales.pipeline') }}"
                           class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors px-1">
                            Limpiar
                        </a>

                        <span class="ml-auto text-xs text-gray-400">
                            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($total) }}</span> cotizaciones
                        </span>

                    </div>
                </div>
            </form>

            {{-- ── TABLA ───────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Cotizaciones abiertas</h2>
                    <a href="{{ route('admin.sales.pipeline') }}?export=csv&ejecutiva={{ $userId }}&etapa={{ $state }}"
                       class="flex items-center gap-1.5 text-xs bg-purple-600 hover:bg-purple-500 text-white
                              px-3 py-1.5 rounded-lg transition-colors font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Exportar CSV
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Orden</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Ejecutiva</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Monto</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Vence</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                            @forelse($pipeline as $order)
                            @php
                                $vence    = $order['validity_date'] ? \Carbon\Carbon::parse($order['validity_date']) : null;
                                $vencido  = $vence && $vence->isPast();
                                $diasLeft = $vence ? (int) now()->diffInDays($vence, false) : null;
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-purple-600 dark:text-purple-400">
                                    {{ $order['name'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                                    {{ is_array($order['partner_id']) ? $order['partner_id'][1] : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ is_array($order['user_id']) ? $order['user_id'][1] : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                    ${{ number_format($order['amount_total'], 2) }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($order['state'] === 'draft')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Borrador</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Enviada</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $order['date_order'] ? \Carbon\Carbon::parse($order['date_order'])->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-xs whitespace-nowrap">
                                    @if($vence)
                                        @if($vencido)
                                            <span class="text-red-500 font-medium">Vencida</span>
                                        @elseif($diasLeft <= 7)
                                            <span class="text-amber-500 font-medium">{{ $diasLeft }}d</span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">{{ $diasLeft }}d</span>
                                        @endif
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center text-sm text-gray-400">
                                    No hay cotizaciones con los filtros seleccionados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ── PAGINACIÓN ───────────────────────────── --}}
                @if($totalPages > 1)
                <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Página <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $page }}</span>
                        de <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $totalPages }}</span>
                        &nbsp;·&nbsp; {{ number_format($total) }} resultados
                    </span>
                    <div class="flex items-center gap-2">
                        @if($page > 1)
                            <a href="{{ route('admin.sales.pipeline', ['page' => $page - 1, 'ejecutiva' => $userId, 'etapa' => $state]) }}"
                               class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 border border-gray-200
                                      dark:border-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Anterior
                            </a>
                        @else
                            <span class="flex items-center gap-1.5 text-xs text-gray-300 dark:text-gray-600 border border-gray-100
                                         dark:border-gray-700 px-3 py-1.5 rounded-lg cursor-not-allowed">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Anterior
                            </span>
                        @endif

                        @if($page < $totalPages)
                            <a href="{{ route('admin.sales.pipeline', ['page' => $page + 1, 'ejecutiva' => $userId, 'etapa' => $state]) }}"
                               class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 border border-gray-200
                                      dark:border-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Siguiente
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @else
                            <span class="flex items-center gap-1.5 text-xs text-gray-300 dark:text-gray-600 border border-gray-100
                                         dark:border-gray-700 px-3 py-1.5 rounded-lg cursor-not-allowed">
                                Siguiente
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </span>
                        @endif
                    </div>
                </div>
                @endif

            </div>{{-- /tabla --}}

        </div>
    </div>

    @push('scripts')
    <script>
        const porEtapa = @json($porEtapa);

        function initPipelineChart() {
            const canvas = document.getElementById('pipelineChart');
            if (!canvas) return;
            if (window._pipelineChart instanceof Chart) window._pipelineChart.destroy();

            window._pipelineChart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels:   porEtapa.map(e => e.etapa),
                    datasets: [{
                        label: 'Monto',
                        data:  porEtapa.map(e => e.monto),
                        backgroundColor: ['rgba(139,92,246,.75)','rgba(234,179,8,.75)','rgba(16,185,129,.75)','rgba(59,130,246,.75)'],
                        borderColor:     ['rgba(139,92,246,1)',  'rgba(234,179,8,1)',  'rgba(16,185,129,1)',  'rgba(59,130,246,1)'],
                        borderWidth: 1, borderRadius: 6, borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b', titleColor: '#e2e8f0',
                            bodyColor: '#94a3b8', borderColor: '#334155', borderWidth: 1, padding: 10,
                            callbacks: {
                                label: ctx => {
                                    const e = porEtapa[ctx.dataIndex];
                                    const monto = new Intl.NumberFormat('es-PA', {
                                        style: 'currency', currency: 'USD', maximumFractionDigits: 0
                                    }).format(ctx.parsed.y);
                                    return [`  ${monto}`, `  ${e.cantidad} cotizaciones`];
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { color: 'rgba(148,163,184,.06)' }, ticks: { color: '#94a3b8', font: { size: 12 } } },
                        y: {
                            grid: { color: 'rgba(148,163,184,.06)' },
                            ticks: {
                                color: '#94a3b8', font: { size: 11 },
                                callback: v => '$' + (v >= 1e6 ? (v/1e6).toFixed(1)+'M' : v >= 1e3 ? (v/1e3).toFixed(0)+'K' : v)
                            }
                        }
                    }
                }
            });
        }

        const _cjs = document.createElement('script');
        _cjs.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        _cjs.onload = initPipelineChart;
        document.head.appendChild(_cjs);
    </script>
    @endpush

</x-app-layout>