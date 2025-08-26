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
        // Fix sequences for all forum tables
        $this->fixSequence('forum_posts');
        $this->fixSequence('forum_threads');
        $this->fixSequence('forum_categories');
        $this->fixSequence('forum_threads_read');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to revert
    }

    /**
     * Fix the sequence for a given table
     */
    private function fixSequence(string $tableName, string $columnName = 'id'): void
    {
        // Only run for PostgreSQL
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        try {
            // Get the sequence name
            $sequenceResult = DB::selectOne("
                SELECT pg_get_serial_sequence(?, ?) as sequence_name
            ", [$tableName, $columnName]);

            $sequenceName = $sequenceResult->sequence_name;

            if (!$sequenceName) {
                // Try the common naming pattern
                $sequenceName = 'public.' . $tableName . '_' . $columnName . '_seq';
            }

            // Get the maximum ID from the table
            $maxId = DB::table($tableName)->max($columnName) ?? 0;

            if ($maxId > 0) {
                // Set the sequence to start from max_id + 1
                $nextVal = $maxId + 1;
                DB::statement("ALTER SEQUENCE {$sequenceName} RESTART WITH {$nextVal}");
                
                // Also ensure the sequence is owned by the correct column
                DB::statement("ALTER SEQUENCE {$sequenceName} OWNED BY {$tableName}.{$columnName}");
                
                echo "Fixed sequence for {$tableName}: next value will be {$nextVal}\n";
            }
        } catch (\Exception $e) {
            // Log but don't fail the migration
            echo "Warning: Could not fix sequence for {$tableName}: " . $e->getMessage() . "\n";
        }
    }
};
