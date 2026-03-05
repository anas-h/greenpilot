<?php

namespace Database\Factories;

use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EcoContribution>
 */
class EcoContributionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'garage_id' => Garage::factory(),
            'filiere' => fake()->randomElement(['huile', 'pneu', 'batterie']),
            'eco_organisme' => fake()->randomElement(['CYCLEVIA', 'Aliapur', 'COREPILE']),
            'annee' => now()->year,
            'trimestre' => fake()->numberBetween(1, 4),
            'quantite' => fake()->randomFloat(3, 10, 500),
            'unite' => 'kg',
            'montant_contribution' => fake()->randomFloat(2, 50, 1000),
            'montant_collecte' => fake()->randomFloat(2, 0, 200),
            'statut' => 'brouillon',
            'enlevement_ids' => null,
            'notes' => null,
        ];
    }

    public function declared(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'declare',
            'date_declaration' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'paye',
            'date_declaration' => now()->subWeek(),
            'date_paiement' => now(),
        ]);
    }
}
