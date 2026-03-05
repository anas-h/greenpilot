<?php

namespace Tests\Feature;

use App\Models\PriseChargePneu;
use Tests\TestCase;

class PneuParticulierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_list_prises_en_charge_pneus(): void
    {
        PriseChargePneu::create([
            'garage_id' => $this->garage->id,
            'nom_particulier' => 'Dupont Jean',
            'telephone' => '0612345678',
            'quantite' => 4,
            'date' => now()->toDateString(),
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/pneus-particuliers', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('data.0.nom_particulier', 'Dupont Jean');
    }

    public function test_can_filter_pneus_by_annee(): void
    {
        PriseChargePneu::create([
            'garage_id' => $this->garage->id,
            'nom_particulier' => 'Current Year',
            'quantite' => 2,
            'date' => '2025-06-15',
            'cree_par' => $this->admin->id,
        ]);

        PriseChargePneu::create([
            'garage_id' => $this->garage->id,
            'nom_particulier' => 'Previous Year',
            'quantite' => 3,
            'date' => '2024-03-10',
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/pneus-particuliers?annee=2025', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_annual_counter(): void
    {
        PriseChargePneu::create([
            'garage_id' => $this->garage->id,
            'nom_particulier' => 'Dupont',
            'quantite' => 4,
            'date' => '2025-03-15',
            'cree_par' => $this->admin->id,
        ]);

        PriseChargePneu::create([
            'garage_id' => $this->garage->id,
            'nom_particulier' => 'Martin',
            'quantite' => 6,
            'date' => '2025-07-20',
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/pneus-particuliers/compteur?annee=2025', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('annee', 2025)
            ->assertJsonPath('total_prises_en_charge', 2)
            ->assertJsonPath('total_pneus', 10);
    }

    public function test_annual_counter_defaults_to_current_year(): void
    {
        PriseChargePneu::create([
            'garage_id' => $this->garage->id,
            'nom_particulier' => 'Dupont',
            'quantite' => 3,
            'date' => now()->toDateString(),
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/pneus-particuliers/compteur', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('annee', (int) now()->year);
    }

    public function test_pneus_require_authentication(): void
    {
        $response = $this->getJson('/api/pneus-particuliers', $this->withGarageHeader());

        $response->assertUnauthorized();
    }

    public function test_pneus_require_garage_header(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/pneus-particuliers');

        $response->assertStatus(400);
    }
}
