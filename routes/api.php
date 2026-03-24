<?php

use App\Http\Controllers\Admin\SurveyController;
use Illuminate\Support\Facades\Route;

// Endpoint para recibir encuestas desde Botmaker
Route::middleware('auth:sanctum')->post('/encuestas', [SurveyController::class, 'store']);