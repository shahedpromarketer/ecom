<?php

use App\Http\Controllers\Api\WordPressSyncController;
use Illuminate\Support\Facades\Route;

Route::post('/wordpress/sync', [WordPressSyncController::class, 'receiveOrder'])
    ->name('api.wordpress.sync');
