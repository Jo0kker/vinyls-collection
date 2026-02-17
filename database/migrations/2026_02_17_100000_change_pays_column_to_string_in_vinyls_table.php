<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change pays from integer to string to store country names instead of numeric codes
        DB::statement('ALTER TABLE vinyls ALTER COLUMN pays TYPE VARCHAR(255) USING pays::VARCHAR(255)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to integer - values that can't be converted will become null
        DB::statement('ALTER TABLE vinyls ALTER COLUMN pays TYPE INTEGER USING NULLIF(pays, \'\')::INTEGER');
    }
};
