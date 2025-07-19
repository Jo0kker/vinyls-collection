<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        $collectionNames = [
            'Ma Collection Rock', 'Vinyles Blues', 'Jazz Classique', 'Punk & New Wave',
            'Progressive Rock', 'Collection Soul', 'Électronique Vintage', 'Folk Américain',
            'Reggae & Dub', 'Musique Française', 'Heavy Metal', 'Funk & Disco',
            'Collection Hip-Hop', 'Indie Rock', 'Musique Classique', 'World Music',
            'Pop des 80s', 'Garage Rock', 'Psychédélique', 'Country & Western',
            'Collection Rare', 'Imports Japonais', 'Picture Discs', 'Éditions Limitées',
            'Compilation Albums', 'Live Recordings', 'Soundtracks', 'Singles Collection'
        ];

        $comments = [
            'Ma collection personnelle de vinyles favoris',
            'Collection dédiée aux albums mythiques',
            'Vinyles collectés au fil des années',
            'Collection thématique soigneusement sélectionnée',
            'Albums incontournables du genre',
            'Ma sélection d\'albums rares et précieux',
            'Collection centrée sur cette période musicale',
            'Vinyles trouvés en brocante et chez les disquaires',
            'Collection en cours de développement',
            'Albums que j\'écoute en boucle'
        ];

        $createdAt = $this->faker->dateTimeBetween('-2 years', '-1 month');
        $modifiedAt = $this->faker->dateTimeBetween($createdAt, 'now');

        return [
            'collection_nom' => $this->faker->randomElement($collectionNames),
            'collection_date_crea' => $createdAt,
            'collection_date_modif' => $modifiedAt,
            'collection_commentaires' => $this->faker->optional(0.7)->randomElement($comments),
            'user_id' => User::factory(),
            'ordre' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn() => [
            'user_id' => $user->id,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn() => [
            'collection_date_crea' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'collection_date_modif' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function withManyVinyls(): static
    {
        return $this->afterCreating(function (Collection $collection) {
            $vinylCount = $this->faker->numberBetween(10, 50);
            $collection->collectionVinyls()->createMany(
                \App\Models\CollectionVinyl::factory($vinylCount)->make()->toArray()
            );
        });
    }
}