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
        if (\DB::getDriverName() === 'sqlite') {
            Schema::table('invoice_templates', fn (Blueprint $t) => $t->string('size')->change());
            return;
        }
        \DB::statement("ALTER TABLE invoice_templates MODIFY COLUMN size ENUM('small','medium','large','2inch','3inch') NOT NULL");
    }

    public function down(): void
    {
        if (\DB::getDriverName() === 'sqlite') { return; }
        \DB::statement("ALTER TABLE invoice_templates MODIFY COLUMN size ENUM('small','medium','large') NOT NULL");
    }
};
