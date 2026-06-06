<?php

use App\Http\Controllers\Api\OrderReceiveController;
use App\Http\Controllers\Api\WordPressSyncController;
use Illuminate\Support\Facades\Route;

// WordPress plugin order sync (API token auth)
Route::middleware('api.token')->group(function () {
    Route::post('/orders/receive', [OrderReceiveController::class, 'store'])
        ->name('api.orders.receive');
    Route::get('/orders', [OrderReceiveController::class, 'index'])
        ->name('api.orders.index');
});

// Legacy webhook route (site api_key auth)
Route::post('/wordpress/sync', [WordPressSyncController::class, 'receiveOrder'])
    ->name('api.wordpress.sync');
