<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite stores enums as TEXT and doesn't enforce constraints,
        // so all courier values (carrybe, redx, paperfly) are already accepted.
        // No schema change needed for SQLite.
    }

    public function down(): void
    {
        // nothing to reverse
    }
};
