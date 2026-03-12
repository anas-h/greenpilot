<?php

namespace Tests\Feature;

use App\Models\Conteneur;
use App\Models\TypeDechet;
use Tests\TestCase;

class ProductionTest extends TestCase
{
    private TypeDechet $type;

    private Conteneur $conteneur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();

        $this->type = TypeDechet::where('dangereux', true)->first();
        $this->conteneur = Conteneur::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $this->type->id,
            'nom' => 'Fut test',
            'code_qr' => 'ECO-TEST-PROD',
            'capacite' => 200,
            'unite' => 'litres',
            'niveau_actuel' => 0,
            'actif' => true,
        ]);
    }

    public function test_can_create_production(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/productions', [
                'type_dechet_id' => $this->type->id,
                'conteneur_id' => $this->conteneur->id,
                'quantite' => 5.5,
                'unite' => 'litres',
                'date_production' => now()->toDateString(),
                'source_type' => 'manuelle',
            ], $this->withGarageHeader());

        $response->assertStatus(201);
        $this->assertDatabaseHas('productions', [
            'type_dechet_id' => $this->type->id,
            'quantite' => 5.5,
        ]);
    }

    public function test_production_updates_conteneur_level(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/productions', [
                'type_dechet_id' => $this->type->id,
                'conteneur_id' => $this->conteneur->id,
                'quantite' => 10,
                'unite' => 'litres',
                'date_production' => now()->toDateString(),
                'source_type' => 'manuelle',
            ], $this->withGarageHeader());

        $this->conteneur->refresh();
        $this->assertEquals(10, (float) $this->conteneur->niveau_actuel);
    }

    public function test_production_cannot_exceed_capacity(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/productions', [
                'type_dechet_id' => $this->type->id,
                'conteneur_id' => $this->conteneur->id,
                'quantite' => 999,
                'unite' => 'litres',
                'date_production' => now()->toDateString(),
                'source_type' => 'manuelle',
            ], $this->withGarageHeader());

        // Should either reject or clamp
        $this->conteneur->refresh();
        $this->assertLessThanOrEqual(200, (float) $this->conteneur->niveau_actuel);
    }

    public function test_can_validate_productions(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/productions', [
                'type_dechet_id' => $this->type->id,
                'quantite' => 5,
                'unite' => 'litres',
                'date_production' => now()->toDateString(),
                'source_type' => 'manuelle',
            ], $this->withGarageHeader());

        $productionId = $response->json('data.id');

        $response = $this->actingAsAdmin()
            ->postJson('/api/productions/validate', [
                'ids' => [$productionId],
            ], $this->withGarageHeader());

        $response->assertOk();
    }

    public function test_mecanicien_can_create_production(): void
    {
        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien');
        $mecanicien->garages()->attach($this->garage->id);

        $response = $this->actingAs($mecanicien)
            ->postJson('/api/productions', [
                'type_dechet_id' => $this->type->id,
                'quantite' => 3,
                'unite' => 'litres',
                'date_production' => now()->toDateString(),
                'source_type' => 'manuelle',
            ], $this->withGarageHeader());

        $response->assertStatus(201);
    }
}
