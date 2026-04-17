<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/metrics', [DashboardController::class, 'metrics']);
Route::post('/metrics/refresh', [DashboardController::class, 'refresh']);
Route::get('/queue-stats', [DashboardController::class, 'queueStats']);
