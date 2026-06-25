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
        Schema::table('wordpress_sites', function (Blueprint $table) {
            $table->boolean('plugin_connected')->default(false)->after('is_active');
            $table->timestamp('plugin_connected_at')->nullable()->after('plugin_connected');
            $table->string('plugin_version')->nullable()->after('plugin_connected_at');
        });
    }

    public function down(): void
    {
        Schema::table('wordpress_sites', function (Blueprint $table) {
            $table->dropColumn(['plugin_connected', 'plugin_connected_at', 'plugin_version']);
        });
    }
};
