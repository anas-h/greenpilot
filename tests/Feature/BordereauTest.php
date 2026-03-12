<?php

namespace Tests\Feature;

use App\Models\Bordereau;
use App\Models\Collecteur;
use App\Models\TypeDechet;
use Tests\TestCase;

class BordereauTest extends TestCase
{
    private Collecteur $collecteur;

    private TypeDechet $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();

        $this->type = TypeDechet::where('dangereux', true)->first();
        $this->collecteur = Collecteur::create([
            'garage_id' => $this->garage->id,
            'raison_sociale' => 'Transport Eco',
            'siret' => '22222222222222',
            'numero_autorisation' => 'AUTH-BSD',
            'date_validite_autorisation' => now()->addYear(),
            'autorisation_adr' => true,
            'actif' => true,
        ]);
    }

    public function test_can_create_bsd_draft(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/bordereaux', [
                'type_bsd' => 'BSDD',
                'code_dechet' => $this->type->code_europeen,
                'denomination_dechet' => $this->type->denomination,
                'quantite' => 100,
                'unite' => 'kg',
                'collecteur_id' => $this->collecteur->id,
                'transporteur_siret' => '22222222222222',
                'transporteur_raison_sociale' => 'Transport Eco',
                'destination_siret' => '33333333333333',
                'destination_raison_sociale' => 'Centre Traitement',
                'code_traitement' => 'R9',
                'date_emission' => now()->toDateString(),
            ], $this->withGarageHeader());

        $response->assertStatus(201);
        $this->assertDatabaseHas('bordereaux', [
            'statut' => 'DRAFT',
            'type_bsd' => 'BSDD',
        ]);
    }

    public function test_can_publish_bsd(): void
    {
        $bsd = Bordereau::create([
            'garage_id' => $this->garage->id,
            'type_bsd' => 'BSDD',
            'statut' => 'DRAFT',
            'statut_sync' => 'en_attente',
            'code_dechet' => '13 02 05*',
            'denomination_dechet' => 'Huiles usagees',
            'quantite' => 50,
            'unite' => 'litres',
            'collecteur_id' => $this->collecteur->id,
            'transporteur_siret' => '22222222222222',
            'transporteur_raison_sociale' => 'Transport',
            'destination_siret' => '33333333333333',
            'destination_raison_sociale' => 'Centre',
            'code_traitement' => 'R9',
            'date_emission' => now(),
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/bordereaux/{$bsd->id}/publish", [], $this->withGarageHeader());

        $response->assertOk();
        $bsd->refresh();
        $this->assertEquals('SEALED', $bsd->statut);
    }

    public function test_can_sign_sealed_bsd(): void
    {
        $bsd = Bordereau::create([
            'garage_id' => $this->garage->id,
            'type_bsd' => 'BSDD',
            'statut' => 'SEALED',
            'statut_sync' => 'en_attente',
            'code_dechet' => '13 02 05*',
            'denomination_dechet' => 'Huiles usagees',
            'quantite' => 50,
            'unite' => 'litres',
            'collecteur_id' => $this->collecteur->id,
            'transporteur_siret' => '22222222222222',
            'transporteur_raison_sociale' => 'Transport',
            'destination_siret' => '33333333333333',
            'destination_raison_sociale' => 'Centre',
            'code_traitement' => 'R9',
            'date_emission' => now(),
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/bordereaux/{$bsd->id}/sign", [], $this->withGarageHeader());

        $response->assertOk();
        $bsd->refresh();
        $this->assertEquals('SIGNED_BY_PRODUCER', $bsd->statut);
    }

    public function test_can_duplicate_refused_bsd(): void
    {
        $bsd = Bordereau::create([
            'garage_id' => $this->garage->id,
            'type_bsd' => 'BSDD',
            'statut' => 'REFUSED',
            'statut_sync' => 'synchronise',
            'code_dechet' => '13 02 05*',
            'denomination_dechet' => 'Huiles usagees',
            'quantite' => 50,
            'unite' => 'litres',
            'collecteur_id' => $this->collecteur->id,
            'transporteur_siret' => '22222222222222',
            'transporteur_raison_sociale' => 'Transport',
            'destination_siret' => '33333333333333',
            'destination_raison_sociale' => 'Centre',
            'date_emission' => now(),
            'motif_refus' => 'Quantite incorrecte',
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/bordereaux/{$bsd->id}/dupliquer", [], $this->withGarageHeader());

        $response->assertStatus(201);
        $this->assertEquals(2, Bordereau::count());
    }
}
