<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Verificar correo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: #030712;" class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl mb-4">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">Verifica tu correo</h1>
            <p class="text-sm text-gray-400 mt-1">Te enviamos un enlace de verificación</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            @if (session('status') == 'verification-link-sent')
                <div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-sm text-green-400">
                    Se envió un nuevo enlace de verificación a tu correo.
                </div>
            @endif

            <p class="text-xs text-gray-500 leading-relaxed mb-6">
                Antes de continuar, verifica tu dirección de correo con el enlace que te enviamos.
                Si no lo recibiste, podemos enviarte otro.
            </p>

            <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
                @csrf
                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
                    Reenviar correo de verificación
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full py-2.5 bg-gray-800 hover:bg-gray-700 border border-gray-700 text-gray-300 font-medium rounded-xl transition text-sm">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>
