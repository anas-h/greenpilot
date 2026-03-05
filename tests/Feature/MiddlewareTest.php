<?php

namespace Tests\Feature;

use App\Models\Conteneur;
use App\Models\Garage;
use App\Models\TypeDechet;
use App\Models\User;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    // ══════════════════════════════════════════════════════════════════
    // EnsureNotReadOnly (subscription.write)
    // ══════════════════════════════════════════════════════════════════

    public function test_read_only_blocks_post_when_trial_expired(): void
    {
        // Expire the trial and ensure no subscription
        $this->entreprise->update([
            'trial_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAsAdmin()
            ->postJson('/api/conteneurs', [
                'nom' => 'Should fail',
                'capacite' => 100,
                'unite' => 'kg',
            ], $this->withGarageHeader());

        $response->assertStatus(403)
            ->assertJsonPath('read_only', true)
            ->assertJsonPath('message', 'Votre essai a expire. Souscrivez un abonnement pour continuer.');
    }

    public function test_read_only_blocks_put_when_trial_expired(): void
    {
        // Create a conteneur while trial is active
        $type = TypeDechet::where('dangereux', true)->first();
        $conteneur = Conteneur::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $type ? $type->id : null,
            'nom' => 'Existing',
            'code_qr' => 'ECO-RO-001',
            'capacite' => 100,
            'unite' => 'kg',
            'actif' => true,
        ]);

        // Expire the trial
        $this->entreprise->update([
            'trial_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/conteneurs/{$conteneur->id}", [
                'nom' => 'Updated',
            ], $this->withGarageHeader());

        $response->assertStatus(403)
            ->assertJsonPath('read_only', true);
    }

    public function test_read_only_blocks_delete_when_trial_expired(): void
    {
        $conteneur = Conteneur::create([
            'garage_id' => $this->garage->id,
            'nom' => 'To Delete',
            'code_qr' => 'ECO-RO-DEL',
            'capacite' => 100,
            'unite' => 'kg',
            'actif' => true,
        ]);

        $this->entreprise->update([
            'trial_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/conteneurs/{$conteneur->id}", [], $this->withGarageHeader());

        $response->assertStatus(403)
            ->assertJsonPath('read_only', true);
    }

    public function test_read_only_allows_get_requests(): void
    {
        $this->entreprise->update([
            'trial_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', $this->withGarageHeader());

        $response->assertOk();
    }

    public function test_read_only_allows_during_active_trial(): void
    {
        $this->entreprise->update([
            'trial_ends_at' => now()->addDays(10),
        ]);

        $response = $this->actingAsAdmin()
            ->postJson('/api/conteneurs', [
                'nom' => 'During trial',
                'capacite' => 100,
                'unite' => 'kg',
            ], $this->withGarageHeader());

        // Should pass the middleware (might fail on validation, but not on 403)
        $this->assertNotEquals(403, $response->status());
    }

    public function test_super_admin_bypasses_read_only(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->entreprise->update([
            'trial_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/conteneurs', [
                'nom' => 'Super admin bypass',
                'capacite' => 100,
                'unite' => 'kg',
            ], $this->withGarageHeader());

        // Should pass the read-only middleware (not a 403 with read_only message)
        $this->assertNotEquals(403, $response->status());
    }

    // ══════════════════════════════════════════════════════════════════
    // EnsureEntrepriseActive (entreprise.active)
    // ══════════════════════════════════════════════════════════════════

    public function test_inactive_entreprise_blocks_access(): void
    {
        $this->entreprise->update(['actif' => false]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/auth/me');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Votre compte entreprise est desactive.');
    }

    public function test_inactive_entreprise_blocks_all_protected_routes(): void
    {
        $this->entreprise->update(['actif' => false]);

        // GET route
        $response = $this->actingAsAdmin()
            ->getJson('/api/subscription');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Votre compte entreprise est desactive.');
    }

    public function test_active_entreprise_allows_access(): void
    {
        $this->assertTrue($this->entreprise->actif);

        $response = $this->actingAsAdmin()
            ->getJson('/api/auth/me');

        $response->assertOk();
    }

    public function test_super_admin_bypasses_inactive_entreprise(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->entreprise->update(['actif' => false]);

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/auth/me');

        $response->assertOk();
    }

    // ══════════════════════════════════════════════════════════════════
    // EnsureGarageSelected (garage)
    // ══════════════════════════════════════════════════════════════════

    public function test_missing_garage_header_returns_400(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard');

        // No X-Garage-Id header
        $response->assertStatus(400)
            ->assertJsonPath('message', 'A garage must be selected. Please provide X-Garage-Id header.');
    }

    public function test_nonexistent_garage_returns_404(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', ['X-Garage-Id' => 99999]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Garage not found or inactive.');
    }

    public function test_inactive_garage_returns_404(): void
    {
        $inactiveGarage = $this->createGarage($this->entreprise, [
            'nom' => 'Inactive Garage',
            'actif' => false,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', ['X-Garage-Id' => $inactiveGarage->id]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Garage not found or inactive.');
    }

    public function test_unauthorized_garage_returns_403(): void
    {
        // Create another enterprise with its own garage
        $otherEntreprise = $this->createEntreprise([
            'siret' => '99999999999999',
            'raison_sociale' => 'Other SARL',
        ]);
        $otherGarage = $this->createGarage($otherEntreprise, ['nom' => 'Other Garage']);

        // Admin from first enterprise trying to access other enterprise's garage
        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', ['X-Garage-Id' => $otherGarage->id]);

        // Should return 404 because the garage doesn't match the user's enterprise
        $response->assertStatus(404);
    }

    public function test_mecanicien_cannot_access_unassigned_garage(): void
    {
        $garage2 = $this->createGarage($this->entreprise, ['nom' => 'Second Garage']);

        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien', [
            'email' => 'meca-middleware@test.com',
        ]);

        $response = $this->actingAs($mecanicien)
            ->getJson('/api/dashboard', ['X-Garage-Id' => $garage2->id]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'You do not have access to this garage.');
    }

    public function test_valid_garage_header_allows_access(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', $this->withGarageHeader());

        $response->assertOk();
    }

    public function test_super_admin_can_access_any_garage(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $otherEntreprise = $this->createEntreprise([
            'siret' => '11112222333344',
            'raison_sociale' => 'Cross SARL',
        ]);
        $otherGarage = $this->createGarage($otherEntreprise, ['nom' => 'Cross Garage']);

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/dashboard', ['X-Garage-Id' => $otherGarage->id]);

        $response->assertOk();
    }

    public function test_admin_can_access_all_garages_in_own_entreprise(): void
    {
        $garage2 = $this->createGarage($this->entreprise, ['nom' => 'Admin Garage 2']);

        $response = $this->actingAsAdmin()
            ->getJson('/api/dashboard', ['X-Garage-Id' => $garage2->id]);

        $response->assertOk();
    }
}
