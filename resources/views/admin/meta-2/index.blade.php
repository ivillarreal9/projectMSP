<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">META 2 — Telefonía</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- Alertas --}}
            @if(session('success'))
                <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-xl text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-xl text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header --}}
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">META 2 — Telefonía</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tickets de Telefonía desde MSP Manager.</p>
                </div>
                <div class="flex items-center gap-3">
                    @if(isset($meta2) && $meta2->total() > 0)
                        <span class="text-sm text-gray-400 dark:text-gray-500">
                            {{ $meta2->total() }} registros
                        </span>

                        {{-- Exportar PDF --}}
                        <a href="{{ route('admin.meta-2.export-pdf', ['month' => request('month'), 'year' => request('year')]) }}"
                           class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Exportar PDF
                        </a>

                        {{-- Vista previa PDF --}}
                        <a href="{{ route('admin.meta-2.pdf-preview') }}"
                           target="_blank"
                           class="inline-flex items-center gap-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium px-4 py-2 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Vista previa
                        </a>
                    @endif
                </div>
            </div>

            {{-- Card principal --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">

                {{-- Filtros --}}
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <form method="GET" action="{{ route('admin.meta-2.index') }}"
                          class="flex flex-wrap items-end gap-3">

                        <div class="relative flex-1 min-w-48">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Ticket o tipo..."
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <select name="month"
                                class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Mes</option>
                            @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $mes)
                                <option value="{{ $i + 1 }}" {{ request('month') == $i + 1 ? 'selected' : '' }}>{{ $mes }}</option>
                            @endforeach
                        </select>

                        <select name="year"
                                class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Año</option>
                            @for($y = 2023; $y <= 2030; $y++)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>

                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Filtrar
                        </button>

                        @if(request('search') || request('month') || request('year'))
                            <a href="{{ route('admin.meta-2.index') }}"
                               class="px-4 py-2 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm rounded-lg transition">
                                Limpiar
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Tabla --}}
                @if(!request('month') || !request('year'))
                    <div class="flex flex-col items-center gap-3 py-16">
                        <div class="w-14 h-14 bg-indigo-50 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-7 h-7 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Selecciona un mes y año para consultar</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Primero obtiene los IDs del período y luego trae el detalle con campos personalizados.</p>
                    </div>
                @elseif($meta2->isEmpty())
                    <div class="flex flex-col items-center gap-3 py-16">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500 font-medium">No se encontraron tickets de Telefonía en ese período</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Ticket #</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Creado</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Completado</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Detalle</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                @foreach($meta2 as $item)
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">

                                    <td class="px-6 py-4">
                                        <span class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                            {{ $item['ticket_number'] }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                            {{ $item['issue_type'] }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $item['created_date'] }}
                                    </td>

                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $item['completed_date'] ?: '—' }}
                                    </td>

                                    {{-- Botón abrir modal --}}
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            onclick='openTicketModal(@json($item))'
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition border border-indigo-200 dark:border-indigo-700">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Ver detalle
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    @if($meta2->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Mostrando {{ $meta2->firstItem() }}–{{ $meta2->lastItem() }} de {{ $meta2->total() }} registros
                        </p>
                        {{ $meta2->links() }}
                    </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Modal detalle ticket --}}
    <div id="ticket-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">

            {{-- Header modal --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 id="modal-ticket-number" class="text-base font-semibold text-gray-800 dark:text-gray-100"></h3>
                        <p id="modal-ticket-type" class="text-xs text-gray-500 dark:text-gray-400"></p>
                    </div>
                </div>
                <button onclick="closeTicketModal()"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Info básica --}}
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 shrink-0">
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-gray-400 dark:text-gray-500">Creado</span>
                        <p id="modal-created" class="font-medium text-gray-700 dark:text-gray-300 mt-0.5"></p>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-gray-500">Completado</span>
                        <p id="modal-completed" class="font-medium text-gray-700 dark:text-gray-300 mt-0.5"></p>
                    </div>
                </div>
            </div>

            {{-- Custom fields — scrollable --}}
            <div class="overflow-y-auto flex-1 px-6 py-4">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">
                    Campos personalizados
                </p>
                <div id="modal-custom-fields" class="space-y-0 divide-y divide-gray-100 dark:divide-gray-700">
                    {{-- Se llena con JS --}}
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center shrink-0">
                <a id="modal-msp-link" href="#" target="_blank"
                   class="inline-flex items-center gap-1.5 text-xs text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Abrir en MSP Manager
                </a>
                <button onclick="closeTicketModal()"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        function openTicketModal(item) {
            // Info básica
            document.getElementById('modal-ticket-number').textContent = 'Ticket ' + item.ticket_number;
            document.getElementById('modal-ticket-type').textContent   = item.issue_type;
            document.getElementById('modal-created').textContent       = item.created_date || '—';
            document.getElementById('modal-completed').textContent     = item.completed_date || '—';
            document.getElementById('modal-msp-link').href             = 'https://app.mspmanager.com/tickets/' + item.ticket_id;

            // Custom fields
            const container = document.getElementById('modal-custom-fields');
            container.innerHTML = '';

            const fields = item.custom_fields || {};
            const skip   = ['ticketId']; // campos a omitir

            let hasFields = false;

            Object.entries(fields).forEach(([key, value]) => {
                if (skip.includes(key)) return;

                hasFields = true;

                const row = document.createElement('div');
                row.className = 'flex items-start justify-between py-2.5 gap-4';

                const label = document.createElement('span');
                label.className = 'text-xs text-gray-500 dark:text-gray-400 shrink-0 w-44';
                label.textContent = key;

                const val = document.createElement('span');
                val.className = 'text-xs font-medium text-gray-800 dark:text-gray-200 text-right';
                val.textContent = value !== '' && value !== null && value !== undefined
                    ? value
                    : '—';

                row.appendChild(label);
                row.appendChild(val);
                container.appendChild(row);
            });

            if (!hasFields) {
                container.innerHTML = '<p class="text-xs text-gray-400 dark:text-gray-500 py-4 text-center">No hay campos personalizados disponibles</p>';
            }

            // Mostrar modal
            const modal = document.getElementById('ticket-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeTicketModal() {
            const modal = document.getElementById('ticket-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Cerrar al hacer clic fuera
        document.getElementById('ticket-modal').addEventListener('click', function(e) {
            if (e.target === this) closeTicketModal();
        });

        // Cerrar con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeTicketModal();
        });
    </script>

</x-app-layout>