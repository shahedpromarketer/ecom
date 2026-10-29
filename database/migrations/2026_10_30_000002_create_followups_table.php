<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->date('scheduled_for')->index();
            $table->string('status', 20)->default('listed')->index();   // listed | done | negative
            $table->string('tag', 40)->nullable();                     // why we call: reorder, feedback, …
            $table->text('note')->nullable();                          // what to talk about
            $table->text('result_note')->nullable();                   // what happened on the call
            $table->timestamp('done_at')->nullable();
            $table->unsignedBigInteger('done_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'scheduled_for']);
        });

        // Managers should see everyone's follow-ups; support stays on their own list.
        $manager = DB::table('roles')->whereNull('tenant_id')->where('name', 'manager')->first();
        if ($manager) {
            $perms = json_decode($manager->permissions ?? '[]', true) ?: [];
            if (! in_array('view_all_followups', $perms, true)) {
                $perms[] = 'view_all_followups';
                DB::table('roles')->where('id', $manager->id)->update(['permissions' => json_encode($perms)]);
            }
        }
        $admin = DB::table('roles')->whereNull('tenant_id')->where('name', 'admin')->first();
        if ($admin) {
            $perms = json_decode($admin->permissions ?? '[]', true) ?: [];
            if (! in_array('view_all_followups', $perms, true)) {
                $perms[] = 'view_all_followups';
                DB::table('roles')->where('id', $admin->id)->update(['permissions' => json_encode($perms)]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('followups');
    }
};
