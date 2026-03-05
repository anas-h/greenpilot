<?php

namespace Tests\Unit;

use App\Models\Conteneur;
use App\Services\ConteneurService;
use Tests\TestCase;

class ConteneurServiceTest extends TestCase
{
    private ConteneurService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
        $this->service = new ConteneurService;
    }

    public function test_adjust_niveau_increases_level(): void
    {
        $conteneur = $this->createConteneur(0, 200);

        $result = $this->service->adjustNiveau($conteneur, 50, 'ajout', $this->admin->id);

        $this->assertEquals(50, (float) $result->niveau_actuel);
        $this->assertDatabaseHas('historique_conteneurs', [
            'conteneur_id' => $conteneur->id,
            'type_evenement' => 'ajout',
            'quantite' => 50,
            'niveau_avant' => 0,
            'niveau_apres' => 50,
        ]);
    }

    public function test_adjust_niveau_clamps_at_capacity(): void
    {
        $conteneur = $this->createConteneur(190, 200);

        $result = $this->service->adjustNiveau($conteneur, 50, 'ajout', $this->admin->id);

        $this->assertEquals(200, (float) $result->niveau_actuel); // Clamped
    }

    public function test_adjust_niveau_clamps_at_zero(): void
    {
        $conteneur = $this->createConteneur(10, 200);

        $result = $this->service->adjustNiveau($conteneur, -50, 'retrait', $this->admin->id);

        $this->assertEquals(0, (float) $result->niveau_actuel); // Clamped
    }

    public function test_check_seuils_returns_warning_at_80_percent(): void
    {
        $conteneur = $this->createConteneur(160, 200);

        $result = $this->service->checkSeuils($conteneur);

        $this->assertNotNull($result);
        $this->assertEquals('conteneur_80', $result['type']);
        $this->assertEquals('moyenne', $result['priorite']);
    }

    public function test_check_seuils_returns_critical_at_95_percent(): void
    {
        $conteneur = $this->createConteneur(195, 200);

        $result = $this->service->checkSeuils($conteneur);

        $this->assertNotNull($result);
        $this->assertEquals('conteneur_95', $result['type']);
        $this->assertEquals('haute', $result['priorite']);
    }

    public function test_check_seuils_returns_null_when_ok(): void
    {
        $conteneur = $this->createConteneur(50, 200);

        $result = $this->service->checkSeuils($conteneur);

        $this->assertNull($result);
    }

    public function test_check_conformite_garage(): void
    {
        $result = $this->service->checkConformiteGarage($this->garage->id);

        $this->assertArrayHasKey('conforme', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('total_conteneurs', $result);
    }

    private function createConteneur(float $niveau, float $capacite): Conteneur
    {
        return Conteneur::create([
            'garage_id' => $this->garage->id,
            'nom' => 'Test conteneur',
            'code_qr' => 'ECO-TEST-'.uniqid(),
            'capacite' => $capacite,
            'unite' => 'litres',
            'niveau_actuel' => $niveau,
            'actif' => true,
        ]);
    }
}
