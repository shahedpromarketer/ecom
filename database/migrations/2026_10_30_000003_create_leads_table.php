<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();  // pending | confirmed | canceled
            $table->string('source', 40)->nullable();
            $table->string('tag', 50)->nullable();
            $table->boolean('is_star')->default(false);
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('interest')->nullable();       // what they asked about
            $table->text('note')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->timestamp('read_at')->nullable();   // null = new / unread
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at']);
        });

        // Managers see every lead; other staff work the leads assigned to them.
        foreach (['manager', 'admin'] as $name) {
            $role = DB::table('roles')->whereNull('tenant_id')->where('name', $name)->first();
            if (! $role) {
                continue;
            }
            $perms = json_decode($role->permissions ?? '[]', true) ?: [];
            if (! in_array('view_all_leads', $perms, true)) {
                $perms[] = 'view_all_leads';
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms)]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
