<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Control de Enlaces Carrier</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Circuitos de red por país y carrier</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($stats['activos'] > 0)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-semibold rounded-full">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                    {{ $stats['activos'] }} activos
                </span>
                @endif
                @if($lastBatch)
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    Actualizado: {{ $lastBatch->created_at->isoFormat('D MMM YYYY') }}
                </span>
                @endif
                @if($stats['total'] > 0)
                <a href="{{ route('admin.enlaces.pdf') }}" target="_blank"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-medium transition"
                   title="Descargar un PDF con todos los circuitos por país">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Exportar PDF
                </a>
                @endif
                @if($lastBatch && $hasCredentials && $hasFolder)
                <form action="{{ route('admin.enlaces.sync') }}" method="POST">
                    @csrf
                    <button type="submit"
                            onclick="this.disabled=true; this.querySelector('svg')?.classList.add('animate-spin'); this.form.submit();"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-white text-xs font-medium hover:opacity-90 transition disabled:opacity-60"
                            style="background:#0078d4"
                            title="Vuelve a descargar de SharePoint el archivo ya importado y actualiza los circuitos">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Actualizar archivo
                    </button>
                </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="carriersApp()" x-init="init()">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Flash messages --}}
            @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-lg text-sm">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg text-sm">
                {{ session('error') }}
            </div>
            @endif

            {{-- ── Stats bar ─────────────────────────────────────────────── --}}
            <div class="bg-gray-900 dark:bg-gray-950 rounded-xl mb-4 px-5 py-4 flex flex-wrap items-center gap-x-8 gap-y-3">
                {{-- Totales por país --}}
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">{{ $stats['total'] }}</div>
                        <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Total</div>
                    </div>
                    @foreach($stats['paises'] as $pais => $count)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">{{ $count }}</div>
                        <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">{{ $pais }}</div>
                    </div>
                    @endforeach
                </div>

                <div class="w-px h-10 bg-gray-700 hidden sm:block"></div>

                {{-- Estado dots --}}
                <div class="flex items-center gap-5 text-sm">
                    <span class="flex items-center gap-1.5 text-gray-300">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        {{ $stats['activos'] }} activos
                    </span>
                    <span class="flex items-center gap-1.5 text-gray-300">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        {{ $stats['incidentes'] }} incidentes
                    </span>
                    <span class="flex items-center gap-1.5 text-gray-300">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                        {{ $stats['mantenimiento'] }} mantenimiento
                    </span>
                </div>

                <div class="w-px h-10 bg-gray-700 hidden sm:block"></div>

                {{-- Capacidad --}}
                <div class="text-sm text-gray-400">
                    Capacidad total:
                    <span class="font-bold text-white">
                        @if($stats['capacidad'] >= 1024)
                            {{ number_format($stats['capacidad'] / 1024, 1) }} GB
                        @else
                            {{ number_format($stats['capacidad']) }} MB
                        @endif
                    </span>
                </div>
            </div>

            {{-- ── SharePoint import panel (solo en la primera carga) ───────── --}}
            @if(!$lastBatch && $hasCredentials)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl mb-4 overflow-hidden">
                <div class="p-4 flex items-center justify-between">
                    <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-200 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21.18 5.42l-4.6-3.98A2 2 0 0015.17 1H8.83a2 2 0 00-1.41.44L2.82 5.42A2 2 0 002 6.97V20a2 2 0 002 2h16a2 2 0 002-2V6.97a2 2 0 00-.82-1.55z"/>
                        </svg>
                        Cargar archivo desde SharePoint
                        <span id="fileBadge" class="hidden text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full"></span>
                    </h3>
                    <button onclick="loadSharePointFiles()" id="loadBtn"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-white text-xs font-medium hover:opacity-90 transition"
                            style="background:#0078d4">
                        <svg id="loadIcon" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                        </svg>
                        <span id="loadText">Consultar archivos</span>
                    </button>
                </div>

                <div id="spEmpty" class="px-4 pb-4">
                    <div class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg p-6 text-center text-gray-400">
                        <p class="text-sm">Haz clic en <strong>Consultar archivos</strong> para ver los archivos Excel disponibles</p>
                    </div>
                </div>

                @if(!$hasFolder)
                <div class="px-4 pb-4">
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-3 text-xs text-amber-700 dark:text-amber-300">
                        Agrega <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded">SHAREPOINT_ENLACES_FOLDER_ID</code> en el .env para apuntar a la carpeta de enlaces.
                    </div>
                </div>
                @endif

                <div id="spFilesList" class="hidden divide-y dark:divide-gray-700"></div>
                <div id="spError" class="hidden px-4 pb-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                        <span id="spErrorMsg"></span>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Filter bar ───────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl mb-4 p-3 flex flex-wrap items-center gap-2">
                {{-- Search --}}
                <div class="relative flex-1 min-w-[220px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input x-model.debounce.300ms="search" type="text" placeholder="Buscar por cliente, IP, ID de circuito, carrier..."
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>

                {{-- Country pills --}}
                <div class="flex items-center gap-1 flex-wrap">
                    <button @click="paisFilter = ''"
                            :class="paisFilter === '' ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'"
                            class="px-3 py-1.5 text-xs font-semibold rounded-full transition">
                        Todos
                    </button>
                    <template x-for="pais in paises" :key="pais">
                        <button @click="paisFilter = paisFilter === pais ? '' : pais"
                                :class="paisFilter === pais ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-full transition flex items-center gap-1">
                            <span x-text="countryFlag(pais)" class="text-xs"></span>
                            <span x-text="pais"></span>
                        </button>
                    </template>
                </div>

                <div class="w-px h-6 bg-gray-200 dark:bg-gray-600 hidden sm:block"></div>

                {{-- Carrier pills --}}
                <div class="flex items-center gap-1 flex-wrap">
                    <button @click="carrierFilter = ''"
                            :class="carrierFilter === '' ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'"
                            class="px-3 py-1.5 text-xs font-semibold rounded-full transition">
                        Carriers
                    </button>
                    <template x-for="c in carriers" :key="c">
                        <button @click="carrierFilter = carrierFilter === c ? '' : c"
                                :class="carrierFilter === c ? 'bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-full transition"
                                x-text="c">
                        </button>
                    </template>
                </div>

                <div class="w-px h-6 bg-gray-200 dark:bg-gray-600 hidden sm:block"></div>

                {{-- View toggle --}}
                <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5">
                    <button @click="vista = 'tarjetas'"
                            :class="vista === 'tarjetas' ? 'bg-white dark:bg-gray-800 shadow text-gray-800 dark:text-gray-100' : 'text-gray-500'"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        Tarjetas
                    </button>
                    <button @click="vista = 'tabla'"
                            :class="vista === 'tabla' ? 'bg-white dark:bg-gray-800 shadow text-gray-800 dark:text-gray-100' : 'text-gray-500'"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6z"/>
                        </svg>
                        Tabla
                    </button>
                </div>

                {{-- Count --}}
                <span class="ml-auto text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                    <span x-text="filtered.length"></span> de <span>{{ $stats['total'] }}</span>
                </span>
            </div>

            {{-- Referencia técnica --}}
            @if($lastBatch?->referencia_tecnica)
            <div class="text-right text-xs text-gray-400 dark:text-gray-500 mb-3">
                Referencia técnica &bull; {{ $lastBatch->referencia_tecnica }} &middot; {{ $lastBatch->created_at->isoFormat('D [de] MMMM [de] YYYY') }}
            </div>
            @endif

            {{-- ── Sin datos ────────────────────────────────────────────── --}}
            @if($stats['total'] === 0)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-16 flex flex-col items-center text-gray-400 dark:text-gray-500">
                <svg class="w-14 h-14 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <p class="text-sm font-medium">No hay circuitos registrados</p>
                <p class="text-xs mt-1">Importa un archivo Excel desde SharePoint para comenzar.</p>
            </div>
            @else

            {{-- ── Skeleton (mientras Alpine.js inicializa) ─────────────── --}}
            <div x-show="!pageReady">
                <div class="mb-8">
                    <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-3"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @for($i = 0; $i < 8; $i++)
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div class="h-4 w-36 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                <div class="h-4 w-14 bg-gray-200 dark:bg-gray-700 rounded-full animate-pulse"></div>
                            </div>
                            <div class="flex gap-1 mb-2">
                                <div class="h-4 w-20 bg-blue-100 dark:bg-blue-900/20 rounded animate-pulse"></div>
                                <div class="h-4 w-16 bg-gray-100 dark:bg-gray-700 rounded animate-pulse"></div>
                            </div>
                            <div class="h-3 w-full bg-gray-100 dark:bg-gray-700 rounded animate-pulse mb-1"></div>
                            <div class="h-3 w-3/4 bg-gray-100 dark:bg-gray-700 rounded animate-pulse mb-3"></div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-3 mb-3">
                                @for($j = 0; $j < 6; $j++)
                                <div>
                                    <div class="h-2 w-16 bg-gray-100 dark:bg-gray-700 rounded animate-pulse mb-1"></div>
                                    <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                </div>
                                @endfor
                            </div>
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                                <div class="h-3 w-28 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-1"></div>
                                <div class="h-2 w-20 bg-gray-100 dark:bg-gray-700 rounded animate-pulse"></div>
                            </div>
                        </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- ── Vista Tarjetas ───────────────────────────────────────── --}}
            <div x-show="pageReady && vista === 'tarjetas'">
                <template x-for="(group, pais) in groupedByPais" :key="pais">
                    <div class="mb-8">
                        <h3 class="text-xs font-bold text-orange-500 uppercase tracking-widest mb-3" x-text="pais"></h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <template x-for="enlace in group" :key="enlace.id">
                                <div @click="openModal(enlace)"
                                     class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition cursor-pointer">

                                    {{-- Header card --}}
                                    <div class="flex items-start justify-between mb-1">
                                        <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 leading-tight flex-1 pr-2" x-text="enlace.cliente"></h4>
                                        <span :class="estadoBadgeClass(enlace.estado)"
                                              class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="estadoDotClass(enlace.estado)"></span>
                                            <span x-text="enlace.estado"></span>
                                        </span>
                                    </div>

                                    {{-- Badges país + carrier --}}
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        <span x-show="enlace.pais"
                                              class="text-[10px] font-medium px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded border border-blue-100 dark:border-blue-800"
                                              x-text="enlace.pais"></span>
                                        <span x-show="enlace.carrier"
                                              class="text-[10px] font-medium px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-600"
                                              x-text="enlace.carrier"></span>
                                    </div>

                                    {{-- Ubicación --}}
                                    <p x-show="enlace.ubicacion" class="text-[11px] text-gray-400 dark:text-gray-500 mb-3 line-clamp-2" x-text="enlace.ubicacion"></p>

                                    {{-- Datos técnicos --}}
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 mb-3">
                                        <div x-show="enlace.id_circuito">
                                            <div class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide">ID Circuito</div>
                                            <div class="text-xs font-mono text-gray-700 dark:text-gray-200 truncate" x-text="enlace.id_circuito"></div>
                                        </div>
                                        <div x-show="enlace.capacidad">
                                            <div class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide">Capacidad</div>
                                            <div class="text-xs font-semibold text-gray-700 dark:text-gray-200" x-text="capacidadLabel(enlace.capacidad)"></div>
                                        </div>
                                        <div x-show="enlace.gateway">
                                            <div class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide">Gateway</div>
                                            <div class="text-xs font-mono text-gray-700 dark:text-gray-200" x-text="enlace.gateway"></div>
                                        </div>
                                        <div x-show="enlace.ip_disponible">
                                            <div class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide">IP Disponible</div>
                                            <div class="text-xs font-mono text-gray-700 dark:text-gray-200" x-text="enlace.ip_disponible"></div>
                                        </div>
                                        <div x-show="enlace.mascara">
                                            <div class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide">Máscara</div>
                                            <div class="text-xs font-mono text-gray-700 dark:text-gray-200" x-text="enlace.mascara"></div>
                                        </div>
                                        <div x-show="enlace.dns">
                                            <div class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide">DNS</div>
                                            <div class="text-xs font-mono text-gray-700 dark:text-gray-200 truncate" x-text="enlace.dns"></div>
                                        </div>
                                    </div>

                                    {{-- Contacto --}}
                                    <div x-show="enlace.contacto_nombre || enlace.contacto_telefono || enlace.contacto_email"
                                         class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                        <div>
                                            <div x-show="enlace.contacto_nombre" class="text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                                                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                <span x-text="enlace.contacto_nombre"></span>
                                            </div>
                                            <div x-show="enlace.contacto_telefono" class="text-[11px] text-gray-400" x-text="enlace.contacto_telefono"></div>
                                        </div>
                                        <div class="flex gap-1">
                                            <span x-show="enlace.contacto_telefono"
                                                  class="text-[10px] px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-600">
                                                Tel
                                            </span>
                                            <span x-show="enlace.contacto_email"
                                                  class="text-[10px] px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-600">
                                                Email
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Sin resultados de filtro --}}
                <div x-show="filtered.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-12 text-center text-gray-400">
                    <p class="text-sm">Sin resultados para los filtros aplicados.</p>
                </div>
            </div>

            {{-- ── Vista Tabla ──────────────────────────────────────────── --}}
            <div x-show="pageReady && vista === 'tabla'" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="text-left px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Cliente</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">País / Carrier</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Estado</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">ID Circuito</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Capacidad</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Gateway</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">IP Disponible</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Máscara / DNS</th>
                                <th class="text-left px-4 py-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Contacto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="enlace in filtered" :key="enlace.id">
                                <tr @click="openModal(enlace)" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition cursor-pointer">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-800 dark:text-gray-200" x-text="enlace.cliente"></div>
                                        <div class="text-gray-400 text-[10px] mt-0.5 truncate max-w-[180px]" x-text="enlace.ubicacion"></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-medium px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded w-fit" x-text="enlace.pais"></span>
                                            <span class="text-[10px] font-medium px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded w-fit" x-text="enlace.carrier"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="estadoBadgeClass(enlace.estado)"
                                              class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="estadoDotClass(enlace.estado)"></span>
                                            <span x-text="enlace.estado"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-gray-700 dark:text-gray-300" x-text="enlace.id_circuito || '—'"></td>
                                    <td class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300" x-text="capacidadLabel(enlace.capacidad)"></td>
                                    <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400" x-text="enlace.gateway || '—'"></td>
                                    <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400" x-text="enlace.ip_disponible || '—'"></td>
                                    <td class="px-4 py-3">
                                        <div class="font-mono text-gray-600 dark:text-gray-400" x-text="enlace.mascara || '—'"></div>
                                        <div class="font-mono text-gray-400 text-[10px] mt-0.5" x-text="[enlace.dns, enlace.dns_secundario].filter(Boolean).join(' / ')"></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-gray-700 dark:text-gray-300" x-text="enlace.contacto_nombre || '—'"></div>
                                        <div class="text-gray-400 text-[10px]" x-text="enlace.contacto_telefono"></div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filtered.length === 0">
                                <td colspan="9" class="px-4 py-10 text-center text-gray-400">Sin resultados para los filtros aplicados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif


        </div>
    </div>

    {{-- ── Modal de detalle ─────────────────────────────────────────────────── --}}
    <div x-data
         x-show="$store.enlaces.showModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @keydown.escape.window="$store.enlaces.showModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none">

        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$store.enlaces.showModal = false"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            <template x-if="$store.enlaces.selected">
                <div>
                    {{-- Modal header --}}
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" x-text="$store.enlaces.selected.cliente"></h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[11px] font-medium px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded" x-text="$store.enlaces.selected.pais"></span>
                                <span class="text-[11px] font-medium px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded" x-text="$store.enlaces.selected.carrier"></span>
                                <span :class="$store.enlaces.estadoBadgeClass($store.enlaces.selected.estado)"
                                      class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="$store.enlaces.estadoDotClass($store.enlaces.selected.estado)"></span>
                                    <span x-text="$store.enlaces.selected.estado"></span>
                                </span>
                            </div>
                            <p x-show="$store.enlaces.selected.ubicacion" class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="$store.enlaces.selected.ubicacion"></p>
                        </div>
                        <button @click="$store.enlaces.showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition ml-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Datos técnicos --}}
                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Datos del circuito</p>
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div x-show="$store.enlaces.selected.id_circuito">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">ID Circuito</div>
                                <div class="text-sm font-mono text-gray-800 dark:text-gray-200 font-semibold" x-text="$store.enlaces.selected.id_circuito"></div>
                            </div>
                            <div x-show="$store.enlaces.selected.so_ref">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">SO / Ref.</div>
                                <div class="text-sm font-mono text-gray-700 dark:text-gray-300" x-text="$store.enlaces.selected.so_ref"></div>
                            </div>
                            <div x-show="$store.enlaces.selected.capacidad">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Capacidad</div>
                                <div class="text-sm font-bold text-gray-800 dark:text-gray-200" x-text="$store.enlaces.capacidadLabel($store.enlaces.selected.capacidad)"></div>
                            </div>
                            <div x-show="$store.enlaces.selected.gateway">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Gateway</div>
                                <div class="text-sm font-mono text-gray-700 dark:text-gray-300" x-text="$store.enlaces.selected.gateway"></div>
                            </div>
                            <div x-show="$store.enlaces.selected.ip_disponible">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">IP Disponible</div>
                                <div class="text-sm font-mono text-gray-700 dark:text-gray-300" x-text="$store.enlaces.selected.ip_disponible"></div>
                            </div>
                            <div x-show="$store.enlaces.selected.mascara">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Máscara</div>
                                <div class="text-sm font-mono text-gray-700 dark:text-gray-300" x-text="$store.enlaces.selected.mascara"></div>
                            </div>
                            <div x-show="$store.enlaces.selected.dns">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">DNS Primario</div>
                                <div class="text-sm font-mono text-gray-700 dark:text-gray-300" x-text="$store.enlaces.selected.dns"></div>
                            </div>
                            <div x-show="$store.enlaces.selected.dns_secundario">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">DNS Secundario</div>
                                <div class="text-sm font-mono text-gray-700 dark:text-gray-300" x-text="$store.enlaces.selected.dns_secundario"></div>
                            </div>
                        </div>

                        {{-- Notas --}}
                        <div x-show="$store.enlaces.selected.notas" class="mb-5">
                            <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Notas</div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line" x-text="$store.enlaces.selected.notas"></p>
                        </div>

                        {{-- Contacto --}}
                        <div x-show="$store.enlaces.selected.contacto_nombre || $store.enlaces.selected.contacto_telefono || $store.enlaces.selected.contacto_email"
                             class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Contacto</p>
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-200" x-text="$store.enlaces.selected.contacto_nombre"></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="$store.enlaces.selected.contacto_telefono"></div>
                                    <div class="text-sm text-blue-600 dark:text-blue-400" x-text="$store.enlaces.selected.contacto_email"></div>
                                </div>
                                <div class="flex gap-2">
                                    <a x-show="$store.enlaces.selected.contacto_telefono"
                                       :href="'tel:' + $store.enlaces.selected.contacto_telefono"
                                       class="px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs font-medium rounded-lg hover:bg-green-200 transition">
                                        Llamar
                                    </a>
                                    <a x-show="$store.enlaces.selected.contacto_email"
                                       :href="'mailto:' + $store.enlaces.selected.contacto_email"
                                       class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium rounded-lg hover:bg-blue-200 transition">
                                        Email
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>

