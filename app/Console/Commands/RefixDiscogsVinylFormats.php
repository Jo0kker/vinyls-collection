<?php

namespace App\Console\Commands;

use App\Jobs\RefixDiscogsVinylFormatChunkJob;
use App\Models\Vinyl;
use App\Models\VinylFormat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefixDiscogsVinylFormats extends Command
{
    protected $signature = 'vinyls:refix-discogs-formats
        {--start-id=0 : Reprendre à partir de cet ID (0 = depuis le début)}
        {--chunk=50 : Nombre de vinyles traités par job (chaque job se redispatche)}
        {--sleep=1100 : Délai entre requêtes Discogs en ms (mini 1000 pour rester sous 60/min)}
        {--all : Traiter tous les vinyles Discogs (par défaut: seulement vinyl_format=1)}
        {--force : Ne pas demander de confirmation}';

    protected $description = 'Lance la correction des formats des vinyles Discogs en arrière-plan via la queue (chunks chaînés).';

    public function handle(): int
    {
        $startId = (int) $this->option('start-id');
        $chunk = max(1, (int) $this->option('chunk'));
        $sleep = max(0, (int) $this->option('sleep'));
        $all = (bool) $this->option('all');

        $query = Vinyl::query()
            ->whereNotNull('discogs_id')
            ->where('id', '>', $startId);

        if (! $all) {
            $query->where('vinyl_format', VinylFormat::QUARANTE_CINQ_T);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('Aucun vinyle à traiter.');
            return self::SUCCESS;
        }

        $hours = (int) ceil(($count * $sleep / 1000) / 3600);
        $minutes = (int) ceil(($count * $sleep / 1000) / 60);

        $this->info(sprintf(
            "%d vinyles à traiter%s.",
            $count,
            $all ? ' (tous les Discogs)' : ' (vinyl_format=1 uniquement)'
        ));
        $this->info(sprintf(
            'Durée estimée : ~%s (%d req à %d ms).',
            $hours >= 2 ? "{$hours}h" : "{$minutes}min",
            $count,
            $sleep,
        ));
        $this->info('Mode: jobs chaînés via la queue Laravel. Le script se termine immédiatement.');

        if (! $this->option('force') && ! $this->confirm('Lancer le traitement ?', true)) {
            $this->warn('Annulé.');
            return self::SUCCESS;
        }

        Cache::forget('vinyls:refix-discogs:progress');

        RefixDiscogsVinylFormatChunkJob::dispatch($startId, $chunk, $sleep, $all);

        $this->newLine();
        $this->info('✔ Job dispatché.');
        $this->line('  Vérifie que le worker tourne : <comment>./vendor/bin/sail artisan queue:work --queue=default --tries=3</comment>');
        $this->line('  Suivre la progression       : <comment>./vendor/bin/sail artisan vinyls:refix-discogs-status</comment>');
        $this->line('  Logs détaillés              : <comment>tail -f storage/logs/laravel.log</comment>');

        return self::SUCCESS;
    }
}
