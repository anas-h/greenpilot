<?php

namespace Database\Seeders;

use App\Models\Collecteur;
use App\Models\Garage;
use App\Models\TypeDechet;
use Illuminate\Database\Seeder;

class CollecteurSeeder extends Seeder
{
    public function run(): void
    {
        $garage = Garage::firstOrFail();

        $collecteurs = [
            [
                'raison_sociale' => 'Veolia Environnement',
                'siret' => '40321072200053',
                'adresse' => '21 rue La Boetie',
                'code_postal' => '75008',
                'ville' => 'Paris',
                'numero_autorisation' => 'PREF-2024-VEO-001',
                'date_validite_autorisation' => now()->addMonths(18),
                'autorisation_adr' => true,
                'numero_adr' => 'ADR-VEO-2024-1234',
                'eco_organisme' => null,
                'contact_nom' => 'Jean Dupont',
                'contact_telephone' => '01 71 75 00 00',
                'contact_email' => 'j.dupont@veolia.com',
                'actif' => true,
                'tarifs' => [
                    // Huiles usagees moteur - rachat
                    ['code' => '13 02 05*', 'prix_unitaire' => 0.08, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    // Huiles boite
                    ['code' => '13 02 06*', 'prix_unitaire' => 0.06, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    // Filtres
                    ['code' => '16 01 07*', 'prix_unitaire' => 0.35, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    // Batteries - rachat
                    ['code' => '16 06 01*', 'prix_unitaire' => 8.50, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    // Solvants
                    ['code' => '14 06 03*', 'prix_unitaire' => 0.90, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    // Absorbants
                    ['code' => '15 02 02*', 'prix_unitaire' => 0.45, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    // Liquide frein
                    ['code' => '16 01 13*', 'prix_unitaire' => 0.55, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    // Liquide refroidissement
                    ['code' => '16 01 14*', 'prix_unitaire' => 0.40, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    // Ferraille - rachat
                    ['code' => '16 01 17', 'prix_unitaire' => 0.12, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    // Aerosols
                    ['code' => '16 05 04*', 'prix_unitaire' => 1.20, 'prix_forfaitaire' => null, 'est_rachat' => false],
                ],
            ],
            [
                'raison_sociale' => 'Suez Recyclage',
                'siret' => '43379578500031',
                'adresse' => 'Tour CB21, 16 place de l\'Iris',
                'code_postal' => '92040',
                'ville' => 'La Defense',
                'numero_autorisation' => 'PREF-2024-SUE-042',
                'date_validite_autorisation' => now()->addMonths(12),
                'autorisation_adr' => true,
                'numero_adr' => 'ADR-SUE-2024-5678',
                'eco_organisme' => null,
                'contact_nom' => 'Marie Lambert',
                'contact_telephone' => '01 58 81 20 00',
                'contact_email' => 'm.lambert@suez.com',
                'actif' => true,
                'tarifs' => [
                    ['code' => '13 02 05*', 'prix_unitaire' => 0.10, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    ['code' => '13 02 06*', 'prix_unitaire' => 0.07, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    ['code' => '16 01 07*', 'prix_unitaire' => 0.30, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '16 06 01*', 'prix_unitaire' => 9.00, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    ['code' => '14 06 03*', 'prix_unitaire' => 1.10, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '15 02 02*', 'prix_unitaire' => 0.50, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '16 01 13*', 'prix_unitaire' => 0.48, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '16 01 14*', 'prix_unitaire' => 0.35, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '16 01 17', 'prix_unitaire' => 0.15, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    ['code' => '16 05 04*', 'prix_unitaire' => 1.00, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    // Emballages contamines
                    ['code' => '15 01 10*', 'prix_unitaire' => 0.60, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    // Cartons
                    ['code' => '15 01 01', 'prix_unitaire' => 0.03, 'prix_forfaitaire' => null, 'est_rachat' => true],
                ],
            ],
            [
                'raison_sociale' => 'Chimirec Ile-de-France',
                'siret' => '55208131700048',
                'adresse' => '5 avenue de la Gare',
                'code_postal' => '77130',
                'ville' => 'Montereau',
                'numero_autorisation' => 'PREF-2023-CHI-107',
                'date_validite_autorisation' => now()->addMonths(6),
                'autorisation_adr' => true,
                'numero_adr' => 'ADR-CHI-2023-9012',
                'eco_organisme' => null,
                'contact_nom' => 'Philippe Martin',
                'contact_telephone' => '01 64 32 10 00',
                'contact_email' => 'p.martin@chimirec.fr',
                'actif' => true,
                'tarifs' => [
                    ['code' => '13 02 05*', 'prix_unitaire' => 0.12, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    ['code' => '13 02 06*', 'prix_unitaire' => 0.09, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    ['code' => '13 02 07*', 'prix_unitaire' => 0.05, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    ['code' => '16 01 07*', 'prix_unitaire' => 0.28, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '14 06 03*', 'prix_unitaire' => 0.85, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '15 02 02*', 'prix_unitaire' => 0.40, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '08 01 11*', 'prix_unitaire' => 1.50, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '16 01 13*', 'prix_unitaire' => 0.52, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '16 01 14*', 'prix_unitaire' => 0.38, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '14 06 01*', 'prix_unitaire' => 25.00, 'prix_forfaitaire' => null, 'est_rachat' => false],
                    ['code' => '16 05 04*', 'prix_unitaire' => 0.95, 'prix_forfaitaire' => null, 'est_rachat' => false],
                ],
            ],
            [
                'raison_sociale' => 'Aliapur Collecte',
                'siret' => '44115aboré7600021',
                'adresse' => '71 cours Albert Thomas',
                'code_postal' => '69003',
                'ville' => 'Lyon',
                'numero_autorisation' => 'PREF-2024-ALI-055',
                'date_validite_autorisation' => now()->addMonths(24),
                'autorisation_adr' => false,
                'numero_adr' => null,
                'eco_organisme' => 'Aliapur',
                'contact_nom' => 'Sophie Renard',
                'contact_telephone' => '04 26 68 38 00',
                'contact_email' => 's.renard@aliapur.fr',
                'actif' => true,
                'tarifs' => [
                    // Pneus - gratuit (eco-organisme)
                    ['code' => '16 01 03', 'prix_unitaire' => 0.00, 'prix_forfaitaire' => 0.00, 'est_rachat' => false],
                ],
            ],
            [
                'raison_sociale' => 'FerPro Recyclage',
                'siret' => '82345678901234',
                'adresse' => '14 zone industrielle des Marais',
                'code_postal' => '93200',
                'ville' => 'Saint-Denis',
                'numero_autorisation' => 'PREF-2023-FER-089',
                'date_validite_autorisation' => now()->subMonths(2), // Expire !
                'autorisation_adr' => false,
                'numero_adr' => null,
                'eco_organisme' => null,
                'contact_nom' => 'Hassan Belhaj',
                'contact_telephone' => '01 48 22 33 44',
                'contact_email' => 'h.belhaj@ferpro.fr',
                'actif' => true,
                'tarifs' => [
                    // Ferraille - meilleur rachat
                    ['code' => '16 01 17', 'prix_unitaire' => 0.18, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    // Metaux non ferreux - rachat
                    ['code' => '16 01 18', 'prix_unitaire' => 1.80, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    // Plastiques
                    ['code' => '16 01 19', 'prix_unitaire' => 0.05, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    // Pare-brise
                    ['code' => '16 01 20', 'prix_unitaire' => 0.02, 'prix_forfaitaire' => null, 'est_rachat' => true],
                    // Cartons - rachat
                    ['code' => '15 01 01', 'prix_unitaire' => 0.05, 'prix_forfaitaire' => null, 'est_rachat' => true],
                ],
            ],
        ];

        foreach ($collecteurs as $data) {
            $tarifs = $data['tarifs'];
            unset($data['tarifs']);

            // Fix SIRET invalide
            $data['siret'] = substr(preg_replace('/[^0-9]/', '', $data['siret']).'00000000000000', 0, 14);

            $collecteur = Collecteur::firstOrCreate(
                ['garage_id' => $garage->id, 'raison_sociale' => $data['raison_sociale']],
                $data
            );

            // Attacher les tarifs
            $pivotData = [];
            foreach ($tarifs as $tarif) {
                $type = TypeDechet::where('code_europeen', $tarif['code'])
                    ->whereNull('entreprise_id')
                    ->first();

                if (! $type) {
                    continue;
                }

                $pivotData[$type->id] = [
                    'prix_unitaire' => $tarif['prix_unitaire'],
                    'prix_forfaitaire' => $tarif['prix_forfaitaire'],
                    'est_rachat' => $tarif['est_rachat'],
                    'date_effet' => now()->subMonths(rand(1, 6)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $collecteur->typesDechets()->syncWithoutDetaching($pivotData);
        }

        $this->command->info(count($collecteurs).' collecteurs avec tarifs crees avec succes.');
    }
}
