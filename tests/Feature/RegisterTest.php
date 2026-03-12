<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegisterTest extends TestCase
{
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean@garage.fr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'raison_sociale' => 'Garage Dupont',
            'siret' => '98765432109876',
            'plan' => 'standard',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user', 'permissions']);

        $this->assertDatabaseHas('entreprises', ['siret' => '98765432109876']);
        $this->assertDatabaseHas('users', ['email' => 'jean@garage.fr']);
    }

    public function test_registration_requires_valid_siret(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean@garage.fr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'raison_sociale' => 'Garage Dupont',
            'siret' => '123', // too short
            'plan' => 'standard',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['siret']);
    }

    public function test_registration_requires_unique_email(): void
    {
        $this->setupTestEnvironment();

        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'admin@test.com', // already exists
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'raison_sociale' => 'Garage Autre',
            'siret' => '99999999999999',
            'plan' => 'standard',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
