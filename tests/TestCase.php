<?php

namespace Tests;

use App\Models\Entreprise;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected Entreprise $entreprise;

    protected Garage $garage;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\TypeDechetSeeder::class);
    }

    protected function createEntreprise(array $attrs = []): Entreprise
    {
        return Entreprise::create(array_merge([
            'raison_sociale' => 'Test Garage SARL',
            'siret' => '12345678901234',
            'adresse' => '1 rue du Test',
            'code_postal' => '75001',
            'ville' => 'Paris',
            'plan' => 'gratuit',
        ], $attrs));
    }

    protected function createGarage(Entreprise $entreprise, array $attrs = []): Garage
    {
        return Garage::create(array_merge([
            'entreprise_id' => $entreprise->id,
            'nom' => 'Garage Test',
            'adresse' => '1 rue du Test',
            'code_postal' => '75001',
            'ville' => 'Paris',
            'activites' => ['mecanique'],
            'actif' => true,
        ], $attrs));
    }

    protected function createAdmin(Entreprise $entreprise, Garage $garage): User
    {
        $user = User::create([
            'entreprise_id' => $entreprise->id,
            'nom' => 'Admin',
            'prenom' => 'Test',
            'email' => 'admin@test.com',
            'password' => 'password',
            'role' => 'admin',
            'garages_ids' => [$garage->id],
            'actif' => true,
        ]);
        $user->assignRole('admin');

        return $user;
    }

    protected function createUser(Entreprise $entreprise, Garage $garage, string $role = 'mecanicien', array $attrs = []): User
    {
        $user = User::create(array_merge([
            'entreprise_id' => $entreprise->id,
            'nom' => 'User',
            'prenom' => ucfirst($role),
            'email' => $role.'@test.com',
            'password' => 'password',
            'role' => $role,
            'garages_ids' => [$garage->id],
            'actif' => true,
        ], $attrs));
        $user->assignRole($role);

        return $user;
    }

    protected function createSuperAdmin(): User
    {
        $user = User::create([
            'entreprise_id' => $this->entreprise->id,
            'nom' => 'Super',
            'prenom' => 'Admin',
            'email' => 'superadmin@test.com',
            'password' => 'password',
            'role' => 'super_admin',
            'actif' => true,
        ]);
        $user->assignRole('super_admin');
        $user->garages()->attach($this->garage->id);

        return $user;
    }

    protected function setupTestEnvironment(): void
    {
        $this->entreprise = $this->createEntreprise();
        $this->garage = $this->createGarage($this->entreprise);
        $this->admin = $this->createAdmin($this->entreprise, $this->garage);
    }

    protected function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin);
    }

    protected function withGarageHeader(): array
    {
        return ['X-Garage-Id' => $this->garage->id];
    }
}
