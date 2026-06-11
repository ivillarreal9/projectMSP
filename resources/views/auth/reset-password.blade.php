<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Restablecer contraseña</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: #030712;" class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl mb-4">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">Restablecer contraseña</h1>
            <p class="text-sm text-gray-400 mt-1">Define tu nueva contraseña</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}"/>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                           placeholder="usuario@ovni.com" required autofocus autocomplete="username"
                           class="w-full px-4 py-2.5 text-sm bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition @error('email') border-red-500 @enderror"/>
                    @error('email')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">Nueva contraseña</label>
                    <input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="new-password"
                           class="w-full px-4 py-2.5 text-sm bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition @error('password') border-red-500 @enderror"/>
                    @error('password')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1.5">Confirmar contraseña</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password"
                           class="w-full px-4 py-2.5 text-sm bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition @error('password_confirmation') border-red-500 @enderror"/>
                    @error('password_confirmation')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition text-sm">
                    Restablecer contraseña
                </button>
            </form>

            <p class="text-center mt-5">
                <a href="{{ route('login') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition">← Volver a iniciar sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
