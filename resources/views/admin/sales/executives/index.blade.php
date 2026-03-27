<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.sales.index') }}" class="hover:text-gray-600 transition">Ventas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Ejecutivas</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @include('admin.sales.partials.nav')

            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Ejecutivas de Ventas</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ count($executives) }} ejecutivas activas</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($executives as $exec)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center text-sm font-bold text-purple-600 dark:text-purple-300">
                            {{ strtoupper(substr($exec['name'], 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $exec['name'] }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $exec['email'] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Equipo</p>
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5 truncate">
                                {{ is_array($exec['sale_team_id']) ? $exec['sale_team_id'][1] : '—' }}
                            </p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-2">
                            <p class="text-xs text-gray-400 dark:text-gray-500">ID</p>
                            <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mt-0.5">
                                #{{ $exec['id'] }}
                            </p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-3 text-center py-16 text-gray-400 text-sm">
                    No hay ejecutivas registradas
                </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>