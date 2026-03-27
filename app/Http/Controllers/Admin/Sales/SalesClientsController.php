<?php
namespace App\Http\Controllers\Admin\Sales;
use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;

class SalesClientsController extends Controller
{
    public function index(OdooService $odoo)
    {
        $clients = $odoo->getClients();
        return view('admin.sales.clients.index', compact('clients'));
    }
}