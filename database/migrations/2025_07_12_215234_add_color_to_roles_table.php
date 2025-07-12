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
        Schema::table('roles', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('guard_name');
        });
        
        // Définir des couleurs par défaut pour les rôles existants
        DB::table('roles')->where('name', 'admin')->update(['color' => '#dc2626']); // Rouge
        DB::table('roles')->where('name', 'moderator')->update(['color' => '#f59e0b']); // Orange
        DB::table('roles')->where('name', 'user')->update(['color' => '#3b82f6']); // Bleu
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
