<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// SPA shell – all paths fall through to Vue Router
Route::get('/{any?}', [DashboardController::class, 'index'])->where('any', '.*');
