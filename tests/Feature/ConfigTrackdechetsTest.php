<?php

namespace Tests\Feature;

use App\Models\ConfigTrackdechets;
use Tests\TestCase;

class ConfigTrackdechetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_show_returns_not_configured_when_no_config(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/config-trackdechets', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('configured', false)
            ->assertJsonPath('config', null);
    }

    public function test_show_returns_config_with_masked_token(): void
    {
        ConfigTrackdechets::create([
            'entreprise_id' => $this->entreprise->id,
            'api_token' => 'my-super-secret-api-token-12345678',
            'environnement' => 'sandbox',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/config-trackdechets', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('configured', true)
            ->assertJsonStructure([
                'config' => [
                    'id',
                    'entreprise_id',
                    'environnement',
                    'api_token_masked',
                    'actif',
                ],
            ]);

        // Verify the token is masked (should not contain the full token)
        $maskedToken = $response->json('config.api_token_masked');
        $this->assertStringContainsString('*', $maskedToken);
        // Last 8 chars should be visible
        $this->assertStringEndsWith(substr('my-super-secret-api-token-12345678', -8), $maskedToken);
    }

    public function test_can_store_config(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/config-trackdechets', [
                'api_token' => 'test-api-token-1234567890',
                'environnement' => 'sandbox',
            ], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('message', 'Configuration Trackdechets enregistree.')
            ->assertJsonPath('config.environnement', 'sandbox')
            ->assertJsonPath('config.entreprise_id', $this->entreprise->id);

        $this->assertDatabaseHas('config_trackdechets', [
            'entreprise_id' => $this->entreprise->id,
            'environnement' => 'sandbox',
        ]);
    }

    public function test_store_updates_existing_config(): void
    {
        ConfigTrackdechets::create([
            'entreprise_id' => $this->entreprise->id,
            'api_token' => 'old-api-token-1234567890',
            'environnement' => 'sandbox',
            'actif' => true,
        ]);

        $response = $this->actingAsAdmin()
            ->postJson('/api/config-trackdechets', [
                'api_token' => 'new-api-token-0987654321',
                'environnement' => 'production',
            ], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('config.environnement', 'production');

        // Verify only one config exists for this entreprise
        $this->assertEquals(1, ConfigTrackdechets::where('entreprise_id', $this->entreprise->id)->count());
    }

    public function test_store_validates_api_token_required(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/config-trackdechets', [
                'environnement' => 'sandbox',
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors('api_token');
    }

    public function test_store_validates_api_token_min_length(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/config-trackdechets', [
                'api_token' => 'short',
                'environnement' => 'sandbox',
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors('api_token');
    }

    public function test_store_validates_environnement_values(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/config-trackdechets', [
                'api_token' => 'test-api-token-1234567890',
                'environnement' => 'invalid',
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors('environnement');
    }

    public function test_store_validates_environnement_required(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/config-trackdechets', [
                'api_token' => 'test-api-token-1234567890',
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors('environnement');
    }

    public function test_config_requires_authentication(): void
    {
        $response = $this->getJson('/api/config-trackdechets', $this->withGarageHeader());

        $response->assertUnauthorized();
    }

    public function test_config_requires_garage_header(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/config-trackdechets');

        $response->assertStatus(400);
    }
}
