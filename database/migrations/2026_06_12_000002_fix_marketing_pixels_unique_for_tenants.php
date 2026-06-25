<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_pixels', function (Blueprint $table) {
            $table->dropUnique(['platform']);
            $table->unique(['platform', 'tenant_id']);
        });

        // Assign existing pixels (tenant_id = null) to Tenant 1
        \DB::table('marketing_pixels')->whereNull('tenant_id')->update(['tenant_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('marketing_pixels', function (Blueprint $table) {
            $table->dropUnique(['platform', 'tenant_id']);
            $table->unique('platform');
        });
    }
};
