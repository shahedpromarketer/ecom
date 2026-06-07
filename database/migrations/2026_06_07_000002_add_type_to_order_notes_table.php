<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('order_notes', 'type'))
                $table->string('type')->default('order')->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('order_notes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
