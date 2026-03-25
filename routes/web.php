<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MassReportController;
use App\Http\Controllers\Admin\ApiMspController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Admin\Meta2Controller;
use App\Http\Controllers\Admin\SalesDashboardController;
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

        // Mass Reports
        Route::resource('reports', MassReportController::class);

        // Surveys — export SIEMPRE antes del resource
        Route::get('surveys/token/current', [SurveyController::class, 'currentToken'])->name('surveys.token.current');
        Route::post('surveys/token', [SurveyController::class, 'generateToken'])->name('surveys.token');
        Route::get('surveys/export', [SurveyController::class, 'export'])->name('surveys.export');
        Route::resource('surveys', SurveyController::class)->only(['index']);

        // META 2
        Route::get('meta-2/pdf-preview', function () {
            $service = new \App\Services\Meta2Service();
            $data    = $service->getPdfReportData(3, 2026);
            return view('admin.meta-2.pdf', $data);
        })->name('meta-2.pdf-preview');

        Route::get('meta-2/export-pdf', [Meta2Controller::class, 'exportPdf'])->name('meta-2.export-pdf');

        Route::get('meta-2/debug-cf', function () {
            $service = new \App\Services\Meta2Service();
            $ids     = $service->getTelefoniaIds(3, 2026);
            $firstId = $ids[0] ?? null;
            if (!$firstId) return response()->json(['error' => 'No hay tickets']);
            $fields  = $service->debugCustomFields($firstId);
            return response()->json($fields);
        })->name('meta-2.debug-cf');

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
    });

// ─── Ventas ───────────────────────────────────────────────────
Route::middleware(['auth', 'role:ventas,admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('sales', [SalesDashboardController::class, 'index'])->name('sales.index');
    });    

require __DIR__.'/auth.php';