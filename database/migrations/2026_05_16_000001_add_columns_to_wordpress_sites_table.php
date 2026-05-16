<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wordpress_sites', function (Blueprint $table) {
            $table->enum('type', ['wordpress', 'woocommerce', 'cartflow', 'landing_page'])
                  ->default('wordpress')->after('name');
            $table->string('consumer_key')->nullable()->after('site_url');
            $table->string('consumer_secret')->nullable()->after('consumer_key');
            $table->string('mobile_number')->nullable()->after('consumer_secret');
            $table->string('invoice_title')->nullable()->after('mobile_number');
            $table->text('invoice_address')->nullable()->after('invoice_title');
            $table->string('logo')->nullable()->after('invoice_address');
            $table->timestamp('last_product_sync_at')->nullable()->after('last_sync_at');
            $table->timestamp('last_order_sync_at')->nullable()->after('last_product_sync_at');
            $table->unsignedInteger('total_products_synced')->default(0)->after('total_synced');
        });
    }

    public function down(): void
    {
        Schema::table('wordpress_sites', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'consumer_key', 'consumer_secret', 'mobile_number',
                'invoice_title', 'invoice_address', 'logo',
                'last_product_sync_at', 'last_order_sync_at', 'total_products_synced',
            ]);
        });
    }
};
