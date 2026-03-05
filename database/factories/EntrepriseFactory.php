<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entreprise>
 */
class EntrepriseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'raison_sociale' => fake()->company(),
            'siret' => fake()->numerify('##############'),
            'forme_juridique' => fake()->randomElement(['SARL', 'SAS', 'SA', 'EURL', 'Auto-entrepreneur']),
            'adresse' => fake()->streetAddress(),
            'code_postal' => fake()->numerify('#####'),
            'ville' => fake()->city(),
            'telephone' => fake()->phoneNumber(),
            'email_contact' => fake()->companyEmail(),
            'plan' => 'gratuit',
            'trial_ends_at' => now()->addDays(14),
            'actif' => true,
            'max_garages' => 3,
            'max_users' => 15,
        ];
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'premium',
            'max_garages' => 999,
            'max_users' => 999,
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'standard',
            'max_garages' => 3,
            'max_users' => 15,
        ]);
    }

    public function trialExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->subDays(1),
        ]);
    }

    public function noTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
