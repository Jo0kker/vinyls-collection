<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Collection;
use App\Models\Vinyl;
use App\Models\CollectionVinyl;
use Carbon\Carbon;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Créer un utilisateur de test s'il n'existe pas
        $user = User::firstOrCreate([
            'email' => 'test@example.com'
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Créer des collections pour cet utilisateur
        $collections = [
            [
                'collection_nom' => 'Rock Classics',
                'collection_commentaires' => 'Ma collection de rock classique',
                'collection_date_crea' => Carbon::now()->subDays(30),
                'collection_date_modif' => Carbon::now()->subDays(2),
                'ordre' => 1,
            ],
            [
                'collection_nom' => 'Jazz Masters',
                'collection_commentaires' => 'Les plus grands du jazz',
                'collection_date_crea' => Carbon::now()->subDays(25),
                'collection_date_modif' => Carbon::now()->subDays(5),
                'ordre' => 2,
            ],
            [
                'collection_nom' => 'Electronic Beats',
                'collection_commentaires' => 'Musique électronique moderne',
                'collection_date_crea' => Carbon::now()->subDays(20),
                'collection_date_modif' => Carbon::now()->subDays(1),
                'ordre' => 3,
            ],
        ];

        foreach ($collections as $collectionData) {
            $collection = $user->collections()->create($collectionData);

            // Créer des vinyles pour chaque collection
            $vinyls = $this->getVinylsForCollection($collection->collection_nom);
            
            foreach ($vinyls as $vinylData) {
                $vinyl = Vinyl::create([
                    'vinyl_nom' => $vinylData['nom'],
                    'vinyl_titre' => $vinylData['titre'],
                    'vinyl_format' => $vinylData['format'],
                    'vinyl_nbcollect' => 1,
                    'vinyl_alias' => 0,
                ]);

                // Créer l'association avec la collection
                CollectionVinyl::create([
                    'collection_id' => $collection->id,
                    'vinyl_id' => $vinyl->id,
                    'user_id' => $user->id,
                    'prix_achat' => $vinylData['prix'],
                    'date_ajout' => Carbon::now()->subDays(rand(1, 30)),
                    'note' => rand(7, 10),
                    'commentaires' => $vinylData['commentaires'] ?? null,
                ]);
            }
        }
    }

    private function getVinylsForCollection($collectionName)
    {
        switch ($collectionName) {
            case 'Rock Classics':
                return [
                    [
                        'nom' => 'Led Zeppelin',
                        'titre' => 'Led Zeppelin IV',
                        'format' => 1, // LP
                        'prix' => 25.99,
                        'commentaires' => 'Excellent état, pochette d\'origine'
                    ],
                    [
                        'nom' => 'Pink Floyd',
                        'titre' => 'The Dark Side of the Moon',
                        'format' => 1, // LP
                        'prix' => 32.50,
                        'commentaires' => 'Réédition 2016, son parfait'
                    ],
                    [
                        'nom' => 'The Beatles',
                        'titre' => 'Abbey Road',
                        'format' => 1, // LP
                        'prix' => 28.00,
                        'commentaires' => 'Pressage original UK'
                    ],
                ];
            
            case 'Jazz Masters':
                return [
                    [
                        'nom' => 'Miles Davis',
                        'titre' => 'Kind of Blue',
                        'format' => 1, // LP
                        'prix' => 45.00,
                        'commentaires' => 'Pressage Blue Note, état mint'
                    ],
                    [
                        'nom' => 'John Coltrane',
                        'titre' => 'A Love Supreme',
                        'format' => 1, // LP
                        'prix' => 38.99,
                        'commentaires' => 'Réédition audiophile'
                    ],
                ];
            
            case 'Electronic Beats':
                return [
                    [
                        'nom' => 'Daft Punk',
                        'titre' => 'Discovery',
                        'format' => 1, // LP
                        'prix' => 35.00,
                        'commentaires' => 'Double LP, édition limitée'
                    ],
                    [
                        'nom' => 'Justice',
                        'titre' => 'Cross',
                        'format' => 1, // LP
                        'prix' => 29.99,
                        'commentaires' => 'Première édition'
                    ],
                ];
            
            default:
                return [];
        }
    }
}