<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Exports\SurveyExport;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        $query = Survey::query();

        if ($request->filled('search')) {
            $query->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('numero_whatsapp', 'like', '%' . $request->search . '%');
        }

        $surveys = $query->latest()->paginate(15)->withQueryString();

        return view('admin.surveys.index', compact('surveys'));
    }

    public function export()
    {
        return Excel::download(
            new SurveyExport,
            'encuestas-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // API pública para recibir encuestas desde WhatsApp
    public function store(Request $request)
    {
        $request->validate([
            'fecha'           => 'required|string',
            'numero_whatsapp' => 'required|string',
            'nombre'          => 'nullable|string',
            'satisfaccion'    => 'nullable|string',
            'recomendacion'   => 'nullable|string',
        ]);

        Survey::create([
            'fecha'           => $request->fecha,
            'numero_whatsapp' => $request->numero_whatsapp,
            'nombre'          => $request->nombre ?? 'Sin nombre',
            'satisfaccion'    => $request->satisfaccion,
            'recomendacion'   => $request->recomendacion,
        ]);

        return response()->json([
            'message' => 'Survey saved successfully.'
        ], 201);
    }
    
    public function currentToken()
    {
        $token = auth()->user()->tokens()->latest()->first();

        return response()->json([
            'token' => $token ? '...' . substr($token->name, -6) . ' (creado ' . $token->created_at->diffForHumans() . ')' : null
        ]);
    }

    public function generateToken()
    {
        $user = auth()->user();
        $user->tokens()->delete();
        $token = $user->createToken('api-surveys')->plainTextToken;

        return response()->json(['token' => $token]);
    }
}