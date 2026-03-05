<?php

namespace Database\Seeders;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $entreprise = Entreprise::firstOrCreate(
            ['siret' => '00000000000000'],
            [
                'raison_sociale' => 'GreenPilot Platform',
                'forme_juridique' => 'SAS',
                'adresse' => '',
                'code_postal' => '75000',
                'ville' => 'Paris',
                'plan' => 'premium',
                'actif' => true,
                'max_garages' => 999,
                'max_users' => 999,
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'admin@greenpilot.fr'],
            [
                'entreprise_id' => $entreprise->id,
                'nom' => 'Admin',
                'prenom' => 'Super',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
                'actif' => true,
                'preferences_notifications' => [
                    'email_alertes_critiques' => true,
                    'email_rappels' => true,
                ],
            ]
        );

        $user->assignRole('super_admin');
    }
}
