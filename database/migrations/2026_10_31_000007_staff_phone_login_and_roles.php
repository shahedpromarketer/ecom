<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff can sign in with an email or a mobile number (email is optional now),
 * and two more starter roles: Sales Executive (renamed Order Executive) and Packing & Dispatch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->index('phone');
        });

        // Store numbers the same way (01XXXXXXXXX) so they can be used to sign in.
        foreach (DB::table('users')->whereNotNull('phone')->get(['id', 'phone']) as $u) {
            DB::table('users')->where('id', $u->id)->update(['phone' => \App\Models\User::normalizePhone($u->phone)]);
        }

        DB::table('roles')->whereNull('tenant_id')->where('name', 'order_executive')
            ->update(['display_name' => 'Sales Executive', 'updated_at' => now()]);

        if (! DB::table('roles')->whereNull('tenant_id')->where('name', 'packing')->exists()) {
            DB::table('roles')->insert([
                'name' => 'packing', 'tenant_id' => null, 'display_name' => 'Packing & Dispatch', 'is_system' => true,
                'permissions' => json_encode(['view_orders', 'view_all_orders', 'manage_orders', 'view_products']),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropIndex(['phone']));
        DB::table('roles')->whereNull('tenant_id')->where('name', 'order_executive')->update(['display_name' => 'Order Executive']);
    }
};
