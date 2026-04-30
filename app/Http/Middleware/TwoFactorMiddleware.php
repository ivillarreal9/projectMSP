<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Rutas que siempre se permiten sin 2FA
        $allowedRoutes = [
            '2fa.setup',
            '2fa.setup.confirm',
            '2fa.verify',
            '2fa.validate',
            'logout',
            'profile.edit',
            'profile.update',
            'profile.destroy',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes)) {
            return $next($request);
        }

        // Si el usuario NO tiene 2FA configurado → forzar setup
        if (!$user->two_factor_secret || !$user->two_factor_confirmed) {
            return redirect()->route('2fa.setup');
        }

        return $next($request);
    }
}
