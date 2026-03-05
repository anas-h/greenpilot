<?php

namespace Tests\Feature;

use App\Models\Collecteur;
use App\Models\Conteneur;
use App\Models\TypeDechet;
use Tests\TestCase;

class EnlevementTest extends TestCase
{
    private TypeDechet $type;

    private Conteneur $conteneur;

    private Collecteur $collecteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();

        $this->type = TypeDechet::where('dangereux', false)->first();
        $this->conteneur = Conteneur::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $this->type->id,
            'nom' => 'Benne ferraille',
            'code_qr' => 'ECO-ENL-TEST',
            'capacite' => 1000,
            'unite' => 'kg',
            'niveau_actuel' => 500,
            'actif' => true,
        ]);
        $this->collecteur = Collecteur::create([
            'garage_id' => $this->garage->id,
            'raison_sociale' => 'Collecte Plus',
            'siret' => '11111111111111',
            'numero_autorisation' => 'AUTH-001',
            'date_validite_autorisation' => now()->addYear(),
            'actif' => true,
        ]);
    }

    public function test_can_create_enlevement(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/enlevements', [
                'collecteur_id' => $this->collecteur->id,
                'date_prevue' => now()->addDays(7)->toDateString(),
                'lignes' => [
                    [
                        'type_dechet_id' => $this->type->id,
                        'conteneur_id' => $this->conteneur->id,
                        'quantite_prevue' => 300,
                        'unite' => 'kg',
                    ],
                ],
            ], $this->withGarageHeader());

        $response->assertStatus(201);
        $this->assertDatabaseHas('enlevements', [
            'collecteur_id' => $this->collecteur->id,
            'statut' => 'brouillon',
        ]);
    }

    public function test_can_complete_enlevement(): void
    {
        // Create enlevement
        $response = $this->actingAsAdmin()
            ->postJson('/api/enlevements', [
                'collecteur_id' => $this->collecteur->id,
                'date_prevue' => now()->toDateString(),
                'lignes' => [
                    [
                        'type_dechet_id' => $this->type->id,
                        'conteneur_id' => $this->conteneur->id,
                        'quantite_prevue' => 300,
                        'unite' => 'kg',
                    ],
                ],
            ], $this->withGarageHeader());

        $enlevementId = $response->json('data.id');
        $ligneId = $response->json('data.lignes.0.id');

        // Complete
        $response = $this->actingAsAdmin()
            ->postJson("/api/enlevements/{$enlevementId}/complete", [
                'date_effective' => now()->toIso8601String(),
                'lignes' => [
                    ['ligne_id' => $ligneId, 'quantite_effective' => 300],
                ],
            ], $this->withGarageHeader());

        $response->assertOk();

        // Container should be updated
        $this->conteneur->refresh();
        $this->assertEquals(200, (float) $this->conteneur->niveau_actuel);
    }

    public function test_calendrier_returns_planned_enlevements(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/enlevements', [
                'collecteur_id' => $this->collecteur->id,
                'date_prevue' => now()->addDays(5)->toDateString(),
                'lignes' => [
                    [
                        'type_dechet_id' => $this->type->id,
                        'quantite_prevue' => 100,
                        'unite' => 'kg',
                    ],
                ],
            ], $this->withGarageHeader());

        $response = $this->actingAsAdmin()
            ->getJson('/api/enlevements-calendrier', $this->withGarageHeader());

        $response->assertOk();
    }
}
