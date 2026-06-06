<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlacklistController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\InvoiceSetupController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/source-data', [DashboardController::class, 'sourceData'])->name('dashboard.source-data');
        Route::get('dashboard/status-data', [DashboardController::class, 'statusData'])->name('dashboard.status-data');
        Route::get('dashboard/order-status', [DashboardController::class, 'orderStatus'])->name('dashboard.orderStatus');
        Route::get('dashboard/order-source', [DashboardController::class, 'orderSource'])->name('dashboard.orderSource');

        // Categories
        Route::patch('categories/{category}/toggle', [CategoryController::class, 'toggleActive'])->name('categories.toggle');
        Route::resource('categories', CategoryController::class);

        // Products
        Route::post('products/bulk-action', [ProductController::class, 'bulkAction'])->name('products.bulkAction');
        Route::post('products/{product}/set-primary-image', [ProductController::class, 'setPrimaryImage'])->name('products.setPrimaryImage');
        Route::delete('products/images/{image}', [ProductController::class, 'deleteImage'])->name('products.deleteImage');
        Route::patch('products/variants/{variant}/stock', [ProductController::class, 'updateStock'])->name('products.updateStock');
        Route::post('products/{product}/clone', [ProductController::class, 'clone'])->name('products.clone');
        Route::resource('products', ProductController::class);

        // Orders
        Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('orders/incomplete', [OrderController::class, 'incomplete'])->name('orders.incomplete');

        // AJAX endpoints for order create (must be before wildcard {order} routes)
        Route::get('orders/search-products', [OrderController::class, 'searchProducts'])->name('orders.searchProducts');
        Route::get('orders/search-customer', [OrderController::class, 'searchCustomer'])->name('orders.searchCustomer');

        Route::post('orders/bulk-status', [OrderController::class, 'bulkStatus'])->name('orders.bulkStatus');
        Route::post('orders/bulk-courier', [OrderController::class, 'bulkCourier'])->name('orders.bulkCourier');
        Route::post('orders/bulk-tag', [OrderController::class, 'bulkTag'])->name('orders.bulkTag');
        Route::get('orders/export-csv', [OrderController::class, 'exportCsv'])->name('orders.exportCsv');
        Route::get('orders/bulk-print', [OrderController::class, 'bulkPrint'])->name('orders.bulkPrint');

        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::patch('orders/{order}/note', [OrderController::class, 'addNote'])->name('orders.addNote');
        Route::post('orders/{order}/notes', [OrderController::class, 'addNote'])->name('orders.notes.add');
        Route::delete('orders/notes/{note}', [OrderController::class, 'deleteNote'])->name('orders.deleteNote');
        Route::post('orders/{order}/shipment', [OrderController::class, 'createShipment'])->name('orders.createShipment');
        Route::post('orders/{order}/submit-courier', [OrderController::class, 'submitCourier'])->name('orders.submitCourier');
        Route::post('orders/{order}/return', [ReturnController::class, 'store'])->name('returns.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');

        // Payments
        Route::get('payments', [PaymentsController::class, 'index'])->name('payments.index');
        Route::patch('payments/{order}/mark-paid', [PaymentsController::class, 'markPaid'])->name('payments.markPaid');
        Route::patch('payments/{order}/mark-unpaid', [PaymentsController::class, 'markUnpaid'])->name('payments.markUnpaid');
        Route::post('payments/bulk-mark-paid', [PaymentsController::class, 'bulkMarkPaid'])->name('payments.bulkMarkPaid');

        // Users
        Route::patch('users/{user}/ban', [UserController::class, 'ban'])->name('users.ban');
        Route::patch('users/{user}/unban', [UserController::class, 'unban'])->name('users.unban');
        Route::resource('users', UserController::class);

        // Roles
        Route::resource('roles', RoleController::class)->except(['show', 'create', 'edit']);

        // Blacklist
        Route::get('blacklist', [BlacklistController::class, 'index'])->name('blacklist.index');
        Route::post('blacklist', [BlacklistController::class, 'store'])->name('blacklist.store');
        Route::delete('blacklist/{blacklist}', [BlacklistController::class, 'destroy'])->name('blacklist.destroy');

        // Coupons
        Route::patch('coupons/{coupon}/toggle', [CouponController::class, 'toggle'])->name('coupons.toggle');
        Route::resource('coupons', CouponController::class);

        // Banners
        Route::post('banners/reorder', [BannerController::class, 'reorder'])->name('banners.reorder');
        Route::patch('banners/{banner}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');
        Route::resource('banners', BannerController::class)->except(['show']);

        // Marketing
        Route::get('marketing', [MarketingController::class, 'index'])->name('marketing.index');
        Route::post('marketing/{platform}', [MarketingController::class, 'update'])->name('marketing.update');
        Route::patch('marketing/{platform}/toggle', [MarketingController::class, 'toggle'])->name('marketing.toggle');

        // Courier
        Route::get('courier', [CourierController::class, 'index'])->name('courier.index');
        Route::post('courier', [CourierController::class, 'update'])->name('courier.update');
        Route::post('courier/test', [CourierController::class, 'testConnection'])->name('courier.test');

        // Analytics
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

        // Inventory
        Route::patch('inventory/variants/{variant}', [InventoryController::class, 'updateStock'])->name('inventory.updateStock');
        Route::post('inventory/bulk-update', [InventoryController::class, 'bulkUpdate'])->name('inventory.bulkUpdate');
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');

        // Reports
        Route::get('reports/products', [ReportController::class, 'productReport'])->name('reports.products');
        Route::get('reports/users', [ReportController::class, 'userReport'])->name('reports.users');
        Route::get('reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock');
        Route::get('reports/payment', [ReportController::class, 'paymentReport'])->name('reports.payment');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        // Invoices
        Route::get('invoices/export/csv', [InvoiceController::class, 'downloadCsv'])->name('invoices.csv');
        Route::patch('invoices/bulk-status', [InvoiceController::class, 'bulkStatusUpdate'])->name('invoices.bulkStatus');
        Route::get('invoices/order/{order}/generate', [InvoiceController::class, 'generateFromOrder'])->name('invoices.generateFromOrder');
        Route::get('invoices/order/{order}/generate-print', [InvoiceController::class, 'generatePrint'])->name('invoices.generatePrint');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'printView'])->name('invoices.print');
        Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.updateStatus');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');

        // Invoice Setup
        Route::get('invoice-setup', [InvoiceSetupController::class, 'index'])->name('invoice-setup.index');
        Route::post('invoice-setup', [InvoiceSetupController::class, 'update'])->name('invoice-setup.update');

        // Returns
        Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::patch('returns/{return}', [ReturnController::class, 'update'])->name('returns.update');

        // Activity log
        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

        // Settings
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

        // API Tokens
        Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('settings.api-tokens');
        Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])->name('settings.api-tokens.store');
        Route::delete('settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('settings.api-tokens.destroy');
        Route::patch('settings/api-tokens/{token}/toggle', [ApiTokenController::class, 'toggle'])->name('settings.api-tokens.toggle');

        // WordPress redirect shortcut
        Route::redirect('wordpress', '/admin/integrations/wordpress')->name('wordpress');

        // Integrations
        Route::prefix('integrations/wordpress')->name('integrations.wordpress.')->group(function () {
            Route::get('/',                    [IntegrationController::class, 'wordpressIndex'])->name('index');
            Route::post('/',                   [IntegrationController::class, 'wordpressStore'])->name('store');
            Route::put('/{site}',              [IntegrationController::class, 'wordpressUpdate'])->name('update');
            Route::delete('/{site}',           [IntegrationController::class, 'wordpressDestroy'])->name('destroy');
            Route::patch('/{site}/toggle',     [IntegrationController::class, 'toggleActive'])->name('toggle');
            Route::post('/{site}/sync-orders', [IntegrationController::class, 'syncOrders'])->name('syncOrders');
            Route::post('/{site}/sync-products',[IntegrationController::class, 'syncProducts'])->name('syncProducts');
            Route::post('/{site}/test',        [IntegrationController::class, 'testConnection'])->name('test');
            Route::post('/{site}/regenerate',  [IntegrationController::class, 'regenerateKey'])->name('regenerate');
        });
    });

});
