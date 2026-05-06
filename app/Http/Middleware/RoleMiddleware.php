<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'No tienes permiso para acceder aquí.');
        }

        // ✅ Verifica el role string legacy O el slug del rol dinámico
        $userRole = $user->role;
        $dynamicSlug = $user->roleModel?->slug ?? null;

        if (!in_array($userRole, $roles) && !in_array($dynamicSlug, $roles)) {
            abort(403, 'No tienes permiso para acceder aquí.');
        }

        return $next($request);
    }
}