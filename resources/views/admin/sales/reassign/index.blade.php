<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.sales.index') }}" class="hover:text-gray-600 transition">Ventas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Reasignación</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @include('admin.sales.partials.nav')

            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Reasignación de Cuentas</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Clientes sin actividad · candidatos a reasignar</p>
                </div>
            </div>

            {{-- Filtro días --}}
            <form method="GET" action="{{ route('admin.sales.reassign') }}"
                  class="flex items-center gap-3">
                <label class="text-sm text-gray-600 dark:text-gray-300">Días sin actividad:</label>
                <select name="days" onchange="this.form.submit()"
                        class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-red-400">
                    <option value="30"  {{ $days == 30  ? 'selected' : '' }}>+30 días</option>
                    <option value="45"  {{ $days == 45  ? 'selected' : '' }}>+45 días</option>
                    <option value="60"  {{ $days == 60  ? 'selected' : '' }}>+60 días</option>
                    <option value="90"  {{ $days == 90  ? 'selected' : '' }}>+90 días</option>
                    <option value="180" {{ $days == 180 ? 'selected' : '' }}>+180 días</option>
                </select>
                <span class="text-sm text-gray-400">{{ count($clients) }} clientes encontrados</span>
            </form>

            {{-- Tabla con checkboxes --}}
            <form id="export-form" method="POST" action="{{ route('admin.sales.reassign.export') }}">
                @csrf

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">

                    {{-- Toolbar --}}
                    <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300">
                            Seleccionar todos
                        </label>
                        <button type="submit"
                                class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Exportar seleccionados
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                    <th class="px-6 py-3 w-10"></th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Ejecutiva actual</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Última factura</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Días sin actividad</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Riesgo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                @forelse($clients as $i => $client)
                                @php
                                    $lastInvoice = $client['date_last_invoice'];
                                    $daysOld     = $lastInvoice
                                        ? now()->diffInDays(\Carbon\Carbon::parse($lastInvoice))
                                        : 999;
                                    $executive   = is_array($client['user_id']) ? $client['user_id'][1] : '—';

                                    $riskColor = match(true) {
                                        $daysOld <= 30  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                        $daysOld <= 60  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                        default         => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                    };
                                    $riskLabel = match(true) {
                                        $daysOld <= 30  => 'Al día',
                                        $daysOld <= 60  => 'Atención',
                                        default         => 'En riesgo',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="clients[{{ $i }}][name]"
                                               value="{{ $client['name'] }}"
                                               class="client-checkbox rounded border-gray-300"
                                               data-executive="{{ $executive }}"
                                               data-days="{{ $daysOld >= 999 ? '—' : $daysOld }}"
                                               data-invoice="{{ $lastInvoice ?? '—' }}">
                                        <input type="hidden" name="clients[{{ $i }}][executive]" value="{{ $executive }}">
                                        <input type="hidden" name="clients[{{ $i }}][days]" value="{{ $daysOld >= 999 ? '—' : $daysOld }}">
                                        <input type="hidden" name="clients[{{ $i }}][last_invoice]" value="{{ $lastInvoice ?? '—' }}">
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ $client['name'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $executive }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $lastInvoice ? \Carbon\Carbon::parse($lastInvoice)->format('d/m/Y') : 'Sin facturas' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold {{ $daysOld > 60 ? 'text-red-600' : ($daysOld > 30 ? 'text-amber-600' : 'text-green-600') }}">
                                        {{ $daysOld >= 999 ? '—' : $daysOld . ' días' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $riskColor }}">
                                            {{ $riskLabel }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center text-sm text-gray-400">
                                        No hay clientes con más de {{ $days }} días sin actividad
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.client-checkbox').forEach(cb => cb.checked = this.checked);
        });
    </script>

</x-app-layout>