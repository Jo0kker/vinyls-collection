<?php

namespace Database\Seeders;

use App\Models\Vinyl;
use Illuminate\Database\Seeder;

class VinylSeeder extends Seeder
{
    public function run(): void
    {
        // Créer des albums mythiques avec des données réalistes
        $mythicAlbums = [
            [
                'vinyl_nom' => 'Pink Floyd',
                'vinyl_titre' => 'The Dark Side of the Moon',
                'vinyl_format' => 1, // LP
                'artiste' => 'Pink Floyd',
                'label' => 'Harvest',
                'annee' => 1973,
                'reference' => 'SHVL 804',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 150,
                'vinyl_alias' => 1,
            ],
            [
                'vinyl_nom' => 'The Beatles',
                'vinyl_titre' => 'Abbey Road',
                'vinyl_format' => 1, // LP
                'artiste' => 'The Beatles',
                'label' => 'Apple',
                'annee' => 1969,
                'reference' => 'PCS 7088',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 120,
            ],
            [
                'vinyl_nom' => 'Led Zeppelin',
                'vinyl_titre' => 'Led Zeppelin IV',
                'vinyl_format' => 1, // LP
                'artiste' => 'Led Zeppelin',
                'label' => 'Atlantic',
                'annee' => 1971,
                'reference' => 'SD 7208',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 95,
            ],
            [
                'vinyl_nom' => 'Queen',
                'vinyl_titre' => 'A Night at the Opera',
                'vinyl_format' => 1, // LP
                'artiste' => 'Queen',
                'label' => 'EMI',
                'annee' => 1975,
                'reference' => 'EMTC 103',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 78,
            ],
            [
                'vinyl_nom' => 'Fleetwood Mac',
                'vinyl_titre' => 'Rumours',
                'vinyl_format' => 1, // LP
                'artiste' => 'Fleetwood Mac',
                'label' => 'Warner Bros',
                'annee' => 1977,
                'reference' => 'BSK 3010',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 88,
            ],
            [
                'vinyl_nom' => 'The Rolling Stones',
                'vinyl_titre' => 'Sticky Fingers',
                'vinyl_format' => 1, // LP
                'artiste' => 'The Rolling Stones',
                'label' => 'Rolling Stones Records',
                'annee' => 1971,
                'reference' => 'COC 59100',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 72,
            ],
            [
                'vinyl_nom' => 'David Bowie',
                'vinyl_titre' => 'The Rise and Fall of Ziggy Stardust',
                'vinyl_format' => 1, // LP
                'artiste' => 'David Bowie',
                'label' => 'RCA',
                'annee' => 1972,
                'reference' => 'SF 8287',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 85,
            ],
            [
                'vinyl_nom' => 'The Doors',
                'vinyl_titre' => 'L.A. Woman',
                'vinyl_format' => 1, // LP
                'artiste' => 'The Doors',
                'label' => 'Elektra',
                'annee' => 1971,
                'reference' => 'EKS-75011',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 65,
            ],
            [
                'vinyl_nom' => 'Miles Davis',
                'vinyl_titre' => 'Kind of Blue',
                'vinyl_format' => 1, // LP
                'artiste' => 'Miles Davis',
                'label' => 'Columbia',
                'annee' => 1959,
                'reference' => 'CS 8163',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 45,
            ],
            [
                'vinyl_nom' => 'John Coltrane',
                'vinyl_titre' => 'A Love Supreme',
                'vinyl_format' => 1, // LP
                'artiste' => 'John Coltrane',
                'label' => 'Impulse!',
                'annee' => 1965,
                'reference' => 'AS-77',
                'vinyl_alias' => 1,
                'vinyl_nbcollect' => 38,
            ],
        ];

        foreach ($mythicAlbums as $album) {
            Vinyl::factory()->create($album);
        }

        // Créer des vinyles rares (faible nombre de collectionneurs)
        Vinyl::factory(15)->rare()->create();

        // Créer des vinyles avec ID Discogs
        Vinyl::factory(25)->withDiscogsId()->create();

        // Créer 100 vinyles aléatoires
        Vinyl::factory(100)->create();

        $this->command->info('Vinyles créés avec succès !');
        $this->command->info('- 10 albums mythiques');
        $this->command->info('- 15 vinyles rares');
        $this->command->info('- 25 vinyles avec données Discogs');
        $this->command->info('- 100 vinyles aléatoires');
    }
}