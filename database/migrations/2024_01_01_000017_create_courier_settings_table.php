<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('courier', ['pathao', 'steadfast', 'carrybe', 'redx', 'paperfly'])->unique();
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('base_url')->nullable();
            $table->string('webhook_url')->nullable();
            $table->json('extra_config')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_settings');
    }
};
