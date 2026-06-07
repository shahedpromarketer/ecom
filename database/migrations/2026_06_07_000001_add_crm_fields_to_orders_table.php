<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'source_website'))
                $table->string('source_website')->nullable()->after('source_url');
            if (!Schema::hasColumn('orders', 'courier_charge'))
                $table->decimal('courier_charge', 10, 2)->default(0)->after('shipping_charge');
            if (!Schema::hasColumn('orders', 'discount_type'))
                $table->enum('discount_type', ['fixed', 'percent'])->default('fixed')->after('discount');
            if (!Schema::hasColumn('orders', 'paid_amount'))
                $table->decimal('paid_amount', 10, 2)->default(0)->after('total');
            if (!Schema::hasColumn('orders', 'reference_no'))
                $table->string('reference_no')->nullable()->after('order_tag');
            if (!Schema::hasColumn('orders', 'skip_followup'))
                $table->boolean('skip_followup')->default(false)->after('reference_no');
            if (!Schema::hasColumn('orders', 'user_ip'))
                $table->string('user_ip')->nullable()->after('skip_followup');
            if (!Schema::hasColumn('orders', 'alt_phone'))
                $table->string('alt_phone')->nullable()->after('shipping_phone');
            if (!Schema::hasColumn('orders', 'email'))
                $table->string('email')->nullable()->after('alt_phone');
            if (!Schema::hasColumn('orders', 'courier_note'))
                $table->text('courier_note')->nullable()->after('notes');
            if (!Schema::hasColumn('orders', 'packing_note'))
                $table->text('packing_note')->nullable()->after('courier_note');
            if (!Schema::hasColumn('orders', 'customer_tag'))
                $table->string('customer_tag')->nullable()->after('order_tag');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'source_website', 'courier_charge', 'discount_type', 'paid_amount',
                'reference_no', 'skip_followup', 'user_ip', 'alt_phone', 'email',
                'courier_note', 'packing_note', 'customer_tag',
            ]);
        });
    }
};
