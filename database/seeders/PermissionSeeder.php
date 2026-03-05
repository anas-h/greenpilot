<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Dashboard
            'dashboard.view',
            'dashboard.view_economie',
            'dashboard.view_multi',

            // Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Garages
            'garages.view',
            'garages.create',
            'garages.edit',
            'garages.delete',

            // Trackdechets
            'trackdechets.configure',

            // Types de dechets
            'types_dechets.view',
            'types_dechets.create',
            'types_dechets.edit',
            'types_dechets.delete',

            // Conteneurs
            'conteneurs.view',
            'conteneurs.create',
            'conteneurs.edit',
            'conteneurs.delete',

            // Collecteurs
            'collecteurs.view',
            'collecteurs.create',
            'collecteurs.edit',
            'collecteurs.delete',

            // Productions
            'productions.view',
            'productions.create',
            'productions.validate',

            // Enlevements
            'enlevements.view',
            'enlevements.create',
            'enlevements.edit',
            'enlevements.complete',

            // Bordereaux
            'bordereaux.view',
            'bordereaux.create',
            'bordereaux.publish',
            'bordereaux.sign',

            // Registre
            'registre.view',
            'registre.export',

            // Fiches de securite
            'fds.view',
            'fds.create',
            'fds.edit',
            'fds.delete',

            // Economie circulaire
            'economie.view',

            // Eco-contributions
            'eco_contributions.view',
            'eco_contributions.create',
            'eco_contributions.edit',

            // Mappings
            'mappings.view',
            'mappings.create',
            'mappings.edit',
            'mappings.delete',

            // Alertes
            'alertes.view',
            'alertes.resolve',

            // Platform admin
            'platform.manage_entreprises',
            'platform.manage_users',
            'platform.view_stats',
            'platform.manage_plans',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ---------------------------------------------------------------
        // Role: admin - ALL permissions
        // ---------------------------------------------------------------
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($permissions);

        // ---------------------------------------------------------------
        // Role: chef_atelier
        // ---------------------------------------------------------------
        $chefAtelierRole = Role::firstOrCreate(['name' => 'chef_atelier', 'guard_name' => 'web']);
        $chefAtelierRole->syncPermissions([
            'dashboard.view',

            // Types de dechets (all)
            'types_dechets.view',
            'types_dechets.create',
            'types_dechets.edit',
            'types_dechets.delete',

            // Conteneurs (all)
            'conteneurs.view',
            'conteneurs.create',
            'conteneurs.edit',
            'conteneurs.delete',

            // Collecteurs (all)
            'collecteurs.view',
            'collecteurs.create',
            'collecteurs.edit',
            'collecteurs.delete',

            // Productions
            'productions.view',
            'productions.create',
            'productions.validate',

            // Enlevements (all)
            'enlevements.view',
            'enlevements.create',
            'enlevements.edit',
            'enlevements.complete',

            // Registre
            'registre.view',

            // Fiches de securite (all)
            'fds.view',
            'fds.create',
            'fds.edit',
            'fds.delete',

            // Mappings (all)
            'mappings.view',
            'mappings.create',
            'mappings.edit',
            'mappings.delete',

            // Alertes (all)
            'alertes.view',
            'alertes.resolve',
        ]);

        // ---------------------------------------------------------------
        // Role: mecanicien
        // ---------------------------------------------------------------
        $mecanicienRole = Role::firstOrCreate(['name' => 'mecanicien', 'guard_name' => 'web']);
        $mecanicienRole->syncPermissions([
            'dashboard.view',

            // Productions (view + create only)
            'productions.view',
            'productions.create',

            // Conteneurs (view only)
            'conteneurs.view',

            // Alertes (view only)
            'alertes.view',
        ]);

        // ---------------------------------------------------------------
        // Role: comptable
        // ---------------------------------------------------------------
        $comptableRole = Role::firstOrCreate(['name' => 'comptable', 'guard_name' => 'web']);
        $comptableRole->syncPermissions([
            'dashboard.view',
            'dashboard.view_economie',

            // Registre
            'registre.view',
            'registre.export',

            // Economie circulaire
            'economie.view',

            // Eco-contributions (all)
            'eco_contributions.view',
            'eco_contributions.create',
            'eco_contributions.edit',

            // Alertes (view only)
            'alertes.view',
        ]);

        // ---------------------------------------------------------------
        // Role: super_admin - ALL permissions (including platform admin)
        // ---------------------------------------------------------------
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions($permissions);
    }
}
