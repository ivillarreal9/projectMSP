<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('admin.surveys.index') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Encuestas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">{{ $type->nombre }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $type->nombre }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $surveys->total() }} respuestas recibidas</p>
                        <span class="text-gray-300 dark:text-gray-600">·</span>
                        <div class="flex flex-wrap gap-1">
                            @foreach($type->campos as $campo)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    {{ $campo }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.surveys.export', $type->slug) }}"
                   class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Descargar Excel
                </a>
            </div>

            {{-- Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">

                {{-- Buscador --}}
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <form method="GET" action="{{ route('admin.surveys.show', $type->slug) }}"
                          class="flex flex-wrap items-center gap-3">
                        <div class="relative w-72">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Buscar por nombre o WhatsApp..."
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </div>
                        <button type="submit"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition">
                            Buscar
                        </button>
                        @if(request('search'))
                            <a href="{{ route('admin.surveys.show', $type->slug) }}"
                               class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                                Limpiar
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Tabla --}}
                @if($surveys->isEmpty())
                    <div class="flex flex-col items-center gap-3 py-16 text-gray-300 dark:text-gray-600">
                        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500">No se encontraron respuestas</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">WhatsApp</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Nombre</th>
                                    @foreach($type->campos as $campo)
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                            {{ ucfirst(str_replace('_', ' ', $campo)) }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                @foreach($surveys as $survey)
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">

                                    {{-- Fecha --}}
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $survey->fecha ? \Carbon\Carbon::parse($survey->fecha)->format('d/m/Y') : 'N/A' }}
                                    </td>

                                    {{-- WhatsApp --}}
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $survey->numero_whatsapp ?? 'N/A' }}
                                    </td>

                                    {{-- Nombre --}}
                                    <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ $survey->nombre ?? 'Sin nombre' }}
                                    </td>

                                    {{-- Campos dinámicos desde data --}}
                                    @foreach($type->campos as $campo)
                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $val  = $survey->field($campo);
                                                $num  = is_numeric($val) ? (int)$val : null;
                                                $color = match(true) {
                                                    $num >= 4                  => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                    $num === 3                 => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                    $num !== null && $num <= 2 => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                    default                    => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                                };
                                            @endphp
                                            @if($num !== null)
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold {{ $color }}">
                                                    {{ $val }}
                                                </span>
                                            @else
                                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $val ?? '—' }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach

                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    @if($surveys->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Mostrando {{ $surveys->firstItem() }}–{{ $surveys->lastItem() }} de {{ $surveys->total() }} respuestas
                        </p>
                        {{ $surveys->links() }}
                    </div>
                    @endif
                @endif

                {{-- Footer --}}
                <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Total: {{ $surveys->total() }} respuestas recibidas
                    </p>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>