{{-- ── JavaScript ──────────────────────────────────────────────────────────── --}}
<script>
// Alpine store for modal (shared with modal overlay outside x-data scope)
document.addEventListener('alpine:init', () => {
    Alpine.store('enlaces', {
        showModal: false,
        selected: null,
        estadoBadgeClass(estado) {
            return {
                activo:        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                incidente:     'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                mantenimiento: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
            }[estado] ?? 'bg-gray-100 text-gray-600';
        },
        estadoDotClass(estado) {
            return {
                activo: 'bg-green-500', incidente: 'bg-red-500', mantenimiento: 'bg-yellow-500'
            }[estado] ?? 'bg-gray-400';
        },
        capacidadLabel(mb) {
            if (!mb) return '—';
            return mb >= 1024 ? (mb / 1024).toFixed(2) + ' GB' : mb + ' MB';
        }
    });
});

function carriersApp() {
    return {
        search: '',
        paisFilter: '',
        carrierFilter: '',
        vista: 'tarjetas',
        pageReady: false,
        enlaces: @json($enlaces),

        init() {
            this.$nextTick(() => { this.pageReady = true; });
            @if($lastBatch && $hasCredentials && $hasFolder)
            this.autoSync();
            @endif
        },

        // Sync en segundo plano: no bloquea la página. Si trae datos nuevos, recarga una vez.
        autoSync() {
            fetch('{{ route('admin.enlaces.auto-sync') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
            })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (data && data.status === 'synced') {
                    window.location.reload();
                }
            })
            .catch(() => {});
        },

        get paises() {
            return [...new Set(this.enlaces.map(e => e.pais).filter(Boolean))].sort();
        },

        get carriers() {
            return [...new Set(this.enlaces.map(e => e.carrier).filter(Boolean))].sort();
        },

        get filtered() {
            return this.enlaces.filter(e => {
                if (this.paisFilter && e.pais !== this.paisFilter) return false;
                if (this.carrierFilter && e.carrier !== this.carrierFilter) return false;
                if (this.search) {
                    const s = this.search.toLowerCase();
                    return (e.cliente || '').toLowerCase().includes(s)
                        || (e.id_circuito || '').toLowerCase().includes(s)
                        || (e.ip_disponible || '').toLowerCase().includes(s)
                        || (e.gateway || '').toLowerCase().includes(s)
                        || (e.carrier || '').toLowerCase().includes(s);
                }
                return true;
            });
        },

        get groupedByPais() {
            const groups = {};
            this.filtered.forEach(e => {
                const key = e.pais || 'Sin País';
                if (!groups[key]) groups[key] = [];
                groups[key].push(e);
            });
            return groups;
        },

        estadoBadgeClass(estado) {
            return {
                activo:        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                incidente:     'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                mantenimiento: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
            }[estado] ?? 'bg-gray-100 text-gray-600';
        },

        estadoDotClass(estado) {
            return { activo: 'bg-green-500', incidente: 'bg-red-500', mantenimiento: 'bg-yellow-500' }[estado] ?? 'bg-gray-400';
        },

        capacidadLabel(mb) {
            if (!mb) return '—';
            return mb >= 1024 ? (mb / 1024).toFixed(2) + ' GB' : mb + ' MB';
        },

        countryFlag(pais) {
            const flags = {
                'Guatemala':    '🇬🇹',
                'El Salvador':  '🇸🇻',
                'Colombia':     '🇨🇴',
                'Honduras':     '🇭🇳',
                'Nicaragua':    '🇳🇮',
                'Costa Rica':   '🇨🇷',
                'Panamá':       '🇵🇦',
                'México':       '🇲🇽',
            };
            return flags[pais] ?? '🌐';
        },

        openModal(enlace) {
            Alpine.store('enlaces').selected = enlace;
            Alpine.store('enlaces').showModal = true;
        },

        closeModal() {
            Alpine.store('enlaces').showModal = false;
        }
    };
}

