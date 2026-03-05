<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
        $this->superAdmin = $this->createSuperAdmin();
    }

    // ── Index (list) ────────────────────────────────────────────────

    public function test_super_admin_can_list_users(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_list_users_includes_relations(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/users');

        $response->assertOk();
        $first = $response->json('data.0');
        $this->assertArrayHasKey('entreprise', $first);
        $this->assertArrayHasKey('roles', $first);
        $this->assertArrayHasKey('garages', $first);
    }

    public function test_list_users_can_filter_by_entreprise(): void
    {
        $otherEntreprise = $this->createEntreprise([
            'siret' => '88888888888888',
            'raison_sociale' => 'Autre SARL',
        ]);
        $otherGarage = $this->createGarage($otherEntreprise);
        $this->createUser($otherEntreprise, $otherGarage, 'mecanicien', [
            'email' => 'other@other.com',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson("/api/admin/users?entreprise_id={$otherEntreprise->id}");

        $response->assertOk();
        foreach ($response->json('data') as $user) {
            $this->assertEquals($otherEntreprise->id, $user['entreprise_id']);
        }
    }

    // ── Update ───────────────────────────────────────────────────────

    public function test_super_admin_can_update_user(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/admin/users/{$this->admin->id}", [
                'nom' => 'Updated',
                'prenom' => 'Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.nom', 'Updated')
            ->assertJsonPath('data.prenom', 'Name');
    }

    public function test_super_admin_can_change_user_role(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/admin/users/{$this->admin->id}", [
                'role' => 'chef_atelier',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.role', 'chef_atelier');

        $this->admin->refresh();
        $this->assertTrue($this->admin->hasRole('chef_atelier'));
    }

    public function test_super_admin_can_deactivate_user(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/admin/users/{$this->admin->id}", [
                'actif' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.actif', false);
    }

    public function test_super_admin_can_update_user_garages(): void
    {
        $garage2 = $this->createGarage($this->entreprise, ['nom' => 'Second Garage']);

        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/admin/users/{$this->admin->id}", [
                'garages_ids' => [$this->garage->id, $garage2->id],
            ]);

        $response->assertOk();
        $garageIds = collect($response->json('data.garages'))->pluck('id')->toArray();
        $this->assertContains($garage2->id, $garageIds);
    }

    public function test_update_user_validates_role(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/admin/users/{$this->admin->id}", [
                'role' => 'invalid_role',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_update_user_with_empty_password_does_not_change_it(): void
    {
        $originalPassword = $this->admin->password;

        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/admin/users/{$this->admin->id}", [
                'nom' => 'KeepPassword',
                'password' => '',
            ]);

        $response->assertOk();
        $this->admin->refresh();
        $this->assertEquals($originalPassword, $this->admin->password);
    }

    // ── Destroy ──────────────────────────────────────────────────────

    public function test_super_admin_can_delete_user(): void
    {
        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien', [
            'email' => 'to-delete@test.com',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/admin/users/{$mecanicien->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Utilisateur supprime.');

        $this->assertSoftDeleted('users', ['id' => $mecanicien->id]);
    }

    public function test_cannot_delete_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/admin/users/{$this->superAdmin->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Impossible de supprimer un super administrateur.');

        // Ensure the super admin still exists
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_delete_user_cleans_up_relations(): void
    {
        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien', [
            'email' => 'cleanup@test.com',
        ]);

        // Attach garage via pivot
        $mecanicien->garages()->sync([$this->garage->id]);

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/admin/users/{$mecanicien->id}");

        $response->assertOk();

        // Pivot should be cleaned
        $this->assertDatabaseMissing('user_garage', [
            'user_id' => $mecanicien->id,
        ]);
    }

    public function test_delete_nonexistent_user_returns_404(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson('/api/admin/users/99999');

        $response->assertNotFound();
    }

    // ── Login As (Impersonation) ─────────────────────────────────────

    public function test_super_admin_can_impersonate_user(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/users/{$this->admin->id}/login-as");

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user',
                'permissions',
                'impersonating',
            ])
            ->assertJsonPath('impersonating', true);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_cannot_impersonate_super_admin(): void
    {
        // Create another super_admin to try impersonating
        $otherSuperAdmin = User::create([
            'entreprise_id' => $this->entreprise->id,
            'nom' => 'Other',
            'prenom' => 'Super',
            'email' => 'other-super@test.com',
            'password' => 'password',
            'role' => 'super_admin',
            'actif' => true,
        ]);
        $otherSuperAdmin->assignRole('super_admin');

        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/users/{$otherSuperAdmin->id}/login-as");

        $response->assertStatus(422)
            ->assertJsonPath('message', "Impossible d'usurper un super administrateur.");
    }

    public function test_impersonation_token_has_expiration(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/users/{$this->admin->id}/login-as");

        $response->assertOk();

        // Verify the token was created with an expiration
        $this->admin->refresh();
        $latestToken = $this->admin->tokens()->latest()->first();
        $this->assertNotNull($latestToken->expires_at);
    }

    public function test_impersonate_nonexistent_user_returns_404(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/users/99999/login-as');

        $response->assertNotFound();
    }

    // ── Authorization: admin cannot access ───────────────────────────

    public function test_admin_cannot_list_users(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/users');

        $response->assertForbidden();
    }

    public function test_admin_cannot_update_user(): void
    {
        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien', [
            'email' => 'target@test.com',
        ]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/users/{$mecanicien->id}", [
                'nom' => 'Hacked',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_delete_user(): void
    {
        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien', [
            'email' => 'nodelete@test.com',
        ]);

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/admin/users/{$mecanicien->id}");

        $response->assertForbidden();
    }

    public function test_admin_cannot_impersonate(): void
    {
        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien', [
            'email' => 'noimpersonate@test.com',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/users/{$mecanicien->id}/login-as");

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_admin_user_routes(): void
    {
        $response = $this->getJson('/api/admin/users');

        $response->assertUnauthorized();
    }
}
