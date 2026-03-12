<?php

namespace Tests\Unit;

use App\Models\Collecteur;
use App\Models\ConfigTrackdechets;
use App\Services\ConformiteService;
use Tests\TestCase;

class ConformiteServiceTest extends TestCase
{
    private ConformiteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
        $this->service = new ConformiteService;
    }

    public function test_returns_score_structure(): void
    {
        $result = $this->service->calculateScore($this->garage->id);

        $this->assertArrayHasKey('score_global', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('conteneurs', $result['details']);
        $this->assertArrayHasKey('bsd', $result['details']);
        $this->assertArrayHasKey('registre', $result['details']);
        $this->assertArrayHasKey('autorisations', $result['details']);
        $this->assertArrayHasKey('fds', $result['details']);
        $this->assertArrayHasKey('trackdechets', $result['details']);
    }

    public function test_score_is_between_0_and_100(): void
    {
        $result = $this->service->calculateScore($this->garage->id);

        $this->assertGreaterThanOrEqual(0, $result['score_global']);
        $this->assertLessThanOrEqual(100, $result['score_global']);
    }

    public function test_trackdechets_score_is_0_when_not_configured(): void
    {
        $result = $this->service->calculateScore($this->garage->id);

        $this->assertEquals(0, $result['details']['trackdechets']['score']);
    }

    public function test_trackdechets_score_is_50_when_configured_but_not_verified(): void
    {
        ConfigTrackdechets::create([
            'entreprise_id' => $this->entreprise->id,
            'api_token' => 'test-token',
            'environnement' => 'sandbox',
            'actif' => true,
        ]);

        $result = $this->service->calculateScore($this->garage->id);

        // Active but not verified (siret_verifie is not set) => score is 50
        $this->assertEquals(50, $result['details']['trackdechets']['score']);
    }

    public function test_expired_collecteur_lowers_autorisations_score(): void
    {
        Collecteur::create([
            'garage_id' => $this->garage->id,
            'raison_sociale' => 'Expire',
            'siret' => '88888888888888',
            'numero_autorisation' => 'EXP-001',
            'date_validite_autorisation' => now()->subDay(),
            'actif' => true,
        ]);

        $result = $this->service->calculateScore($this->garage->id);

        $this->assertLessThan(100, $result['details']['autorisations']['score']);
    }
}
