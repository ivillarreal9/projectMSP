<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MassReportController;
use App\Http\Controllers\Admin\ApiMspController;
use App\Http\Controllers\Admin\Meta2Controller;
use App\Http\Controllers\Admin\Sales\SalesDashboardController;
use App\Http\Controllers\Admin\Sales\SalesPipelineController;
use App\Http\Controllers\Admin\ApiCustomersController;
use App\Http\Controllers\Admin\Sales\SalesClientsController;
use App\Http\Controllers\Admin\Sales\SalesExecutivesController;
use App\Http\Controllers\Admin\Sales\SalesReassignController;
use App\Http\Controllers\Admin\MspReportController;
use App\Http\Controllers\Admin\ClientMergeController;
use App\Http\Controllers\Admin\SurveyTypeController;
use App\Http\Controllers\Admin\SurveyController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Solo Admin ───────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Users
        Route::resource('users', UserController::class);
        Route::put('users/{user}/password', [UserController::class, 'changePassword'])
             ->name('users.password');


        // ── MSP Reports ── NUEVO ──────────────────────────────────────────────
        Route::prefix('reports/msp')->name('msp.')->group(function () {
            Route::get('/',        [MspReportController::class, 'index'])->name('index');
            Route::post('/upload', [MspReportController::class, 'upload'])->name('upload');

            Route::get('/clientes',            [MspReportController::class, 'clientes'])->name('clientes');
            Route::get('/clientes/{customer}', [MspReportController::class, 'clienteDetalle'])->name('clientes.detalle');
            Route::post('/clientes/{customer}/logo', [MspReportController::class, 'uploadLogo'])->name('clientes.logo');
            Route::post('/clientes/{customer}/update', [MspReportController::class, 'updateCliente'])->name('clientes.update');
            
            Route::get('/pdf/{customer}/preview',  [MspReportController::class, 'pdfPreview'])->name('pdf.preview');
            Route::get('/pdf/{customer}/download', [MspReportController::class, 'pdfDownload'])->name('pdf.download');

            Route::get('/correos',         [MspReportController::class, 'correos'])->name('correos');
            Route::post('/correos/enviar', [MspReportController::class, 'enviarCorreo'])->name('correos.enviar');
            Route::post('/correos/masivo', [MspReportController::class, 'enviarMasivo'])->name('correos.masivo');

            Route::get('/chat',      [MspReportController::class, 'chat'])->name('chat');
            Route::post('/chat/api', [MspReportController::class, 'chatApi'])->name('chat.api');
            Route::get('/sharepoint',        [MspReportController::class, 'sharepointIndex'])->name('sharepoint');
            Route::post('/sharepoint/import',[MspReportController::class, 'sharepointImport'])->name('sharepoint.import');
            Route::post('/settings', [MspReportController::class, 'saveSettings'])->name('settings.save');

        });

        // Surveys
        Route::get('surveys', [SurveyTypeController::class, 'index'])->name('surveys.index');
        Route::post('survey-types', [SurveyTypeController::class, 'store'])->name('survey-types.store');
        Route::delete('survey-types/{surveyType}', [SurveyTypeController::class, 'destroy'])->name('survey-types.destroy');
        Route::get('surveys/{slug}', [SurveyController::class, 'show'])->name('surveys.show');
        Route::get('surveys/{slug}/export', [SurveyController::class, 'export'])->name('surveys.export');
        Route::post('surveys/token', [SurveyController::class, 'generateToken'])->name('surveys.token');

        // META 2
        Route::get('meta-2/pdf-preview', function () {
            $service = new \App\Services\Meta2Service();
            $data    = $service->getPdfReportData(3, 2026);
            return view('admin.meta-2.pdf', $data);
        })->name('meta-2.pdf-preview');

        Route::get('meta-2/export-pdf', [Meta2Controller::class, 'exportPdf'])->name('meta-2.export-pdf');
        Route::get('meta-2/export-excel', [Meta2Controller::class, 'exportExcel'])->name('meta-2.export-excel');

        Route::get('meta-2/debug-cf', function () {
            $service = new \App\Services\Meta2Service();
            $ids     = $service->getTelefoniaIds(3, 2026);
            $firstId = '81010fd2-4ca8-f011-8e61-000d3a4fe16d';
            $fields  = $service->debugCustomFields($firstId);
            return response()->json($fields);
        })->name('meta-2.debug-cf');

        // ✅ Nueva ruta SSE — debe ir ANTES del resource
        Route::get('meta-2/stream', [Meta2Controller::class, 'stream'])->name('meta-2.stream');

        Route::resource('meta-2', Meta2Controller::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });

// ─── Admin y Editor ───────────────────────────────────────────
Route::middleware(['auth', 'role:admin,editor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // API MSP
        Route::get('api-msp', [ApiMspController::class, 'index'])->name('api-msp.index');
        Route::post('api-msp/credentials', [ApiMspController::class, 'saveCredential'])->name('api-msp.credentials');
        Route::get('api-msp/export', [ApiMspController::class, 'export'])->name('api-msp.export');
        Route::get('api-msp/results', [ApiMspController::class, 'results'])->name('api-msp.results');
        Route::post('api-msp/chat', [ApiMspController::class, 'chat'])->name('api-msp.chat');
        Route::get('api-customers', [ApiCustomersController::class, 'index'])->name('api-customers.index');
        Route::get('api-customers/fetch', [ApiCustomersController::class, 'fetch'])->name('api-customers.fetch');
        Route::get('api-customers/export', [ApiCustomersController::class, 'export'])->name('api-customers.export');

        Route::get('client-merge', [ClientMergeController::class, 'index'])->name('client-merge.index');
        Route::post('client-merge/process', [ClientMergeController::class, 'process'])->name('client-merge.process');  
    });

// SSE fuera del grupo — solo auth, sin session middleware que bloquea el stream
Route::get('admin/api-msp/stream', [ApiMspController::class, 'stream'])
    ->middleware(['auth', 'role:admin,editor'])
    ->name('admin.api-msp.stream');

// ─── Ventas ───────────────────────────────────────────────────
Route::middleware(['auth', 'role:ventas,admin'])
    ->prefix('admin/sales')
    ->name('admin.sales.')
    ->group(function () {
        Route::get('/',          [SalesDashboardController::class,  'index'])->name('index');
        Route::get('/pipeline',  [SalesPipelineController::class,   'index'])->name('pipeline');
        Route::get('/clients',   [SalesClientsController::class,    'index'])->name('clients');
        Route::get('/executives',[SalesExecutivesController::class, 'index'])->name('executives');
        Route::get('/reassign',  [SalesReassignController::class,   'index'])->name('reassign');
        Route::post('/reassign/export', [SalesReassignController::class, 'export'])->name('reassign.export');
    }); 

Route::get('/odoo-test', function () {
    $odoo = new \App\Services\Sales\OdooService();
    
    // Probar login
    $uid = $odoo->login();
    
    return response()->json([
        'uid'    => $uid,
        'url'    => config('services.odoo.url'),
        'db'     => config('services.odoo.db'),
        'user'   => config('services.odoo.username'),
    ]);
});

// ─── Prueba AI (temporal) ───────────────────────────────────
Route::get('/ai-test', function () {
    $response = \Laravel\Ai\Ai::textProvider('anthropic')
        ->generate('Hola, responde en español: ¿qué es Laravel en una sola oración?');

    return response()->json(['respuesta' => (string) $response]);
}); 
require __DIR__.'/auth.php';