<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback()
    {
        try {
            $msUser = Socialite::driver('azure')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'No se pudo completar el inicio de sesión con Microsoft. Inténtalo de nuevo.']);
        }

        $email = $msUser->getEmail();

        if (!$email) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Microsoft no devolvió un correo electrónico válido.']);
        }

        // Solo usuarios ya registrados en el sistema
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta Microsoft (' . $email . ') no está registrada en el sistema. Contacta al administrador.']);
        }

        // Login completo — SSO omite el 2FA
        Auth::login($user);
        session()->regenerate();
        session([
            '2fa_verified' => true,
            'sso_login'    => true,
        ]);

        return redirect()->intended(route('dashboard'));
    }
}
