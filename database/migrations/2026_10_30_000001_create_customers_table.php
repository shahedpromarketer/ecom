<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('phone', 20);                     // normalized 01XXXXXXXXX
            $table->string('name')->nullable();
            $table->string('alt_phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('tag', 50)->nullable();           // e.g. loyal, vip, fraud
            $table->boolean('is_blocked')->default(false);
            $table->unsignedBigInteger('assigned_to')->nullable()->index();

            // Kept up to date from the customer's orders (App\Services\CustomerSync).
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('delivered_orders')->default(0);
            $table->unsignedInteger('cancelled_orders')->default(0);
            $table->unsignedInteger('returned_orders')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);   // delivered/completed orders only
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable()->index();

            $table->timestamps();

            $table->unique(['tenant_id', 'phone']);
        });

        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note');
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('user_id')->index();
        });

        $this->backfill();
    }

    /** One customer per (shop, phone) from existing orders, then link the orders. */
    private function backfill(): void
    {
        $normalize = fn ($p) => \App\Support\Phone::normalize($p);

        DB::table('orders')
            ->whereNotNull('tenant_id')
            ->whereNotNull('shipping_phone')
            ->orderBy('id')
            ->chunkById(500, function ($orders) use ($normalize) {
                foreach ($orders as $order) {
                    $phone = $normalize($order->shipping_phone);
                    if ($phone === '') {
                        continue;
                    }

                    $customerId = DB::table('customers')
                        ->where('tenant_id', $order->tenant_id)
                        ->where('phone', $phone)
                        ->value('id');

                    if (! $customerId) {
                        $customerId = DB::table('customers')->insertGetId([
                            'tenant_id'  => $order->tenant_id,
                            'phone'      => $phone,
                            'name'       => $order->shipping_name,
                            'alt_phone'  => $order->alt_phone ?? null,
                            'email'      => $order->email ?? null,
                            'address'    => $order->shipping_address,
                            'district'   => $order->shipping_district,
                            'created_at' => $order->created_at ?? now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        // Later orders carry the most recent name/address.
                        DB::table('customers')->where('id', $customerId)->update(array_filter([
                            'name'     => $order->shipping_name,
                            'address'  => $order->shipping_address,
                            'district' => $order->shipping_district,
                        ]));
                    }

                    DB::table('orders')->where('id', $order->id)->update(['customer_id' => $customerId]);
                }
            });

        DB::table('customers')->orderBy('id')->chunkById(500, function ($customers) {
            foreach ($customers as $c) {
                \App\Services\CustomerSync::recalculateById($c->id);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });
        Schema::dropIfExists('customer_notes');
        Schema::dropIfExists('customers');
    }
};
