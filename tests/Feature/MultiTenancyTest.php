<?php

namespace Tests\Feature;

use App\Models\Conteneur;
use App\Models\Garage;
use App\Models\User;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    public function test_user_cannot_access_other_enterprise_garage(): void
    {
        $this->setupTestEnvironment();

        // Create another enterprise with a garage
        $otherEntreprise = $this->createEntreprise(['siret' => '99999999999999', 'raison_sociale' => 'Autre SARL']);
        $otherGarage = $this->createGarage($otherEntreprise, ['nom' => 'Autre garage']);

        $response = $this->actingAsAdmin()
            ->getJson('/api/conteneurs', ['X-Garage-Id' => $otherGarage->id]);

        $response->assertStatus(404); // Garage not found for this user's enterprise
    }

    public function test_mecanicien_cannot_access_unassigned_garage(): void
    {
        $this->setupTestEnvironment();

        $garage2 = $this->createGarage($this->entreprise, ['nom' => 'Second garage']);
        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien', [
            'email' => 'meca2@test.com',
            'garages_ids' => [$this->garage->id], // NOT garage2
        ]);

        $response = $this->actingAs($mecanicien)
            ->getJson('/api/conteneurs', ['X-Garage-Id' => $garage2->id]);

        $response->assertStatus(403);
    }

    public function test_admin_can_access_all_enterprise_garages(): void
    {
        $this->setupTestEnvironment();

        $garage2 = $this->createGarage($this->entreprise, ['nom' => 'Second garage']);

        $response = $this->actingAsAdmin()
            ->getJson('/api/conteneurs', ['X-Garage-Id' => $garage2->id]);

        // Admin has access to all garages in their enterprise
        $response->assertOk();
    }

    public function test_data_isolation_between_garages(): void
    {
        $this->setupTestEnvironment();

        $garage2 = $this->createGarage($this->entreprise, ['nom' => 'Second garage']);

        // Create conteneur in garage 1
        Conteneur::create([
            'garage_id' => $this->garage->id,
            'nom' => 'Fut garage 1',
            'code_qr' => 'ECO-G1-001',
            'capacite' => 100,
            'unite' => 'kg',
            'actif' => true,
        ]);

        // Create conteneur in garage 2
        Conteneur::create([
            'garage_id' => $garage2->id,
            'nom' => 'Fut garage 2',
            'code_qr' => 'ECO-G2-001',
            'capacite' => 100,
            'unite' => 'kg',
            'actif' => true,
        ]);

        // Query garage 1 should only see its conteneur
        $response = $this->actingAsAdmin()
            ->getJson('/api/conteneurs', ['X-Garage-Id' => $this->garage->id]);

        $response->assertOk()
            ->assertJsonCount(1, 'data');
        $this->assertEquals('Fut garage 1', $response->json('data.0.nom'));
    }
}
