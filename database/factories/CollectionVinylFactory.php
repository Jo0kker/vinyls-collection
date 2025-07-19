<?php

namespace Database\Factories;

use App\Models\CollectionVinyl;
use App\Models\Collection;
use App\Models\Vinyl;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionVinylFactory extends Factory
{
    protected $model = CollectionVinyl::class;

    public function definition(): array
    {
        $provenanceIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]; // IDs pour les provenances

        $anneeAchat = $this->faker->numberBetween(2000, 2024);
        $dateAjout = $this->faker->dateTimeBetween('-2 years', 'now');

        return [
            'collection_id' => Collection::factory(),
            'vinyl_id' => Vinyl::factory(),
            'user_id' => User::factory(),
            'exemplaire_id' => $this->faker->optional(0.3)->numberBetween(1, 10),
            'annee_achat' => $anneeAchat,
            'provenance' => $this->faker->optional(0.8)->randomElement($provenanceIds),
            'prix_achat' => $this->faker->optional(0.8)->randomFloat(2, 5, 150),
            'vente' => $this->faker->boolean(10), // 10% chance d'être en vente
            'commentaires' => $this->faker->optional(0.4)->sentence(),
            'note' => $this->faker->optional(0.6)->numberBetween(1, 10),
            'date_ajout' => $dateAjout,
        ];
    }

    public function forCollection(Collection $collection): static
    {
        return $this->state(fn() => [
            'collection_id' => $collection->id,
            'user_id' => $collection->user_id,
        ]);
    }

    public function forVinyl(Vinyl $vinyl): static
    {
        return $this->state(fn() => [
            'vinyl_id' => $vinyl->id,
        ]);
    }

    public function expensive(): static
    {
        return $this->state(fn() => [
            'prix_achat' => $this->faker->randomFloat(2, 50, 500),
            'note' => $this->faker->numberBetween(8, 10), // Note élevée
        ]);
    }

    public function recentPurchase(): static
    {
        return $this->state(fn() => [
            'annee_achat' => $this->faker->numberBetween(2022, 2024),
            'date_ajout' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }
}