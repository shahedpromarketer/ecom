<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The UI offers more statuses (damage, hand_delivery, others, …) and payment states
            // than the old ENUMs allowed, so saving them failed. Shops will also get custom statuses.
            $table->string('status', 40)->default('pending')->change();
            $table->string('payment_status', 30)->default('unpaid')->change();

            // Two shops can legitimately have the same order number (e.g. both import WooCommerce #555).
            $table->dropUnique(['order_number']);
            $table->unique(['tenant_id', 'order_number']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->unique(['tenant_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'invoice_number']);
            $table->unique('invoice_number');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'order_number']);
            $table->unique('order_number');
        });
    }
};
