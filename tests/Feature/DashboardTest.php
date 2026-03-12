<?php

namespace Tests\Feature;

use App\Models\Alerte;
use App\Models\Conteneur;
use App\Models\Production;
use App\Models\TypeDechet;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard', $this->withGarageHeader());

        $response->assertUnauthorized();
    }

    public function test_dashboard_requires_garage_header(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard');

        $response->assertStatus(400);
    }

    public function test_dashboard_returns_kpis_structure(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonStructure([
                'kpis' => [
                    'productions_mois',
                    'enlevements_planifies',
                    'alertes_actives',
                    'score_conformite',
                ],
                'alertes',
                'conteneurs',
                'enlevements',
            ]);
    }

    public function test_dashboard_counts_productions_for_current_month(): void
    {
        $type = TypeDechet::first();

        Production::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $type->id,
            'quantite' => 5,
            'unite' => 'kg',
            'date_production' => now(),
            'source_type' => 'manuelle',
            'employe_id' => $this->admin->id,
        ]);

        Production::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $type->id,
            'quantite' => 3,
            'unite' => 'kg',
            'date_production' => now()->subMonths(2),
            'source_type' => 'manuelle',
            'employe_id' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('kpis.productions_mois', 1);
    }

    public function test_dashboard_counts_active_alertes(): void
    {
        Alerte::create([
            'garage_id' => $this->garage->id,
            'type' => 'capacite',
            'priorite' => 'haute',
            'titre' => 'Conteneur plein',
            'message' => 'Capacité atteinte',
        ]);

        Alerte::create([
            'garage_id' => $this->garage->id,
            'type' => 'expiration',
            'priorite' => 'moyenne',
            'titre' => 'Expirée',
            'message' => 'Autorisation expirée',
            'resolue' => true,
            'date_resolution' => now(),
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('kpis.alertes_actives', 1);
    }

    public function test_dashboard_returns_top_containers(): void
    {
        $type = TypeDechet::first();

        Conteneur::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $type->id,
            'nom' => 'Fut 90%',
            'code_qr' => 'QR-TEST-90',
            'capacite' => 100,
            'unite' => 'kg',
            'niveau_actuel' => 90,
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'conteneurs');
    }

    public function test_economie_returns_financial_data(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard/economie', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonStructure([
                'kpis' => ['cout_total', 'revenu_total', 'cout_net'],
                'evolution' => ['labels', 'couts', 'revenus'],
                'repartition' => ['labels', 'values'],
                'comparatif' => ['headers', 'items'],
            ]);
    }

    public function test_economie_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard/economie', $this->withGarageHeader());

        $response->assertUnauthorized();
    }
}
