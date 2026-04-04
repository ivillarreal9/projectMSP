<?php

use App\Http\Controllers\Admin\SurveyController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\SurveyApiController;

Route::middleware('auth:sanctum')->post('/surveys/{token}', [SurveyApiController::class, 'receive']);