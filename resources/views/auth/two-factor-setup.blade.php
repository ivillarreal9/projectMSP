<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Configurar 2FA — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: #030712;" class=" min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        {{-- Logo / App name --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-orange-500/10 rounded-2xl mb-4">
                <svg class="w-7 h-7 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">Activar autenticación de dos factores</h1>
            <p class="text-sm text-gray-400 mt-1">Escanea el código QR con tu app autenticadora</p>
        </div>

        {{-- Card --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 space-y-6">

            {{-- Paso 1 --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-6 h-6 rounded-full bg-orange-500 text-white text-xs font-bold flex items-center justify-center">1</span>
                    <p class="text-sm font-semibold text-gray-200">Descarga una app autenticadora</p>
                </div>
                <p class="text-xs text-gray-400 ml-8">
                    Google Authenticator, Microsoft Authenticator o Authy.
                </p>
            </div>

            <hr class="border-gray-800"/>

            {{-- Paso 2 — QR --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-6 h-6 rounded-full bg-orange-500 text-white text-xs font-bold flex items-center justify-center">2</span>
                    <p class="text-sm font-semibold text-gray-200">Escanea el código QR</p>
                </div>

                <div class="flex justify-center my-4">
                    <div class="bg-white p-3 rounded-xl">
                        {!! $qrSvg !!}
                    </div>
                </div>

                {{-- Código manual --}}
                <details class="mt-3">
                    <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-300 transition text-center">
                        ¿No puedes escanear? Ingresa el código manualmente
                    </summary>
                    <div class="mt-2 p-3 bg-gray-800 rounded-lg text-center">
                        <p class="text-xs text-gray-400 mb-1">Clave secreta:</p>
                        <code class="text-sm font-mono text-orange-400 tracking-widest break-all">{{ $secret }}</code>
                    </div>
                </details>
            </div>

            <hr class="border-gray-800"/>

            {{-- Paso 3 — Verificar --}}
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-6 h-6 rounded-full bg-orange-500 text-white text-xs font-bold flex items-center justify-center">3</span>
                    <p class="text-sm font-semibold text-gray-200">Ingresa el código de verificación</p>
                </div>

                <form method="POST" action="{{ route('2fa.setup.confirm') }}">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <input type="text"
                                   name="code"
                                   maxlength="6"
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   placeholder="000000"
                                   autofocus
                                   class="w-full px-4 py-3 text-center text-2xl font-mono tracking-[0.5em]
                                          bg-gray-800 border border-gray-700 rounded-xl text-white
                                          placeholder-gray-600 focus:outline-none focus:border-orange-500 transition
                                          @error('code') border-red-500 @enderror"/>
                            @error('code')
                                <p class="text-xs text-red-400 mt-1.5 text-center">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl transition text-sm">
                            Activar 2FA
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <p class="text-center text-xs text-gray-600 mt-6">
            {{ config('app.name') }} · Seguridad de cuenta
        </p>
    </div>

</body>
</html>
