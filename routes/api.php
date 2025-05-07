<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/analytics', [OrderController::class, 'analytics']);
