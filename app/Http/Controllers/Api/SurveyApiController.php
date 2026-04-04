<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyType;
use Illuminate\Http\Request;

class SurveyApiController extends Controller
{
    public function receive(Request $request, string $token)
    {
        $type = SurveyType::where('token', $token)
                          ->where('activo', true)
                          ->firstOrFail();

        // Construir data con solo los campos del tipo
        $data = [];
        foreach ($type->campos as $campo) {
            $data[$campo] = $request->input($campo);
        }

        Survey::create([
            'survey_type_id'  => $type->id,
            'fecha'           => now()->toDateString(),
            'numero_whatsapp' => $request->input('numero_whatsapp'),
            'nombre'          => $request->input('nombre', 'Sin nombre'),
            'data'            => $data,
        ]);

        return response()->json(['message' => 'Encuesta guardada.'], 201);
    }
}