<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Single column indexes
            $table->index('status',         'idx_orders_status');
            $table->index('shipping_phone', 'idx_orders_phone');
            $table->index('created_at',     'idx_orders_created_at');
            $table->index('payment_status', 'idx_orders_payment_status');
            $table->index('source',         'idx_orders_source');

            // Composite: sidebar counts (status + created_at for sorting)
            $table->index(['status', 'created_at'], 'idx_orders_status_created');

            // Composite: incomplete orders filter
            $table->index(['status', 'payment_status', 'source'], 'idx_orders_incomplete');

            // Composite: duplicate detection bulk query
            $table->index(['shipping_phone', 'status'], 'idx_orders_phone_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_status');
            $table->dropIndex('idx_orders_phone');
            $table->dropIndex('idx_orders_created_at');
            $table->dropIndex('idx_orders_payment_status');
            $table->dropIndex('idx_orders_source');
            $table->dropIndex('idx_orders_status_created');
            $table->dropIndex('idx_orders_incomplete');
            $table->dropIndex('idx_orders_phone_status');
        });
    }
};
