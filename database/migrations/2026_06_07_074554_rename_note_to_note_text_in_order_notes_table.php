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
        Schema::table('order_notes', function (Blueprint $table) {
            if (Schema::hasColumn('order_notes', 'note') && ! Schema::hasColumn('order_notes', 'note_text')) {
                $table->renameColumn('note', 'note_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_notes', function (Blueprint $table) {
            if (Schema::hasColumn('order_notes', 'note_text') && ! Schema::hasColumn('order_notes', 'note')) {
                $table->renameColumn('note_text', 'note');
            }
        });
    }
};
