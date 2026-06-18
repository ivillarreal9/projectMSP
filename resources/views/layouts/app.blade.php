<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Aplicar tema antes de render para evitar flash --}}
    <script>
        if (localStorage.theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
            if (!localStorage.theme) localStorage.theme = 'light';
        }
    </script>

    <style>
        /* ── Barra de progreso de navegación ── */
        #nav-loader {
            position: fixed; top: 0; left: 0; z-index: 9999;
            height: 3px; width: 0%;
            background: linear-gradient(90deg, #f97316, #fb923c, #fdba74);
            transition: width 0.12s ease, opacity 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }
        #nav-loader.loading {
            opacity: 1;
            animation: navprogress 1.4s ease-in-out infinite;
        }
        #nav-loader.done { width: 100% !important; opacity: 0; transition: width 0.15s ease, opacity 0.4s ease 0.1s; }
        @keyframes navprogress {
            0%   { width: 0%;  }
            30%  { width: 55%; }
            60%  { width: 75%; }
            85%  { width: 88%; }
            100% { width: 93%; }
        }

        /* ── Skeleton veil global ── */
        #page-veil {
            position: absolute;
            inset: 0;
            z-index: 20;
            pointer-events: none;
            transition: opacity 0.22s ease;
        }
        html.dark #page-veil { background: #111827; }
        html:not(.dark) #page-veil { background: #f9fafb; }

        /* Shimmer animation para skeletons */
        @keyframes shimmer {
            0%   { background-position: -600px 0; }
            100% { background-position:  600px 0; }
        }
        .sk {
            background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
            background-size: 600px 100%;
            animation: shimmer 1.4s infinite linear;
            border-radius: 6px;
        }
        html.dark .sk {
            background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
            background-size: 600px 100%;
        }
    </style>
</head>
<body class="font-sans antialiased min-h-screen bg-gray-50 dark:bg-gray-900" style="color:var(--text-primary)">

    {{-- Barra de progreso de navegación (todas las páginas) --}}
    <div id="nav-loader"></div>

    {{-- Navigation --}}
    @include('layouts.navigation')

    {{-- Contenedor relativo para el veil skeleton --}}
    <div style="position: relative;">

        {{-- ── Skeleton veil (visible hasta que DOMContentLoaded lo elimina) ── --}}
        <div id="page-veil" aria-hidden="true">
            {{-- Skeleton del sub-header --}}
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-3">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <div>
                        <div class="sk h-5 w-40 mb-2"></div>
                        <div class="sk h-3 w-28"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="sk h-7 w-24 rounded-lg"></div>
                        <div class="sk h-7 w-20 rounded-lg"></div>
                    </div>
                </div>
            </div>
            {{-- Skeleton del contenido principal --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-7">
                {{-- Barra oscura tipo stats --}}
                <div class="sk h-14 rounded-xl mb-6" style="background: #1f2937 !important; animation: none; opacity: 0.7;"></div>
                {{-- Grid de tarjetas skeleton --}}
                <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:1rem;">
                    @for($__i = 0; $__i < 8; $__i++)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="sk h-24" style="border-radius:0;"></div>
                        <div class="p-4">
                            <div class="sk h-4 w-28 mb-2"></div>
                            <div class="sk h-3 w-20"></div>
                        </div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>

        {{-- Sub-header real --}}
        @isset($header)
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                {{ $header }}
            </div>
        </div>
        @endisset

        {{-- Page Content --}}
        <main>
            {{ $slot }}
        </main>

    </div>{{-- /.relative --}}

    <script>
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.theme = isDark ? 'dark' : 'light';
            updateThemeIcons(isDark);
        }

        function updateThemeIcons(isDark) {
            document.querySelectorAll('.icon-moon').forEach(el => el.classList.toggle('hidden', isDark));
            document.querySelectorAll('.icon-sun').forEach(el => el.classList.toggle('hidden', !isDark));
        }

        // ── Ocultar skeleton veil al cargar ──────────────────────────────────
        (function () {
            function hideVeil() {
                const v = document.getElementById('page-veil');
                if (!v) return;
                v.style.opacity = '0';
                setTimeout(function () { if (v && v.parentNode) v.parentNode.removeChild(v); }, 250);
            }
            document.addEventListener('DOMContentLoaded', function () {
                updateThemeIcons(document.documentElement.classList.contains('dark'));
                hideVeil();
            });
            window.addEventListener('pageshow', hideVeil);
        })();

        // ── Barra de progreso de navegación ──────────────────────────────────
        (function () {
            const bar = document.getElementById('nav-loader');
            let timer = null;

            function start() {
                clearTimeout(timer);
                bar.classList.remove('done');
                bar.classList.add('loading');
            }

            function finish() {
                bar.classList.remove('loading');
                bar.classList.add('done');
                timer = setTimeout(() => bar.classList.remove('done'), 500);
            }

            document.addEventListener('click', function (e) {
                const a = e.target.closest('a');
                if (!a) return;
                const href = a.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
                if (a.target === '_blank') return;
                try {
                    const url = new URL(href, location.href);
                    if (url.origin !== location.origin) return;
                } catch (_) { return; }
                start();
            });

            document.addEventListener('submit', function (e) {
                if (e.target.tagName === 'FORM' && !e.target.dataset.noLoader) start();
            });

            window.addEventListener('pageshow', finish);
            document.addEventListener('DOMContentLoaded', finish);
        })();
    </script>

    @stack('scripts')
</body>
</html>
