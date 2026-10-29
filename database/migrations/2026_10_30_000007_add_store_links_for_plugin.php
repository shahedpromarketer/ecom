<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Links CRM orders/products to the WooCommerce store they came from (Ecom Pilot plugin v2). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Was ENUM(cod, bkash, nagad): rocket/card (offered in the order form) and online
            // gateways from WooCommerce could not be saved.
            $table->string('payment_method', 30)->default('cod')->change();
            $table->unsignedBigInteger('wordpress_site_id')->nullable()->after('source_website');
            $table->index(['wordpress_site_id', 'source_order_id'], 'idx_orders_site_source');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_variation_id')->nullable()->after('sku')->index();
        });

        Schema::table('wordpress_sites', function (Blueprint $table) {
            $table->json('status_map')->nullable();          // CRM status => WooCommerce status (null = defaults)
            $table->string('rest_url')->nullable();          // the plugin's REST base, reported on connect
            $table->timestamp('last_seen_at')->nullable();   // last signed request from the plugin
        });
    }

    public function down(): void
    {
        Schema::table('wordpress_sites', function (Blueprint $table) {
            $table->dropColumn(['status_map', 'rest_url', 'last_seen_at']);
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('wp_variation_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_site_source');
            $table->dropColumn('wordpress_site_id');
        });
    }
};
