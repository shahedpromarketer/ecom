<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Deleted orders, products, categories and customers go to the Recycle Bin instead of disappearing. */
return new class extends Migration
{
    private array $tables = ['orders', 'products', 'categories', 'customers'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropSoftDeletes();
                $table->dropColumn('deleted_by');
            });
        }
    }
};
