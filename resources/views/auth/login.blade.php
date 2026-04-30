<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Iniciar sesión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: #030712;" class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl mb-4">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">Reportes MSP</h1>
            <p class="text-sm text-gray-400 mt-1">Ingresa tus credenciales para continuar</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            @if (session('status'))
                <div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-sm text-green-400">
                    {{ session('status') }}
                </div>
            @endif
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           placeholder="usuario@ovni.com" required autofocus autocomplete="username"
                           class="w-full px-4 py-2.5 text-sm bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition @error('email') border-red-500 @enderror"/>
                    @error('email')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="text-sm font-medium text-gray-300">Contraseña</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition">¿Olvidaste tu contraseña?</a>
                        @endif
                    </div>
                    <input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password"
                           class="w-full px-4 py-2.5 text-sm bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition @error('password') border-red-500 @enderror"/>
                    @error('password')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-2">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-indigo-500"/>
                    <label for="remember_me" class="text-sm text-gray-400">Mantener sesión iniciada</label>
                </div>
                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
                    Iniciar sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>