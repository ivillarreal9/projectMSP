<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.sales.index') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Ventas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Clientes</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @include('admin.sales.partials.nav')

            {{-- TÍTULO --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Clientes Existentes</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Ovnicom ·
                    @if($ejecutivaNombre)
                        <span class="text-purple-400 font-medium">{{ $ejecutivaNombre }}</span> ·
                    @endif
                    {{ number_format($total) }} clientes · actualizado {{ now()->format('d/m/Y H:i') }}
                </p>
            </div>

            {{-- KPI CARDS --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                {{-- Total --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-50 dark:bg-slate-500/10">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($total) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">
                        {{ $ejecutivaNombre ? 'Cartera de ' . $ejecutivaNombre : 'Cartera total' }}
                    </p>
                </div>

                {{-- Al día --}}
                <a href="{{ route('admin.sales.clients', array_merge(request()->only('ejecutiva'), ['riesgo' => 'al_dia', 'page' => 1])) }}"
                   class="bg-white dark:bg-gray-800 rounded-xl border shadow-sm p-5 flex flex-col gap-3 transition
                          {{ $riesgo === 'al_dia' ? 'border-green-400 dark:border-green-600' : 'border-gray-200 dark:border-gray-700 hover:border-green-400' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Al día</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($countAlDia) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">0 – 30 días sin factura</p>
                </a>

                {{-- Atención --}}
                <a href="{{ route('admin.sales.clients', array_merge(request()->only('ejecutiva'), ['riesgo' => 'atencion', 'page' => 1])) }}"
                   class="bg-white dark:bg-gray-800 rounded-xl border shadow-sm p-5 flex flex-col gap-3 transition
                          {{ $riesgo === 'atencion' ? 'border-amber-400 dark:border-amber-600' : 'border-gray-200 dark:border-gray-700 hover:border-amber-400' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Atención</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-500/10">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-amber-500">{{ number_format($countAtencion) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">31 – 60 días sin factura</p>
                </a>

                {{-- En riesgo --}}
                <a href="{{ route('admin.sales.clients', array_merge(request()->only('ejecutiva'), ['riesgo' => 'critico', 'page' => 1])) }}"
                   class="bg-white dark:bg-gray-800 rounded-xl border shadow-sm p-5 flex flex-col gap-3 transition
                          {{ $riesgo === 'critico' ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700 hover:border-red-400' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">En riesgo</span>
                        <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/10">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-red-500">{{ number_format($countEnRiesgo) }}</p>
                    <p class="text-xs text-gray-400 -mt-2">Más de 60 días sin factura</p>
                </a>

            </div>

            {{-- FILTROS --}}
            <form method="GET" action="{{ route('admin.sales.clients') }}">
                <input type="hidden" name="page" value="1">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-4 py-3">
                    <div class="flex flex-wrap items-center gap-3">

                        <select name="ejecutiva" onchange="this.form.submit()"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                                       text-gray-800 dark:text-gray-200 text-sm rounded-lg px-3 py-2
                                       focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todas las ejecutivas</option>
                            @foreach($ejecutivas as $ej)
                                <option value="{{ $ej['id'] }}" {{ $ejecutiva == $ej['id'] ? 'selected' : '' }}>
                                    {{ $ej['name'] }}
                                </option>
                            @endforeach
                        </select>

                        <select name="riesgo"
                                class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                                       text-gray-800 dark:text-gray-200 text-sm rounded-lg px-3 py-2
                                       focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los estados</option>
                            <option value="al_dia"   {{ $riesgo === 'al_dia'   ? 'selected' : '' }}>Al día (0–30 días)</option>
                            <option value="atencion" {{ $riesgo === 'atencion' ? 'selected' : '' }}>Atención (31–60 días)</option>
                            <option value="critico"  {{ $riesgo === 'critico'  ? 'selected' : '' }}>En riesgo (+60 días)</option>
                        </select>

                        <button type="submit"
                                class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-500 text-white
                                       text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                            </svg>
                            Filtrar
                        </button>

                        @if($ejecutiva || $riesgo)
                            <a href="{{ route('admin.sales.clients') }}"
                               class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition px-1">
                                Limpiar
                            </a>
                        @endif

                        <div class="ml-auto flex items-center gap-4 text-xs text-gray-400">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-green-500"></span>Al día</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span>Atención</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span>En riesgo</span>
                        </div>

                    </div>
                </div>
            </form>

            {{-- TABLA --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">

                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Cartera de clientes
                        @if($ejecutivaNombre)
                            <span class="ml-1 text-xs font-normal text-purple-400">· {{ $ejecutivaNombre }}</span>
                        @endif
                        @if($riesgo)
                            <span class="ml-1 text-xs font-normal text-gray-400">· filtrado:
                                <span class="{{ $riesgo === 'critico' ? 'text-red-500' : ($riesgo === 'atencion' ? 'text-amber-500' : 'text-green-500') }}">
                                    {{ $riesgo === 'critico' ? 'En riesgo' : ($riesgo === 'atencion' ? 'Atención' : 'Al día') }}
                                </span>
                            </span>
                        @endif
                    </h2>
                    <span class="text-xs text-gray-400">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($totalFiltrado) }}</span> clientes
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Ejecutiva</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Última factura</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Días sin actividad</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                            @forelse($clients as $client)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">

                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $client['name'] }}</p>
                                    @if(($client['customer_rank'] ?? 0) > 1)
                                        <p class="text-xs text-gray-400 mt-0.5">Recurrente · rank {{ $client['customer_rank'] }}</p>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $client['executive_name'] }}
                                </td>

                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    @if($client['last_invoice'])
                                        {{ \Carbon\Carbon::parse($client['last_invoice'])->format('d/m/Y') }}
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">Sin facturas</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold {{ $client['days_color'] }}">
                                        {{ $client['days_inactive'] >= 999 ? '—' : $client['days_inactive'] . ' días' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $client['risk_color'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $client['risk_dot'] }}"></span>
                                        {{ $client['risk_display'] }}
                                    </span>
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <p class="text-sm text-gray-400">No hay clientes con los filtros seleccionados.</p>
                                    @if($ejecutiva || $riesgo)
                                        <a href="{{ route('admin.sales.clients') }}"
                                           class="mt-2 inline-block text-xs text-blue-500 hover:text-blue-400 transition">
                                            Limpiar filtros
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINACIÓN --}}
                @if($totalPages > 1)
                <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Página <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $page }}</span>
                        de <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $totalPages }}</span>
                        &nbsp;·&nbsp; {{ number_format($total) }} resultados
                    </span>
                    <div class="flex items-center gap-2">
                        @if($page > 1)
                            <a href="{{ route('admin.sales.clients', ['page' => $page - 1, 'ejecutiva' => $ejecutiva, 'riesgo' => $riesgo]) }}"
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
                            <a href="{{ route('admin.sales.clients', ['page' => $page + 1, 'ejecutiva' => $ejecutiva, 'riesgo' => $riesgo]) }}"
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

        </div>
    </div>
</x-app-layout>