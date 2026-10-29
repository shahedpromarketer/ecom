<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles: system roles stay shared (tenant_id NULL); custom roles belong to one shop.
        // Role names only need to be unique inside a shop.
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
            $table->dropUnique(['name']);
            $table->unique(['tenant_id', 'name']);
        });

        // API tokens belong to the shop of the user who created them.
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
        });

        DB::table('api_tokens')->orderBy('id')->each(function ($token) {
            DB::table('api_tokens')->where('id', $token->id)->update([
                'tenant_id' => DB::table('users')->where('id', $token->created_by)->value('tenant_id'),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'name']);
            $table->dropColumn('tenant_id');
            $table->unique('name');
        });
    }
};
