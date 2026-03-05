<?php

namespace Tests\Feature;

use App\Models\MappingOperation;
use App\Models\TypeDechet;
use Tests\TestCase;

class MappingOperationTest extends TestCase
{
    private TypeDechet $typeDechet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();

        $this->typeDechet = TypeDechet::first();
    }

    public function test_can_list_mappings(): void
    {
        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/mappings', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('data.0.mot_cle', 'vidange');
    }

    public function test_can_create_mapping(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/mappings', [
                'mot_cle' => 'changement filtre',
                'type_dechet_id' => $this->typeDechet->id,
                'quantite_defaut' => 0.5,
                'unite' => 'kg',
            ], $this->withGarageHeader());

        $response->assertStatus(201)
            ->assertJsonPath('mot_cle', 'changement filtre');

        $this->assertDatabaseHas('mapping_operations', [
            'mot_cle' => 'changement filtre',
            'garage_id' => $this->garage->id,
        ]);
    }

    public function test_create_mapping_validates_required_fields(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/mappings', [], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mot_cle', 'type_dechet_id', 'quantite_defaut', 'unite']);
    }

    public function test_create_mapping_rejects_duplicate_mot_cle(): void
    {
        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->postJson('/api/mappings', [
                'mot_cle' => 'vidange',
                'type_dechet_id' => $this->typeDechet->id,
                'quantite_defaut' => 3.000,
                'unite' => 'litres',
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Un mapping avec ce mot-cle existe deja pour ce garage.',
            ]);
    }

    public function test_can_update_mapping(): void
    {
        $mapping = MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/mappings/{$mapping->id}", [
                'quantite_defaut' => 7.500,
                'actif' => false,
            ], $this->withGarageHeader());

        $response->assertOk();

        $mapping->refresh();
        $this->assertEquals('7.500', $mapping->quantite_defaut);
        $this->assertFalse($mapping->actif);
    }

    public function test_update_rejects_duplicate_mot_cle(): void
    {
        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        $otherMapping = MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'filtre a huile',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 0.500,
            'unite' => 'kg',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/mappings/{$otherMapping->id}", [
                'mot_cle' => 'vidange',
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Un mapping avec ce mot-cle existe deja pour ce garage.',
            ]);
    }

    public function test_can_delete_mapping(): void
    {
        $mapping = MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/mappings/{$mapping->id}", [], $this->withGarageHeader());

        $response->assertStatus(204);

        $this->assertDatabaseMissing('mapping_operations', ['id' => $mapping->id]);
    }

    public function test_cannot_update_mapping_from_another_garage(): void
    {
        $otherGarage = $this->createGarage($this->entreprise, [
            'nom' => 'Other Garage',
        ]);

        $mapping = MappingOperation::create([
            'garage_id' => $otherGarage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/mappings/{$mapping->id}", [
                'mot_cle' => 'hijacked',
            ], $this->withGarageHeader());

        $response->assertStatus(403);
    }

    public function test_cannot_delete_mapping_from_another_garage(): void
    {
        $otherGarage = $this->createGarage($this->entreprise, [
            'nom' => 'Other Garage',
        ]);

        $mapping = MappingOperation::create([
            'garage_id' => $otherGarage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/mappings/{$mapping->id}", [], $this->withGarageHeader());

        $response->assertStatus(403);
    }

    public function test_can_filter_mappings_by_active(): void
    {
        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'active mapping',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'inactive mapping',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 3.000,
            'unite' => 'kg',
            'actif' => false,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/mappings?actif=true', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_mappings_by_search(): void
    {
        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange moteur',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'filtre a huile',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 0.500,
            'unite' => 'kg',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/mappings?search=vidange', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_suggest_returns_matching_mappings(): void
    {
        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 5.000,
            'unite' => 'litres',
            'actif' => true,
        ]);

        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'filtre',
            'type_dechet_id' => $this->typeDechet->id,
            'quantite_defaut' => 0.500,
            'unite' => 'kg',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->postJson('/api/mappings/suggest', [
                'description' => 'Vidange moteur avec changement de filtre a huile',
            ], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonStructure([
                'suggestions' => [
                    '*' => ['mapping_id', 'mot_cle', 'type_dechet', 'quantite_defaut', 'unite'],
                ],
            ]);
    }

    public function test_suggest_requires_description(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/mappings/suggest', [], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors('description');
    }

    public function test_mappings_require_authentication(): void
    {
        $response = $this->getJson('/api/mappings', $this->withGarageHeader());

        $response->assertUnauthorized();
    }
}
