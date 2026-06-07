<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_logs', 'action_performed'))
                $table->string('action_performed')->nullable()->after('action');
            if (!Schema::hasColumn('activity_logs', 'old_data'))
                $table->json('old_data')->nullable()->after('properties');
            if (!Schema::hasColumn('activity_logs', 'updated_data'))
                $table->json('updated_data')->nullable()->after('old_data');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['action_performed', 'old_data', 'updated_data']);
        });
    }
};
