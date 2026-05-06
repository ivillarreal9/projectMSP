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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if(session('error'))
                <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl text-sm text-red-700 dark:text-red-400 mb-6">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Buscador + Filtros --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <form method="GET" action="{{ route('admin.glpi.items', $itemtype) }}" class="flex gap-2 flex-1">
                    @if(request('type_id'))
                        <input type="hidden" name="type_id" value="{{ request('type_id') }}">
                    @endif
                    <input type="hidden" name="sort" value="{{ request('sort', 'nombre_asc') }}">
                    <div class="relative flex-1 max-w-sm">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="Buscar por nombre..."
                               class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:border-orange-400 transition"/>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Buscar
                    </button>
                    @if($search || request('type_id'))
                        <a href="{{ route('admin.glpi.items', $itemtype) }}" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                            Limpiar
                        </a>
                    @endif
                </form>

                {{-- Ordenamiento --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0">Ordenar:</span>
                    @php
                        $sorts = [
                            'nombre_asc'     => 'Nombre A→Z',
                            'nombre_desc'    => 'Nombre Z→A',
                            'total_desc'     => 'Más equipos',
                            'total_asc'      => 'Menos equipos',
                            'deposito_desc'  => 'Más en depósito',
                            'deposito_asc'   => 'Menos en depósito',
                        ];
                        $currentSort = request('sort', 'nombre_asc');
                    @endphp
                    @foreach($sorts as $key => $label_sort)
                    <a href="{{ route('admin.glpi.items', $itemtype) }}?sort={{ $key }}&search={{ $search }}{{ request('type_id') ? '&type_id='.request('type_id') : '' }}"
                       class="px-3 py-1.5 text-xs rounded-lg font-medium transition
                              {{ $currentSort === $key
                                  ? 'bg-orange-500 text-white'
                                  : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        {{ $label_sort }}
                    </a>
                    @endforeach
                </div>
            </div>

            @if(count($items) > 0)

                @php
                    // ── Agrupar por modelo ────────────────────────────────────
                    $grouped = [];
                    foreach ($items as $item) {
                        $modelo = $item['networkequipmentmodels_id']
                            ?? $item['computermodels_id']
                            ?? $item['printermodels_id']
                            ?? $item['phonemodelid']
                            ?? 'Sin modelo';
                        if (!$modelo || $modelo === '0' || $modelo === 0) $modelo = 'Sin modelo';

                        if (!isset($grouped[$modelo])) {
                            $grouped[$modelo] = ['equipos' => [], 'en_deposito' => 0];
                        }
                        $grouped[$modelo]['equipos'][] = $item;
                        if (str_contains(strtolower($item['states_id'] ?? ''), 'dep')) {
                            $grouped[$modelo]['en_deposito']++;
                        }
                    }

                    // ── Ordenar grupos según filtro ───────────────────────────
                    switch ($currentSort) {
                        case 'nombre_asc':
                            ksort($grouped);
                            break;
                        case 'nombre_desc':
                            krsort($grouped);
                            break;
                        case 'total_desc':
                            uasort($grouped, fn($a, $b) => count($b['equipos']) - count($a['equipos']));
                            break;
                        case 'total_asc':
                            uasort($grouped, fn($a, $b) => count($a['equipos']) - count($b['equipos']));
                            break;
                        case 'deposito_desc':
                            uasort($grouped, fn($a, $b) => $b['en_deposito'] - $a['en_deposito']);
                            break;
                        case 'deposito_asc':
                            uasort($grouped, fn($a, $b) => $a['en_deposito'] - $b['en_deposito']);
                            break;
                    }
                @endphp

                {{-- ── Grid de grupos 4 por fila ───────────────────────────── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($grouped as $modelo => $data)
                    @php
                        $totalGrupo   = count($data['equipos']);
                        $deposito     = $data['en_deposito'];
                        $pct          = $totalGrupo > 0 ? round(($deposito / $totalGrupo) * 100) : 0;
                        $slugModelo   = Str::slug($modelo);
                    @endphp

                    {{-- Tarjeta de grupo colapsable --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

                        {{-- Header con toggle --}}
                        <button onclick="toggleGroup('{{ $slugModelo }}')"
                                class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition text-left">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-7 h-7 bg-orange-50 dark:bg-orange-900/30 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $modelo }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs text-orange-500 font-semibold">{{ $totalGrupo }}</span>
                                        <span class="text-xs text-gray-400">{{ $totalGrupo === 1 ? 'equipo' : 'equipos' }}</span>
                                        @if($deposito > 0)
                                            <span class="text-xs px-1.5 py-0.5 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-800 font-medium">
                                                {{ $deposito }} dep.
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <svg id="chevron-{{ $slugModelo }}"
                                 class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-200 -rotate-90"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Barra de depósito --}}
                        @if($totalGrupo > 0)
                        <div class="h-1 bg-gray-100 dark:bg-gray-700">
                            <div class="h-1 {{ $pct > 25 ? 'bg-amber-400' : 'bg-orange-400' }} transition-all"
                                 style="width: {{ max(0, 100 - $pct) }}%"></div>
                        </div>
                        @endif

                        {{-- Lista de equipos colapsable --}}
                        <div id="grupo-{{ $slugModelo }}"
                             class="hidden border-t border-gray-100 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($data['equipos'] as $item)
                            @php
                                $estado      = $item['states_id'] ?? '—';
                                $estadoLower = strtolower($estado);
                                $estadoColor = match(true) {
                                    str_contains($estadoLower, 'activo') => 'text-green-600 dark:text-green-400',
                                    str_contains($estadoLower, 'dep')    => 'text-amber-600 dark:text-amber-400',
                                    str_contains($estadoLower, 'indef')  => 'text-gray-400',
                                    default                              => 'text-blue-500',
                                };
                            @endphp
                            <div class="px-4 py-3 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">
                                        {{ $item['name'] ?: '(sin nombre)' }}
                                    </p>
                                    <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $item['serial'] ?: '—' }}</p>
                                    <p class="text-xs {{ $estadoColor }} mt-0.5">{{ $estado }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    <a href="{{ route('admin.glpi.show', [$itemtype, $item['id']]) }}"
                                       class="text-xs text-blue-500 hover:text-blue-700 font-medium transition">Ver</a>
                                    <a href="{{ route('admin.glpi.edit', [$itemtype, $item['id']]) }}"
                                       class="text-xs text-orange-500 hover:text-orange-700 font-medium transition">Editar</a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Paginación --}}
                @if($totalPages > 1)
                <div class="flex items-center justify-between py-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Página {{ $page }} de {{ $totalPages }} · {{ number_format($total) }} registros
                    </p>
                    <div class="flex items-center gap-1">
                        @if($page > 1)
                            <a href="{{ route('admin.glpi.items', $itemtype) }}?page={{ $page - 1 }}&search={{ $search }}&sort={{ $currentSort }}{{ request('type_id') ? '&type_id='.request('type_id') : '' }}"
                               class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                ← Anterior
                            </a>
                        @endif
                        @if($page < $totalPages)
                            <a href="{{ route('admin.glpi.items', $itemtype) }}?page={{ $page + 1 }}&search={{ $search }}&sort={{ $currentSort }}{{ request('type_id') ? '&type_id='.request('type_id') : '' }}"
                               class="px-3 py-1.5 text-xs bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition">
                                Siguiente →
                            </a>
                        @endif
                    </div>
                </div>
                @endif

            @else
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
                    <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-sm font-medium">No se encontraron registros</p>
                    <p class="text-xs mt-1">
                        @if($search) Intenta con otro término de búsqueda.
                        @else Aún no hay {{ strtolower($label) }} registrados.
                        @endif
                    </p>
                </div>
            @endif

        </div>
    </div>

    <script>
        function toggleGroup(slug) {
            const body    = document.getElementById('grupo-' + slug);
            const chevron = document.getElementById('chevron-' + slug);
            const open    = !body.classList.contains('hidden');

            body.classList.toggle('hidden', open);
            chevron.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
        }
    </script>
</x-app-layout>