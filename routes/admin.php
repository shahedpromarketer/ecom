<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BlacklistController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\InvoiceSetupController;
use App\Http\Controllers\Admin\InvoiceTemplateController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderNoteController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin', 'tenant', 'permission'])->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/source-data', [DashboardController::class, 'sourceData'])->name('dashboard.source-data');
        Route::get('dashboard/status-data', [DashboardController::class, 'statusData'])->name('dashboard.status-data');
        Route::get('dashboard/order-status', [DashboardController::class, 'orderStatus'])->name('dashboard.orderStatus');
        Route::get('dashboard/order-source', [DashboardController::class, 'orderSource'])->name('dashboard.orderSource');
        Route::get('dashboard/summary-data', [DashboardController::class, 'summaryData'])->name('dashboard.summaryData');

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
        Route::get('orders/search-live', [OrderController::class, 'searchLive'])->name('orders.searchLive');
        Route::get('orders/search-products', [OrderController::class, 'searchProducts'])->name('orders.searchProducts');
        Route::get('orders/search-customer', [OrderController::class, 'searchCustomer'])->name('orders.searchCustomer');

        Route::get('sidebar-counts', [OrderController::class, 'sidebarCounts'])->name('sidebar.counts');
        Route::get('orders/{order}/quick-view', [OrderController::class, 'quickView'])->name('orders.quickView');
        Route::patch('orders/{order}/quick-field', [OrderController::class, 'quickField'])->name('orders.quickField');
        Route::post('orders/bulk-status', [OrderController::class, 'bulkStatus'])->name('orders.bulkStatus');
        Route::post('orders/bulk-courier', [OrderController::class, 'bulkCourier'])->name('orders.bulkCourier');
        Route::post('orders/bulk-tag', [OrderController::class, 'bulkTag'])->name('orders.bulkTag');
        Route::get('orders/export-csv', [OrderController::class, 'exportCsv'])->name('orders.exportCsv');
        Route::get('orders/bulk-print', [OrderController::class, 'bulkPrint'])->name('orders.bulkPrint');

        Route::match(['PUT', 'PATCH'], 'orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::post('orders/{order}/items', [OrderController::class, 'addItem'])->name('orders.addItem');
        Route::delete('orders/items/{item}', [OrderController::class, 'removeItem'])->name('orders.removeItem');
        Route::patch('orders/{order}/note', [OrderController::class, 'addNote'])->name('orders.addNote');
        Route::delete('orders/notes/{note}', [OrderController::class, 'deleteNote'])->name('orders.deleteNote');
        Route::post('orders/{order}/shipment', [OrderController::class, 'createShipment'])->name('orders.createShipment');
        Route::post('orders/{order}/submit-courier', [OrderController::class, 'submitCourier'])->name('orders.submitCourier');
        Route::get('orders/{order}/track-courier', [OrderController::class, 'trackCourier'])->name('orders.trackCourier');
        Route::patch('orders/{order}/update-tracking', [OrderController::class, 'updateTracking'])->name('orders.updateTracking');
        Route::post('orders/{order}/return', [ReturnController::class, 'store'])->name('returns.store');
        Route::get('orders/{id}/products-search', [OrderController::class, 'productSearch'])->name('orders.productSearch');
        Route::post('orders/{id}/reorder', [OrderController::class, 'reOrder'])->name('orders.reOrder');
        Route::post('orders/{id}/notes', [OrderNoteController::class, 'store'])->name('orders.notes.store');
        Route::delete('orders/{id}/notes/{noteId}', [OrderNoteController::class, 'destroy'])->name('orders.notes.destroy');
        Route::post('orders/{id}/customer-notes', [OrderNoteController::class, 'storeCustomer'])->name('orders.customerNotes.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');

        // Customers
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::patch('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::post('customers/{customer}/notes', [CustomerController::class, 'storeNote'])->name('customers.notes.store');
        Route::delete('customers/notes/{note}', [CustomerController::class, 'destroyNote'])->name('customers.notes.destroy');

        // Recycle bin (shop owners only — not mapped in Permissions)
        Route::get('recycle-bin', [\App\Http\Controllers\Admin\RecycleBinController::class, 'index'])->name('recycle-bin.index');
        Route::post('recycle-bin/{type}/restore', [\App\Http\Controllers\Admin\RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
        Route::post('recycle-bin/{type}/delete', [\App\Http\Controllers\Admin\RecycleBinController::class, 'destroy'])->name('recycle-bin.destroy');
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        // Saved list filters (orders, customers, leads, follow-ups)
        Route::post('saved-filters', [\App\Http\Controllers\Admin\SavedFilterController::class, 'store'])->name('saved-filters.store');
        Route::delete('saved-filters/{savedFilter}', [\App\Http\Controllers\Admin\SavedFilterController::class, 'destroy'])->name('saved-filters.destroy');

        // Order distribution
        Route::get('distribution', [\App\Http\Controllers\Admin\DistributionController::class, 'index'])->name('distribution.index');
        Route::post('distribution', [\App\Http\Controllers\Admin\DistributionController::class, 'update'])->name('distribution.update');
        Route::post('distribution/run', [\App\Http\Controllers\Admin\DistributionController::class, 'run'])->name('distribution.run');

        // Failed orders (unfinished checkouts)
        Route::get('failed-orders', [\App\Http\Controllers\Admin\FailedOrderController::class, 'index'])->name('failed-orders.index');
        Route::post('failed-orders/bulk', [\App\Http\Controllers\Admin\FailedOrderController::class, 'bulk'])->name('failed-orders.bulk');
        Route::patch('failed-orders/{failed}', [\App\Http\Controllers\Admin\FailedOrderController::class, 'update'])->name('failed-orders.update');

        // Leads / pre-orders
        Route::get('leads', [\App\Http\Controllers\Admin\LeadController::class, 'index'])->name('leads.index');
        Route::post('leads', [\App\Http\Controllers\Admin\LeadController::class, 'store'])->name('leads.store');
        Route::post('leads/import', [\App\Http\Controllers\Admin\LeadController::class, 'import'])->name('leads.import');
        Route::post('leads/bulk', [\App\Http\Controllers\Admin\LeadController::class, 'bulk'])->name('leads.bulk');
        Route::patch('leads/{lead}', [\App\Http\Controllers\Admin\LeadController::class, 'update'])->name('leads.update');

        // Follow-ups (call list)
        Route::get('followups', [\App\Http\Controllers\Admin\FollowupController::class, 'index'])->name('followups.index');
        Route::post('followups', [\App\Http\Controllers\Admin\FollowupController::class, 'store'])->name('followups.store');
        Route::post('followups/bulk', [\App\Http\Controllers\Admin\FollowupController::class, 'bulk'])->name('followups.bulk');
        Route::patch('followups/{followup}/complete', [\App\Http\Controllers\Admin\FollowupController::class, 'complete'])->name('followups.complete');
        Route::patch('followups/{followup}/reopen', [\App\Http\Controllers\Admin\FollowupController::class, 'reopen'])->name('followups.reopen');
        Route::delete('followups/{followup}', [\App\Http\Controllers\Admin\FollowupController::class, 'destroy'])->name('followups.destroy');

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

        // Blacklist (Phone)
        Route::get('blacklist', [BlacklistController::class, 'index'])->name('blacklist.index');
        Route::post('blacklist', [BlacklistController::class, 'store'])->name('blacklist.store');
        Route::delete('blacklist/{blacklist}', [BlacklistController::class, 'destroy'])->name('blacklist.destroy');
        // Blacklist (IP)
        Route::post('blacklist/ip', [BlacklistController::class, 'storeIp'])->name('blacklist.ip.store');
        Route::delete('blacklist/ip/{ip}', [BlacklistController::class, 'destroyIp'])->name('blacklist.ip.destroy');

        // Coupons
        Route::patch('coupons/{coupon}/toggle', [CouponController::class, 'toggle'])->name('coupons.toggle');
        Route::resource('coupons', CouponController::class);

        // Marketing
        Route::get('marketing', [MarketingController::class, 'index'])->name('marketing.index');
        Route::post('marketing/{platform}', [MarketingController::class, 'update'])->name('marketing.update');
        Route::patch('marketing/{platform}/toggle', [MarketingController::class, 'toggle'])->name('marketing.toggle');

        // Courier
        Route::get('courier', [CourierController::class, 'index'])->name('courier.index');
        Route::post('courier', [CourierController::class, 'store'])->name('courier.store');
        Route::put('courier/{account}', [CourierController::class, 'update'])->name('courier.update');
        Route::delete('courier/{account}', [CourierController::class, 'destroy'])->name('courier.destroy');
        Route::post('courier/{account}/default', [CourierController::class, 'makeDefault'])->name('courier.default');
        Route::post('courier/{account}/test', [CourierController::class, 'test'])->name('courier.test');

        // Packing, hand-over and returns (scanning)
        Route::get('dispatch', [\App\Http\Controllers\Admin\DispatchController::class, 'index'])->name('dispatch.index');
        Route::post('dispatch/lookup', [\App\Http\Controllers\Admin\DispatchController::class, 'lookup'])->name('dispatch.lookup');
        Route::post('dispatch/pack', [\App\Http\Controllers\Admin\DispatchController::class, 'pack'])->name('dispatch.pack');
        Route::post('dispatch/handover', [\App\Http\Controllers\Admin\DispatchController::class, 'handover'])->name('dispatch.handover');
        Route::post('dispatch/receive', [\App\Http\Controllers\Admin\DispatchController::class, 'receive'])->name('dispatch.receive');

        // Courier payment matching
        Route::get('courier-payments', [\App\Http\Controllers\Admin\CourierPaymentController::class, 'index'])->name('courier-payments.index');
        Route::post('courier-payments', [\App\Http\Controllers\Admin\CourierPaymentController::class, 'store'])->name('courier-payments.store');
        Route::get('courier-payments/{payment}', [\App\Http\Controllers\Admin\CourierPaymentController::class, 'show'])->name('courier-payments.show');

        // Analytics
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

        // Inventory
        Route::get('inventory/export-csv', [InventoryController::class, 'exportCsv'])->name('inventory.exportCsv');
        Route::patch('inventory/variants/{variant}', [InventoryController::class, 'updateStock'])->name('inventory.updateStock');
        Route::post('inventory/variants/{variant}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::get('inventory/variants/{variant}/logs', [InventoryController::class, 'logs'])->name('inventory.logs');
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

        // Invoice Templates
        Route::get('invoice-templates', [InvoiceTemplateController::class, 'index'])->name('invoice-templates.index');
        Route::post('invoice-templates/{id}/activate', [InvoiceTemplateController::class, 'activate'])->name('invoice-templates.activate');
        Route::get('invoices/preview/{templateId}', [InvoiceController::class, 'preview'])->name('invoices.preview');

        // Returns
        Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::patch('returns/{return}', [ReturnController::class, 'update'])->name('returns.update');

        // Activity log
        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

        // Profile
        Route::get('profile', [ProfileController::class, 'show'])->name('profile');
        Route::post('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Settings
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

        // API Token
        Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('settings.api-tokens');
        Route::post('settings/api-tokens/regenerate', [ApiTokenController::class, 'regenerate'])->name('settings.api-tokens.regenerate');

        // WordPress redirect shortcut
        Route::redirect('wordpress', '/admin/integrations/wordpress')->name('wordpress');

        // Integrations: developer docs (custom websites) and Google Sheet setup
        Route::get('integrations/{site}/api-docs', [IntegrationController::class, 'apiDocs'])->name('integrations.api-docs');
        Route::get('integrations/{site}/sheet', [\App\Http\Controllers\Admin\GoogleSheetController::class, 'edit'])->name('integrations.sheet.edit');
        Route::post('integrations/{site}/sheet/columns', [\App\Http\Controllers\Admin\GoogleSheetController::class, 'columns'])->name('integrations.sheet.columns');
        Route::post('integrations/{site}/sheet', [\App\Http\Controllers\Admin\GoogleSheetController::class, 'update'])->name('integrations.sheet.update');
        Route::post('integrations/{site}/sheet/import', [\App\Http\Controllers\Admin\GoogleSheetController::class, 'import'])->name('integrations.sheet.import');

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
            Route::post('/{site}/import-recent-orders', [IntegrationController::class, 'importRecentOrders'])->name('importRecentOrders');
            Route::post('/{site}/force-connect',        [IntegrationController::class, 'forceConnect'])->name('forceConnect');
        });
    });

});
