<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SurveyApiController;

// Webhook encuestas — autenticado con Sanctum + rate limiting
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->post('/surveys/{token}', [SurveyApiController::class, 'receive']);