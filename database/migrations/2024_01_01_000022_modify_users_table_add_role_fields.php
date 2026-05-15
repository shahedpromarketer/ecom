<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            $table->boolean('is_banned')->default(false)->after('remember_token');
            $table->string('ban_reason')->nullable()->after('is_banned');
            $table->timestamp('last_login_at')->nullable()->after('ban_reason');
            $table->integer('order_cancel_count')->default(0)->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'is_banned', 'ban_reason', 'last_login_at', 'order_cancel_count']);
        });
    }
};
