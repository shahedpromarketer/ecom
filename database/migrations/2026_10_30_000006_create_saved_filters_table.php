<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('page', 40);              // orders | customers | leads | followups
            $table->string('name', 100);
            $table->text('query');                    // the URL query string, e.g. status=pending&source=web
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_filters');
    }
};
