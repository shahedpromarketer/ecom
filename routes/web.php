<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->is_super_admin) return redirect('/super-admin/dashboard');
        if (auth()->user()->role === 'admin') return redirect('/admin/dashboard');
    }
    return redirect('/admin/login');
});

Route::get('/dashboard', function () {
    if (!auth()->check()) return redirect('/admin/login');
    if (auth()->user()->is_super_admin) return redirect('/super-admin/dashboard');
    return redirect('/admin/dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Registration
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);

// Super Admin
Route::prefix('super-admin')->middleware(['auth', 'super_admin'])->name('super.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    // Tenants (Feature 1, 5, 6, 9)
    Route::get('/tenants',             [SuperAdminController::class, 'tenants'])->name('tenants.index');
    Route::get('/tenants/create',      [SuperAdminController::class, 'createTenant'])->name('tenants.create');
    Route::post('/tenants',            [SuperAdminController::class, 'storeTenant'])->name('tenants.store');
    Route::get('/tenants/{id}',        [SuperAdminController::class, 'showTenant'])->name('tenants.show');
    Route::post('/tenants/{id}/suspend',     [SuperAdminController::class, 'suspendTenant'])->name('tenants.suspend');
    Route::post('/tenants/{id}/activate',    [SuperAdminController::class, 'activateTenant'])->name('tenants.activate');
    Route::delete('/tenants/{id}',           [SuperAdminController::class, 'deleteTenant'])->name('tenants.delete');
    Route::post('/tenants/{id}/assign-plan', [SuperAdminController::class, 'assignPlan'])->name('tenants.plan');
    Route::post('/tenants/{id}/limits',      [SuperAdminController::class, 'updateLimits'])->name('tenants.limits');
    Route::post('/tenants/bulk-action',      [SuperAdminController::class, 'bulkAction'])->name('tenants.bulk');
    Route::post('/tenants/{id}/sync-orders', [SuperAdminController::class, 'syncTenantOrders'])->name('tenants.sync-orders');

    // Impersonation (Feature 2)
    Route::post('/impersonate/{id}',   [SuperAdminController::class, 'impersonate'])->name('impersonate');
    Route::post('/stop-impersonating', [SuperAdminController::class, 'stopImpersonating'])->name('stop-impersonating');

    // Announcements (Feature 7)
    Route::get('/announcements',        [SuperAdminController::class, 'announcements'])->name('announcements');
    Route::post('/announcements',       [SuperAdminController::class, 'sendAnnouncement'])->name('announcements.send');
    Route::delete('/announcements/{id}',[SuperAdminController::class, 'deleteAnnouncement'])->name('announcements.delete');

    // System Health (Feature 8)
    Route::get('/system-health',        [SuperAdminController::class, 'systemHealth'])->name('system-health');
    Route::post('/system-health/clear-failed', [SuperAdminController::class, 'clearFailedJobs'])->name('system-health.clear-failed');

    // Activity Log (Feature 10)
    Route::get('/activity-log',         [SuperAdminController::class, 'activityLog'])->name('activity-log');
});

require __DIR__.'/auth.php';
