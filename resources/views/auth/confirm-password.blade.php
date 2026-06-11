<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Confirmar contraseña</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: #030712;" class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl mb-4">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">Zona segura</h1>
            <p class="text-sm text-gray-400 mt-1">Confirma tu contraseña para continuar</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">Contraseña</label>
                    <input id="password" type="password" name="password" placeholder="••••••••" required autofocus autocomplete="current-password"
                           class="w-full px-4 py-2.5 text-sm bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition @error('password') border-red-500 @enderror"/>
                    @error('password')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
                    Confirmar
                </button>
            </form>
        </div>
    </div>
</body>
</html>
