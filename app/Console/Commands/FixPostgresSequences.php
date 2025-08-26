<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPostgresSequences extends Command
{
    protected $signature = 'db:fix-sequences {--table=}';
    protected $description = 'Resynchronize PostgreSQL sequences with the current maximum IDs in tables';

    public function handle()
    {
        $this->info('🔧 Fixing PostgreSQL sequences...');

        $tableName = $this->option('table');

        if ($tableName) {
            $this->fixSequenceForTable($tableName);
        } else {
            $this->fixAllSequences();
        }

        $this->info('✅ Sequences fixed successfully!');
        return 0;
    }

    private function fixAllSequences()
    {
        // Get all tables with serial/identity columns
        $tables = DB::select("
            SELECT 
                t.table_name,
                c.column_name,
                pg_get_serial_sequence(t.table_schema || '.' || t.table_name, c.column_name) as sequence_name
            FROM information_schema.tables t
            JOIN information_schema.columns c 
                ON t.table_name = c.table_name 
                AND t.table_schema = c.table_schema
            WHERE 
                t.table_schema = 'public' 
                AND t.table_type = 'BASE TABLE'
                AND c.column_default LIKE 'nextval%'
            ORDER BY t.table_name
        ");

        if (empty($tables)) {
            $this->warn('No tables with sequences found.');
            return;
        }

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        foreach ($tables as $table) {
            $this->fixSequenceForTable($table->table_name, $table->column_name, $table->sequence_name);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
    }

    private function fixSequenceForTable($tableName, $columnName = 'id', $sequenceName = null)
    {
        try {
            // If no sequence name provided, try to get it
            if (!$sequenceName) {
                $result = DB::selectOne("
                    SELECT pg_get_serial_sequence(?, ?) as sequence_name
                ", [$tableName, $columnName]);
                
                $sequenceName = $result->sequence_name;
            }

            if (!$sequenceName) {
                // Try common naming pattern
                $sequenceName = $tableName . '_' . $columnName . '_seq';
                
                // Check if this sequence exists
                $exists = DB::selectOne("
                    SELECT EXISTS (
                        SELECT 1 FROM pg_class 
                        WHERE relkind = 'S' 
                        AND relname = ?
                    ) as exists
                ", [$sequenceName]);
                
                if (!$exists->exists) {
                    $this->warn("⚠️  No sequence found for table: {$tableName}");
                    return;
                }
            }

            // Get the current maximum ID from the table
            $maxId = DB::table($tableName)->max($columnName) ?? 0;

            // Get current sequence value
            $currentVal = DB::selectOne("SELECT last_value FROM {$sequenceName}")->last_value ?? 0;

            $this->info("📊 Table: {$tableName}");
            $this->info("   Current max ID: {$maxId}");
            $this->info("   Current sequence value: {$currentVal}");

            if ($maxId >= $currentVal) {
                // Reset the sequence to max_id + 1
                $newVal = $maxId + 1;
                DB::statement("ALTER SEQUENCE {$sequenceName} RESTART WITH {$newVal}");
                $this->info("   ✅ Sequence updated to: {$newVal}");
            } else {
                $this->info("   ✓ Sequence is already correct");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error fixing sequence for table {$tableName}: " . $e->getMessage());
        }
    }
}