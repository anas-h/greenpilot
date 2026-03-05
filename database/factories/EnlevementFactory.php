<?php

namespace Database\Factories;

use App\Models\Collecteur;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enlevement>
 */
class EnlevementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'garage_id' => Garage::factory(),
            'numero' => 'ENL-'.date('Y').'-'.fake()->unique()->numerify('#####'),
            'collecteur_id' => Collecteur::factory(),
            'date_prevue' => now()->addDays(7),
            'date_effective' => null,
            'statut' => 'brouillon',
            'numero_bon_enlevement' => null,
            'bon_enlevement_path' => null,
            'cout_total' => 0,
            'revenu_total' => 0,
            'notes' => null,
            'recurrence' => null,
            'prochaine_date' => null,
            'cree_par' => null,
        ];
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'complete',
            'date_effective' => now(),
            'cout_total' => fake()->randomFloat(2, 50, 500),
            'revenu_total' => fake()->randomFloat(2, 0, 100),
        ]);
    }

    public function planifie(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'planifie',
        ]);
    }

    public function enCours(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'en_cours',
        ]);
    }

    public function annule(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'annule',
        ]);
    }

    public function recurring(string $frequency = 'mensuel'): static
    {
        return $this->state(fn (array $attributes) => [
            'recurrence' => $frequency,
            'prochaine_date' => now()->addMonth(),
        ]);
    }
}
