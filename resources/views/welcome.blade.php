<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes MSP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center transition-colors duration-300">

    {{-- Toggle dark/light --}}
    <button id="theme-toggle"
            class="fixed top-5 right-5 p-2 rounded-lg text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
        <svg class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        <svg class="w-4 h-4 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
    </button>

    <div class="w-full max-w-sm px-6">

        {{-- Logo + título --}}
        <div class="text-center mb-10">
            <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">
                Reportes de Ventas y MSP
            </h1>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1.5">
                Acceso interno · Ovnicom
            </p>
        </div>

        {{-- Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-7 shadow-sm">

            @if(Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="flex items-center justify-center gap-2 w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition">
                        Ir al Dashboard
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="flex items-center justify-center gap-2 w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition mb-3">
                        Iniciar sesión
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"/>
                        </svg>
                    </a>
                @endauth
            @endif

            {{-- Módulos disponibles (solo visual, no clickeable) --}}
            <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-800 space-y-2.5">
                <p class="text-xs text-gray-400 dark:text-gray-600 uppercase tracking-widest mb-3">Módulos</p>

                @foreach([
                    ['color' => 'indigo', 'label' => 'Dashboard de Ventas'],
                    ['color' => 'purple', 'label' => 'API MSP'],
                    ['color' => 'emerald','label' => 'META 2 — Telefonía'],
                    ['color' => 'sky',    'label' => 'Encuestas de Satisfacción'],
                    ['color' => 'amber',  'label' => 'Gestión de Usuarios'],
                    ['color' => 'orange', 'label' => 'Reportes Masivos'],
                ] as $mod)
                <div class="flex items-center gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $mod['color'] }}-500"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $mod['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-gray-400 dark:text-gray-600 mt-6">
            © {{ date('Y') }} Ovnicom · Sistema interno
        </p>
    </div>

    <script>
        const html = document.documentElement;
        if (localStorage.theme === 'dark' ||
            (!localStorage.theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        }
        document.getElementById('theme-toggle').addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.theme = html.classList.contains('dark') ? 'dark' : 'light';
        });
    </script>

</body>
</html>