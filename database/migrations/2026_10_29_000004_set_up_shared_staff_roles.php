<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Shared starter roles every shop can assign to staff. Shops can create their own roles too.
 * Users: role = 'admin' (shop owner, full access) or 'staff' (permissions from role_id).
 */
return new class extends Migration
{
    private array $roles = [
        'admin' => ['Administrator', [
            'view_orders', 'manage_orders', 'export_orders', 'view_products', 'manage_products',
            'view_customers', 'manage_customers', 'ban_customers', 'manage_marketing',
            'view_reports', 'export_reports', 'manage_settings', 'manage_staff',
        ]],
        'manager' => ['Manager', [
            'view_orders', 'manage_orders', 'export_orders', 'view_products', 'manage_products',
            'view_customers', 'manage_customers', 'ban_customers', 'view_reports', 'export_reports',
        ]],
        'order_executive' => ['Order Executive', ['view_orders', 'manage_orders', 'view_products', 'view_customers']],
        'support' => ['Support (view only)', ['view_orders', 'view_customers']],
        'inventory' => ['Inventory Staff', ['view_products', 'manage_products', 'view_orders']],
    ];

    public function up(): void
    {
        // Was ENUM('admin','customer'), which cannot hold 'staff'.
        \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('role', 20)->default('staff')->change();
        });

        foreach ($this->roles as $name => [$display, $permissions]) {
            $existing = DB::table('roles')->whereNull('tenant_id')->where('name', $name)->first();
            $values = ['display_name' => $display, 'permissions' => json_encode($permissions), 'is_system' => true, 'updated_at' => now()];

            $existing
                ? DB::table('roles')->where('id', $existing->id)->update($values)
                : DB::table('roles')->insert(['name' => $name, 'tenant_id' => null, 'created_at' => now()] + $values);
        }

        // No storefront any more, so no "customer" role.
        DB::table('roles')->whereNull('tenant_id')->where('name', 'customer')->delete();

        // Old staff accounts used role = manager/support with no role_id; move them to the new model.
        foreach (['manager', 'support'] as $old) {
            $roleId = DB::table('roles')->whereNull('tenant_id')->where('name', $old)->value('id');
            DB::table('users')->where('role', $old)->update(['role' => 'staff', 'role_id' => $roleId]);
        }

        // Anyone else who is not an admin but already has a role_id is staff.
        DB::table('users')->whereNotIn('role', ['admin', 'staff'])->whereNotNull('role_id')->update(['role' => 'staff']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'staff')->update(['role' => 'customer']);
    }
};
