<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Aplicar tema ANTES de renderizar para evitar flash --}}
        <script>
            // Por defecto: modo claro. Solo dark si el usuario lo eligió explícitamente.
            if (localStorage.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            }
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <script>
            function toggleTheme() {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.theme = 'light';
                    const moon = document.getElementById('icon-moon');
                    const sun  = document.getElementById('icon-sun');
                    if (moon) moon.classList.remove('hidden');
                    if (sun)  sun.classList.add('hidden');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.theme = 'dark';
                    const moon = document.getElementById('icon-moon');
                    const sun  = document.getElementById('icon-sun');
                    if (moon) moon.classList.add('hidden');
                    if (sun)  sun.classList.remove('hidden');
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const moon = document.getElementById('icon-moon');
                const sun  = document.getElementById('icon-sun');
                if (!moon || !sun) return;
                if (document.documentElement.classList.contains('dark')) {
                    moon.classList.add('hidden');
                    sun.classList.remove('hidden');
                } else {
                    moon.classList.remove('hidden');
                    sun.classList.add('hidden');
                }
            });
        </script>

        @stack('scripts')
    </body>
</html>