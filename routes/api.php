<?php

use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Api\Sales\CommissionsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Encuestas desde Botmaker
    Route::post('/encuestas', [SurveyController::class, 'store']);

    // Comisiones de vendedores
    Route::prefix('commissions')->group(function () {
        Route::get('/{vendedor_id}/month', [CommissionsController::class, 'month']);
        Route::get('/{vendedor_id}/year',  [CommissionsController::class, 'year']);
    });
});
