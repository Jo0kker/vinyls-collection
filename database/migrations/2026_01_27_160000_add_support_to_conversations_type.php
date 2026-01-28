<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: drop the old check constraint and create a new one with 'support'
        DB::statement("ALTER TABLE conversations DROP CONSTRAINT IF EXISTS conversations_type_check");
        DB::statement("ALTER TABLE conversations ADD CONSTRAINT conversations_type_check CHECK (type::text = ANY (ARRAY['direct'::text, 'group'::text, 'support'::text]))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE conversations DROP CONSTRAINT IF EXISTS conversations_type_check");
        DB::statement("ALTER TABLE conversations ADD CONSTRAINT conversations_type_check CHECK (type::text = ANY (ARRAY['direct'::text, 'group'::text]))");
    }
};
