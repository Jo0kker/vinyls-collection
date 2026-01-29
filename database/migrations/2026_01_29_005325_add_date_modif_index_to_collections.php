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
        // Composite index for the common query pattern:
        // WHERE user_id = ? AND visibility = ? ORDER BY collection_date_modif DESC
        // Using raw SQL to specify DESC order on the date column
        DB::statement('CREATE INDEX collections_user_visibility_date_idx ON collections (user_id, visibility, collection_date_modif DESC)');

        // Also add a simple index on collection_date_modif for other queries
        Schema::table('collections', function (Blueprint $table) {
            $table->index('collection_date_modif', 'collections_date_modif_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS collections_user_visibility_date_idx');

        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex('collections_date_modif_idx');
        });
    }
};
