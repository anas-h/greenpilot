<?php

namespace Database\Factories;

use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collecteur>
 */
class CollecteurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'garage_id' => Garage::factory(),
            'raison_sociale' => fake()->company().' Collecte',
            'siret' => fake()->numerify('##############'),
            'adresse' => fake()->streetAddress(),
            'code_postal' => fake()->numerify('#####'),
            'ville' => fake()->city(),
            'numero_autorisation' => 'AUT-'.fake()->numerify('####-####'),
            'date_validite_autorisation' => now()->addYear(),
            'autorisation_adr' => true,
            'numero_adr' => 'ADR-'.fake()->numerify('######'),
            'contact_nom' => fake()->name(),
            'contact_telephone' => fake()->phoneNumber(),
            'contact_email' => fake()->safeEmail(),
            'actif' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_validite_autorisation' => now()->subMonth(),
        ]);
    }

    public function noAdr(): static
    {
        return $this->state(fn (array $attributes) => [
            'autorisation_adr' => false,
            'numero_adr' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }
}
