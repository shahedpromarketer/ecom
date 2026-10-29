<?php

use App\Http\Controllers\Api\OrderReceiveController;
use App\Http\Controllers\Api\PluginConnectController;
use App\Http\Controllers\Api\PluginValidateController;
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

// Ecom Pilot Netpilot plugin endpoints (api_key auth via X-API-KEY header)
Route::post('/plugin/connect',       [PluginConnectController::class, 'connect'])->name('api.plugin.connect');
Route::post('/plugin/disconnect',    [PluginConnectController::class, 'disconnect'])->name('api.plugin.disconnect');
Route::get('/plugin/ping',           [PluginConnectController::class, 'ping'])->name('api.plugin.ping');
Route::post('/plugin/validate-order',[PluginValidateController::class, 'validate'])->name('api.plugin.validate');

// Ecom Pilot plugin v2 — every request HMAC-signed with the store key (see App\Support\PluginSignature)
Route::prefix('v1/store')->middleware(['plugin.signed', 'throttle:600,1'])->name('api.store.')->group(function () {
    Route::post('/connect',  [\App\Http\Controllers\Api\StorePluginController::class, 'connect'])->name('connect');
    Route::post('/orders',   [\App\Http\Controllers\Api\StorePluginController::class, 'order'])->name('orders');
    Route::post('/validate', [\App\Http\Controllers\Api\StorePluginController::class, 'validateCheckout'])->name('validate');
    Route::post('/products', [\App\Http\Controllers\Api\StorePluginController::class, 'product'])->name('products');
    Route::post('/stock',    [\App\Http\Controllers\Api\StorePluginController::class, 'stock'])->name('stock');
    Route::post('/incomplete', [\App\Http\Controllers\Api\StorePluginController::class, 'incomplete'])->name('incomplete');
});

// Public REST API for custom websites — Authorization: Bearer {connection key}. Docs: Integrations → API docs.
Route::prefix('v1')->middleware(['store.key', 'throttle:300,1'])->name('api.v1.')->group(function () {
    Route::post('/orders',                [\App\Http\Controllers\Api\PublicStoreApiController::class, 'storeOrder'])->name('orders.store');
    Route::get('/orders/{externalId}',    [\App\Http\Controllers\Api\PublicStoreApiController::class, 'showOrder'])->name('orders.show');
    Route::post('/checkout/check',        [\App\Http\Controllers\Api\PublicStoreApiController::class, 'check'])->name('checkout.check');
    Route::post('/incomplete',            [\App\Http\Controllers\Api\PublicStoreApiController::class, 'incomplete'])->name('incomplete');
});

// Parcel status updates from couriers (the token identifies the shop's courier account)
Route::match(['get', 'post'], '/courier-webhook/{token}', \App\Http\Controllers\Api\CourierWebhookController::class)
    ->middleware('throttle:600,1')->name('api.courier.webhook');

// Legacy webhook route (site api_key auth)
Route::post('/wordpress/sync', [WordPressSyncController::class, 'receiveOrder'])
    ->name('api.wordpress.sync');

// WooCommerce native webhook (no API key — HMAC signature from X-WC-Webhook-Signature)
Route::post('/woocommerce/webhook', [WordPressSyncController::class, 'receiveWebhook'])
    ->name('api.woocommerce.webhook');
