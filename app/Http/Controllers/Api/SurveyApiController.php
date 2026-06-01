<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyType;
use Illuminate\Http\Request;

/**
 * Controlador para la recepción de encuestas vía webhook.
 *
 * Recibe respuestas de encuestas desde sistemas externos (ej: bots de WhatsApp,
 * formularios web, IVR) mediante un token secreto en la URL que identifica y
 * autentica el tipo de encuesta. No utiliza Sanctum — la autenticación se
 * realiza exclusivamente mediante el token de 64 caracteres hexadecimales
 * incluido en la ruta.
 *
 * Ruta base: /api/v1/surveys
 * Autenticación: token en URL (64 chars hexadecimales, verificado contra survey_types.token)
 */
class SurveyApiController extends Controller
{
    /**
     * Recibe y almacena la respuesta de una encuesta enviada por webhook.
     *
     * El token de la URL determina el tipo de encuesta (SurveyType) y sus campos
     * permitidos. Solo se persisten los campos declarados en SurveyType::campos —
     * cualquier campo extra en el payload se descarta sin error. Los valores de
     * texto se sanean con strip_tags y trim para prevenir XSS.
     *
     * La respuesta genérica "Token inválido" se usa tanto para tokens con formato
     * incorrecto como para tokens existentes pero inactivos, evitando así revelar
     * información sobre el estado interno de los tipos de encuesta.
     *
     * POST /api/v1/surveys/{token}
     * Autenticación: token en URL (sin Bearer, sin sesión)
     *
     * @param  Request $request  Body con los campos definidos en SurveyType::campos.
     *                           Campos especiales procesados siempre: numero_whatsapp (string),
     *                           nombre (string, default: 'Sin nombre')
     * @param  string  $token    Token hexadecimal de 64 caracteres que identifica el SurveyType
     * @return \Illuminate\Http\JsonResponse
     *         201 con { message: 'Encuesta guardada.' }
     *         | 401 si el token tiene formato inválido o no corresponde a un SurveyType activo
     */
    public function receive(Request $request, string $token)
    {
        // El token de la URL es la autenticación del webhook — debe tener exactamente 64 chars hex
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        $type = SurveyType::where('token', $token)
                          ->where('activo', true)
                          ->first();

        if (!$type) {
            // Respuesta genérica para no revelar si el token existe pero está inactivo
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        // Construir data con solo los campos declarados en el tipo — ignorar campos extra
        $data = [];
        foreach ($type->campos as $campo) {
            $valor = $request->input($campo);
            $data[$campo] = is_string($valor) ? strip_tags(trim($valor)) : $valor;
        }

        Survey::create([
            'survey_type_id'  => $type->id,
            'fecha'           => now()->toDateString(),
            'numero_whatsapp' => strip_tags(trim((string) $request->input('numero_whatsapp', ''))),
            'nombre'          => strip_tags(trim((string) $request->input('nombre', 'Sin nombre'))),
            'data'            => $data,
        ]);

        return response()->json(['message' => 'Encuesta guardada.'], 201);
    }
}