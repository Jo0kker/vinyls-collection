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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('vinyl_count')->default(0)->after('email');
            $table->integer('public_collections_count')->default(0)->after('vinyl_count');
        });

        // Populate vinyl_count for existing users
        DB::statement("
            UPDATE users
            SET vinyl_count = (
                SELECT COUNT(*)
                FROM collection_vinyls
                WHERE collection_vinyls.user_id = users.id
            )
        ");

        // Populate public_collections_count for existing users
        DB::statement("
            UPDATE users
            SET public_collections_count = (
                SELECT COUNT(*)
                FROM collections
                WHERE collections.user_id = users.id
                AND collections.visibility = 'public'
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vinyl_count', 'public_collections_count']);
        });
    }
};
