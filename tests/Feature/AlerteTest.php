<?php

namespace Tests\Feature;

use App\Models\Alerte;
use Tests\TestCase;

class AlerteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_list_alertes(): void
    {
        Alerte::create([
            'garage_id' => $this->garage->id,
            'type' => 'conteneur_80',
            'priorite' => 'moyenne',
            'message' => 'Conteneur a 80% de capacite',
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/alertes', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_alerte_count(): void
    {
        Alerte::create([
            'garage_id' => $this->garage->id,
            'type' => 'conteneur_95',
            'priorite' => 'haute',
            'message' => 'Conteneur presque plein',
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/alertes/count', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_can_resolve_alerte(): void
    {
        $alerte = Alerte::create([
            'garage_id' => $this->garage->id,
            'type' => 'fds_manquante',
            'priorite' => 'moyenne',
            'message' => 'FDS manquante',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/alertes/{$alerte->id}/resolve", [], $this->withGarageHeader());

        $response->assertOk();
        $alerte->refresh();
        $this->assertTrue($alerte->resolue);
    }

    public function test_can_mark_alertes_read(): void
    {
        Alerte::create([
            'garage_id' => $this->garage->id,
            'type' => 'enlevement_j3',
            'priorite' => 'basse',
            'message' => 'Enlevement dans 3 jours',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson('/api/alertes/mark-read', ['all' => true], $this->withGarageHeader());

        $response->assertOk();
    }
}
