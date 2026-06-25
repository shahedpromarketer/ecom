<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_settings', function (Blueprint $table) {
            $table->dropUnique('courier_settings_courier_unique');
            $table->unique(['courier', 'tenant_id']);
        });

        // Also fix blacklisted_phones unique on phone — make it per tenant
        Schema::table('blacklisted_phones', function (Blueprint $table) {
            try {
                $table->dropUnique(['phone']);
            } catch (\Exception $e) {
                // may not exist
            }
            $table->unique(['phone', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('courier_settings', function (Blueprint $table) {
            $table->dropUnique(['courier', 'tenant_id']);
            $table->unique('courier');
        });

        Schema::table('blacklisted_phones', function (Blueprint $table) {
            $table->dropUnique(['phone', 'tenant_id']);
            $table->unique('phone');
        });
    }
};
