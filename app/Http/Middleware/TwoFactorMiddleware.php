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
        if (app()->environment('local')) {
            return $next($request);
        }

        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // SSO Microsoft omite el 2FA — Microsoft ya autenticó al usuario
        if (session('sso_login')) {
            return $next($request);
        }

        // Rutas que siempre se permiten sin 2FA (solo las estrictamente necesarias)
        $allowedRoutes = [
            '2fa.setup',
            '2fa.setup.confirm',
            '2fa.verify',
            '2fa.validate',
            'logout',
            'profile.edit',
            'profile.update',
            // profile.destroy removido: eliminar cuenta requiere 2FA confirmado
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes)) {
            return $next($request);
        }

        // Si el usuario NO tiene 2FA configurado → forzar setup
        if (!$user->two_factor_secret || !$user->two_factor_confirmed) {
            return redirect()->route('2fa.setup');
        }

        // Verificar que el 2FA fue validado en esta sesión
        if (!session('2fa_verified')) {
            // Log out the user before redirecting to avoid a redirect loop:
            // authenticated + no 2fa_verified + no 2fa_user_id causes:
            // /2fa/verify → /login (RedirectIfAuthenticated) → /dashboard → /2fa/verify → ...
            $userId = $user->id;
            Auth::logout();
            session()->put('2fa_user_id', $userId);
            session()->save();
            return redirect()->route('2fa.verify');
        }

        return $next($request);
    }
}