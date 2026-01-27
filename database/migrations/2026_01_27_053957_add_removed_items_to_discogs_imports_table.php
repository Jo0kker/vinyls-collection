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
        Schema::table('discogs_imports', function (Blueprint $table) {
            $table->integer('removed_items')->default(0)->after('skipped_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discogs_imports', function (Blueprint $table) {
            $table->dropColumn('removed_items');
        });
    }
};
