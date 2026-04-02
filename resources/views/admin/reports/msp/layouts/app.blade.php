{{-- resources/views/msp/layouts/app.blade.php
     Usa x-app-layout del proyecto existente (Breeze) en lugar de sidebar propio --}}

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                {{-- Breadcrumb --}}
                <a href="{{ route('dashboard') }}"
                   class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    Dashboard
                </a>
                <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-sm text-gray-400 dark:text-gray-500">MSP Reports</span>
                @isset($breadcrumb)
                    <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $breadcrumb }}</span>
                @endisset
            </div>

            {{-- Nav rápida del módulo --}}
            <nav class="flex items-center gap-1">
                <a href="{{ route('admin.msp.index') }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all
                          {{ request()->routeIs('admin.msp.index') ? 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Importar Excel
                </a>
                <a href="{{ route('admin.msp.clientes') }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all
                          {{ request()->routeIs('admin.msp.clientes*') ? 'bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Clientes
                </a>
                <a href="{{ route('admin.msp.correos') }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all
                          {{ request()->routeIs('admin.msp.correos*') ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Correos
                </a>
                <a href="{{ route('admin.msp.chat') }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all
                          {{ request()->routeIs('admin.msp.chat*') ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Chat IA
                </a>

            </nav>
        </div>
    </x-slot>

    {{-- Flash messages --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-4">
        <div class="flex items-center gap-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800
                    text-green-800 dark:text-green-300 px-4 py-3 rounded-xl text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-4">
        <div class="flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                    text-red-800 dark:text-red-300 px-4 py-3 rounded-xl text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    </div>
    @endif

    {{-- Contenido de la vista --}}
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @yield('content')
        </div>
    </div>
    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @stack('scripts') 
</x-app-layout>
