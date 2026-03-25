<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;

class SalesDashboardController extends Controller
{
    public function index(OdooService $odoo)
    {
        $sales = $odoo->getSalesOrders();
        //dd($sales);
        return view('admin.sales.dashboard.index', compact('sales'));
    }
}