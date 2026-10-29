<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** "Failed orders": checkouts where the visitor typed a phone number but did not finish. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomplete_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('wordpress_site_id')->nullable();
            $table->string('session_key', 100);                // one row per visitor checkout
            $table->string('phone', 20)->index();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->json('items')->nullable();                 // [{name, qty, price, variant}]
            $table->decimal('total', 12, 2)->default(0);
            $table->string('page_url', 500)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('status', 20)->default('pending')->index();   // pending | recovered | deleted
            $table->string('delete_reason')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();         // the order that recovered it
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'wordpress_site_id', 'session_key'], 'incomplete_session_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomplete_orders');
    }
};
