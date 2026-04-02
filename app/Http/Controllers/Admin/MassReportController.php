<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MspReport;
use App\Models\MspUploadBatch;
use Illuminate\Http\Request;

class MassReportController extends Controller
{
    public function index()
    {
        $periodos = MspReport::uniquePeriodos();
        $batches  = MspUploadBatch::orderByDesc('created_at')->take(10)->get();
        
        return view('admin.reports.index', compact('periodos', 'batches'));
    }

    public function create() {}

    public function store(Request $request) {}

    public function show(string $id) {}

    public function edit(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}