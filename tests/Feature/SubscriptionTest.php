<?php

namespace Tests\Feature;

use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_get_subscription_status(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/subscription');

        $response->assertOk()
            ->assertJsonStructure([
                'plan',
                'trial_ends_at',
                'on_trial',
                'days_remaining',
                'subscribed',
                'read_only',
            ]);
    }

    public function test_subscription_status_shows_trial_info(): void
    {
        $this->entreprise->update([
            'trial_ends_at' => now()->addDays(10),
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/subscription');

        $response->assertOk()
            ->assertJsonPath('on_trial', true)
            ->assertJsonPath('days_remaining', 10);
    }

    public function test_subscription_status_shows_expired_trial(): void
    {
        $this->entreprise->update([
            'trial_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/subscription');

        $response->assertOk()
            ->assertJsonPath('on_trial', false)
            ->assertJsonPath('read_only', true);
    }

    public function test_can_get_stripe_config(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/subscription/config');

        $response->assertOk()
            ->assertJsonStructure(['public_key']);
    }

    public function test_create_subscription_validates_plan(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/subscription/create', [
                'plan' => 'invalid_plan',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan']);
    }

    public function test_create_subscription_requires_valid_plan(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/subscription/create', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan']);
    }

    public function test_subscription_status_requires_auth(): void
    {
        $response = $this->getJson('/api/subscription');

        $response->assertUnauthorized();
    }

    public function test_change_plan_validates_plan(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/subscription/change-plan', [
                'plan' => 'invalid',
            ]);

        $response->assertStatus(422);
    }

    public function test_invoices_returns_empty_without_stripe(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/subscription/invoices');

        $response->assertOk()
            ->assertJson([]);
    }

    public function test_payment_methods_returns_empty_without_stripe(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/subscription/payment-methods');

        $response->assertOk()
            ->assertJson([]);
    }
}
