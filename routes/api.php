<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SurveyApiController;
use App\Http\Controllers\Api\Sales\CommissionsController;
use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\MspClientController;
use App\Http\Controllers\Api\MspCustomerController;
use App\Http\Controllers\Api\MspReportApiController;
use App\Http\Controllers\Api\MspTicketsController;
use App\Http\Controllers\Api\EnlaceApiController;

// ── Auth — emitir / revocar Bearer tokens ────────────────────────────────────
Route::middleware('throttle:3,15')
    ->post('/v1/auth/token', [AuthTokenController::class, 'issue']);

Route::middleware(['auth:sanctum', 'throttle:10,1'])
    ->delete('/v1/auth/token', [AuthTokenController::class, 'revoke']);

// ── Encuestas (webhook público — autenticado por token en URL) ────────────────
Route::middleware('throttle:30,1')
    ->post('/surveys/{token}', [SurveyApiController::class, 'receive']);

// ── MSP Reports — períodos disponibles y PDF por cliente ─────────────────────
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->get('/v1/reports/msp/periodos', [MspReportApiController::class, 'periodos']);

Route::middleware(['auth:sanctum', 'throttle:20,1'])
    ->get('/v1/reports/msp/pdf', [MspReportApiController::class, 'download']);

// ── MSP Customer por RUC ─────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:30,1'])
    ->get('/v1/msp/customer', [MspCustomerController::class, 'findByRuc']);

// ── MSP Tickets por cliente ───────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:20,1'])
    ->group(function () {
        Route::get('/v1/msp/search',         [MspTicketsController::class, 'search']);
        Route::get('/v1/msp/tickets',        [MspTicketsController::class, 'tickets']);
        Route::get('/v1/msp/ticket-details', [MspTicketsController::class, 'ticketDetails']);
    });

// ── MSP Clientes ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:10,1'])
    ->post('/v1/msp-clients/bulk-update', [MspClientController::class, 'bulkUpdate']);

// ── Ventas / Comisiones ───────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('v1/commissions')
    ->group(function () {
        Route::get('/{vendedor_id}/month', [CommissionsController::class, 'month'])
            ->whereNumber('vendedor_id');
        Route::get('/{vendedor_id}/year',  [CommissionsController::class, 'year'])
            ->whereNumber('vendedor_id');
    });

// ── Control de Enlaces ────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('v1/enlaces')
    ->group(function () {
        Route::get('/',            [EnlaceApiController::class, 'index']);
        Route::post('/by-country', [EnlaceApiController::class, 'byCountry']);
    });