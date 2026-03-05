<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConformiteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_score_endpoint_returns_expected_structure(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/conformite/score', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'score_global',
                    'details' => [
                        'conteneurs' => ['poids', 'score', 'details'],
                        'bsd' => ['poids', 'score', 'details'],
                        'registre' => ['poids', 'score', 'details'],
                        'autorisations' => ['poids', 'score', 'details'],
                        'fds' => ['poids', 'score', 'details'],
                        'trackdechets' => ['poids', 'score', 'details'],
                    ],
                ],
            ]);
    }

    public function test_score_global_is_between_0_and_100(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/conformite/score', $this->withGarageHeader());

        $response->assertOk();

        $scoreGlobal = $response->json('data.score_global');
        $this->assertGreaterThanOrEqual(0, $scoreGlobal);
        $this->assertLessThanOrEqual(100, $scoreGlobal);
    }

    public function test_score_details_contain_correct_weights(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/conformite/score', $this->withGarageHeader());

        $response->assertOk();

        $details = $response->json('data.details');
        $this->assertEquals(20, $details['conteneurs']['poids']);
        $this->assertEquals(25, $details['bsd']['poids']);
        $this->assertEquals(20, $details['registre']['poids']);
        $this->assertEquals(15, $details['autorisations']['poids']);
        $this->assertEquals(10, $details['fds']['poids']);
        $this->assertEquals(10, $details['trackdechets']['poids']);
    }

    public function test_score_requires_authentication(): void
    {
        $response = $this->getJson('/api/conformite/score', $this->withGarageHeader());

        $response->assertUnauthorized();
    }

    public function test_score_requires_garage_header(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/conformite/score');

        $response->assertStatus(400);
    }
}
