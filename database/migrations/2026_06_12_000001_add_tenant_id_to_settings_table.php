<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('group');
        });

        // Drop the old single-column unique on `key`
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
        });

        // Add composite unique so each tenant can have their own value per key
        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['key', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key', 'tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->unique('key');
        });
    }
};
