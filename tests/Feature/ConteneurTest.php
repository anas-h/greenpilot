<?php

namespace Tests\Feature;

use App\Models\Conteneur;
use App\Models\TypeDechet;
use Tests\TestCase;

class ConteneurTest extends TestCase
{
    public function test_can_list_conteneurs(): void
    {
        $this->setupTestEnvironment();

        $type = TypeDechet::where('dangereux', true)->first();
        Conteneur::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $type->id,
            'nom' => 'Fut huiles',
            'code_qr' => 'ECO-1-1-TEST01',
            'capacite' => 200,
            'unite' => 'litres',
            'niveau_actuel' => 50,
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/conteneurs', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_create_conteneur(): void
    {
        $this->setupTestEnvironment();
        $type = TypeDechet::where('dangereux', true)->first();

        $response = $this->actingAsAdmin()
            ->postJson('/api/conteneurs', [
                'nom' => 'Nouveau fut',
                'type_dechet_id' => $type->id,
                'capacite' => 500,
                'unite' => 'litres',
                'emplacement' => 'Zone dechets',
            ], $this->withGarageHeader());

        $response->assertStatus(201);
        $this->assertDatabaseHas('conteneurs', ['nom' => 'Nouveau fut']);
    }

    public function test_can_scan_conteneur_by_qr(): void
    {
        $this->setupTestEnvironment();

        Conteneur::create([
            'garage_id' => $this->garage->id,
            'nom' => 'Fut scan',
            'code_qr' => 'ECO-SCAN-TEST',
            'capacite' => 100,
            'unite' => 'kg',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/conteneurs/scan/ECO-SCAN-TEST', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('data.nom', 'Fut scan');
    }
}
