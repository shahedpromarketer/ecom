<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Ecom Pilot no longer has its own storefront, so these tables have nothing writing to them.
// One-way: rolling back does not bring the storefront tables back.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('banners');
    }

    public function down(): void
    {
        //
    }
};
