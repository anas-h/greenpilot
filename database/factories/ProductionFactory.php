<?php

namespace Database\Factories;

use App\Models\Garage;
use App\Models\TypeDechet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Production>
 */
class ProductionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'garage_id' => Garage::factory(),
            'type_dechet_id' => TypeDechet::inRandomOrder()->first()?->id ?? 1,
            'conteneur_id' => null,
            'quantite' => fake()->randomFloat(3, 0.5, 50),
            'unite' => 'kg',
            'date_production' => now(),
            'source_type' => 'manuelle',
            'reference_externe' => null,
            'vehicule_info' => fake()->optional()->regexify('[A-Z]{2}-[0-9]{3}-[A-Z]{2}'),
            'employe_id' => null,
            'valide' => false,
            'valide_par' => null,
            'date_validation' => null,
            'notes' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'valide' => true,
            'date_validation' => now(),
        ]);
    }

    public function fromScan(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'scan_qr',
        ]);
    }

    public function fromImport(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'import_or',
        ]);
    }
}
