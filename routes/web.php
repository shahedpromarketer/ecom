<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ThankYouController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use Illuminate\Support\Facades\Route;

// ── Frontend Public Routes ────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');

Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');

Route::get('/wishlist', function () {
    return redirect()->route('home');
})->name('wishlist');

Route::get('/orders', function () {
    return redirect()->route('home');
})->name('orders.index');

// ── Quick Order (AJAX) ────────────────────────────────────
Route::post('/order/quick', [QuickOrderController::class, 'store'])->name('order.quick');
Route::post('/cart/checkout', [App\Http\Controllers\CartController::class, 'checkout'])->name('cart.checkout');
Route::get('/order/thank-you', [ThankYouController::class, 'index'])->name('order.thank-you');

// ── Dashboard redirect ────────────────────────────────────
Route::get('/dashboard', function () {
    if (!auth()->check()) return redirect('/admin/login');
    if (auth()->user()->is_super_admin ?? false) return redirect('/super-admin/dashboard');
    return redirect('/admin/dashboard');
})->middleware(['auth'])->name('dashboard');

// ── Auth / Profile ────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Registration
if (class_exists(RegisterController::class)) {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
}

// Super Admin
if (class_exists(SuperAdminController::class)) {
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
        Route::post('/stop-impersonating',       [SuperAdminController::class, 'stopImpersonating'])->name('stop-impersonating');

        Route::get('/announcements',             [SuperAdminController::class, 'announcements'])->name('announcements');
        Route::post('/announcements',            [SuperAdminController::class, 'sendAnnouncement'])->name('announcements.send');
        Route::delete('/announcements/{id}',     [SuperAdminController::class, 'deleteAnnouncement'])->name('announcements.delete');

        Route::get('/system-health',             [SuperAdminController::class, 'systemHealth'])->name('system-health');
        Route::post('/system-health/clear-failed', [SuperAdminController::class, 'clearFailedJobs'])->name('system-health.clear-failed');

        Route::get('/activity-log',              [SuperAdminController::class, 'activityLog'])->name('activity-log');
    });
}

require __DIR__ . '/auth.php';

// ── Admin Panel ───────────────────────────────────────────
if (file_exists(__DIR__ . '/admin.php')) {
    require __DIR__ . '/admin.php';
}
