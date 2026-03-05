<?php

namespace Tests\Unit;

use App\Models\Collecteur;
use App\Models\Enlevement;
use App\Models\LigneEnlevement;
use App\Models\TypeDechet;
use App\Services\RegistreService;
use Tests\TestCase;

class RegistreServiceTest extends TestCase
{
    private RegistreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
        $this->service = new RegistreService;
    }

    public function test_build_registre_returns_array(): void
    {
        $result = $this->service->buildRegistre($this->garage->id);

        $this->assertIsArray($result);
    }

    public function test_build_registre_includes_completed_enlevements(): void
    {
        $type = TypeDechet::where('dangereux', false)->first();
        $collecteur = Collecteur::create([
            'garage_id' => $this->garage->id,
            'raison_sociale' => 'Registre Collecte',
            'siret' => '11111111111111',
            'numero_autorisation' => 'AUTH-REG',
            'date_validite_autorisation' => now()->addYear(),
            'actif' => true,
        ]);

        $enlevement = Enlevement::create([
            'garage_id' => $this->garage->id,
            'numero' => 'ENL-2024-00001',
            'collecteur_id' => $collecteur->id,
            'date_prevue' => now()->subDays(5),
            'date_effective' => now()->subDays(5),
            'statut' => 'complete',
            'cree_par' => $this->admin->id,
        ]);

        LigneEnlevement::create([
            'enlevement_id' => $enlevement->id,
            'type_dechet_id' => $type->id,
            'quantite_prevue' => 100,
            'quantite_effective' => 95,
            'unite' => 'kg',
        ]);

        $result = $this->service->buildRegistre($this->garage->id);

        $this->assertNotEmpty($result);
        $this->assertEquals('Registre Collecte', $result[0]['collecteur'] ?? $result[0]['collecteur_raison_sociale'] ?? '');
    }

    public function test_build_registre_filters_by_date(): void
    {
        $result = $this->service->buildRegistre(
            $this->garage->id,
            ['date_debut' => now()->addYear()->toDateString()]
        );

        $this->assertEmpty($result);
    }
}
