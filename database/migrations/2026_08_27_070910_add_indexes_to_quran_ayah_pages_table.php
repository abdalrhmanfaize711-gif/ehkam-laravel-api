<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Indexes already exist on quran_ayah_pages.
        // Nothing to do.
    }

    public function down(): void
    {
        // Nothing to rollback.
    }
};