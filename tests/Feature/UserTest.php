<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_list_users(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/users');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_can_create_user(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/users', [
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'jean.dupont@test.com',
                'password' => 'password123',
                'role' => 'mecanicien',
                'garages_ids' => [$this->garage->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nom', 'Dupont');

        $this->assertDatabaseHas('users', ['email' => 'jean.dupont@test.com']);
    }

    public function test_create_user_respects_plan_limit(): void
    {
        $this->entreprise->update(['max_users' => 1]);

        $response = $this->actingAsAdmin()
            ->postJson('/api/users', [
                'nom' => 'Extra',
                'prenom' => 'User',
                'email' => 'extra@test.com',
                'password' => 'password123',
                'role' => 'mecanicien',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => "Limite d'utilisateurs atteinte pour votre plan. Passez a un plan superieur."]);
    }

    public function test_can_show_user(): void
    {
        $user = $this->createUser($this->entreprise, $this->garage, 'mecanicien');

        $response = $this->actingAsAdmin()
            ->getJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_can_update_user(): void
    {
        $user = $this->createUser($this->entreprise, $this->garage, 'mecanicien');

        $response = $this->actingAsAdmin()
            ->putJson("/api/users/{$user->id}", [
                'nom' => 'Modifié',
                'prenom' => 'Nom',
                'email' => $user->email,
                'role' => 'chef_atelier',
                'garages_ids' => [$this->garage->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.nom', 'Modifié');
    }

    public function test_cannot_self_delete(): void
    {
        $response = $this->actingAsAdmin()
            ->deleteJson("/api/users/{$this->admin->id}");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Vous ne pouvez pas supprimer votre propre compte.']);
    }

    public function test_can_delete_other_user(): void
    {
        $user = $this->createUser($this->entreprise, $this->garage, 'mecanicien');

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/users/{$user->id}");

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_cannot_access_another_entreprise_user(): void
    {
        $otherEntreprise = $this->createEntreprise(['siret' => '99999999999999']);
        $otherGarage = $this->createGarage($otherEntreprise);
        $otherUser = $this->createUser($otherEntreprise, $otherGarage, 'mecanicien', ['email' => 'other@other.com']);

        $response = $this->actingAsAdmin()
            ->getJson("/api/users/{$otherUser->id}");

        $response->assertForbidden();
    }

    public function test_mecanicien_cannot_create_users(): void
    {
        $mecanicien = $this->createUser($this->entreprise, $this->garage, 'mecanicien');

        $response = $this->actingAs($mecanicien)
            ->postJson('/api/users', [
                'nom' => 'Test',
                'prenom' => 'User',
                'email' => 'nonadmin@test.com',
                'password' => 'password123',
                'role' => 'mecanicien',
            ]);

        $response->assertForbidden();
    }

    public function test_create_user_validates_required_fields(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nom', 'prenom', 'email', 'password', 'role']);
    }

    public function test_create_user_validates_unique_email(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/users', [
                'nom' => 'Doublon',
                'prenom' => 'Email',
                'email' => 'admin@test.com',
                'password' => 'password123',
                'role' => 'mecanicien',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_user_assigns_garages_via_pivot(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/users', [
                'nom' => 'Pivot',
                'prenom' => 'Test',
                'email' => 'pivot@test.com',
                'password' => 'password123',
                'role' => 'mecanicien',
                'garages_ids' => [$this->garage->id],
            ]);

        $response->assertStatus(201);

        $user = User::where('email', 'pivot@test.com')->first();
        $this->assertTrue($user->garages->contains($this->garage));
    }
}
