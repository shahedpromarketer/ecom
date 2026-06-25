<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $tables = [
            'orders',
            'products',
            'categories',
            'order_notes',
            'activity_logs',
            'coupons',
            'banners',
            'order_returns',
            'invoices',
            'courier_shipments',
            'marketing_pixels',
            'courier_settings',
            'wordpress_sites',
            'blacklisted_phones',
            'invoice_templates',
            'sync_logs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('tenant_id')->nullable()->index();
                });
            }
        }
    }

    public function down(): void {
        $tables = [
            'orders',
            'products',
            'categories',
            'order_notes',
            'activity_logs',
            'coupons',
            'banners',
            'order_returns',
            'invoices',
            'courier_shipments',
            'marketing_pixels',
            'courier_settings',
            'wordpress_sites',
            'blacklisted_phones',
            'invoice_templates',
            'sync_logs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('tenant_id');
                });
            }
        }
    }
};
