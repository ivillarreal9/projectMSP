<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                    <a href="{{ route('admin.glpi.index') }}" class="hover:text-orange-500 transition">GLPI</a>
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $label }}</span>
                </nav>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $label }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ number_format($total) }} registros totales</p>
            </div>
            <a href="{{ route('admin.glpi.create', $itemtype) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Agregar
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">

            @if(session('error'))
                <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl text-sm text-red-700 dark:text-red-400 mb-6">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Buscador --}}
            <form method="GET" action="{{ route('admin.glpi.items', $itemtype) }}" class="flex gap-2 mb-8">
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Buscar por nombre..."
                           class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:border-orange-400 transition"/>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                    Buscar
                </button>
                @if($search)
                    <a href="{{ route('admin.glpi.items', $itemtype) }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 transition flex items-center">
                        Limpiar
                    </a>
                @endif
            </form>

            {{-- Grupos por tipo --}}
            @forelse($grouped as $tipo => $modelos)
            @php $tipoKey = 'grupo-' . Str::slug($tipo); @endphp

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">

                {{-- Header clickeable del tipo --}}
                <button onclick="toggleGroup('{{ $tipoKey }}')"
                        class="w-full flex items-center gap-3 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition text-left">

                    {{-- Icono --}}
                    <div class="w-8 h-8 bg-cyan-50 dark:bg-cyan-900/30 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                        </svg>
                    </div>

                    {{-- Nombre y stats --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $tipo }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            <span class="font-semibold text-gray-600 dark:text-gray-300">{{ number_format(array_sum(array_column($modelos, 'total'))) }}</span> equipos ·
                            <span class="font-semibold text-amber-500">{{ number_format(array_sum(array_column($modelos, 'deposito'))) }}</span> en depósito ·
                            <span class="text-gray-400">{{ count($modelos) }} modelos</span>
                        </p>
                    </div>

                    {{-- Flecha --}}
                    <svg id="arrow-{{ $tipoKey }}"
                         class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                {{-- Contenido colapsable (cerrado por defecto) --}}
                <div id="{{ $tipoKey }}" class="hidden border-t border-gray-100 dark:border-gray-700 p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        @foreach($modelos as $modelo => $datos)
                        @php $modeloKey = $tipoKey . '-' . Str::slug($modelo); @endphp

                        <div>
                            {{-- Tarjeta modelo --}}
                            <div onclick="toggleDevices('{{ $modeloKey }}')"
                                 id="card-{{ $modeloKey }}"
                                 class="cursor-pointer bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl p-4
                                        hover:border-cyan-400 dark:hover:border-cyan-500 hover:shadow-md transition-all duration-200">

                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 leading-snug min-h-[2.5rem]">
                                    {{ $modelo }}
                                </p>

                                <div class="mb-3">
                                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">Total</p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-white leading-none">
                                        {{ number_format($datos['total']) }}
                                    </p>
                                </div>

                                <div class="h-px bg-gray-200 dark:bg-gray-600 mb-3"></div>

                                <div class="mb-4">
                                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-0.5">En Depósito</p>
                                    <p class="text-xl font-bold {{ $datos['deposito'] > 0 ? 'text-amber-500' : 'text-gray-300 dark:text-gray-600' }} leading-none">
                                        {{ number_format($datos['deposito']) }}
                                    </p>
                                </div>

                                @if($datos['total'] > 0)
                                @php $pct = min(100, round(($datos['total'] - $datos['deposito']) / $datos['total'] * 100)); @endphp
                                <div class="h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden mb-3">
                                    <div class="h-full rounded-full {{ $pct > 70 ? 'bg-green-500' : ($pct > 40 ? 'bg-cyan-500' : 'bg-amber-500') }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                @endif

                                <div class="flex items-center justify-center gap-1 text-xs text-gray-400">
                                    <svg id="arrow-{{ $modeloKey }}" class="w-3 h-3 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    <span>Ver dispositivos</span>
                                </div>
                            </div>

                            {{-- Lista desplegable dispositivos --}}
                            <div id="devices-{{ $modeloKey }}"
                                 class="hidden mt-2 bg-white dark:bg-gray-800 border border-cyan-200 dark:border-cyan-800 rounded-xl overflow-hidden shadow-lg">

                                <div class="px-4 py-2.5 bg-cyan-50 dark:bg-cyan-900/20 border-b border-cyan-100 dark:border-cyan-800 flex items-center justify-between">
                                    <p class="text-xs font-semibold text-cyan-700 dark:text-cyan-400">{{ $modelo }}</p>
                                    <span class="text-xs text-cyan-500">{{ $datos['total'] }} dispositivos</span>
                                </div>

                                <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-80 overflow-y-auto">
                                    @foreach($datos['items'] as $device)
                                    <div class="px-4 py-2.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                        <div class="min-w-0 flex-1 mr-3">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                                {{ $device['name'] ?: '(sin nombre)' }}
                                            </p>
                                            <p class="text-xs text-gray-400 font-mono">{{ $device['serial'] ?? '—' }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            @php
                                                $s  = $device['states_id'] ?? null;
                                                $sl = is_array($s) ? ($s['name'] ?? '—') : ($s ?? '—');
                                                $sc = match(true) {
                                                    str_contains(strtolower($sl), 'activ')  => 'green',
                                                    str_contains(strtolower($sl), 'dep')    => 'amber',
                                                    str_contains(strtolower($sl), 'devuel') => 'red',
                                                    default => 'gray'
                                                };
                                            @endphp
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                                bg-{{ $sc }}-50 text-{{ $sc }}-700 dark:bg-{{ $sc }}-900/30 dark:text-{{ $sc }}-400">
                                                {{ $sl }}
                                            </span>
                                            <a href="{{ route('admin.glpi.show', [$itemtype, $device['id']]) }}"
                                               class="text-xs text-blue-500 hover:text-blue-700 font-medium">Ver</a>
                                            <a href="{{ route('admin.glpi.edit', [$itemtype, $device['id']]) }}"
                                               class="text-xs text-orange-500 hover:text-orange-700 font-medium">Editar</a>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
            @empty
            <div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
                <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-sm font-medium">No se encontraron registros</p>
                @if($search)
                    <a href="{{ route('admin.glpi.items', $itemtype) }}" class="mt-2 text-xs text-orange-500 hover:text-orange-700">Limpiar búsqueda</a>
                @endif
            </div>
            @endforelse

        </div>
    </div>

    <script>
        // Toggle grupo tipo (flecha lateral → apunta abajo al abrir)
        function toggleGroup(key) {
            const content = document.getElementById(key);
            const arrow   = document.getElementById('arrow-' + key);
            const isOpen  = !content.classList.contains('hidden');
            content.classList.toggle('hidden', isOpen);
            arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
        }

        // Toggle dispositivos dentro de tarjeta modelo
        function toggleDevices(key) {
            const devices = document.getElementById('devices-' + key);
            const arrow   = document.getElementById('arrow-' + key);
            const card    = document.getElementById('card-' + key);
            const isOpen  = !devices.classList.contains('hidden');
            devices.classList.toggle('hidden', isOpen);
            arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
            card.classList.toggle('border-cyan-400', !isOpen);
        }
    </script>
</x-app-layout>