<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Takes part in automatic order distribution.
            $table->boolean('receives_orders')->default(false)->after('role_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');
            $table->index(['tenant_id', 'assigned_to', 'status'], 'idx_orders_tenant_assignee_status');
        });

        // Who sees every order. Order Executives keep to their own (that is what distribution is for).
        foreach (['admin', 'manager', 'support', 'inventory'] as $name) {
            $role = DB::table('roles')->whereNull('tenant_id')->where('name', $name)->first();
            if (! $role) {
                continue;
            }
            $perms = json_decode($role->permissions ?? '[]', true) ?: [];
            if (! in_array('view_all_orders', $perms, true)) {
                $perms[] = 'view_all_orders';
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms)]);
            }
        }

        // Shop-made roles that could already see orders keep seeing all of them,
        // so nothing disappears for existing staff after this update.
        DB::table('roles')->whereNotNull('tenant_id')->orderBy('id')->each(function ($role) {
            $perms = json_decode($role->permissions ?? '[]', true) ?: [];
            if (in_array('view_orders', $perms, true) && ! in_array('view_all_orders', $perms, true)) {
                $perms[] = 'view_all_orders';
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_tenant_assignee_status');
            $table->dropColumn('assigned_at');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('receives_orders');
        });
    }
};
