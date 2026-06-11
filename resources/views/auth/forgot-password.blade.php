<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Recuperar contraseña</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: #030712;" class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl mb-4">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">Recuperar contraseña</h1>
            <p class="text-sm text-gray-400 mt-1">Te enviaremos un enlace para restablecerla</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            @if (session('status'))
                <div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-sm text-green-400">
                    {{ session('status') }}
                </div>
            @endif
            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           placeholder="usuario@ovni.com" required autofocus autocomplete="username"
                           class="w-full px-4 py-2.5 text-sm bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition @error('email') border-red-500 @enderror"/>
                    @error('email')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
                    Enviar enlace de recuperación
                </button>
            </form>

            <p class="text-center mt-5">
                <a href="{{ route('login') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition">← Volver a iniciar sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
