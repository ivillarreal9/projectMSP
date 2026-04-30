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
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ number_format($total) }} registros encontrados</p>
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Alertas --}}
            @if(session('success'))
                <div class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl text-sm text-green-700 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl text-sm text-red-700 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Buscador --}}
            <form method="GET" action="{{ route('admin.glpi.items', $itemtype) }}" class="flex gap-2">
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Buscar por nombre..."
                           class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:border-orange-400 transition"/>
                </div>
                <button type="submit"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Buscar
                </button>
                @if($search)
                    <a href="{{ route('admin.glpi.items', $itemtype) }}"
                       class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                        Limpiar
                    </a>
                @endif
            </form>

            {{-- Tabla --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                @if(count($items) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Serial</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Inventario</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Entidad</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($items as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500 font-mono text-xs">
                                        #{{ $item['id'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">
                                        {{ $item['name'] ?? '(sin nombre)' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">
                                        {{ $item['serial'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                        {{ $item['otherserial'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                        {{ $item['entities_id'] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $estado = $item['states_id'] ?? null;
                                            $estadoLabel = is_array($estado) ? ($estado['name'] ?? '—') : ($estado ?? '—');
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                            {{ $estadoLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.glpi.show', [$itemtype, $item['id']]) }}"
                                               class="text-xs text-blue-500 hover:text-blue-700 transition font-medium">
                                                Ver
                                            </a>
                                            <a href="{{ route('admin.glpi.edit', [$itemtype, $item['id']]) }}"
                                               class="text-xs text-orange-500 hover:text-orange-700 transition font-medium">
                                                Editar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    @if($totalPages > 1)
                    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Página {{ $page }} de {{ $totalPages }} · {{ number_format($total) }} registros
                        </p>
                        <div class="flex items-center gap-1">
                            @if($page > 1)
                                <a href="{{ route('admin.glpi.items', $itemtype) }}?page={{ $page - 1 }}&search={{ $search }}"
                                   class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    ← Anterior
                                </a>
                            @endif
                            @if($page < $totalPages)
                                <a href="{{ route('admin.glpi.items', $itemtype) }}?page={{ $page + 1 }}&search={{ $search }}"
                                   class="px-3 py-1.5 text-xs bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition">
                                    Siguiente →
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif

                @else
                    <div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
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
    </div>
</x-app-layout>
