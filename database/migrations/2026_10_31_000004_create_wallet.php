<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepaid wallet: each shop recharges (payment screenshot, approved by us) and every new
 * order costs a small fee. At zero balance orders still arrive but order work is locked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->decimal('order_charge', 8, 2)->default(0);   // per new order; 0 = free shop (new shops get config wallet.order_charge)
        });
        // Shops that exist already (ours / demo) stay free until we set a price for them.
        DB::table('tenants')->update(['order_charge' => 0]);

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('type', 20);              // recharge | order | adjustment
            $table->decimal('amount', 12, 2);        // + adds, − takes
            $table->decimal('balance_after', 12, 2);
            $table->foreignId('order_id')->nullable()->unique();   // one charge per order, ever
            $table->foreignId('recharge_request_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('wallet_recharge_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->decimal('amount', 12, 2);
            $table->string('method', 20);            // bkash | nagad | rocket | bank | other
            $table->string('transaction_id', 100);
            $table->string('sender', 100)->nullable();   // number / account it was paid from
            $table->string('screenshot_path')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('pending');   // pending | approved | rejected
            $table->string('reject_reason', 255)->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_recharge_requests');
        Schema::dropIfExists('wallet_transactions');
        Schema::table('tenants', fn (Blueprint $t) => $t->dropColumn(['wallet_balance', 'order_charge']));
    }
};
