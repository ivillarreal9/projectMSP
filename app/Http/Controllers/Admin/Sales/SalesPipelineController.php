<?php
namespace App\Http\Controllers\Admin\Sales;
use App\Http\Controllers\Controller;
use App\Services\Sales\OdooService;

class SalesPipelineController extends Controller
{
    public function index(OdooService $odoo)
    {
        $pipeline = $odoo->getPipeline();
        return view('admin.sales.pipeline.index', compact('pipeline'));
    }
}