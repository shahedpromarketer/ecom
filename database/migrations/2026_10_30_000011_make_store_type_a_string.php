<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The old enum did not allow custom websites or Google Sheets. */
    public function up(): void
    {
        Schema::table('wordpress_sites', function (Blueprint $table) {
            $table->string('type', 30)->default('woocommerce')->change();
        });
    }

    public function down(): void
    {
        // Left as a string: new store types may already be saved.
    }
};
