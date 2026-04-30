<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Verificación 2FA — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: #030712;" class=" min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm">

        {{-- Icono --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-orange-500/10 rounded-2xl mb-4">
                <svg class="w-7 h-7 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">Verificación de dos factores</h1>
            <p class="text-sm text-gray-400 mt-1">Ingresa el código de tu app autenticadora</p>
        </div>

        {{-- Card --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">

            <form method="POST" action="{{ route('2fa.validate') }}">
                @csrf

                <div class="space-y-5">

                    {{-- Input código --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 text-center">
                            Código de 6 dígitos
                        </label>
                        <input type="text"
                               name="code"
                               maxlength="6"
                               inputmode="numeric"
                               autocomplete="one-time-code"
                               placeholder="000000"
                               autofocus
                               class="w-full px-4 py-4 text-center text-3xl font-mono tracking-[0.6em]
                                      bg-gray-800 border border-gray-700 rounded-xl text-white
                                      placeholder-gray-700 focus:outline-none focus:border-orange-500 transition
                                      @error('code') border-red-500 @enderror"/>
                        @error('code')
                            <p class="text-xs text-red-400 mt-2 text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl transition text-sm">
                        Verificar y entrar
                    </button>

                </div>
            </form>

            {{-- Volver al login --}}
            <div class="mt-5 text-center">
                <a href="{{ route('login') }}"
                   class="text-xs text-gray-500 hover:text-gray-300 transition">
                    ← Volver al inicio de sesión
                </a>
            </div>

        </div>

        <p class="text-center text-xs text-gray-600 mt-6">
            {{ config('app.name') }} · Seguridad de cuenta
        </p>
    </div>

</body>
</html>
