<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The last delivery history each courier gave for a customer's number, kept per shop,
// so it can still be shown if a courier stops sharing it (like RedX did).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('phone', 11);
            $table->string('courier', 20);
            $table->unsignedInteger('total')->nullable();
            $table->unsignedInteger('success')->nullable();
            $table->unsignedInteger('failed')->nullable();
            $table->string('rating', 40)->nullable();
            $table->unsignedInteger('reports')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->unique(['tenant_id', 'phone', 'courier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_scores');
    }
};
