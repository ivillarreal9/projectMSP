<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SalesDashboardController extends Controller
{
    public function index()
    {
        return view('admin.sales.index');
    }
}