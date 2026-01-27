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
        Schema::table('users', function (Blueprint $table) {
            $table->string('discogs_username')->nullable();
            $table->string('discogs_oauth_token')->nullable();
            $table->string('discogs_oauth_token_secret')->nullable();
            $table->timestamp('discogs_connected_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'discogs_username',
                'discogs_oauth_token',
                'discogs_oauth_token_secret',
                'discogs_connected_at'
            ]);
        });
    }
};
