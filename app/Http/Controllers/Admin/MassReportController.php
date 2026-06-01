<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MspReport;
use App\Models\MspUploadBatch;
use Illuminate\Http\Request;

/**
 * Controlador de reportes masivos MSP (vista de resumen general).
 *
 * Sirve como punto de entrada al módulo de reportes, mostrando un dashboard
 * con los períodos disponibles y los últimos lotes importados.
 *
 * Los métodos CRUD (create, store, show, edit, update, destroy) están definidos
 * como stubs vacíos para seguir la estructura de ResourceController de Laravel,
 * pero la funcionalidad real de cada acción está implementada en MspReportController.
 *
 * Vista:
 *   - admin.reports.index → Dashboard general de reportes con períodos y lotes
 *
 * @see \App\Http\Controllers\Admin\MspReportController  Lógica completa de reportes MSP
 */
class MassReportController extends Controller
{
    /**
     * Dashboard de reportes MSP: muestra períodos disponibles y los últimos 10 lotes.
     *
     * @return \Illuminate\View\View  Vista admin.reports.index con: periodos, batches
     */
    public function index()
    {
        $periodos = MspReport::uniquePeriodos();
        $batches  = MspUploadBatch::orderByDesc('created_at')->take(10)->get();
        
        return view('admin.reports.index', compact('periodos', 'batches'));
    }

    /**
     * Formulario de creación de reporte masivo (stub — no implementado).
     *
     * @return void
     */
    public function create() {}

    /**
     * Persiste un nuevo reporte masivo (stub — no implementado).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function store(Request $request) {}

    /**
     * Muestra un reporte masivo por ID (stub — no implementado).
     *
     * @param  string  $id
     * @return void
     */
    public function show(string $id) {}

    /**
     * Formulario de edición de reporte masivo (stub — no implementado).
     *
     * @param  string  $id
     * @return void
     */
    public function edit(string $id) {}

    /**
     * Actualiza un reporte masivo (stub — no implementado).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $id
     * @return void
     */
    public function update(Request $request, string $id) {}

    /**
     * Elimina un reporte masivo (stub — no implementado).
     *
     * @param  string  $id
     * @return void
     */
    public function destroy(string $id) {}
}