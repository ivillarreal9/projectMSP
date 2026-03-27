<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.sales.index') }}" class="hover:text-gray-600 transition">Ventas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Clientes</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @include('admin.sales.partials.nav')

            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Clientes Existentes</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ count($clients) }} clientes · actividad y estado de riesgo</p>
            </div>

            {{-- Leyenda --}}
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> Al día (0–30 días)</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Atención (31–60 días)</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> En riesgo (+60 días)</span>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Ejecutiva</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Última factura</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Días sin actividad</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                            @forelse($clients as $client)
                            @php
                                $lastInvoice = $client['date_last_invoice'];
                                $days = $lastInvoice
                                    ? now()->diffInDays(\Carbon\Carbon::parse($lastInvoice))
                                    : 999;

                                $riskColor = match(true) {
                                    $days <= 30  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                    $days <= 60  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                    default      => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                };
                                $riskDot = match(true) {
                                    $days <= 30  => 'bg-green-500',
                                    $days <= 60  => 'bg-amber-400',
                                    default      => 'bg-red-500',
                                };
                                $riskLabel = match(true) {
                                    $days <= 30  => 'Al día',
                                    $days <= 60  => 'Atención',
                                    default      => 'En riesgo',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                                <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ $client['name'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ is_array($client['user_id']) ? $client['user_id'][1] : '—' }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $lastInvoice ? \Carbon\Carbon::parse($lastInvoice)->format('d/m/Y') : 'Sin facturas' }}
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold {{ $days > 60 ? 'text-red-600' : ($days > 30 ? 'text-amber-600' : 'text-green-600') }}">
                                    {{ $days >= 999 ? '—' : $days . ' días' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $riskColor }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $riskDot }}"></span>
                                        {{ $riskLabel }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-sm text-gray-400">
                                    No hay clientes registrados
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>