<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">API MSP</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Alertas --}}
            @if(session('success'))
                <div class="mb-4 flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error') || isset($error))
                <div class="mb-4 flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('error') ?? $error }}
                </div>
            @endif

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">API MSP</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Consulta tickets desde MSP Manager por rango de fecha.
                        @if($credential)
                            <span class="inline-flex items-center gap-1 ml-2 text-green-600 dark:text-green-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Conectado como {{ $credential->username }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 ml-2 text-red-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Sin credenciales configuradas
                            </span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    @if(!empty($tickets))
                        <a href="{{ route('admin.api-msp.export', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}"
                           class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Exportar Excel
                        </a>
                    @endif
                    <button onclick="openCredModal()"
                            class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Credenciales
                    </button>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-5">
                <form method="GET" action="{{ route('admin.api-msp.index') }}"
                      class="px-6 py-4 flex flex-wrap items-end gap-4">

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha desde</label>
                        <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}"
                               class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha hasta</label>
                        <input type="date" name="fecha_fin" value="{{ $fechaFin }}"
                               class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                    </div>

                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Filtrar
                    </button>

                    @if(!empty($tickets))
                        <span class="text-sm text-gray-400 dark:text-gray-500 self-center">
                            {{ count($tickets) }} registros encontrados
                        </span>
                    @endif
                </form>
            </div>

            {{-- Tabla --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                @if(empty($tickets))
                    <div class="flex flex-col items-center gap-3 py-16 text-gray-300 dark:text-gray-600">
                        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500">
                            @if(!$credential)
                                Configura las credenciales para comenzar
                            @else
                                Selecciona un rango de fechas y presiona Filtrar
                            @endif
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Ticket #</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Título</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Ubicación</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Sub-tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Work Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Técnico</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Creado</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">Completado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                @foreach($tickets as $ticket)
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                                    <td class="px-4 py-3 font-mono text-xs text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                        {{ $ticket['TicketNumber'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-800 dark:text-gray-200 max-w-xs truncate" title="{{ $ticket['TicketTitle'] ?? '' }}">
                                        {{ $ticket['TicketTitle'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ $ticket['CustomerName'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ $ticket['LocationName'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ $ticket['TicketIssueTypeName'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ $ticket['TicketSubIssueTypeName'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if(!empty($ticket['WorkType']))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                                {{ $ticket['WorkType'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ trim(($ticket['UserFirstName'] ?? '') . ' ' . ($ticket['UserLastName'] ?? '')) ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                                        {{ $ticket['CreatedDate'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                                        {{ $ticket['CompletedDate'] ?? 'N/A' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Total: {{ count($tickets) }} registros — Período: {{ $fechaInicio }} al {{ $fechaFin }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Credenciales --}}
    <div id="cred-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">

            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-full flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Credenciales MSP</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Autenticación Basic para MSP Manager</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.api-msp.credentials') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Usuario (email)</label>
                    <input type="text" name="username"
                           value="{{ $credential->username ?? '' }}"
                           placeholder="usuario@empresa.com"
                           class="w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contraseña</label>
                    <input type="password" name="password"
                           placeholder="••••••••"
                           class="w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Base URL de la API</label>
                    <input type="text" name="base_url"
                           value="{{ $credential->base_url ?? 'https://api.mspmanager.com/odata' }}"
                           class="w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeCredModal()"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition">
                        Guardar credenciales
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCredModal() {
            const m = document.getElementById('cred-modal');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }
        function closeCredModal() {
            const m = document.getElementById('cred-modal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }
        document.getElementById('cred-modal').addEventListener('click', function(e) {
            if (e.target === this) closeCredModal();
        });
    </script>

</x-app-layout>