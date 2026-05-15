<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source')->nullable()->after('notes');
            $table->string('source_url')->nullable()->after('source');
            $table->string('source_order_id')->nullable()->after('source_url');
            $table->string('utm_source')->nullable()->after('source_order_id');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
            $table->string('order_tag')->nullable()->after('utm_campaign');
            $table->enum('print_status', ['unprinted', 'printed'])->default('unprinted')->after('order_tag');
            $table->string('hold_reason')->nullable()->after('print_status');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('hold_reason');
            $table->boolean('cod_changed')->default(false)->after('assigned_to');
            $table->boolean('is_duplicate')->default(false)->after('cod_changed');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn([
                'source', 'source_url', 'source_order_id',
                'utm_source', 'utm_medium', 'utm_campaign',
                'order_tag', 'print_status', 'hold_reason',
                'assigned_to', 'cod_changed', 'is_duplicate',
            ]);
        });
    }
};
