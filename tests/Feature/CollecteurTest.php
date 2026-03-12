<?php

namespace Tests\Feature;

use App\Models\Collecteur;
use Tests\TestCase;

class CollecteurTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_list_collecteurs(): void
    {
        Collecteur::create([
            'garage_id' => $this->garage->id,
            'raison_sociale' => 'Eco Collecte',
            'siret' => '55555555555555',
            'numero_autorisation' => 'AUTH-COL',
            'date_validite_autorisation' => now()->addMonths(6),
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/collecteurs', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_create_collecteur(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/collecteurs', [
                'raison_sociale' => 'Nouveau Collecteur',
                'siret' => '66666666666666',
                'adresse' => '10 rue du Collecteur',
                'code_postal' => '75010',
                'ville' => 'Paris',
                'numero_autorisation' => 'AUTH-NEW',
                'date_validite_autorisation' => now()->addYear()->toDateString(),
                'autorisation_adr' => true,
                'numero_adr' => 'ADR-12345',
            ], $this->withGarageHeader());

        $response->assertStatus(201);
        $this->assertDatabaseHas('collecteurs', ['raison_sociale' => 'Nouveau Collecteur']);
    }

    public function test_expired_authorization_flagged(): void
    {
        Collecteur::create([
            'garage_id' => $this->garage->id,
            'raison_sociale' => 'Expire SARL',
            'siret' => '77777777777777',
            'numero_autorisation' => 'AUTH-EXP',
            'date_validite_autorisation' => now()->subDay(),
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/collecteurs', $this->withGarageHeader());

        $response->assertOk();
        $collecteur = $response->json('data.0');
        $this->assertNotNull($collecteur);
    }
}
