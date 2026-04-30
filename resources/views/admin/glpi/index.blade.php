<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">GLPI — Inventario</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Gestión de activos y equipos</p>
            </div>

            {{-- Botones de sesión GLPI --}}
            <div class="flex items-center gap-2">

                {{-- Indicador de estado --}}
                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full
                    {{ session('glpi_session_active')
                        ? 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                    <span class="w-1.5 h-1.5 rounded-full
                        {{ session('glpi_session_active') ? 'bg-green-500' : 'bg-gray-400' }}">
                    </span>
                    {{ session('glpi_session_active') ? 'Sesión activa' : 'Sin sesión' }}
                </span>

                {{-- Iniciar sesión --}}
                <form method="POST" action="{{ route('admin.glpi.session.init') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                                   bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Iniciar sesión
                    </button>
                </form>

                {{-- Cerrar sesión --}}
                <form method="POST" action="{{ route('admin.glpi.session.kill') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                                   bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar sesión
                    </button>
                </form>

            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Alertas --}}
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl text-sm text-red-700 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Título de sección --}}
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4">
                Tipos de activos
            </p>

            {{-- Grid de tarjetas por tipo de activo --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                @php
                $icons = [
                    'Computer'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                    'NetworkEquipment' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>',
                    'Printer'          => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>',
                    'Phone'            => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
                    'Monitor'          => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                    'Peripheral'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
                    'Software'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>',
                ];
                $colors = [
                    'Computer'         => ['border' => 'blue-400',   'bg' => 'blue-50',   'darkbg' => 'blue-900/30',   'icon' => 'blue-500',   'text' => 'blue-600'],
                    'NetworkEquipment' => ['border' => 'cyan-400',   'bg' => 'cyan-50',   'darkbg' => 'cyan-900/30',   'icon' => 'cyan-500',   'text' => 'cyan-600'],
                    'Printer'          => ['border' => 'teal-400',   'bg' => 'teal-50',   'darkbg' => 'teal-900/30',   'icon' => 'teal-500',   'text' => 'teal-600'],
                    'Phone'            => ['border' => 'green-400',  'bg' => 'green-50',  'darkbg' => 'green-900/30',  'icon' => 'green-500',  'text' => 'green-600'],
                    'Monitor'          => ['border' => 'indigo-400', 'bg' => 'indigo-50', 'darkbg' => 'indigo-900/30', 'icon' => 'indigo-500', 'text' => 'indigo-600'],
                    'Peripheral'       => ['border' => 'purple-400', 'bg' => 'purple-50', 'darkbg' => 'purple-900/30', 'icon' => 'purple-500', 'text' => 'purple-600'],
                    'Software'         => ['border' => 'orange-400', 'bg' => 'orange-50', 'darkbg' => 'orange-900/30', 'icon' => 'orange-500', 'text' => 'orange-600'],
                ];
                @endphp

                @foreach($summary as $type => $info)
                @php $c = $colors[$type] ?? $colors['Computer']; @endphp
                <a href="{{ route('admin.glpi.items', $type) }}"
                   class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5
                          hover:border-{{ $c['border'] }} dark:hover:border-{{ $c['border'] }}
                          hover:shadow-md transition-all duration-200">

                    <div class="flex items-start justify-between mb-4">
                        <div class="w-10 h-10 bg-{{ $c['bg'] }} dark:bg-{{ $c['darkbg'] }} rounded-lg flex items-center justify-center
                                    group-hover:bg-{{ $c['bg'] }} transition">
                            <svg class="w-5 h-5 text-{{ $c['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                {!! $icons[$type] ?? $icons['Computer'] !!}
                            </svg>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-{{ $c['border'] }} transition"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>

                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200
                               group-hover:text-{{ $c['text'] }} dark:group-hover:text-{{ $c['icon'] }} transition">
                        {{ $info['label'] }}
                    </h3>

                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        <span class="text-lg font-bold text-{{ $c['icon'] }}">{{ number_format($info['total']) }}</span>
                        registros
                    </p>
                </a>
                @endforeach

            </div>
        </div>
    </div>
</x-app-layout>