<?php

use App\Http\Controllers\Api\OrderReceiveController;
use App\Http\Controllers\Api\WordPressSyncController;
use Illuminate\Support\Facades\Route;

// WordPress plugin order sync (API token auth)
Route::middleware('api.token')->group(function () {
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'Connected to Clothonic CRM',
            'version' => '1.0.0',
        ]);
    })->name('api.ping');

    Route::post('/orders/receive', [OrderReceiveController::class, 'store'])
        ->name('api.orders.receive');
    Route::get('/orders', [OrderReceiveController::class, 'index'])
        ->name('api.orders.index');
});

// Legacy webhook route (site api_key auth)
Route::post('/wordpress/sync', [WordPressSyncController::class, 'receiveOrder'])
    ->name('api.wordpress.sync');
