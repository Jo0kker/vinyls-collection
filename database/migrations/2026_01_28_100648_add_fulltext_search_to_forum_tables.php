<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Add search_vector column to forum_threads using raw SQL (Laravel doesn't support tsvector type)
        DB::statement('ALTER TABLE forum_threads ADD COLUMN search_vector tsvector');

        // Add search_vector column to forum_posts
        DB::statement('ALTER TABLE forum_posts ADD COLUMN search_vector tsvector');

        // Create GIN indexes for fast full-text search
        DB::statement('CREATE INDEX forum_threads_search_vector_idx ON forum_threads USING GIN (search_vector)');
        DB::statement('CREATE INDEX forum_posts_search_vector_idx ON forum_posts USING GIN (search_vector)');

        // Create function to update thread search vector
        DB::statement("
            CREATE OR REPLACE FUNCTION forum_threads_search_vector_update() RETURNS trigger AS $$
            BEGIN
                NEW.search_vector := setweight(to_tsvector('french', COALESCE(NEW.title, '')), 'A');
                RETURN NEW;
            END
            $$ LANGUAGE plpgsql;
        ");

        // Create trigger for threads
        DB::statement("
            CREATE TRIGGER forum_threads_search_vector_trigger
            BEFORE INSERT OR UPDATE OF title ON forum_threads
            FOR EACH ROW EXECUTE FUNCTION forum_threads_search_vector_update();
        ");

        // Create function to update post search vector
        DB::statement("
            CREATE OR REPLACE FUNCTION forum_posts_search_vector_update() RETURNS trigger AS $$
            BEGIN
                NEW.search_vector := to_tsvector('french', COALESCE(NEW.content, ''));
                RETURN NEW;
            END
            $$ LANGUAGE plpgsql;
        ");

        // Create trigger for posts
        DB::statement("
            CREATE TRIGGER forum_posts_search_vector_trigger
            BEFORE INSERT OR UPDATE OF content ON forum_posts
            FOR EACH ROW EXECUTE FUNCTION forum_posts_search_vector_update();
        ");

        // Populate existing data
        DB::statement("UPDATE forum_threads SET search_vector = setweight(to_tsvector('french', COALESCE(title, '')), 'A')");
        DB::statement("UPDATE forum_posts SET search_vector = to_tsvector('french', COALESCE(content, ''))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Drop triggers
        DB::statement('DROP TRIGGER IF EXISTS forum_threads_search_vector_trigger ON forum_threads');
        DB::statement('DROP TRIGGER IF EXISTS forum_posts_search_vector_trigger ON forum_posts');

        // Drop functions
        DB::statement('DROP FUNCTION IF EXISTS forum_threads_search_vector_update()');
        DB::statement('DROP FUNCTION IF EXISTS forum_posts_search_vector_update()');

        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS forum_threads_search_vector_idx');
        DB::statement('DROP INDEX IF EXISTS forum_posts_search_vector_idx');

        // Drop columns
        DB::statement('ALTER TABLE forum_threads DROP COLUMN IF EXISTS search_vector');
        DB::statement('ALTER TABLE forum_posts DROP COLUMN IF EXISTS search_vector');
    }
};
