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
        Schema::table('collection_vinyls', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantite')->default(1)->after('exemplaire_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_vinyls', function (Blueprint $table) {
            $table->dropColumn('quantite');
        });
    }
};
