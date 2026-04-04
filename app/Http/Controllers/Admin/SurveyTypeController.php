<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyType;
use Illuminate\Http\Request;

class SurveyTypeController extends Controller
{
    public function index()
    {
        $types = SurveyType::withCount('surveys')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.surveys.index', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'campos' => 'required|array|min:1',
            'campos.*' => 'required|string|max:100',
        ]);

        $type = SurveyType::create([
            'nombre' => $request->nombre,
            'campos' => $request->campos,
        ]);

        return response()->json([
            'id'      => $type->id,
            'nombre'  => $type->nombre,
            'slug'    => $type->slug,
            'snippet' => $type->snippet(),
        ]);
    }

    public function destroy(SurveyType $surveyType)
    {
        $surveyType->delete();
        return back()->with('success', 'Encuesta eliminada.');
    }
}