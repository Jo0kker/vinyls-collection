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
        // Enable pg_trgm extension for ILIKE searches (if not already enabled)
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // GIN indexes for ILIKE pattern matching - much faster than seq scan
        DB::statement('CREATE INDEX vinyls_vinyl_nom_trgm_idx ON vinyls USING GIN (vinyl_nom gin_trgm_ops)');
        DB::statement('CREATE INDEX vinyls_artiste_trgm_idx ON vinyls USING GIN (artiste gin_trgm_ops)');
        DB::statement('CREATE INDEX vinyls_label_trgm_idx ON vinyls USING GIN (label gin_trgm_ops)');

        // B-tree indexes for sorting and exact matches
        Schema::table('vinyls', function (Blueprint $table) {
            $table->index('vinyl_nom', 'vinyls_vinyl_nom_idx');
            $table->index('artiste', 'vinyls_artiste_idx');
            $table->index('annee', 'vinyls_annee_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS vinyls_vinyl_nom_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS vinyls_artiste_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS vinyls_label_trgm_idx');

        Schema::table('vinyls', function (Blueprint $table) {
            $table->dropIndex('vinyls_vinyl_nom_idx');
            $table->dropIndex('vinyls_artiste_idx');
            $table->dropIndex('vinyls_annee_idx');
        });
    }
};
