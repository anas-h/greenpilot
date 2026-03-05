<?php

namespace Tests\Feature;

use Tests\TestCase;

class GarageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_list_garages(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/garages');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_can_create_garage(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/garages', [
                'nom' => 'Nouveau Garage',
                'adresse' => '10 rue de la Paix',
                'code_postal' => '75002',
                'ville' => 'Paris',
                'activites' => ['mecanique', 'carrosserie'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nom', 'Nouveau Garage');

        $this->assertDatabaseHas('garages', ['nom' => 'Nouveau Garage']);
    }

    public function test_create_garage_respects_plan_limit(): void
    {
        $this->entreprise->update(['max_garages' => 1]);

        $response = $this->actingAsAdmin()
            ->postJson('/api/garages', [
                'nom' => 'Deuxieme Garage',
                'adresse' => '20 rue Test',
                'code_postal' => '75003',
                'ville' => 'Paris',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Limite de garages atteinte pour votre plan. Passez a un plan superieur.']);
    }

    public function test_auto_determines_icpe_regime_non_classe(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/garages', [
                'nom' => 'Petit Garage',
                'adresse' => '1 rue Test',
                'code_postal' => '75001',
                'ville' => 'Paris',
                'surface_atelier' => 500,
            ]);

        $response->assertStatus(201);
        $this->assertEquals('non_classe', $response->json('data.regime_icpe'));
    }

    public function test_auto_determines_icpe_regime_declaration(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/garages', [
                'nom' => 'Moyen Garage',
                'adresse' => '2 rue Test',
                'code_postal' => '75002',
                'ville' => 'Paris',
                'surface_atelier' => 3000,
            ]);

        $response->assertStatus(201);
        $this->assertEquals('declaration', $response->json('data.regime_icpe'));
    }

    public function test_auto_determines_icpe_regime_enregistrement(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/garages', [
                'nom' => 'Grand Garage',
                'adresse' => '3 rue Test',
                'code_postal' => '75003',
                'ville' => 'Paris',
                'surface_atelier' => 6000,
            ]);

        $response->assertStatus(201);
        $this->assertEquals('enregistrement', $response->json('data.regime_icpe'));
    }

    public function test_can_show_garage(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson("/api/garages/{$this->garage->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $this->garage->id);
    }

    public function test_can_update_garage(): void
    {
        $response = $this->actingAsAdmin()
            ->putJson("/api/garages/{$this->garage->id}", [
                'nom' => 'Garage Modifié',
                'adresse' => $this->garage->adresse,
                'code_postal' => $this->garage->code_postal,
                'ville' => $this->garage->ville,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.nom', 'Garage Modifié');
    }

    public function test_can_delete_garage(): void
    {
        $response = $this->actingAsAdmin()
            ->deleteJson("/api/garages/{$this->garage->id}");

        $response->assertOk();
        $this->assertSoftDeleted('garages', ['id' => $this->garage->id]);
    }

    public function test_cannot_access_another_entreprise_garage(): void
    {
        $otherEntreprise = $this->createEntreprise(['siret' => '99999999999999']);
        $otherGarage = $this->createGarage($otherEntreprise);

        $response = $this->actingAsAdmin()
            ->getJson("/api/garages/{$otherGarage->id}");

        $response->assertForbidden();
    }

    public function test_non_admin_only_sees_assigned_garages(): void
    {
        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien');
        $mecanicien->garages()->attach($this->garage->id);

        $otherGarage = $this->createGarage($this->entreprise, ['nom' => 'Autre Garage']);

        $response = $this->actingAs($mecanicien)
            ->getJson('/api/garages');

        $response->assertOk();

        $garageIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->garage->id, $garageIds);
        $this->assertNotContains($otherGarage->id, $garageIds);
    }

    public function test_create_garage_validates_required_fields(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/garages', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nom', 'adresse', 'code_postal', 'ville']);
    }
}
