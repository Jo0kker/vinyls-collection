<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('vinyls')
            ->whereNotIn('vinyl_format', DB::table('vinyl_formats')->pluck('id'))
            ->update(['vinyl_format' => 14]);

        Schema::table('vinyls', function (Blueprint $table) {
            $table->foreign('vinyl_format')
                ->references('id')
                ->on('vinyl_formats')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vinyls', function (Blueprint $table) {
            $table->dropForeign(['vinyl_format']);
        });
    }
};
