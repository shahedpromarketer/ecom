<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Shops only send the amount and a screenshot now; the transaction ID is optional.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_recharge_requests', function (Blueprint $table) {
            $table->string('transaction_id', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Leave it nullable: old rows may have no transaction ID.
    }
};
