<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_pixels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['facebook_pixel', 'tiktok_pixel', 'google_tag_manager', 'google_analytics', 'microsoft_clarity']);
            $table->text('code');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_pixels');
    }
};
