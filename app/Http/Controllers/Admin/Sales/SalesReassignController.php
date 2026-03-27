<?php
namespace App\Http\Controllers\Admin\Sales;
use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;
use Illuminate\Http\Request;

class SalesReassignController extends Controller
{
    public function index(Request $request, OdooService $odoo)
    {
        $days    = $request->get('days', 60);
        $clients = $odoo->getClientsForReassign($days);
        return view('admin.sales.reassign.index', compact('clients', 'days'));
    }

    public function export(Request $request)
    {
        $ids     = $request->input('ids', []);
        $clients = $request->input('clients', []);

        $csv  = "Nombre,Ejecutiva,Días sin actividad,Última factura\n";
        foreach ($clients as $c) {
            $csv .= "\"{$c['name']}\",\"{$c['executive']}\",\"{$c['days']}\",\"{$c['last_invoice']}\"\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="reasignacion-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}