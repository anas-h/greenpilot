<?php

namespace Database\Factories;

use App\Models\Garage;
use App\Models\TypeDechet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FicheSecurite>
 */
class FicheSecuriteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'garage_id' => Garage::factory(),
            'type_dechet_id' => TypeDechet::inRandomOrder()->first()?->id ?? 1,
            'nom_produit' => fake()->word().' '.fake()->randomElement(['Pro', 'Plus', 'Clean', 'Max']),
            'fournisseur' => fake()->company(),
            'fichier_path' => 'fiches/fds-'.fake()->uuid().'.pdf',
            'date_emission' => now()->subMonths(6),
            'date_validite' => now()->addMonths(18),
            'actif' => true,
            'cree_par' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_validite' => now()->subMonth(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }
}
