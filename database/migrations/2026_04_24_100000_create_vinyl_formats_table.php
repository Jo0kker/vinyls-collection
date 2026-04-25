<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vinyl_formats', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();

        $formats = [
            ['id' => 1,  'label' => '45 tours',        'slug' => '45t',        'sort_order' => 20],
            ['id' => 2,  'label' => 'Maxi 45 tours',   'slug' => 'maxi-45t',   'sort_order' => 25],
            ['id' => 3,  'label' => 'LP (33 tours)',   'slug' => 'lp',         'sort_order' => 10],
            ['id' => 4,  'label' => '2 LP',            'slug' => '2lp',        'sort_order' => 11],
            ['id' => 5,  'label' => '3 LP',            'slug' => '3lp',        'sort_order' => 12],
            ['id' => 6,  'label' => '4 LP',            'slug' => '4lp',        'sort_order' => 13],
            ['id' => 7,  'label' => '5 LP',            'slug' => '5lp',        'sort_order' => 14],
            ['id' => 8,  'label' => '6 LP',            'slug' => '6lp',        'sort_order' => 15],
            ['id' => 9,  'label' => '78 tours',        'slug' => '78t',        'sort_order' => 30],
            ['id' => 10, 'label' => 'CD',              'slug' => 'cd',         'sort_order' => 40],
            ['id' => 11, 'label' => 'K7 (cassette)',   'slug' => 'k7',         'sort_order' => 50],
            ['id' => 12, 'label' => 'DVD',             'slug' => 'dvd',        'sort_order' => 60],
            ['id' => 13, 'label' => 'VHS',             'slug' => 'vhs',        'sort_order' => 70],
            ['id' => 14, 'label' => 'Autre',           'slug' => 'autre',      'sort_order' => 999],
            ['id' => 15, 'label' => 'EP',              'slug' => 'ep',         'sort_order' => 16],
            ['id' => 16, 'label' => 'Maxi 33 tours',   'slug' => 'maxi-33t',   'sort_order' => 17],
            ['id' => 17, 'label' => 'LP 25cm (10")',   'slug' => 'lp-25cm',    'sort_order' => 18],
        ];

        foreach ($formats as &$f) {
            $f['active'] = true;
            $f['created_at'] = $now;
            $f['updated_at'] = $now;
        }

        DB::table('vinyl_formats')->insert($formats);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('vinyl_formats', 'id'), (SELECT COALESCE(MAX(id), 1) FROM vinyl_formats))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vinyl_formats');
    }
};
