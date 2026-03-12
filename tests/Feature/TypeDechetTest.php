<?php

namespace Tests\Feature;

use App\Models\TypeDechet;
use Tests\TestCase;

class TypeDechetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_list_types_dechets_including_system_and_custom(): void
    {
        // System types are already seeded via TypeDechetSeeder in TestCase::setUp()
        $systemCount = TypeDechet::whereNull('entreprise_id')->count();

        // Create a custom type for the enterprise
        TypeDechet::create([
            'entreprise_id' => $this->entreprise->id,
            'denomination' => 'Dechet custom test',
            'code_europeen' => '99 99 99',
            'dangereux' => false,
            'unite_mesure' => 'kg',
            'categorie' => 'autre',
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/types-dechets', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonStructure(['data']);

        $data = $response->json('data');
        $this->assertCount($systemCount + 1, $data);
    }

    public function test_can_list_system_types_only(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/types-dechets/systeme');

        $response->assertOk()
            ->assertJsonStructure(['data']);

        $data = $response->json('data');
        foreach ($data as $type) {
            $this->assertNull($type['entreprise_id']);
        }
    }

    public function test_can_create_custom_type_dechet(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/types-dechets', [
                'denomination' => 'Liquide de refroidissement usage',
                'dangereux' => true,
                'unite_mesure' => 'litres',
                'categorie' => 'fluide',
                'code_europeen' => '16 01 14',
                'consignes_stockage' => 'Stocker dans un conteneur etanche',
            ], $this->withGarageHeader());

        $response->assertStatus(201)
            ->assertJsonPath('data.denomination', 'Liquide de refroidissement usage')
            ->assertJsonPath('data.dangereux', true)
            ->assertJsonPath('data.entreprise_id', $this->entreprise->id);

        $this->assertDatabaseHas('types_dechets', [
            'denomination' => 'Liquide de refroidissement usage',
            'entreprise_id' => $this->entreprise->id,
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/types-dechets', [], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['denomination', 'dangereux', 'unite_mesure', 'categorie']);
    }

    public function test_create_validates_unite_mesure_values(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/types-dechets', [
                'denomination' => 'Test',
                'dangereux' => false,
                'unite_mesure' => 'invalid_unit',
                'categorie' => 'Test',
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors('unite_mesure');
    }

    public function test_cannot_modify_system_type_dechet(): void
    {
        $systemType = TypeDechet::whereNull('entreprise_id')->first();

        $response = $this->actingAsAdmin()
            ->putJson("/api/types-dechets/{$systemType->id}", [
                'denomination' => 'Modified system type',
            ], $this->withGarageHeader());

        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Les types systeme ne peuvent pas etre modifies.',
            ]);
    }

    public function test_cannot_delete_system_type_dechet(): void
    {
        $systemType = TypeDechet::whereNull('entreprise_id')->first();

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/types-dechets/{$systemType->id}", [], $this->withGarageHeader());

        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Les types systeme ne peuvent pas etre supprimes.',
            ]);
    }

    public function test_can_update_custom_type_dechet(): void
    {
        $customType = TypeDechet::create([
            'entreprise_id' => $this->entreprise->id,
            'denomination' => 'Type custom original',
            'code_europeen' => '99 99 98',
            'dangereux' => false,
            'unite_mesure' => 'kg',
            'categorie' => 'autre',
        ]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/types-dechets/{$customType->id}", [
                'denomination' => 'Type custom modifie',
                'dangereux' => true,
            ], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('data.denomination', 'Type custom modifie')
            ->assertJsonPath('data.dangereux', true);
    }

    public function test_can_delete_custom_type_dechet(): void
    {
        $customType = TypeDechet::create([
            'entreprise_id' => $this->entreprise->id,
            'denomination' => 'Type a supprimer',
            'code_europeen' => '99 99 97',
            'dangereux' => false,
            'unite_mesure' => 'kg',
            'categorie' => 'autre',
        ]);

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/types-dechets/{$customType->id}", [], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Type de dechet supprime.',
            ]);

        $this->assertSoftDeleted('types_dechets', ['id' => $customType->id]);
    }

    public function test_can_toggle_type_activation_for_garage(): void
    {
        $systemType = TypeDechet::whereNull('entreprise_id')->first();

        // First toggle: attach and activate
        $response = $this->actingAsAdmin()
            ->postJson("/api/types-dechets/{$systemType->id}/toggle", [], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('actif', true);

        // Second toggle: deactivate
        $response = $this->actingAsAdmin()
            ->postJson("/api/types-dechets/{$systemType->id}/toggle", [], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('actif', false);

        // Third toggle: reactivate
        $response = $this->actingAsAdmin()
            ->postJson("/api/types-dechets/{$systemType->id}/toggle", [], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('actif', true);
    }

    public function test_cannot_access_other_enterprise_custom_types(): void
    {
        $otherEntreprise = $this->createEntreprise([
            'raison_sociale' => 'Other Enterprise',
            'siret' => '98765432109876',
        ]);

        $otherType = TypeDechet::create([
            'entreprise_id' => $otherEntreprise->id,
            'denomination' => 'Other enterprise type',
            'code_europeen' => '99 99 96',
            'dangereux' => false,
            'unite_mesure' => 'kg',
            'categorie' => 'autre',
        ]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/types-dechets/{$otherType->id}", [
                'denomination' => 'Hijacked type',
            ], $this->withGarageHeader());

        $response->assertStatus(403);
    }

    public function test_types_dechets_require_authentication(): void
    {
        $response = $this->getJson('/api/types-dechets', $this->withGarageHeader());

        $response->assertUnauthorized();
    }
}