// ── SharePoint file loading ───────────────────────────────────────────────────
function loadSharePointFiles() {
    const btn  = document.getElementById('loadBtn');
    const icon = document.getElementById('loadIcon');
    const text = document.getElementById('loadText');

    btn.disabled = true;
    icon.style.animation = 'spin 1s linear infinite';
    text.textContent = 'Cargando...';

    fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json',
                   'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) throw new Error(data.error);
        renderFiles(data.files || []);
    })
    .catch(err => {
        document.getElementById('spEmpty').classList.add('hidden');
        document.getElementById('spError').classList.remove('hidden');
        document.getElementById('spErrorMsg').textContent = err.message;
    })
    .finally(() => {
        btn.disabled = false;
        icon.style.animation = '';
        text.textContent = 'Actualizar lista';
    });
}

function renderFiles(files) {
    const empty = document.getElementById('spEmpty');
    const list  = document.getElementById('spFilesList');
    const badge = document.getElementById('fileBadge');

    empty.classList.add('hidden');
    list.innerHTML = '';

    if (files.length === 0) {
        empty.classList.remove('hidden');
        return;
    }

    badge.textContent = files.length + ' archivos';
    badge.classList.remove('hidden');
    list.classList.remove('hidden');

    files.forEach(f => {
        const row = document.createElement('div');
        row.className = 'px-5 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition';
        row.innerHTML = `
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">${esc(f.name)}</div>
                    <div class="text-xs text-gray-400">${esc(f.size)} &bull; ${new Date(f.modified).toLocaleDateString('es-MX')}</div>
                </div>
            </div>
            <button onclick="openImportModal(this)" data-filename="${esc(f.name)}" data-itemid="${esc(f.item_id || '')}"
                    class="px-3 py-1.5 rounded-lg text-white text-xs font-medium hover:opacity-90 transition"
                    style="background:#0078d4">
                Importar
            </button>`;
        list.appendChild(row);
    });
}

function openImportModal(btn) {
    const filename = btn.dataset.filename;
    const itemId   = btn.dataset.itemid;
    if (!confirm(`¿Importar "${filename}"?\n\nSe agregarán los circuitos a la base de datos.`)) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.enlaces.sharepoint.import") }}';
    form.innerHTML = `
        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
        <input type="hidden" name="filename" value="${esc(filename)}">
        <input type="hidden" name="item_id"  value="${esc(itemId)}">`;
    document.body.appendChild(form);
    form.submit();
}

function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
