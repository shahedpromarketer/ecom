<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        \DB::statement("ALTER TABLE invoice_templates MODIFY COLUMN size ENUM('small','medium','large','2inch','3inch','3x2','3x3','3x4','4x4') NOT NULL");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE invoice_templates MODIFY COLUMN size ENUM('small','medium','large','2inch','3inch') NOT NULL");
    }
};
