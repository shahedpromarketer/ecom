<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->enum('status', ['active', 'suspended', 'expired'])->default('active');
            $table->enum('plan', ['basic', 'pro', 'enterprise'])->default('basic');
            $table->timestamp('plan_expires_at')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->integer('max_orders')->default(500);
            $table->integer('max_users')->default(2);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tenants'); }
};
