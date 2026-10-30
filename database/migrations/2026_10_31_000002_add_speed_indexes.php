<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Indexes for the queries every page runs: one shop's rows by status and newest first. */
return new class extends Migration
{
    private const INDEXES = [
        'orders'            => [['tenant_id', 'status', 'created_at'], ['tenant_id', 'created_at'], ['tenant_id', 'shipping_phone']],
        'incomplete_orders' => [['tenant_id', 'status', 'last_activity_at']],
        'courier_shipments' => [['tenant_id', 'status'], ['tenant_id', 'courier_name', 'status']],
        'activity_logs'     => [['tenant_id', 'created_at']],
        'customers'         => [['tenant_id', 'last_order_at']],
        'sync_logs'         => [['site_id', 'created_at']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $sets) {
            Schema::table($table, function (Blueprint $t) use ($table, $sets) {
                foreach ($sets as $cols) {
                    $t->index($cols, $table . '_' . implode('_', $cols) . '_speed');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $sets) {
            Schema::table($table, function (Blueprint $t) use ($table, $sets) {
                foreach ($sets as $cols) {
                    $t->dropIndex($table . '_' . implode('_', $cols) . '_speed');
                }
            });
        }
    }
};
