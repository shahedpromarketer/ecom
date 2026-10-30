<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Which store a synced product came from, so the CRM can send its stock back there. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('wordpress_site_id')->nullable()->after('wp_product_id')->index();
        });

        // Existing synced products: the shop's only WooCommerce store, when there is just one.
        $stores = DB::table('wordpress_sites')->whereIn('type', ['woocommerce', 'wordpress'])->get(['id', 'tenant_id'])->groupBy('tenant_id');
        foreach ($stores as $tenantId => $list) {
            if ($list->count() === 1) {
                DB::table('products')->where('tenant_id', $tenantId)->whereNotNull('wp_product_id')->whereNull('wordpress_site_id')
                    ->update(['wordpress_site_id' => $list->first()->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wordpress_site_id');
        });
    }
};
