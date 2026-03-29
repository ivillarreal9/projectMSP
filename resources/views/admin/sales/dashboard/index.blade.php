<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Ventas</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @include('admin.sales.partials.nav')

            {{-- Header --}}
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Resumen General</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">KPIs comerciales · Ovnicom · actualizados ahora</p>
                </div>
            </div>

            {{-- Fila 1 — KPIs principales --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

                {{-- Leads --}}
                <div class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Leads</p>
                        <div class="w-7 h-7 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($kpis['leads']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Activos</p>
                </div>

                {{-- Oportunidades --}}
                <div class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Oport.</p>
                        <div class="w-7 h-7 bg-purple-50 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($kpis['opportunities']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">En progreso</p>
                </div>

                {{-- Cotizaciones --}}
                <div class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Cotiz.</p>
                        <div class="w-7 h-7 bg-amber-50 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($kpis['quotations']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Abiertas / enviadas</p>
                </div>

                {{-- Ganadas --}}
                <div class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Ganadas</p>
                        <div class="w-7 h-7 bg-green-50 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($kpis['won']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Cerradas ganadas</p>
                </div>

                {{-- Clientes en riesgo --}}
                <div class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-900/50 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-red-400 uppercase tracking-wider">En riesgo</p>
                        <div class="w-7 h-7 bg-red-50 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($kpis['atRisk']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">+60 días sin actividad</p>
                </div>

                {{-- Pipeline total --}}
                <div class="xl:col-span-1 bg-white dark:bg-gray-800 rounded-xl border border-indigo-200 dark:border-indigo-900/50 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Pipeline</p>
                        <div class="w-7 h-7 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        ${{ number_format($kpis['pipelineTotal'] ?? 0, 0) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Monto total abierto</p>
                </div>
            </div>

            {{-- Fila 2 — Win Rate --}}
            @php
                $winRate = $kpis['opportunities'] > 0
                    ? round(($kpis['won'] / ($kpis['opportunities'] + $kpis['won'])) * 100)
                    : 0;
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Win Rate global</p>
                    <span class="text-sm font-bold {{ $winRate >= 50 ? 'text-green-600' : ($winRate >= 25 ? 'text-amber-500' : 'text-red-500') }}">
                        {{ $winRate }}%
                    </span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-500
                        {{ $winRate >= 50 ? 'bg-green-500' : ($winRate >= 25 ? 'bg-amber-400' : 'bg-red-500') }}"
                         style="width: {{ $winRate }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    {{ number_format($kpis['won']) }} ganadas de {{ number_format($kpis['won'] + $kpis['opportunities']) }} oportunidades totales
                </p>
            </div>

            {{-- Fila 3 — Accesos rápidos --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('admin.sales.pipeline') }}"
                   class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-purple-400 hover:shadow-md transition-all flex items-center gap-3">
                    <div class="w-9 h-9 bg-purple-50 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:bg-purple-100 transition shrink-0">
                        <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-purple-600 transition">Pipeline</p>
                        <p class="text-xs text-gray-400">{{ number_format($kpis['quotations']) }} cotizaciones abiertas</p>
                    </div>
                </a>

                <a href="{{ route('admin.sales.clients') }}"
                   class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-blue-400 hover:shadow-md transition-all flex items-center gap-3">
                    <div class="w-9 h-9 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:bg-blue-100 transition shrink-0">
                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 transition">Clientes</p>
                        <p class="text-xs text-gray-400">Actividad y riesgo</p>
                    </div>
                </a>

                <a href="{{ route('admin.sales.executives') }}"
                   class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-amber-400 hover:shadow-md transition-all flex items-center gap-3">
                    <div class="w-9 h-9 bg-amber-50 dark:bg-amber-900/30 rounded-lg flex items-center justify-center group-hover:bg-amber-100 transition shrink-0">
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-amber-600 transition">Ejecutivas</p>
                        <p class="text-xs text-gray-400">Métricas individuales</p>
                    </div>
                </a>

                <a href="{{ route('admin.sales.reassign') }}"
                   class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-red-400 hover:shadow-md transition-all flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-50 dark:bg-red-900/30 rounded-lg flex items-center justify-center group-hover:bg-red-100 transition shrink-0">
                        <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-red-600 transition">Reasignación</p>
                        <p class="text-xs text-gray-400">{{ number_format($kpis['atRisk'] ?? 0) }} cuentas en riesgo</p>
                    </div>
                </a>
            </div>

        </div>
    </div>
</x-app-layout>