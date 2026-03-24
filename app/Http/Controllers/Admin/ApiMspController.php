<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MspCredential;
use App\Services\MspService;
use App\Exports\ApiMspExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ApiMspController extends Controller
{
    public function index(Request $request)
    {
        $tickets     = [];
        $error       = null;
        $credential  = MspCredential::latest()->first();
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d'));
        $fechaFin    = $request->get('fecha_fin', now()->format('Y-m-d'));

        if ($credential && $request->filled('fecha_inicio')) {
            try {
                $service = new MspService();
                $tickets = $service->getTickets($fechaInicio, $fechaFin);
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.api-msp.index', compact(
            'tickets', 'error', 'credential', 'fechaInicio', 'fechaFin'
        ));
    }

    public function saveCredential(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'base_url' => 'nullable|url',
        ]);

        // Solo una credencial activa
        MspCredential::truncate();

        MspCredential::create([
            'username' => $request->username,
            'password' => $request->password,
            'base_url' => $request->base_url ?? 'https://api.mspmanager.com/odata',
        ]);

        return back()->with('success', 'Credenciales guardadas correctamente.');
    }

    public function export(Request $request)
    {
        try {
            $service = new MspService();
            $tickets = $service->getTickets(
                $request->get('fecha_inicio', now()->startOfMonth()->format('Y-m-d')),
                $request->get('fecha_fin', now()->format('Y-m-d'))
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return Excel::download(
            new ApiMspExport($tickets),
            'tickets-msp-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}