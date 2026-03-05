<?php

namespace Database\Factories;

use App\Models\Garage;
use App\Models\TypeDechet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conteneur>
 */
class ConteneurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'garage_id' => Garage::factory(),
            'type_dechet_id' => TypeDechet::inRandomOrder()->first()?->id ?? 1,
            'nom' => 'Conteneur '.fake()->word(),
            'code_qr' => 'QR-'.Str::uuid(),
            'capacite' => 200.00,
            'unite' => 'kg',
            'niveau_actuel' => 0.00,
            'emplacement' => fake()->randomElement(['Atelier', 'Extérieur', 'Zone stockage', 'Entrée']),
            'mixte' => false,
            'date_mise_en_service' => now()->subMonths(3),
            'actif' => true,
        ];
    }

    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'niveau_actuel' => $attributes['capacite'] ?? 200.00,
        ]);
    }

    public function at80Percent(): static
    {
        return $this->state(fn (array $attributes) => [
            'niveau_actuel' => ($attributes['capacite'] ?? 200.00) * 0.80,
        ]);
    }

    public function at95Percent(): static
    {
        return $this->state(fn (array $attributes) => [
            'niveau_actuel' => ($attributes['capacite'] ?? 200.00) * 0.95,
        ]);
    }

    public function mixte(): static
    {
        return $this->state(fn (array $attributes) => [
            'mixte' => true,
            'type_dechet_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }
}
