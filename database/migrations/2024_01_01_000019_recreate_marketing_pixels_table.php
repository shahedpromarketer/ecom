<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('marketing_pixels');
        Schema::create('marketing_pixels', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['facebook', 'google_analytics', 'tiktok', 'google_tag_manager', 'microsoft_clarity'])->unique();
            $table->string('pixel_id')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('test_code')->nullable();
            $table->text('head_code')->nullable();
            $table->text('body_code')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_pixels');
    }
};
