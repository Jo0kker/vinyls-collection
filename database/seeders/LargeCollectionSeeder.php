<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\CollectionVinyl;
use App\Models\User;
use App\Models\Vinyl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LargeCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création de grandes collections pour les tests de pagination...');
        
        // D'abord, créer beaucoup de vinyles si nécessaire
        $vinylCount = Vinyl::count();
        $targetVinylCount = 5000; // On veut au moins 5000 vinyles pour avoir de la variété
        
        if ($vinylCount < $targetVinylCount) {
            $this->command->info("Création de " . ($targetVinylCount - $vinylCount) . " vinyles supplémentaires...");
            
            // Utilisation de chunks pour éviter les problèmes de mémoire
            $chunks = ceil(($targetVinylCount - $vinylCount) / 500);
            
            for ($i = 0; $i < $chunks; $i++) {
                $vinylsToCreate = min(500, $targetVinylCount - Vinyl::count());
                if ($vinylsToCreate <= 0) break;
                
                Vinyl::factory($vinylsToCreate)->create();
                $this->command->info("  - Chunk " . ($i + 1) . "/$chunks créé");
            }
        }
        
        // Récupérer tous les IDs de vinyles pour éviter de charger tous les modèles
        $vinylIds = Vinyl::pluck('id')->toArray();
        $this->command->info("Total de vinyles disponibles: " . count($vinylIds));
        
        // Créer des utilisateurs de test avec de grandes collections
        $testUsers = [
            ['name' => 'Test Pagination 1', 'email' => 'pagination1@test.com'],
            ['name' => 'Test Pagination 2', 'email' => 'pagination2@test.com'],
            ['name' => 'Test Pagination 3', 'email' => 'pagination3@test.com'],
        ];
        
        foreach ($testUsers as $userData) {
            // Vérifier si l'utilisateur existe déjà
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['password' => bcrypt('password')])
            );
            
            $this->command->info("Création de collections pour {$user->name}...");
            
            // Collection principale avec 1500+ vinyles
            $mainCollection = Collection::create([
                'user_id' => $user->id,
                'collection_nom' => 'Collection Principale (1500+ vinyles)',
                'collection_commentaires' => 'Grande collection pour tester la pagination',
                'collection_date_crea' => now(),
                'collection_date_modif' => now(),
            ]);
            
            $this->addVinylsToCollection($mainCollection, $vinylIds, 1500);
            
            // Collection moyenne avec 800 vinyles
            $mediumCollection = Collection::create([
                'user_id' => $user->id,
                'collection_nom' => 'Collection Moyenne (800 vinyles)',
                'collection_commentaires' => 'Collection de taille moyenne',
                'collection_date_crea' => now(),
                'collection_date_modif' => now(),
            ]);
            
            $this->addVinylsToCollection($mediumCollection, $vinylIds, 800);
            
            // Collection énorme avec 2500 vinyles
            $hugeCollection = Collection::create([
                'user_id' => $user->id,
                'collection_nom' => 'Collection Énorme (2500 vinyles)',
                'collection_commentaires' => 'Collection massive pour tester les performances',
                'collection_date_crea' => now(),
                'collection_date_modif' => now(),
            ]);
            
            $this->addVinylsToCollection($hugeCollection, $vinylIds, 2500);
            
            // Collection géante avec 3000 vinyles
            $giantCollection = Collection::create([
                'user_id' => $user->id,
                'collection_nom' => 'Collection Géante (3000 vinyles)',
                'collection_commentaires' => 'Collection extrême pour stress test',
                'collection_date_crea' => now(),
                'collection_date_modif' => now(),
            ]);
            
            $this->addVinylsToCollection($giantCollection, $vinylIds, 3000);
        }
        
        // Créer une collection partagée très large pour un utilisateur existant
        $firstUser = User::first();
        if ($firstUser) {
            $sharedLargeCollection = Collection::create([
                'user_id' => $firstUser->id,
                'collection_nom' => 'Collection Test Performance (4000 vinyles)',
                'collection_commentaires' => 'Collection de test ultime pour la pagination et les performances',
                'collection_date_crea' => now(),
                'collection_date_modif' => now(),
            ]);
            
            $this->addVinylsToCollection($sharedLargeCollection, $vinylIds, 4000);
        }
        
        $this->command->info('Grandes collections créées avec succès!');
        $this->command->info('Collections créées:');
        $this->command->info('- 3 utilisateurs de test avec 4 collections chacun');
        $this->command->info('- Collections de 800, 1500, 2500 et 3000 vinyles');
        $this->command->info('- 1 collection de test performance avec 4000 vinyles');
    }
    
    /**
     * Ajouter des vinyles à une collection de manière optimisée
     */
    private function addVinylsToCollection(Collection $collection, array $vinylIds, int $count): void
    {
        $this->command->info("  - Ajout de $count vinyles à la collection '{$collection->collection_nom}'...");
        
        // Shuffle et prendre le nombre de vinyles requis
        $selectedVinylIds = collect($vinylIds)->shuffle()->take($count);
        
        // Préparer les données pour l'insertion en masse
        $collectionVinylsData = [];
        $now = now();
        
        foreach ($selectedVinylIds as $vinylId) {
            $collectionVinylsData[] = [
                'collection_id' => $collection->id,
                'vinyl_id' => $vinylId,
                'user_id' => $collection->user_id,
                'prix_achat' => fake()->randomFloat(2, 5, 500),
                'date_ajout' => fake()->dateTimeBetween('-2 years', 'now'),
                'annee_achat' => fake()->numberBetween(2000, 2024),
                'provenance' => fake()->numberBetween(1, 5),
                'vente' => fake()->boolean(10),
                'commentaires' => fake()->optional(0.3)->sentence(),
                'note' => fake()->optional(0.5)->numberBetween(1, 5),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            // Insérer par batch de 500 pour éviter les problèmes de mémoire
            if (count($collectionVinylsData) >= 500) {
                DB::table('collection_vinyls')->insert($collectionVinylsData);
                $collectionVinylsData = [];
            }
        }
        
        // Insérer les derniers enregistrements
        if (!empty($collectionVinylsData)) {
            DB::table('collection_vinyls')->insert($collectionVinylsData);
        }
        
        $this->command->info("    ✓ $count vinyles ajoutés");
    }
}