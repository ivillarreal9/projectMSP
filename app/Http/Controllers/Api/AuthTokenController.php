<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador de autenticación por token para la API pública.
 *
 * Gestiona la emisión y revocación de Bearer tokens de Laravel Sanctum
 * con vigencia de 30 días. Cada usuario solo puede tener un token activo
 * con nombre 'api' — al emitir uno nuevo se revocan los anteriores.
 *
 * Ruta base: /api/v1/auth
 * Autenticación: ninguna para emitir; Bearer token para revocar.
 * Throttle: 3 intentos cada 15 minutos (middleware 'throttle:3,15').
 */
class AuthTokenController extends Controller
{
    /**
     * Genera un Bearer token con vigencia de 30 días.
     *
     * Revoca automáticamente cualquier token anterior del mismo usuario
     * con nombre 'api' antes de crear uno nuevo — evita acumulación de tokens.
     *
     * POST /api/v1/auth/token
     * Autenticación: ninguna (pública con throttle 3/15min)
     *
     * @param  Request $request  Requiere: email (string), password (string)
     * @return JsonResponse      201 con token | 401 credenciales incorrectas | 422 validación
     */
    public function issue(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        // Revocar tokens anteriores con el mismo nombre (evita acumulación)
        $user->tokens()->where('name', 'api')->delete();

        $token = $user->createToken('api', ['*'], now()->addDays(30));

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at->toDateTimeString(),
        ], 201);
    }

    /**
     * Revoca el Bearer token activo del usuario autenticado.
     *
     * Elimina el token incluido en el encabezado Authorization de la petición.
     * Después de esta llamada el token queda inválido para futuras peticiones.
     *
     * DELETE /api/v1/auth/token
     * Autenticación: Bearer token (Sanctum)
     *
     * @param  Request $request  Sin body requerido; token leído del encabezado Authorization
     * @return JsonResponse      200 con mensaje de confirmación | 401 sin token válido
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token revocado correctamente.']);
    }
}
