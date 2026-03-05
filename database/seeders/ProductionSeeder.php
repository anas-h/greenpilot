<?php

namespace Database\Seeders;

use App\Models\Conteneur;
use App\Models\Garage;
use App\Models\Production;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $garage = Garage::firstOrFail();
        $user = User::firstOrFail();
        $conteneurs = Conteneur::where('garage_id', $garage->id)->with('typeDechet')->get();

        if ($conteneurs->isEmpty()) {
            $this->command->warn('Aucun conteneur trouve. Lancez ConteneurSeeder d\'abord.');

            return;
        }

        $count = 0;

        // Generer des productions sur les 3 derniers mois
        for ($daysAgo = 90; $daysAgo >= 0; $daysAgo -= rand(1, 3)) {
            $date = now()->subDays($daysAgo);

            // 2 a 5 productions par jour ouvre
            if ($date->isWeekend()) {
                continue;
            }

            $nbProductions = rand(2, 5);

            for ($i = 0; $i < $nbProductions; $i++) {
                $conteneur = $conteneurs->random();
                $typeDechet = $conteneur->typeDechet;

                if (! $typeDechet) {
                    continue;
                }

                // Quantites realistes selon le type
                $quantite = match ($typeDechet->categorie) {
                    'huile' => round(rand(30, 80) / 10, 1),       // 3-8 litres
                    'filtre' => round(rand(3, 15) / 10, 1),       // 0.3-1.5 kg
                    'batterie' => 1,                                // 1 unite
                    'pneu' => rand(1, 4),                          // 1-4 unites
                    'fluide' => round(rand(5, 50) / 10, 1),       // 0.5-5 litres
                    'solvant' => round(rand(5, 20) / 10, 1),      // 0.5-2 litres
                    'absorbant' => round(rand(5, 30) / 10, 1),    // 0.5-3 kg
                    'emballage' => round(rand(10, 50) / 10, 1),   // 1-5 kg
                    'metal' => round(rand(20, 150) / 10, 1),      // 2-15 kg
                    'peinture' => round(rand(5, 20) / 10, 1),     // 0.5-2 kg
                    default => round(rand(10, 50) / 10, 1),       // 1-5
                };

                $sourceTypes = ['manuelle', 'scan_qr', 'manuelle', 'manuelle', 'scan_qr'];
                $vehicules = [
                    'Renault Clio 2024 - AB-123-CD',
                    'Peugeot 308 2022 - EF-456-GH',
                    'Citroen C3 2023 - IJ-789-KL',
                    'BMW Serie 3 2021 - MN-012-OP',
                    'VW Golf 2023 - QR-345-ST',
                    'Toyota Yaris 2024 - UV-678-WX',
                    'Mercedes Classe A 2022 - YZ-901-AB',
                    'Dacia Sandero 2023 - CD-234-EF',
                    null, null, // Parfois pas de vehicule
                ];

                $valide = $daysAgo > 3; // Les productions anciennes sont validees

                Production::create([
                    'garage_id' => $garage->id,
                    'type_dechet_id' => $typeDechet->id,
                    'conteneur_id' => $conteneur->id,
                    'quantite' => $quantite,
                    'unite' => $typeDechet->unite_mesure,
                    'date_production' => $date->format('Y-m-d'),
                    'source_type' => $sourceTypes[array_rand($sourceTypes)],
                    'vehicule_info' => $vehicules[array_rand($vehicules)],
                    'employe_id' => $user->id,
                    'valide' => $valide,
                    'valide_par' => $valide ? $user->id : null,
                    'date_validation' => $valide ? $date->addHours(rand(1, 8)) : null,
                    'notes' => null,
                ]);

                $count++;
            }
        }

        $this->command->info($count.' productions creees sur 3 mois.');
    }
}
