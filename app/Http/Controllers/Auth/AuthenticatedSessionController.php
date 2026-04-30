<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // CASO 1: Sin 2FA configurado → forzar setup
        if (!$user->two_factor_secret || !$user->two_factor_confirmed) {
            $request->session()->regenerate();
            return redirect()->route('2fa.setup');
        }

        // CASO 2: Con 2FA → pedir código en cada login
        $userId = $user->id;
        Auth::logout();

        $request->session()->put('2fa_user_id', $userId);
        $request->session()->save();

        return redirect()->route('2fa.verify');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
