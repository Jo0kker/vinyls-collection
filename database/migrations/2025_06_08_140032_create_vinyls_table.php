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
        Schema::create('vinyls', function (Blueprint $table) {
            $table->id();
            $table->integer('old_id')->nullable()->after('id');
            $table->string('vinyl_nom');
            $table->string('vinyl_titre');
            $table->integer('vinyl_format');
            $table->integer('vinyl_nbcollect');
            $table->integer('vinyl_alias');
            $table->string('reference')->nullable();
            $table->string('artiste')->nullable();
            $table->string('label')->nullable();
            $table->integer('annee')->nullable();
            $table->integer('pays')->nullable();
            $table->text('tracks')->nullable();
            $table->text('specificite')->nullable();
            $table->string('pochette')->nullable();
            $table->text('visuels')->nullable();
            $table->string('refMatrice')->nullable();
            $table->string('distribution')->nullable();
            $table->integer('edition')->nullable();
            $table->integer('anneeOriginal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vinyls');
    }
};
