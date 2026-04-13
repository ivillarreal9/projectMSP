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

// ─── Públicas ─────────────────────────────────────────────────────────────────
Route::get('/', fn() => view('welcome'));

Route::get('/dashboard', fn() => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ─── Perfil ───────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ── Usuarios ──────────────────────────────────────────────────────────
        Route::resource('users', UserController::class);
        Route::put('users/{user}/password', [UserController::class, 'changePassword'])
            ->name('users.password');

        // ── MSP Reports ───────────────────────────────────────────────────────
        Route::prefix('reports/msp')->name('msp.')->group(function () {

            // Importación
            Route::get('/',             [MspReportController::class, 'index'])->name('index');
            Route::post('/upload',      [MspReportController::class, 'upload'])->name('upload');

            // SharePoint
            Route::get('/sharepoint',         [MspReportController::class, 'sharepointIndex'])->name('sharepoint');
            Route::post('/sharepoint/import', [MspReportController::class, 'sharepointImport'])->name('sharepoint.import');

            // Clientes
            Route::get('/clientes',                        [MspReportController::class, 'clientes'])->name('clientes');
            Route::get('/clientes/{customer}',             [MspReportController::class, 'clienteDetalle'])->name('clientes.detalle');
            Route::post('/clientes/{customer}/update',     [MspReportController::class, 'updateCliente'])->name('clientes.update');
            Route::post('/clientes/{customer}/logo',       [MspReportController::class, 'uploadLogo'])->name('clientes.logo');

            // PDFs
            Route::get('/pdf/{customer}/preview',          [MspReportController::class, 'pdfPreview'])->name('pdf.preview');
            Route::get('/pdf/{customer}/download',         [MspReportController::class, 'pdfDownload'])->name('pdf.download');
            Route::get('/pdf/descarga-masiva',             [MspReportController::class, 'descargaMasivaIndex'])->name('pdf.masiva');
            Route::post('/pdf/descarga-masiva/zip',        [MspReportController::class, 'descargaMasivaZip'])->name('pdf.masiva.zip');

            // Correos
            Route::get('/correos',          [MspReportController::class, 'correos'])->name('correos');
            Route::post('/correos/enviar',  [MspReportController::class, 'enviarCorreo'])->name('correos.enviar');
            Route::post('/correos/masivo',  [MspReportController::class, 'enviarMasivo'])->name('correos.masivo');

            // Chat IA
            Route::get('/chat',      [MspReportController::class, 'chat'])->name('chat');
            Route::post('/chat/api', [MspReportController::class, 'chatApi'])->name('chat.api');

            // Configuración
            Route::post('/settings', [MspReportController::class, 'saveSettings'])->name('settings.save');
        });

        // ── Encuestas ─────────────────────────────────────────────────────────
        Route::get('surveys',                           [SurveyTypeController::class, 'index'])->name('surveys.index');
        Route::post('survey-types',                     [SurveyTypeController::class, 'store'])->name('survey-types.store');
        Route::delete('survey-types/{surveyType}',      [SurveyTypeController::class, 'destroy'])->name('survey-types.destroy');
        Route::get('surveys/{slug}',                    [SurveyController::class, 'show'])->name('surveys.show');
        Route::get('surveys/{slug}/export',             [SurveyController::class, 'export'])->name('surveys.export');
        Route::post('surveys/token',                    [SurveyController::class, 'generateToken'])->name('surveys.token');

        // ── Meta 2 ────────────────────────────────────────────────────────────
        Route::get('meta-2/stream',      [Meta2Controller::class, 'stream'])->name('meta-2.stream');
        Route::get('meta-2/pdf-preview', fn() => view('admin.meta-2.pdf', (new \App\Services\Meta2Service())->getPdfReportData(3, 2026)))->name('meta-2.pdf-preview');
        Route::get('meta-2/export-pdf',  [Meta2Controller::class, 'exportPdf'])->name('meta-2.export-pdf');
        Route::get('meta-2/export-excel',[Meta2Controller::class, 'exportExcel'])->name('meta-2.export-excel');
        Route::resource('meta-2', Meta2Controller::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });

// ─── Admin y Editor ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,editor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // API MSP
        Route::get('api-msp',         [ApiMspController::class, 'index'])->name('api-msp.index');
        Route::get('api-msp/export',  [ApiMspController::class, 'export'])->name('api-msp.export');
        Route::get('api-msp/results', [ApiMspController::class, 'results'])->name('api-msp.results');
        Route::post('api-msp/chat',   [ApiMspController::class, 'chat'])->name('api-msp.chat');
        Route::post('api-msp/credentials', [ApiMspController::class, 'saveCredentials'])->name('api-msp.credentials'); // ← AGREGAR


        // API Customers
        Route::get('api-customers',        [ApiCustomersController::class, 'index'])->name('api-customers.index');
        Route::get('api-customers/fetch',  [ApiCustomersController::class, 'fetch'])->name('api-customers.fetch');
        Route::get('api-customers/export', [ApiCustomersController::class, 'export'])->name('api-customers.export');

        // Client Merge
        Route::get('client-merge',          [ClientMergeController::class, 'index'])->name('client-merge.index');
        Route::post('client-merge/process', [ClientMergeController::class, 'process'])->name('client-merge.process');
    });

// ─── SSE — fuera del grupo para evitar bloqueo de sesión ─────────────────────
Route::get('admin/api-msp/stream', [ApiMspController::class, 'stream'])
    ->middleware(['auth', 'role:admin,editor'])
    ->name('admin.api-msp.stream');

// ─── Ventas ───────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:ventas,admin'])
    ->prefix('admin/sales')
    ->name('admin.sales.')
    ->group(function () {
        Route::get('/',           [SalesDashboardController::class,  'index'])->name('index');
        Route::get('/pipeline',   [SalesPipelineController::class,   'index'])->name('pipeline');
        Route::get('/clients',    [SalesClientsController::class,    'index'])->name('clients');
        Route::get('/executives', [SalesExecutivesController::class, 'index'])->name('executives');
        Route::get('/reassign',   [SalesReassignController::class,   'index'])->name('reassign');
        Route::post('/reassign/export', [SalesReassignController::class, 'export'])->name('reassign.export');
    });

require __DIR__.'/auth.php';