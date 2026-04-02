<?php
// ═══════════════════════════════════════════════════════════════
// ARCHIVO 1: routes/web.php — agregar estas rutas
// ═══════════════════════════════════════════════════════════════

use App\Http\Controllers\SharePointWebhookController;

// Webhook de SharePoint — fuera del grupo auth (Microsoft llama directamente)
Route::get('/msp/webhook',    [SharePointWebhookController::class, 'validate'])->name('msp.webhook.validate');
Route::post('/msp/webhook',   [SharePointWebhookController::class, 'notify'])->name('msp.webhook.notify');

// Registro del webhook (solo admin)
Route::post('/admin/reports/msp/webhook/register', [SharePointWebhookController::class, 'register'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.msp.webhook.register');

// Sincronización manual (botón en la UI)
Route::post('/admin/reports/msp/sync', [App\Http\Controllers\MspReportController::class, 'syncSharePoint'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.msp.sync');


// ═══════════════════════════════════════════════════════════════
// ARCHIVO 2: routes/console.php — scheduler día 5 de cada mes
// ═══════════════════════════════════════════════════════════════
/*
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncSharePointExcelJob;

Schedule::job(new SyncSharePointExcelJob())
    ->monthlyOn(5, '08:00')   // Día 5 de cada mes a las 8am
    ->timezone('America/Panama')
    ->withoutOverlapping()
    ->name('msp-sharepoint-sync')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('MSP SharePoint sync falló en el scheduler');
    });
*/


// ═══════════════════════════════════════════════════════════════
// ARCHIVO 3: config/services.php — agregar SharePoint
// ═══════════════════════════════════════════════════════════════
/*
'sharepoint' => [
    'tenant_id'     => env('AZURE_TENANT_ID'),
    'client_id'     => env('AZURE_CLIENT_ID'),
    'client_secret' => env('AZURE_CLIENT_SECRET'),
    'site_url'      => env('SHAREPOINT_SITE_URL'),
    'folder'        => env('SHAREPOINT_FOLDER'),
    'file'          => env('SHAREPOINT_FILE'),
],
*/


// ═══════════════════════════════════════════════════════════════
// ARCHIVO 4: .env — agregar variables
// ═══════════════════════════════════════════════════════════════
/*
AZURE_TENANT_ID=940ff61e-cc6e-4fb7-869a-9216d6297f40
AZURE_CLIENT_ID=77d1bd34-f925-4eb6-b871-303a6f9422ed
AZURE_CLIENT_SECRET=tu_client_secret_completo
SHAREPOINT_SITE_URL=https://ovnicom0.sharepoint.com/sites/InformaciondeClientes
SHAREPOINT_FOLDER=Documentos compartidos/TEST_2026 _MSP_LARAVEL
SHAREPOINT_FILE=MSP_REPORT_CLIENTES_2026.xlsx
*/


// ═══════════════════════════════════════════════════════════════
// ARCHIVO 5: MspReportController.php — agregar método syncSharePoint
// ═══════════════════════════════════════════════════════════════
/*
public function syncSharePoint(Request $request)
{
    $periodo = $request->input('periodo', now()->translatedFormat('F Y'));

    \App\Jobs\SyncSharePointExcelJob::dispatch($periodo, true);

    return back()->with('success', "🔄 Sincronización con SharePoint iniciada para {$periodo}. Los datos se actualizarán en breve.");
}
*/
