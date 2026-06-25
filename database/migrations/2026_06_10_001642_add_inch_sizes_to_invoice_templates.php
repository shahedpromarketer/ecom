<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \DB::statement("ALTER TABLE invoice_templates MODIFY COLUMN size ENUM('small','medium','large','2inch','3inch') NOT NULL");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE invoice_templates MODIFY COLUMN size ENUM('small','medium','large') NOT NULL");
    }
};
