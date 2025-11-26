<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\GetWeatherController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {

    
    Route::get('/weather', [GetWeatherController::class, 'history']);
    Route::post('/weather', [GetWeatherController::class, 'show']);

    Route::post('/predict', [PredictionController::class, 'predict']);
    Route::get('/descriptive', [PredictionController::class, 'list']);
    Route::get('/images/{id}', [PredictionController::class, 'show']);
    Route::get('/stats', [PredictionController::class, 'stats']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'show']);
    Route::put('/update', [AuthController::class, 'update']);
    Route::get('/update', [AuthController::class, 'showRaw']);

     Route::prefix('admin')->group(function () {
        Route::get('/users', [AuthController::class, 'allUsers']);
        Route::get('/activity-logs', [AuditController::class, 'index']);
    });
});
