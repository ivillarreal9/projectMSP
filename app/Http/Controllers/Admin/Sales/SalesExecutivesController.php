<?php
namespace App\Http\Controllers\Admin\Sales;
use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;

class SalesExecutivesController extends Controller
{
    public function index(OdooService $odoo)
    {
        $executives = $odoo->getExecutives();
        return view('admin.sales.executives.index', compact('executives'));
    }
}