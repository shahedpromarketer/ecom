<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickOrderController;
use Illuminate\Support\Facades\Route;

// ── Frontend Public Routes ────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');

// Shop / Product (stubs — to be built in next phases)
Route::get('/shop', function () {
    return redirect()->route('home');
})->name('shop');

Route::get('/product/{slug}', function ($slug) {
    $product = \App\Models\Product::where('slug', $slug)->where('status', 'active')->firstOrFail();
    return redirect()->route('home'); // placeholder until product page is built
})->name('product.show');

Route::get('/wishlist', function () {
    return redirect()->route('home');
})->name('wishlist');

Route::get('/orders', function () {
    return redirect()->route('home');
})->name('orders.index');

// ── Quick Order (AJAX) ────────────────────────────────────
Route::post('/order/quick', [QuickOrderController::class, 'store'])->name('order.quick');

// ── Auth / Profile ────────────────────────────────────────
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

// ── Admin Panel ───────────────────────────────────────────
// Admin routes loaded separately (check routes/admin.php if exists)
if (file_exists(__DIR__ . '/admin.php')) {
    require __DIR__ . '/admin.php';
}
