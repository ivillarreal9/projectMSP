<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyType;
use App\Exports\SurveyExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $type = SurveyType::where('slug', $slug)->firstOrFail();

        $query = Survey::where('survey_type_id', $type->id)->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('numero_whatsapp', 'like', '%' . $request->search . '%');
            });
        }

        $surveys = $query->paginate(20)->withQueryString();

        return view('admin.surveys.show', compact('type', 'surveys'));
    }

    public function export(string $slug)
    {
        $type = SurveyType::where('slug', $slug)->firstOrFail();

        return Excel::download(
            new SurveyExport($type),
            'encuestas-' . $type->slug . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}