<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ─────────────────────────────────────────────
    // SETUP — primera vez que el usuario configura 2FA
    // ─────────────────────────────────────────────

    /**
     * Muestra la pantalla de configuración del 2FA (QR + código manual).
     */
    public function setup(Request $request)
    {
        $user = Auth::user();

        // Si ya tiene 2FA confirmado, redirigir al dashboard
        if ($user->two_factor_confirmed) {
            return redirect()->route('dashboard');
        }

        // Generar secret si no tiene uno aún
        if (!$user->two_factor_secret) {
            $secret = $this->google2fa->generateSecretKey();
            $user->two_factor_secret = Crypt::encryptString($secret);
            $user->save();
        } else {
            $secret = Crypt::decryptString($user->two_factor_secret);
        }

        // Generar QR en SVG
        $qrUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrSvg  = $writer->writeString($qrUrl);

        return view('auth.two-factor-setup', compact('secret', 'qrSvg'));
    }

    /**
     * Confirma que el usuario escaneó el QR correctamente.
     */
    public function confirmSetup(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6',
        ]);

        $user   = Auth::user();
        $secret = Crypt::decryptString($user->two_factor_secret);

        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Código incorrecto. Verifica tu app autenticadora.']);
        }

        $user->two_factor_confirmed = true;
        $user->save();

        session()->forget('2fa_setup_pending');

        return redirect()->route('dashboard')
            ->with('success', '¡2FA activado correctamente! Tu cuenta está protegida.');
    }

    // ─────────────────────────────────────────────
    // VERIFY — cada vez que el usuario hace login
    // ─────────────────────────────────────────────

    /**
     * Muestra el formulario para ingresar el código 2FA tras el login.
     */
    public function verify(Request $request)
    {
        // Si no hay sesión 2FA pendiente, redirigir
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-verify');
    }

    /**
     * Valida el código 2FA y completa el login.
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6',
        ]);

        $userId = session('2fa_user_id');

        if (!$userId) {
            return redirect()->route('login')
                ->withErrors(['code' => 'Sesión expirada. Inicia sesión de nuevo.']);
        }

        $user   = \App\Models\User::findOrFail($userId);
        $secret = Crypt::decryptString($user->two_factor_secret);

        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Código incorrecto. Inténtalo de nuevo.']);
        }

        // Login completo
        Auth::login($user);
        session()->forget('2fa_user_id');
        session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
