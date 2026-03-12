<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\Garage;
use App\Models\User;
use Tests\TestCase;

class AdminEntrepriseTest extends TestCase
{
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
        $this->superAdmin = $this->createSuperAdmin();
    }

    // ── Index (list) ────────────────────────────────────────────────

    public function test_super_admin_can_list_entreprises(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/entreprises');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_list_entreprises_returns_counts(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/entreprises');

        $response->assertOk();

        $first = $response->json('data.0');
        $this->assertArrayHasKey('garages_count', $first);
        $this->assertArrayHasKey('users_count', $first);
    }

    public function test_list_entreprises_can_filter_by_plan(): void
    {
        $this->createEntreprise([
            'siret' => '11111111111111',
            'raison_sociale' => 'Premium SARL',
            'plan' => 'premium',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/entreprises?plan=premium');

        $response->assertOk();
        foreach ($response->json('data') as $entreprise) {
            $this->assertEquals('premium', $entreprise['plan']);
        }
    }

    public function test_list_entreprises_can_filter_by_actif(): void
    {
        $this->createEntreprise([
            'siret' => '22222222222222',
            'raison_sociale' => 'Inactive SARL',
            'actif' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/entreprises?actif=false');

        $response->assertOk();
        foreach ($response->json('data') as $entreprise) {
            $this->assertFalse($entreprise['actif']);
        }
    }

    public function test_list_entreprises_excludes_archived_by_default(): void
    {
        $archived = $this->createEntreprise([
            'siret' => '33333333333333',
            'raison_sociale' => 'Archived SARL',
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/entreprises');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($archived->id, $ids);
    }

    public function test_list_entreprises_can_show_only_archived(): void
    {
        $archived = $this->createEntreprise([
            'siret' => '33333333333333',
            'raison_sociale' => 'Archived SARL',
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/entreprises?archived=true');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($archived->id, $ids);
    }

    // ── Show ─────────────────────────────────────────────────────────

    public function test_super_admin_can_show_entreprise(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson("/api/admin/entreprises/{$this->entreprise->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'raison_sociale', 'siret'],
                'subscription' => ['on_trial', 'subscribed', 'read_only', 'trial_days_remaining'],
            ]);
    }

    public function test_show_entreprise_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/entreprises/99999');

        $response->assertNotFound();
    }

    // ── Store ────────────────────────────────────────────────────────

    public function test_super_admin_can_create_entreprise(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/entreprises', [
                'raison_sociale' => 'Nouvelle Entreprise',
                'siret' => '44444444444444',
                'adresse' => '10 rue du Test',
                'code_postal' => '69001',
                'ville' => 'Lyon',
                'plan' => 'premium',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.raison_sociale', 'Nouvelle Entreprise')
            ->assertJsonPath('data.plan', 'premium')
            ->assertJsonPath('data.actif', true);

        $this->assertDatabaseHas('entreprises', [
            'raison_sociale' => 'Nouvelle Entreprise',
            'siret' => '44444444444444',
        ]);
    }

    public function test_create_entreprise_validates_required_fields(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/entreprises', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['raison_sociale', 'siret']);
    }

    public function test_create_entreprise_sets_trial_period(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/admin/entreprises', [
                'raison_sociale' => 'Trial Entreprise',
                'siret' => '55555555555555',
                'adresse' => '5 rue du Trial',
                'code_postal' => '75003',
                'ville' => 'Paris',
            ]);

        $response->assertStatus(201);
        $created = Entreprise::find($response->json('data.id'));
        $this->assertNotNull($created->trial_ends_at);
        $this->assertTrue($created->trial_ends_at->isFuture());
    }

    // ── Update ───────────────────────────────────────────────────────

    public function test_super_admin_can_update_entreprise(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/admin/entreprises/{$this->entreprise->id}", [
                'raison_sociale' => 'Updated SARL',
                'plan' => 'premium',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.raison_sociale', 'Updated SARL')
            ->assertJsonPath('data.plan', 'premium');
    }

    public function test_update_entreprise_validates_plan(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->putJson("/api/admin/entreprises/{$this->entreprise->id}", [
                'plan' => 'invalid_plan',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan']);
    }

    // ── Toggle Active ────────────────────────────────────────────────

    public function test_super_admin_can_toggle_active(): void
    {
        $this->assertTrue($this->entreprise->actif);

        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/toggle-active");

        $response->assertOk()
            ->assertJsonPath('data.actif', false);

        // Toggle back
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/toggle-active");

        $response->assertOk()
            ->assertJsonPath('data.actif', true);
    }

    // ── Toggle Archive ───────────────────────────────────────────────

    public function test_super_admin_can_toggle_archive(): void
    {
        $this->assertNull($this->entreprise->archived_at);

        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/toggle-archive");

        $response->assertOk();
        $this->assertNotNull($response->json('data.archived_at'));

        // Toggle back
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/toggle-archive");

        $response->assertOk();
        $this->assertNull($response->json('data.archived_at'));
    }

    // ── Store Garage ─────────────────────────────────────────────────

    public function test_super_admin_can_create_garage_for_entreprise(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/garages", [
                'nom' => 'Nouveau Garage Admin',
                'adresse' => '20 avenue du Test',
                'code_postal' => '75002',
                'ville' => 'Paris',
                'activites' => ['carrosserie'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nom', 'Nouveau Garage Admin')
            ->assertJsonPath('data.entreprise_id', $this->entreprise->id);

        $this->assertDatabaseHas('garages', ['nom' => 'Nouveau Garage Admin']);
    }

    public function test_create_garage_auto_determines_icpe_regime(): void
    {
        // surface_atelier >= 5000 => enregistrement
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/garages", [
                'nom' => 'Grand Garage',
                'adresse' => '1 rue du Grand',
                'code_postal' => '75001',
                'ville' => 'Paris',
                'surface_atelier' => 6000,
            ]);

        $response->assertStatus(201);
        $garage = Garage::find($response->json('data.id'));
        $this->assertEquals('enregistrement', $garage->regime_icpe);

        // surface_atelier >= 2000 => declaration
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/garages", [
                'nom' => 'Moyen Garage',
                'adresse' => '2 rue du Moyen',
                'code_postal' => '75002',
                'ville' => 'Paris',
                'surface_atelier' => 3000,
            ]);

        $response->assertStatus(201);
        $garage = Garage::find($response->json('data.id'));
        $this->assertEquals('declaration', $garage->regime_icpe);

        // surface_atelier < 2000 => non_classe
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/garages", [
                'nom' => 'Petit Garage',
                'adresse' => '3 rue du Petit',
                'code_postal' => '75003',
                'ville' => 'Paris',
                'surface_atelier' => 500,
            ]);

        $response->assertStatus(201);
        $garage = Garage::find($response->json('data.id'));
        $this->assertEquals('non_classe', $garage->regime_icpe);
    }

    // ── Store User ───────────────────────────────────────────────────

    public function test_super_admin_can_create_user_for_entreprise(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/users", [
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'jean.dupont@test.com',
                'password' => 'password123',
                'role' => 'mecanicien',
                'garages_ids' => [$this->garage->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nom', 'Dupont')
            ->assertJsonPath('data.entreprise_id', $this->entreprise->id);

        $this->assertDatabaseHas('users', [
            'email' => 'jean.dupont@test.com',
            'entreprise_id' => $this->entreprise->id,
        ]);
    }

    public function test_create_user_validates_required_fields(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/users", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nom', 'prenom', 'email', 'password', 'role']);
    }

    public function test_create_user_validates_role(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/users", [
                'nom' => 'Test',
                'prenom' => 'User',
                'email' => 'test.role@test.com',
                'password' => 'password123',
                'role' => 'super_admin', // not allowed
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    // ── Stats ────────────────────────────────────────────────────────

    public function test_super_admin_can_get_stats(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_entreprises',
                    'total_garages',
                    'total_users',
                    'par_plan' => ['standard', 'premium'],
                    'en_trial',
                    'abonnes',
                    'expires',
                    'entreprises_actives',
                    'entreprises_inactives',
                    'entreprises_archivees',
                    'entreprises_recentes',
                    'bordereaux_en_erreur',
                ],
            ]);
    }

    public function test_stats_reflect_actual_counts(): void
    {
        // We already have 1 entreprise from setUp
        $this->createEntreprise([
            'siret' => '66666666666666',
            'raison_sociale' => 'Second SARL',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/admin/stats');

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.total_entreprises'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.total_garages'));
    }

    // ── Authorization: admin cannot access ───────────────────────────

    public function test_admin_cannot_list_entreprises(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/entreprises');

        $response->assertForbidden();
    }

    public function test_admin_cannot_show_entreprise(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson("/api/admin/entreprises/{$this->entreprise->id}");

        $response->assertForbidden();
    }

    public function test_admin_cannot_create_entreprise(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/entreprises', [
                'raison_sociale' => 'Hack SARL',
                'siret' => '77777777777777',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_update_entreprise(): void
    {
        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/entreprises/{$this->entreprise->id}", [
                'raison_sociale' => 'Hacked',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_toggle_active(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/toggle-active");

        $response->assertForbidden();
    }

    public function test_admin_cannot_toggle_archive(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/toggle-archive");

        $response->assertForbidden();
    }

    public function test_admin_cannot_get_stats(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/stats');

        $response->assertForbidden();
    }

    public function test_admin_cannot_create_garage_for_entreprise(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/garages", [
                'nom' => 'Should Fail',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_create_user_for_entreprise(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson("/api/admin/entreprises/{$this->entreprise->id}/users", [
                'nom' => 'Should',
                'prenom' => 'Fail',
                'email' => 'fail@test.com',
                'password' => 'password123',
                'role' => 'mecanicien',
            ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_admin_routes(): void
    {
        $response = $this->getJson('/api/admin/entreprises');

        $response->assertUnauthorized();
    }
}
