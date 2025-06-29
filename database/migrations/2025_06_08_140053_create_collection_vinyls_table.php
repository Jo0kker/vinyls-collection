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
        Schema::create('collection_vinyls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('vinyl_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('exemplaire_id')->nullable();
            $table->integer('annee_achat')->nullable();
            $table->integer('provenance')->nullable();
            $table->double('prix_achat')->nullable();
            $table->boolean('vente')->default(false);
            $table->text('commentaires')->nullable();
            $table->integer('note')->nullable();
            $table->dateTime('date_ajout')->nullable();
            $table->timestamps();

            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');
            $table->foreign('vinyl_id')->references('id')->on('vinyls')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_vinyls');
    }
};
