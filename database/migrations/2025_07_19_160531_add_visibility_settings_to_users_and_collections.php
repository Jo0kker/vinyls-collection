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
        // Ajouter des champs de profil public aux utilisateurs
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('profile_public')->default(true)->after('email_verified_at');
            $table->string('location')->nullable()->after('old_id');
            $table->json('social_links')->nullable()->after('location');
        });

        // Ajouter des paramètres de visibilité aux collections
        Schema::table('collections', function (Blueprint $table) {
            $table->enum('visibility', ['private', 'public', 'friends'])->default('public')->after('collection_commentaires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_public', 'location', 'social_links']);
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
