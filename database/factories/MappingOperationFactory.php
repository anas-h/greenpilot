<?php

namespace Database\Factories;

use App\Models\Garage;
use App\Models\TypeDechet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MappingOperation>
 */
class MappingOperationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'garage_id' => Garage::factory(),
            'mot_cle' => fake()->randomElement([
                'vidange', 'filtre huile', 'filtre air', 'plaquettes frein',
                'liquide frein', 'climatisation', 'batterie', 'pneus',
            ]),
            'type_dechet_id' => TypeDechet::inRandomOrder()->first()?->id ?? 1,
            'quantite_defaut' => fake()->randomFloat(3, 0.1, 10),
            'unite' => 'kg',
            'actif' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }
}
