<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/predict', [PredictionController::class, 'predict']);
    Route::get('/descriptive', [PredictionController::class, 'list']);
    Route::get('/images/{id}', [PredictionController::class, 'show']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/stats', [PredictionController::class, 'stats']);
    Route::post('/farm/location', [LocationController::class, 'saveLocation']);
    Route::get('/farm/weather', [LocationController::class, 'getWeather']);
    Route::get('/profile', [AuthController::class, 'show']);
    Route::put('/update', [AuthController::class, 'update']);

     Route::prefix('admin')->group(function () {
        Route::get('/users', [AuthController::class, 'allUsers']);
        Route::get('/activity-logs', [AuditController::class, 'index']);
    });
});
