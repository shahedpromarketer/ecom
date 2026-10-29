<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Two shops may both have a "jacket" category (products got the same fix earlier). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_slug_unique');
            $table->unique(['slug', 'tenant_id'], 'categories_slug_tenant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_slug_tenant_unique');
            $table->unique('slug', 'categories_slug_unique');
        });
    }
};
