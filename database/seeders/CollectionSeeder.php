<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\CollectionVinyl;
use App\Models\User;
use App\Models\Vinyl;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $vinyls = Vinyl::all();

        if ($users->isEmpty() || $vinyls->isEmpty()) {
            $this->command->warn('Aucun utilisateur ou vinyle trouvé. Assurez-vous d\'exécuter UserSeeder et VinylSeeder avant.');
            return;
        }

        // Créer des collections pour chaque utilisateur
        foreach ($users as $user) {
            // Nombre aléatoire de collections par utilisateur (1 à 4)
            $collectionsCount = fake()->numberBetween(1, 4);
            
            for ($i = 0; $i < $collectionsCount; $i++) {
                $collection = Collection::factory()
                    ->forUser($user)
                    ->create();

                // Ajouter des vinyles à chaque collection
                $vinylsToAdd = $vinyls->random(fake()->numberBetween(3, 15));

                foreach ($vinylsToAdd as $vinyl) {
                    // Éviter les doublons dans la même collection
                    if (!$collection->collectionVinyls()->where('vinyl_id', $vinyl->id)->exists()) {
                        CollectionVinyl::factory()
                            ->forCollection($collection)
                            ->forVinyl($vinyl)
                            ->create();
                    }
                }
            }
        }

        // Créer quelques collections avec beaucoup de vinyles pour les premiers utilisateurs
        $powerUsers = $users->take(3);
        foreach ($powerUsers as $user) {
            $bigCollection = Collection::factory()
                ->forUser($user)
                ->create([
                    'collection_nom' => 'Ma Grande Collection',
                    'collection_commentaires' => 'Collection principale avec tous mes vinyles favoris'
                ]);

            // Ajouter 30-50 vinyles
            $manyVinyls = $vinyls->random(fake()->numberBetween(30, 50));
            
            foreach ($manyVinyls as $vinyl) {
                if (!$bigCollection->collectionVinyls()->where('vinyl_id', $vinyl->id)->exists()) {
                    CollectionVinyl::factory()
                        ->forCollection($bigCollection)
                        ->forVinyl($vinyl)
                        ->create();
                }
            }
        }

        // Créer quelques collections avec des vinyles chers pour simuler des collectionneurs sérieux
        $collectors = $users->take(5);
        foreach ($collectors as $user) {
            $expensiveCollection = Collection::factory()
                ->forUser($user)
                ->create([
                    'collection_nom' => 'Collection Rare',
                    'collection_commentaires' => 'Mes vinyles les plus précieux et rares'
                ]);

            // Ajouter 5-10 vinyles chers
            $rareVinyls = $vinyls->random(fake()->numberBetween(5, 10));
            
            foreach ($rareVinyls as $vinyl) {
                if (!$expensiveCollection->collectionVinyls()->where('vinyl_id', $vinyl->id)->exists()) {
                    CollectionVinyl::factory()
                        ->forCollection($expensiveCollection)
                        ->forVinyl($vinyl)
                        ->expensive()
                        ->create();
                }
            }
        }

        $totalCollections = Collection::count();
        $totalCollectionVinyls = CollectionVinyl::count();

        $this->command->info('Collections créées avec succès !');
        $this->command->info("- {$totalCollections} collections au total");
        $this->command->info("- {$totalCollectionVinyls} entrées de vinyles dans les collections");
        $this->command->info('- Collections variées par utilisateur (1-4 par utilisateur)');
        $this->command->info('- Collections spéciales pour les power users');
        $this->command->info('- Collections rares avec vinyles chers');
    }
}