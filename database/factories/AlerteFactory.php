<?php

namespace Database\Factories;

use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alerte>
 */
class AlerteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'garage_id' => Garage::factory(),
            'type' => fake()->randomElement(['conformite', 'expiration', 'capacite', 'documentation']),
            'priorite' => fake()->randomElement(['basse', 'moyenne', 'haute']),
            'titre' => fake()->sentence(4),
            'message' => fake()->sentence(),
            'detail' => null,
            'lue' => false,
            'resolue' => false,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolue' => true,
            'date_resolution' => now(),
        ]);
    }

    public function critique(): static
    {
        return $this->state(fn (array $attributes) => [
            'priorite' => 'critique',
        ]);
    }

    public function haute(): static
    {
        return $this->state(fn (array $attributes) => [
            'priorite' => 'haute',
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'lue' => true,
        ]);
    }

    public function capacite(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'capacite',
            'titre' => 'Conteneur presque plein',
            'message' => 'Le conteneur a atteint 80% de sa capacité.',
        ]);
    }

    public function expiration(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expiration',
            'titre' => 'Autorisation bientôt expirée',
            'message' => "L'autorisation du collecteur expire bientôt.",
        ]);
    }
}
