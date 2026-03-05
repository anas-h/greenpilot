<?php

namespace Database\Factories;

use App\Models\Collecteur;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bordereau>
 */
class BordereauFactory extends Factory
{
    public function definition(): array
    {
        return [
            'garage_id' => Garage::factory(),
            'type_bsd' => 'BSDD',
            'statut' => 'DRAFT',
            'statut_sync' => 'en_attente',
            'code_dechet' => '13 02 05*',
            'denomination_dechet' => 'Huiles usagées moteur',
            'quantite' => fake()->randomFloat(3, 1, 100),
            'unite' => 'kg',
            'collecteur_id' => Collecteur::factory(),
            'transporteur_siret' => fake()->numerify('##############'),
            'transporteur_raison_sociale' => fake()->company().' Transport',
            'destination_siret' => fake()->numerify('##############'),
            'destination_raison_sociale' => fake()->company().' Traitement',
            'destination_adresse' => fake()->address(),
            'code_traitement' => 'R9',
            'date_emission' => now(),
            'cree_par' => null,
        ];
    }

    public function sealed(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'SEALED',
        ]);
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'SIGNED_BY_PRODUCER',
            'date_signature_producteur' => now(),
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'SENT',
            'date_signature_producteur' => now()->subDay(),
            'date_enlevement' => now(),
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'PROCESSED',
            'date_signature_producteur' => now()->subWeek(),
            'date_enlevement' => now()->subDays(5),
            'date_reception' => now()->subDays(3),
            'date_traitement' => now(),
        ]);
    }

    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut_sync' => 'synchronise',
            'trackdechets_id' => 'TD-'.fake()->uuid(),
            'trackdechets_numero' => 'BSD-'.fake()->numerify('########'),
            'derniere_sync' => now(),
        ]);
    }

    public function syncError(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut_sync' => 'erreur',
            'erreur_sync' => 'Connection refused',
        ]);
    }

    public function bsff(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_bsd' => 'BSFF',
            'code_dechet' => '14 06 01*',
            'denomination_dechet' => 'Fluides frigorigènes',
        ]);
    }
}
