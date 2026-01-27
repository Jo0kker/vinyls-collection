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
        // Table pour mapper les dossiers Discogs aux collections locales
        Schema::create('discogs_folder_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('collection_id')->constrained()->onDelete('cascade');
            $table->integer('discogs_folder_id');
            $table->string('discogs_folder_name');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'discogs_folder_id']);
        });

        // Table pour suivre les imports en cours et historique
        Schema::create('discogs_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('collection_id')->constrained()->onDelete('cascade');
            $table->integer('discogs_folder_id');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->integer('imported_items')->default(0);
            $table->integer('skipped_items')->default(0);
            $table->text('error_message')->nullable();
            $table->json('errors')->nullable(); // Détails des erreurs par item
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discogs_imports');
        Schema::dropIfExists('discogs_folder_mappings');
    }
};
