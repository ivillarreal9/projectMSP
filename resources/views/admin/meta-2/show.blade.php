<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Detalle del Ticket - Telefonía</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Ticket #{{ $meta2['ticket_number'] }}</p>
            </div>
            <a href="{{ route('admin.meta-2.index') }}"
               class="text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium">
                ← Volver
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

                {{-- Header con título --}}
                <div class="px-6 sm:px-8 py-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $meta2['ticket_title'] }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                <span class="font-semibold">Ticket #{{ $meta2['ticket_number'] }}</span>
                                • 
                                <span>{{ $meta2['issue_type'] }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Contenido --}}
                <div class="p-6 sm:p-8">
                    <div class="space-y-6">
                        
                        {{-- Cliente y Ubicación --}}
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Cliente
                                </label>
                                <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $meta2['customer_name'] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Ubicación
                                </label>
                                <p class="text-sm text-gray-900 dark:text-gray-100">{{ $meta2['location_name'] ?? '—' }}</p>
                            </div>
                        </div>

                        {{-- Tipo de Problema --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Tipo de Problema
                            </label>
                            <div class="inline-flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800">
                                    {{ $meta2['issue_type'] }}
                                </span>
                            </div>
                        </div>

                        {{-- Fechas --}}
                        <div class="grid grid-cols-2 gap-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Fecha de Creación
                                </label>
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $meta2['created_date'] }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Fecha de Completación
                                </label>
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $meta2['completed_date'] ?? 'Pendiente' }}
                                </p>
                            </div>
                        </div>

                    </div>

                    {{-- Botones de acción --}}
                    <div class="flex gap-3 mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <a href="https://app.mspmanager.com/tickets/{{ $meta2['ticket_id'] }}" target="_blank"
                           class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Abrir en MSP Manager
                        </a>
                        
                        <a href="{{ route('admin.meta-2.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-semibold rounded-lg transition">
                            Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
