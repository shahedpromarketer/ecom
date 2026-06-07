<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'wp_product_id')) {
                $table->string('wp_product_id')->nullable()->index()->after('id');
            }
            if (! Schema::hasColumn('products', 'regular_price')) {
                $table->decimal('regular_price', 10, 2)->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumnIfExists('wp_product_id');
            $table->dropColumnIfExists('regular_price');
        });
    }
};
