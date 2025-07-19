<?php

namespace Database\Factories;

use App\Models\Vinyl;
use Illuminate\Database\Eloquent\Factories\Factory;

class VinylFactory extends Factory
{
    protected $model = Vinyl::class;

    public function definition(): array
    {
        $artists = [
            'Pink Floyd', 'The Beatles', 'Led Zeppelin', 'Queen', 'The Rolling Stones',
            'Bob Dylan', 'David Bowie', 'The Doors', 'Fleetwood Mac', 'AC/DC',
            'Black Sabbath', 'Deep Purple', 'The Who', 'Yes', 'Genesis',
            'King Crimson', 'Jethro Tull', 'Cream', 'The Jimi Hendrix Experience',
            'Janis Joplin', 'Joni Mitchell', 'Neil Young', 'Crosby, Stills & Nash',
            'The Velvet Underground', 'T. Rex', 'Slade', 'The Kinks',
            'The Animals', 'The Byrds', 'Buffalo Springfield', 'Santana',
            'Miles Davis', 'John Coltrane', 'Herbie Hancock', 'Weather Report',
            'Kraftwerk', 'Tangerine Dream', 'Jean-Michel Jarre', 'Giorgio Moroder'
        ];

        $albums = [
            'The Dark Side of the Moon', 'Abbey Road', 'Led Zeppelin IV', 'A Night at the Opera',
            'Sticky Fingers', 'Highway 61 Revisited', 'The Rise and Fall of Ziggy Stardust',
            'L.A. Woman', 'Rumours', 'Back in Black', 'Paranoid', 'Machine Head',
            'Tommy', 'Close to the Edge', 'The Lamb Lies Down on Broadway',
            'In the Court of the Crimson King', 'Aqualung', 'Disraeli Gears',
            'Are You Experienced', 'Pearl', 'Blue', 'Harvest', 'Déjà Vu',
            'The Velvet Underground & Nico', 'Electric Warrior', 'Noddy Holder',
            'Village Green Preservation Society', 'The Animals', 'Mr. Tambourine Man',
            'Buffalo Springfield Again', 'Abraxas', 'Kind of Blue', 'A Love Supreme',
            'Head Hunters', 'Heavy Weather', 'Autobahn', 'Phaedra', 'Oxygène', 'I Feel Love'
        ];

        // Les formats sont stockés comme des entiers dans la base (1=LP, 2=45T, etc.)
        $formatIds = [1, 2, 3, 4, 5, 6];
        $labels = [
            'EMI', 'Columbia', 'Atlantic', 'Polydor', 'Warner Bros', 'Capitol',
            'Decca', 'RCA', 'Motown', 'Stax', 'Blue Note', 'Prestige',
            'ECM', 'Island', 'Virgin', 'Chrysalis', 'A&M', 'Elektra'
        ];

        $artist = $this->faker->randomElement($artists);
        $album = $this->faker->randomElement($albums);

        return [
            'vinyl_nom' => $artist,
            'vinyl_titre' => $album,
            'vinyl_format' => $this->faker->randomElement($formatIds),
            'vinyl_nbcollect' => $this->faker->numberBetween(1, 500),
            'vinyl_alias' => $this->faker->numberBetween(1, 100),
            'artiste' => $artist,
            'pochette' => $this->faker->optional(0.8)->imageUrl(640, 640, 'music', true, $album),
            'reference' => $this->faker->bothify('??-####'),
            'label' => $this->faker->randomElement($labels),
            'annee' => $this->faker->numberBetween(1960, 2024),
            'discogs_id' => $this->faker->optional(0.7)->numberBetween(1000000, 9999999),
            'discogs_type' => $this->faker->optional(0.7)->randomElement(['release', 'master']),
            'discogs_updated_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function withDiscogsId(): static
    {
        return $this->state(fn() => [
            'discogs_id' => $this->faker->numberBetween(1000000, 9999999),
            'discogs_type' => 'release',
            'discogs_updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    public function rare(): static
    {
        return $this->state(fn() => [
            'vinyl_nbcollect' => $this->faker->numberBetween(1, 50),
            'annee' => $this->faker->numberBetween(1960, 1980),
        ]);
    }
}