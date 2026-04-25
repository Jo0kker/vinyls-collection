<?php

namespace App\Console\Commands;

use App\Models\Vinyl;
use App\Models\VinylFormat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefixDiscogsVinylFormatsStatus extends Command
{
    protected $signature = 'vinyls:refix-discogs-status';

    protected $description = 'Affiche la progression de la correction des formats Discogs.';

    public function handle(): int
    {
        $progress = Cache::get('vinyls:refix-discogs:progress');

        if (! $progress) {
            $this->warn('Aucun job de correction en cours ou cache vide.');
            return self::SUCCESS;
        }

        $remaining = Vinyl::whereNotNull('discogs_id')
            ->where('vinyl_format', VinylFormat::QUARANTE_CINQ_T)
            ->where('id', '>', $progress['last_id'] ?? 0)
            ->count();

        $this->info('Correction des formats Discogs');
        $this->line(sprintf('  Statut       : %s', $progress['status'] ?? 'inconnu'));
        $this->line(sprintf('  Dernier ID   : %d', $progress['last_id'] ?? 0));
        $this->line(sprintf('  Mis à jour   : %d', $progress['updated'] ?? 0));
        $this->line(sprintf('  Inchangés    : %d', $progress['unchanged'] ?? 0));
        $this->line(sprintf('  404 Discogs  : %d', $progress['not_found'] ?? 0));
        $this->line(sprintf('  Erreurs      : %d', $progress['failed'] ?? 0));
        $this->line(sprintf('  Restants (vinyl_format=1) : %d', $remaining));

        if (isset($progress['updated_at'])) {
            $this->line(sprintf('  Dernière maj : %s', $progress['updated_at']));
        }
        if (isset($progress['finished_at'])) {
            $this->line(sprintf('  Terminé le   : %s', $progress['finished_at']));
        }

        return self::SUCCESS;
    }
}
