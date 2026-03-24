<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Encuestas de Satisfacción</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Alertas --}}
            @if(session('success'))
                <div class="mb-4 flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Encuestas Recibidas</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Respuestas de satisfacción recibidas vía WhatsApp.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-400 dark:text-gray-500">
                        {{ $surveys->total() }} registros
                    </span>
                    <a href="{{ route('admin.surveys.export') }}"
                       class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Descargar Excel
                    </a>

                    {{-- Botón Crear Token --}}
                    <button onclick="openTokenModal()"
                            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Crear Token
                    </button>
                </div>
            </div>

            {{-- Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">

                {{-- Buscador --}}
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <form method="GET" action="{{ route('admin.surveys.index') }}"
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
                            <a href="{{ route('admin.surveys.index') }}"
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
                        <p class="text-sm text-gray-400 dark:text-gray-500">No se encontraron encuestas</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Número WhatsApp</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Satisfacción</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Recomendación</th>
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

                                    {{-- Satisfacción --}}
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $sat = is_numeric($survey->satisfaccion) ? (int)$survey->satisfaccion : null;
                                            $satColor = match(true) {
                                                $sat >= 4              => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                $sat === 3             => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                $sat !== null && $sat <= 2 => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                default                => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold {{ $satColor }}">
                                            {{ $survey->satisfaccion ?? '?' }}
                                        </span>
                                    </td>

                                    {{-- Recomendación --}}
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $rec = is_numeric($survey->recomendacion) ? (int)$survey->recomendacion : null;
                                            $recColor = match(true) {
                                                $rec >= 4              => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                                $rec === 3             => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                $rec !== null && $rec <= 2 => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                default                => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold {{ $recColor }}">
                                            {{ $survey->recomendacion ?? '?' }}
                                        </span>
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    @if($surveys->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Mostrando {{ $surveys->firstItem() }}–{{ $surveys->lastItem() }} de {{ $surveys->total() }} encuestas
                        </p>
                        {{ $surveys->links() }}
                    </div>
                    @endif
                @endif

                {{-- Footer total --}}
                <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Total: {{ $surveys->total() }} encuestas recibidas
                    </p>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal Crear Token --}}
    <div id="token-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">

            {{-- Header modal --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-full flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Crear nuevo token</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Esta acción invalidará el token anterior</p>
                </div>
            </div>

            {{-- Token anterior --}}
            <div id="current-token-section" class="mb-5 hidden">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Token actual que será eliminado:</p>
                <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2">
                    <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span id="current-token-value"
                          class="text-xs font-mono text-gray-600 dark:text-gray-300 truncate">
                        Cargando...
                    </span>
                </div>
            </div>

            <div id="no-token-section" class="mb-5 hidden">
                <div class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg px-3 py-2">
                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs text-blue-600 dark:text-blue-300">No tienes ningún token activo actualmente.</span>
                </div>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                ¿Estás seguro que deseas generar un nuevo token? El token anterior quedará <span class="font-semibold text-red-500">invalidado inmediatamente</span>.
            </p>

            {{-- Nuevo token generado --}}
            <div id="new-token-section" class="mb-5 hidden">
                <p class="text-xs font-medium text-green-600 dark:text-green-400 mb-1">✓ Nuevo token generado:</p>
                <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg px-3 py-2">
                    <span id="new-token-value"
                          class="text-xs font-mono text-gray-700 dark:text-gray-200 break-all select-all">
                    </span>
                    <button onclick="copyToken()"
                            class="shrink-0 p-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 transition"
                            title="Copiar token">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Copia este token ahora, no volverá a mostrarse completo.</p>
            </div>

            {{-- Botones --}}
            <div id="modal-actions" class="flex justify-end gap-3">
                <button onclick="closeTokenModal()"
                        class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    Cancelar
                </button>
                <button id="confirm-btn" onclick="confirmGenerateToken()"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                    Sí, generar token
                </button>
            </div>

            {{-- Botón cerrar tras generar --}}
            <div id="close-actions" class="hidden justify-end">
                <button onclick="closeTokenModal()"
                        class="px-4 py-2 bg-gray-800 dark:bg-gray-600 hover:bg-gray-900 text-white text-sm font-medium rounded-lg transition">
                    Cerrar
                </button>
            </div>

        </div>
    </div>

    <script>
        function openTokenModal() {
            const modal = document.getElementById('token-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Resetear estado
            document.getElementById('new-token-section').classList.add('hidden');
            document.getElementById('current-token-section').classList.add('hidden');
            document.getElementById('no-token-section').classList.add('hidden');
            document.getElementById('modal-actions').classList.remove('hidden');
            document.getElementById('close-actions').classList.add('hidden');
            document.getElementById('confirm-btn').disabled = false;
            document.getElementById('confirm-btn').textContent = 'Sí, generar token';

            // Obtener token actual
            fetch('{{ route("admin.surveys.token.current") }}')
                .then(r => r.json())
                .then(data => {
                    if (data.token) {
                        document.getElementById('current-token-value').textContent = data.token;
                        document.getElementById('current-token-section').classList.remove('hidden');
                    } else {
                        document.getElementById('no-token-section').classList.remove('hidden');
                    }
                });
        }

        function closeTokenModal() {
            const modal = document.getElementById('token-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function confirmGenerateToken() {
            const btn = document.getElementById('confirm-btn');
            btn.disabled = true;
            btn.textContent = 'Generando...';

            fetch('{{ route("admin.surveys.token") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('new-token-value').textContent = data.token;
                document.getElementById('new-token-section').classList.remove('hidden');
                document.getElementById('current-token-section').classList.add('hidden');
                document.getElementById('no-token-section').classList.add('hidden');
                document.getElementById('modal-actions').classList.add('hidden');
                document.getElementById('close-actions').classList.remove('hidden');
                document.getElementById('close-actions').classList.add('flex');
            })
            .catch(() => {
                btn.disabled = false;
                btn.textContent = 'Sí, generar token';
                alert('Error al generar el token. Inténtalo de nuevo.');
            });
        }

        function copyToken() {
            const token = document.getElementById('new-token-value').textContent;
            navigator.clipboard.writeText(token).then(() => {
                alert('Token copiado al portapapeles');
            });
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('token-modal').addEventListener('click', function(e) {
            if (e.target === this) closeTokenModal();
        });
    </script>

</x-app-layout>