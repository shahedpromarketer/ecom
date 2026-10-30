<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use Illuminate\Support\Facades\Route;

// Ecom Pilot is a CRM only — there is no storefront. The root sends people to their panel.
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('admin.login');
    }

    return auth()->user()->is_super_admin
        ? redirect()->route('super.dashboard')
        : redirect()->route('admin.dashboard');
})->name('home');

Route::redirect('/dashboard', '/')->name('dashboard');

// Shop owner sign-up (starts a trial tenant)
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');

// Leaving impersonation must work while logged in as the tenant admin,
// so it sits outside the super_admin middleware. The controller checks the session.
Route::post('/super-admin/stop-impersonating', [SuperAdminController::class, 'stopImpersonating'])
    ->middleware('auth')
    ->name('super.stop-impersonating');

Route::prefix('super-admin')->middleware(['auth', 'super_admin'])->name('super.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/tenants',                   [SuperAdminController::class, 'tenants'])->name('tenants.index');
    Route::get('/tenants/create',            [SuperAdminController::class, 'createTenant'])->name('tenants.create');
    Route::post('/tenants',                  [SuperAdminController::class, 'storeTenant'])->name('tenants.store');
    Route::get('/tenants/{id}',              [SuperAdminController::class, 'showTenant'])->name('tenants.show');
    Route::post('/tenants/{id}/suspend',     [SuperAdminController::class, 'suspendTenant'])->name('tenants.suspend');
    Route::post('/tenants/{id}/activate',    [SuperAdminController::class, 'activateTenant'])->name('tenants.activate');
    Route::delete('/tenants/{id}',           [SuperAdminController::class, 'deleteTenant'])->name('tenants.delete');
    Route::post('/tenants/{id}/assign-plan', [SuperAdminController::class, 'assignPlan'])->name('tenants.plan');
    Route::post('/tenants/{id}/limits',      [SuperAdminController::class, 'updateLimits'])->name('tenants.limits');
    Route::post('/tenants/bulk-action',      [SuperAdminController::class, 'bulkAction'])->name('tenants.bulk');
    Route::post('/tenants/{id}/sync-orders', [SuperAdminController::class, 'syncTenantOrders'])->name('tenants.sync-orders');

    Route::post('/impersonate/{id}',         [SuperAdminController::class, 'impersonate'])->name('impersonate');

    Route::get('/announcements',             [SuperAdminController::class, 'announcements'])->name('announcements');
    Route::post('/announcements',            [SuperAdminController::class, 'sendAnnouncement'])->name('announcements.send');
    Route::delete('/announcements/{id}',     [SuperAdminController::class, 'deleteAnnouncement'])->name('announcements.delete');

    Route::get('/system-health',             [SuperAdminController::class, 'systemHealth'])->name('system-health');
    Route::post('/system-health/clear-failed', [SuperAdminController::class, 'clearFailedJobs'])->name('system-health.clear-failed');

    Route::get('/activity-log',              [SuperAdminController::class, 'activityLog'])->name('activity-log');

    // Wallet: recharge requests, balances, per-order charges
    Route::get('/wallet',                    [\App\Http\Controllers\SuperAdmin\WalletAdminController::class, 'index'])->name('wallet');
    Route::post('/wallet/requests/{id}/approve', [\App\Http\Controllers\SuperAdmin\WalletAdminController::class, 'approve'])->name('wallet.approve');
    Route::post('/wallet/requests/{id}/reject',  [\App\Http\Controllers\SuperAdmin\WalletAdminController::class, 'reject'])->name('wallet.reject');
    Route::get('/wallet/requests/{id}/screenshot', [\App\Http\Controllers\SuperAdmin\WalletAdminController::class, 'screenshot'])->name('wallet.screenshot');
    Route::post('/wallet/tenants/{id}',      [\App\Http\Controllers\SuperAdmin\WalletAdminController::class, 'updateTenant'])->name('wallet.tenant');
    Route::get('/wallet/tenants/{id}/history', [\App\Http\Controllers\SuperAdmin\WalletAdminController::class, 'history'])->name('wallet.history');
    Route::post('/wallet/payment-info',      [\App\Http\Controllers\SuperAdmin\WalletAdminController::class, 'paymentInfo'])->name('wallet.payment-info');
});

require __DIR__ . '/auth.php';

// routes/admin.php is loaded once, from bootstrap/app.php.
