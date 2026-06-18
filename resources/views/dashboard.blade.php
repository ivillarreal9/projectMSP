<x-app-layout>

    {{-- ── Welcome banner ─────────────────────────────────────────────────────── --}}
    <div class="bg-gray-900 dark:bg-gray-950 border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-6">
            <div class="flex items-center justify-between gap-6">

                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse"></div>
                        <span class="text-[11px] font-bold text-orange-400/70 uppercase tracking-[0.2em]">
                            Ovnicom Analytics Platform
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-tight">
                        Hola, <span class="text-orange-400">{{ Str::before(auth()->user()->name, ' ') ?: auth()->user()->name }}</span>
                    </h1>
                    <p class="text-sm text-gray-500 mt-2">
                        {{ now()->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                        <span class="mx-2 text-gray-700">&middot;</span>
                        <span class="text-gray-600 tabular-nums">{{ now()->format('H:i') }}</span>
                    </p>
                </div>

                @php $totalModulos = count(auth()->user()->modulosAccesibles()); @endphp
                @if($totalModulos > 0)
                <div class="hidden sm:flex items-center gap-4 bg-gray-800/50 border border-gray-700/50 rounded-2xl px-5 py-3.5">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white leading-none">{{ $totalModulos }}</div>
                        <div class="text-[10px] text-gray-500 uppercase tracking-widest mt-1">
                            {{ $totalModulos === 1 ? 'módulo' : 'módulos' }}
                        </div>
                    </div>
                    <div class="w-px h-9 bg-gray-700"></div>
                    <div class="w-9 h-9 bg-orange-500/15 border border-orange-500/20 rounded-xl flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>

    {{-- ── Módulos ──────────────────────────────────────────────────────────── --}}
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-3 mb-6">
                <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em] whitespace-nowrap">
                    Módulos disponibles
                </p>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700/60"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">

                {{-- ── Reportes Masivos ── --}}
                @if(auth()->user()->canAccessModule('msp_reports'))
                <a href="{{ route('admin.msp.index') }}"
                   class="group flex flex-col bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-0.5 hover:border-orange-300 dark:hover:border-orange-600/50 transition-all duration-200">
                    <div class="h-28 bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-500/20 dark:to-amber-500/10 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-2xl shadow-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-7 h-7 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 px-5 py-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">Reportes Masivos</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Generación masiva de PDFs y email</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-orange-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endif

                {{-- ── Usuarios ── --}}
                @if(auth()->user()->canAccessModule('usuarios'))
                <a href="{{ route('admin.users.index') }}"
                   class="group flex flex-col bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-0.5 hover:border-indigo-300 dark:hover:border-indigo-600/50 transition-all duration-200">
                    <div class="h-28 bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-500/20 dark:to-indigo-600/10 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-2xl shadow-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-7 h-7 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 px-5 py-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">Usuarios</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Gestión de accesos y roles</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-indigo-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endif

                {{-- ── API MSP ── --}}
                @if(auth()->user()->canAccessModule('api_msp'))
                <a href="{{ route('admin.api-msp.index') }}"
                   class="group flex flex-col bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-0.5 hover:border-purple-300 dark:hover:border-purple-600/50 transition-all duration-200">
                    <div class="h-28 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-500/20 dark:to-purple-600/10 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-2xl shadow-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-7 h-7 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 px-5 py-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">API MSP</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Consulta de tickets por cliente</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-purple-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endif

                {{-- ── META 2 ── --}}
                @if(auth()->user()->canAccessModule('meta2'))
                <a href="{{ route('admin.meta-2.index') }}"
                   class="group flex flex-col bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-0.5 hover:border-emerald-300 dark:hover:border-emerald-600/50 transition-all duration-200">
                    <div class="h-28 bg-gradient-to-br from-emerald-50 to-teal-100 dark:from-emerald-500/20 dark:to-teal-500/10 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-2xl shadow-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-7 h-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 px-5 py-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">META 2</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Tickets de Telefonía con streaming</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endif

                {{-- ── Encuestas ── --}}
                @if(auth()->user()->canAccessModule('encuestas'))
                <a href="{{ route('admin.surveys.index') }}"
                   class="group flex flex-col bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-0.5 hover:border-violet-300 dark:hover:border-violet-600/50 transition-all duration-200">
                    <div class="h-28 bg-gradient-to-br from-violet-50 to-purple-100 dark:from-violet-500/20 dark:to-purple-500/10 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-2xl shadow-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-7 h-7 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M12 11v4m-2-2h4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 px-5 py-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">Encuestas</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Satisfacción de clientes vía WhatsApp</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-violet-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endif

                {{-- ── Ventas ── --}}
                @if(auth()->user()->canAccessModule('sales'))
                <a href="{{ route('admin.sales.index') }}"
                   class="group flex flex-col bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-0.5 hover:border-sky-300 dark:hover:border-sky-600/50 transition-all duration-200">
                    <div class="h-28 bg-gradient-to-br from-sky-50 to-blue-100 dark:from-sky-500/20 dark:to-blue-500/10 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-2xl shadow-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-7 h-7 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 px-5 py-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">Dashboard de Ventas</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Seguimiento comercial Odoo</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-sky-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endif

                {{-- ── GLPI ── --}}
                @if(auth()->user()->canAccessModule('glpi'))
                <a href="{{ route('admin.glpi.index') }}"
                   class="group flex flex-col bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-0.5 hover:border-cyan-300 dark:hover:border-cyan-600/50 transition-all duration-200">
                    <div class="h-28 bg-gradient-to-br from-cyan-50 to-sky-100 dark:from-cyan-500/20 dark:to-sky-500/10 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-2xl shadow-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-7 h-7 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 px-5 py-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">GLPI</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Inventario de activos y equipos IT</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-cyan-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endif

                {{-- ── Meraki ── --}}
                @if(auth()->user()->canAccessModule('meraki'))
                <a href="{{ route('admin.meraki.index') }}"
                   class="group flex flex-col bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-0.5 hover:border-teal-300 dark:hover:border-teal-600/50 transition-all duration-200">
                    <div class="h-28 bg-gradient-to-br from-teal-50 to-emerald-100 dark:from-teal-500/20 dark:to-emerald-500/10 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-2xl shadow-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-7 h-7 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 px-5 py-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">Meraki</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Redes y dispositivos Cisco Meraki</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-teal-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endif

                {{-- ── Control de Enlaces ── --}}
                @if(auth()->user()->canAccessModule('enlaces'))
                <a href="{{ route('admin.enlaces.index') }}"
                   class="group flex flex-col bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:shadow-xl hover:-translate-y-0.5 hover:border-blue-300 dark:hover:border-blue-600/50 transition-all duration-200">
                    <div class="h-28 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-500/20 dark:to-indigo-500/10 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white dark:bg-gray-700 rounded-2xl shadow-md flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-7 h-7 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 px-5 py-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">Control de Enlaces</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Circuitos carrier por país</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-blue-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
                @endif

                {{-- Sin módulos --}}
                @if($totalModulos === 0)
                <div class="col-span-full flex flex-col items-center justify-center py-20 text-center text-gray-400 dark:text-gray-500">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Sin módulos asignados</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Contacta al administrador para que te asigne un rol.</p>
                </div>
                @endif

            </div>
        </div>
    </div>

</x-app-layout>
