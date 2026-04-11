<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.sales.index') }}" class="hover:text-gray-600 transition">Ventas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Reasignación</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @include('admin.sales.partials.nav')

            {{-- ── TÍTULO ──────────────────────────────────── --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Reasignación de Cuentas</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Clientes sin actividad · candidatos a reasignar
                </p>
            </div>

            {{-- ── KPI CARDS ───────────────────────────────── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-50 dark:bg-slate-500/10">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalClients) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">Clientes encontrados</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Al día</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($alDia) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">0 – 30 días</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Atención</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-500/10">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-amber-500">{{ number_format($atencion) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">31 – 60 días</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">En riesgo</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/10">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-red-500">{{ number_format($enRiesgo) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">Más de 60 días</p>
                </div>

            </div>

            {{-- ── FILTROS ─────────────────────────────────── --}}
            <form method="GET" action="{{ route('admin.sales.reassign') }}" id="filter-form">
                <input type="hidden" name="page" value="1">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-4 py-3">
                    <div class="flex flex-wrap items-center gap-3">

                        {{-- Días sin actividad --}}
                        <select name="days"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                                       text-gray-800 dark:text-gray-200 text-sm rounded-lg px-3 py-2
                                       focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="30"  {{ $days == 30  ? 'selected' : '' }}>+30 días</option>
                            <option value="45"  {{ $days == 45  ? 'selected' : '' }}>+45 días</option>
                            <option value="60"  {{ $days == 60  ? 'selected' : '' }}>+60 días</option>
                            <option value="90"  {{ $days == 90  ? 'selected' : '' }}>+90 días</option>
                            <option value="180" {{ $days == 180 ? 'selected' : '' }}>+180 días</option>
                        </select>

                        {{-- Ejecutiva --}}
                        <select name="ejecutiva"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                                       text-gray-800 dark:text-gray-200 text-sm rounded-lg px-3 py-2
                                       focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="">Todas las ejecutivas</option>
                            @foreach($ejecutivas as $ej)
                                <option value="{{ $ej['id'] }}" {{ $ejecutiva == $ej['id'] ? 'selected' : '' }}>
                                    {{ $ej['name'] }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit"
                                class="flex items-center gap-1.5 bg-red-600 hover:bg-red-500 text-white
                                       text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                            </svg>
                            Filtrar
                        </button>

                        <a href="{{ route('admin.sales.reassign') }}"
                           class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors px-1">
                            Limpiar
                        </a>

                        <span class="ml-auto text-xs text-gray-400">
                            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($totalClients) }}</span> clientes
                        </span>

                    </div>
                </div>
            </form>

            {{-- ── TABLA CON CHECKBOXES ────────────────────── --}}
            <form id="export-form" method="POST" action="{{ route('admin.sales.reassign.export') }}">
                @csrf

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">

                    {{-- Toolbar --}}
                    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 cursor-pointer select-none">
                            <input type="checkbox" id="select-all"
                                   class="rounded border-gray-300 text-red-500 focus:ring-red-400">
                            <span>Seleccionar todos</span>
                            <span id="selected-count"
                                  class="text-xs text-gray-400 font-normal">(0 seleccionados)</span>
                        </label>
                        <button type="submit"
                                class="flex items-center gap-1.5 bg-red-600 hover:bg-red-500 text-white
                                       text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Exportar seleccionados
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                    <th class="px-6 py-3 w-10"></th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Ejecutiva actual</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Última factura</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Días sin actividad</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Riesgo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                @forelse($clients as $i => $client)
                                @php
                                    $daysOld     = $client['days_old'];
                                    $executive   = $client['executive'];
                                    $lastInvoice = $client['date_last_invoice'];

                                    $riskColor = match($client['risk_label']) {
                                        'Al día'    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                        'Atención'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                        default     => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                    };
                                    $daysColor = match($client['risk_label']) {
                                        'Al día'   => 'text-emerald-600 dark:text-emerald-400',
                                        'Atención' => 'text-amber-600 dark:text-amber-400',
                                        default    => 'text-red-600 dark:text-red-400',
                                    };
                                    $rowIndex = (($page - 1) * 50) + $i;
                                @endphp
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                                    <td class="px-6 py-4">
                                        <input type="checkbox"
                                               name="clients[{{ $rowIndex }}][name]"
                                               value="{{ $client['name'] }}"
                                               class="client-checkbox rounded border-gray-300 text-red-500 focus:ring-red-400">
                                        <input type="hidden" name="clients[{{ $rowIndex }}][executive]"   value="{{ $executive }}">
                                        <input type="hidden" name="clients[{{ $rowIndex }}][days]"        value="{{ $daysOld >= 999 ? '—' : $daysOld }}">
                                        <input type="hidden" name="clients[{{ $rowIndex }}][last_invoice]" value="{{ $lastInvoice ?? '—' }}">
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ $client['name'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $executive }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $lastInvoice ? \Carbon\Carbon::parse($lastInvoice)->format('d/m/Y') : 'Sin facturas' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold {{ $daysColor }}">
                                            {{ $daysOld >= 999 ? '—' : $daysOld . ' días' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $riskColor }}">
                                            {{ $client['risk_label'] }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center text-sm text-gray-400">
                                        No hay clientes con más de {{ $days }} días sin actividad.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- ── PAGINACIÓN ───────────────────────── --}}
                    @if($totalPages > 1)
                    <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Página <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $page }}</span>
                            de <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $totalPages }}</span>
                            &nbsp;·&nbsp; {{ number_format($totalClients) }} resultados
                        </span>
                        <div class="flex items-center gap-2">
                            @if($page > 1)
                                <a href="{{ route('admin.sales.reassign', ['page' => $page - 1, 'days' => $days, 'ejecutiva' => $ejecutiva]) }}"
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
                                <a href="{{ route('admin.sales.reassign', ['page' => $page + 1, 'days' => $days, 'ejecutiva' => $ejecutiva]) }}"
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

                </div>
            </form>

        </div>
    </div>

    @push('scripts')
    <script>
        const selectAll   = document.getElementById('select-all');
        const countEl     = document.getElementById('selected-count');
        const checkboxes  = document.querySelectorAll('.client-checkbox');

        function updateCount() {
            const n = document.querySelectorAll('.client-checkbox:checked').length;
            countEl.textContent = '(' + n + ' seleccionado' + (n !== 1 ? 's' : '') + ')';
            selectAll.indeterminate = n > 0 && n < checkboxes.length;
            selectAll.checked = n === checkboxes.length && checkboxes.length > 0;
        }

        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateCount();
        });

        checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
    </script>
    @endpush

</x-app-layout>