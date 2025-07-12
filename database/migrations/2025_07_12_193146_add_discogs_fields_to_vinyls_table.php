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
        Schema::table('vinyls', function (Blueprint $table) {
            $table->string('discogs_id')->nullable()->after('pochette');
            $table->enum('discogs_type', ['release', 'master'])->nullable()->after('discogs_id');
            $table->timestamp('discogs_updated_at')->nullable()->after('discogs_type');
            
            // Index pour optimiser les recherches
            $table->index(['discogs_id', 'discogs_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vinyls', function (Blueprint $table) {
            $table->dropIndex(['discogs_id', 'discogs_type']);
            $table->dropColumn(['discogs_id', 'discogs_type', 'discogs_updated_at']);
        });
    }
};
