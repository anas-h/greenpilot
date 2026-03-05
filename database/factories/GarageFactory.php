<?php

namespace Database\Factories;

use App\Models\Entreprise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Garage>
 */
class GarageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'entreprise_id' => Entreprise::factory(),
            'nom' => 'Garage '.fake()->company(),
            'siret_etablissement' => fake()->numerify('##############'),
            'adresse' => fake()->streetAddress(),
            'code_postal' => fake()->numerify('#####'),
            'ville' => fake()->city(),
            'telephone' => fake()->phoneNumber(),
            'surface_atelier' => fake()->numberBetween(100, 1500),
            'regime_icpe' => 'non_classe',
            'activites' => ['mecanique'],
            'actif' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }

    public function declaration(): static
    {
        return $this->state(fn (array $attributes) => [
            'surface_atelier' => 3000,
            'regime_icpe' => 'declaration',
        ]);
    }

    public function enregistrement(): static
    {
        return $this->state(fn (array $attributes) => [
            'surface_atelier' => 6000,
            'regime_icpe' => 'enregistrement',
        ]);
    }
}
