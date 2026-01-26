<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CollectionVinyl;
use App\Models\Collection;
use Illuminate\Support\Facades\DB;

class SyncUserVinylCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-user-vinyl-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les compteurs vinyl_count et public_collections_count pour tous les utilisateurs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Synchronisation des compteurs utilisateurs...');

        $users = User::all();
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            // Compter les vinyles de l'utilisateur
            $vinylCount = CollectionVinyl::where('user_id', $user->id)->count();

            // Compter les collections publiques
            $publicCollectionsCount = Collection::where('user_id', $user->id)
                ->where('visibility', 'public')
                ->count();

            // Mettre à jour l'utilisateur
            $user->update([
                'vinyl_count' => $vinylCount,
                'public_collections_count' => $publicCollectionsCount,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Synchronisation terminée !');

        // Afficher un résumé
        $this->table(
            ['Métrique', 'Total'],
            [
                ['Utilisateurs mis à jour', $users->count()],
                ['Total vinyles', CollectionVinyl::count()],
                ['Total collections publiques', Collection::where('visibility', 'public')->count()],
            ]
        );

        return Command::SUCCESS;
    }
}
