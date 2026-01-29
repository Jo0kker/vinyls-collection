<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index on collection_vinyls for vinyl lookups (used in vinyl show page)
        Schema::table('collection_vinyls', function (Blueprint $table) {
            $table->index('vinyl_id', 'cv_vinyl_id_idx');
            $table->index('collection_id', 'cv_collection_id_idx');
            $table->index('user_id', 'cv_user_id_idx');
            // Composite index for common queries
            $table->index(['vinyl_id', 'collection_id'], 'cv_vinyl_collection_idx');
            $table->index(['user_id', 'collection_id'], 'cv_user_collection_idx');
        });

        // Index on collections for visibility filtering
        Schema::table('collections', function (Blueprint $table) {
            $table->index('visibility', 'collections_visibility_idx');
            $table->index('user_id', 'collections_user_id_idx');
            // Composite for common query pattern
            $table->index(['user_id', 'visibility'], 'collections_user_visibility_idx');
        });

        // Index on users for profile_public filtering
        Schema::table('users', function (Blueprint $table) {
            $table->index('profile_public', 'users_profile_public_idx');
        });

        // Index on vinyls for common lookups
        Schema::table('vinyls', function (Blueprint $table) {
            $table->index('discogs_id', 'vinyls_discogs_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_vinyls', function (Blueprint $table) {
            $table->dropIndex('cv_vinyl_id_idx');
            $table->dropIndex('cv_collection_id_idx');
            $table->dropIndex('cv_user_id_idx');
            $table->dropIndex('cv_vinyl_collection_idx');
            $table->dropIndex('cv_user_collection_idx');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex('collections_visibility_idx');
            $table->dropIndex('collections_user_id_idx');
            $table->dropIndex('collections_user_visibility_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_profile_public_idx');
        });

        Schema::table('vinyls', function (Blueprint $table) {
            $table->dropIndex('vinyls_discogs_id_idx');
        });
    }
};
