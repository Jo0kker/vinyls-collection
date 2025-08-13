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
        Schema::table('vinyls', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('discogs_type');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Assigner un créateur aux vinyles manuels existants
        // Pour chaque vinyle manuel, on prend un propriétaire aléatoire
        $manualVinyls = DB::table('vinyls')
            ->where('discogs_type', 'manual')
            ->orWhereNull('discogs_id')
            ->get();

        foreach ($manualVinyls as $vinyl) {
            // Récupérer un propriétaire aléatoire de ce vinyle
            $randomOwner = DB::table('collection_vinyls')
                ->where('vinyl_id', $vinyl->id)
                ->inRandomOrder()
                ->first();

            if ($randomOwner) {
                DB::table('vinyls')
                    ->where('id', $vinyl->id)
                    ->update(['created_by' => $randomOwner->user_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vinyls', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
