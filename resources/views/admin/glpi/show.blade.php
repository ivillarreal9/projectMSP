<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                    <a href="{{ route('admin.glpi.index') }}" class="hover:text-orange-500 transition">GLPI</a>
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <a href="{{ route('admin.glpi.items', $itemtype) }}" class="hover:text-orange-500 transition">{{ $label }}</a>
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $item['name'] ?? '#' . $item['id'] }}</span>
                </nav>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                    {{ $item['name'] ?? '(sin nombre)' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">ID #{{ $item['id'] }}</p>
            </div>
            <a href="{{ route('admin.glpi.edit', [$itemtype, $item['id']]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Info principal --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Información general</h3>
                </div>
                <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                    $fields = [
                        'name'          => 'Nombre',
                        'serial'        => 'Número de serie',
                        'otherserial'   => 'Número de inventario',
                        'entities_id'   => 'Entidad',
                        'states_id'     => 'Estado',
                        'locations_id'  => 'Ubicación',
                        'users_id'      => 'Usuario asignado',
                        'groups_id'     => 'Grupo asignado',
                        'manufacturers_id' => 'Fabricante',
                        'computermodels_id' => 'Modelo',
                        'comment'       => 'Comentario',
                        'date_creation' => 'Fecha de creación',
                        'date_mod'      => 'Última modificación',
                    ];
                    @endphp

                    @foreach($fields as $key => $fieldLabel)
                        @if(isset($item[$key]) && $item[$key] !== '' && $item[$key] !== null && $item[$key] !== 0)
                        <div class="{{ $key === 'comment' ? 'sm:col-span-2' : '' }}">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">
                                {{ $fieldLabel }}
                            </p>
                            <p class="text-sm text-gray-800 dark:text-gray-200">
                                @php
                                    $val = $item[$key];
                                    echo is_array($val) ? ($val['name'] ?? json_encode($val)) : $val;
                                @endphp
                            </p>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Todos los campos raw (colapsado) --}}
            <details class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <summary class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-400 cursor-pointer hover:text-gray-800 dark:hover:text-gray-200 transition select-none">
                    Ver todos los campos de GLPI
                </summary>
                <div class="px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                        @foreach($item as $key => $value)
                        <div>
                            <p class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $key }}</p>
                            <p class="text-xs text-gray-700 dark:text-gray-300 break-all">
                                @php
                                    echo is_array($value) ? json_encode($value) : ($value ?? '—');
                                @endphp
                            </p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </details>

            {{-- Botón volver --}}
            <div>
                <a href="{{ route('admin.glpi.items', $itemtype) }}"
                   class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver a {{ $label }}
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
