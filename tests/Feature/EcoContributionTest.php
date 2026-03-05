<?php

namespace Tests\Feature;

use App\Models\EcoContribution;
use Tests\TestCase;

class EcoContributionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_list_eco_contributions(): void
    {
        EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'huile',
            'eco_organisme' => 'Eco-Huile',
            'annee' => 2025,
            'trimestre' => 1,
            'quantite' => 100.000,
            'unite' => 'litres',
            'montant_contribution' => 50.00,
            'montant_collecte' => 0.00,
            'statut' => 'brouillon',
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/eco-contributions', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('data.0.filiere', 'huile');
    }

    public function test_can_filter_eco_contributions_by_annee(): void
    {
        EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'huile',
            'eco_organisme' => 'Eco-Huile',
            'annee' => 2025,
            'trimestre' => 1,
            'quantite' => 100.000,
            'unite' => 'litres',
            'montant_contribution' => 50.00,
            'statut' => 'brouillon',
        ]);

        EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'pneu',
            'eco_organisme' => 'Eco-Pneu',
            'annee' => 2024,
            'trimestre' => 1,
            'quantite' => 50.000,
            'unite' => 'unites',
            'montant_contribution' => 25.00,
            'statut' => 'brouillon',
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/eco-contributions?annee=2025', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_eco_contributions_by_statut(): void
    {
        EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'huile',
            'eco_organisme' => 'Eco-Huile',
            'annee' => 2025,
            'trimestre' => 1,
            'quantite' => 100.000,
            'unite' => 'litres',
            'montant_contribution' => 50.00,
            'statut' => 'brouillon',
        ]);

        EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'pneu',
            'eco_organisme' => 'Eco-Pneu',
            'annee' => 2025,
            'trimestre' => 2,
            'quantite' => 50.000,
            'unite' => 'unites',
            'montant_contribution' => 25.00,
            'statut' => 'declare',
            'date_declaration' => now(),
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/eco-contributions?statut=declare', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_declare_brouillon_eco_contribution(): void
    {
        $contribution = EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'huile',
            'eco_organisme' => 'Eco-Huile',
            'annee' => 2025,
            'trimestre' => 1,
            'quantite' => 100.000,
            'unite' => 'litres',
            'montant_contribution' => 50.00,
            'statut' => 'brouillon',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/eco-contributions/{$contribution->id}/declare", [], $this->withGarageHeader());

        $response->assertOk();
        $contribution->refresh();
        $this->assertEquals('declare', $contribution->statut);
        $this->assertNotNull($contribution->date_declaration);
    }

    public function test_cannot_declare_already_declared_eco_contribution(): void
    {
        $contribution = EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'huile',
            'eco_organisme' => 'Eco-Huile',
            'annee' => 2025,
            'trimestre' => 1,
            'quantite' => 100.000,
            'unite' => 'litres',
            'montant_contribution' => 50.00,
            'statut' => 'declare',
            'date_declaration' => now(),
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/eco-contributions/{$contribution->id}/declare", [], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Seules les eco-contributions en brouillon peuvent etre declarees.',
            ]);
    }

    public function test_can_payer_declared_eco_contribution(): void
    {
        $contribution = EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'huile',
            'eco_organisme' => 'Eco-Huile',
            'annee' => 2025,
            'trimestre' => 1,
            'quantite' => 100.000,
            'unite' => 'litres',
            'montant_contribution' => 50.00,
            'statut' => 'declare',
            'date_declaration' => now(),
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/eco-contributions/{$contribution->id}/payer", [
                'reference_paiement' => 'REF-2025-001',
            ], $this->withGarageHeader());

        $response->assertOk();
        $contribution->refresh();
        $this->assertEquals('paye', $contribution->statut);
        $this->assertNotNull($contribution->date_paiement);
    }

    public function test_cannot_payer_brouillon_eco_contribution(): void
    {
        $contribution = EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'huile',
            'eco_organisme' => 'Eco-Huile',
            'annee' => 2025,
            'trimestre' => 1,
            'quantite' => 100.000,
            'unite' => 'litres',
            'montant_contribution' => 50.00,
            'statut' => 'brouillon',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/eco-contributions/{$contribution->id}/payer", [], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Seules les eco-contributions declarees peuvent etre marquees comme payees.',
            ]);
    }

    public function test_cannot_update_declared_eco_contribution(): void
    {
        $contribution = EcoContribution::create([
            'garage_id' => $this->garage->id,
            'filiere' => 'huile',
            'eco_organisme' => 'Eco-Huile',
            'annee' => 2025,
            'trimestre' => 1,
            'quantite' => 100.000,
            'unite' => 'litres',
            'montant_contribution' => 50.00,
            'statut' => 'declare',
            'date_declaration' => now(),
        ]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/eco-contributions/{$contribution->id}", [
                'notes' => 'Updated notes',
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Seules les eco-contributions en brouillon peuvent etre modifiees.',
            ]);
    }

    public function test_cannot_access_eco_contribution_from_another_garage(): void
    {
        $otherGarage = $this->createGarage($this->entreprise, [
            'nom' => 'Other Garage',
        ]);

        $contribution = EcoContribution::create([
            'garage_id' => $otherGarage->id,
            'filiere' => 'huile',
            'eco_organisme' => 'Eco-Huile',
            'annee' => 2025,
            'trimestre' => 1,
            'quantite' => 100.000,
            'unite' => 'litres',
            'montant_contribution' => 50.00,
            'statut' => 'brouillon',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/eco-contributions/{$contribution->id}/declare", [], $this->withGarageHeader());

        $response->assertStatus(403);
    }

    public function test_eco_contributions_require_authentication(): void
    {
        $response = $this->getJson('/api/eco-contributions', $this->withGarageHeader());

        $response->assertUnauthorized();
    }
}
