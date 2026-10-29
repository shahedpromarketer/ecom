<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wordpress_sites', function (Blueprint $table) {
            // Custom websites: where to POST order status changes (optional).
            $table->string('webhook_url')->nullable();
            // Type-specific settings, e.g. Google Sheet URL, tab and column mapping.
            $table->json('settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wordpress_sites', function (Blueprint $table) {
            $table->dropColumn(['webhook_url', 'settings']);
        });
    }
};
