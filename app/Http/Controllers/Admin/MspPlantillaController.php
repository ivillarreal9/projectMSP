<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MspPlantilla;
use Illuminate\Http\Request;

class MspPlantillaController extends Controller
{
    public function index()
    {
        $plantillas = MspPlantilla::orderByDesc('es_predeterminada')
            ->orderBy('nombre')
            ->get();
        return response()->json($plantillas);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'  => 'required|string|max:100',
            'asunto'  => 'nullable|string|max:200',
            'mensaje' => 'required|string',
            'imagen'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = [
            'nombre'  => $request->input('nombre'),
            'asunto'  => $request->input('asunto'),
            'mensaje' => $request->input('mensaje'),
            'es_predeterminada' => false,
        ];

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')
                ->store('plantillas', 'public');
        }

        $plantilla = MspPlantilla::create($data);

        return response()->json(['success' => true, 'plantilla' => $plantilla]);
    }

    public function destroy(MspPlantilla $plantilla)
    {
        if ($plantilla->es_predeterminada) {
            return response()->json(['error' => 'No puedes eliminar una plantilla predeterminada.'], 403);
        }
        $plantilla->delete();
        return response()->json(['success' => true]);
    }
}