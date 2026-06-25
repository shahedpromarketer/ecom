<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the global unique index on slug (blocks multi-tenant sync)
            $table->dropUnique('products_slug_unique');

            // Add composite unique index: same slug allowed across different tenants
            $table->unique(['slug', 'tenant_id'], 'products_slug_tenant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_tenant_unique');
            $table->unique('slug', 'products_slug_unique');
        });
    }
};
