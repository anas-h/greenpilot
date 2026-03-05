<?php

namespace Database\Seeders;

use App\Models\MappingOperation;
use App\Models\TypeDechet;
use Illuminate\Database\Seeder;

class MappingOperationSeeder extends Seeder
{
    /**
     * This seeder is a TEMPLATE and should NOT run automatically
     * via DatabaseSeeder. Use the static method createForGarage()
     * to create default mappings for a specific garage.
     */
    public function run(): void
    {
        // This seeder is intentionally left as a no-op.
        // Use MappingOperationSeeder::createForGarage($garageId) instead.
        $this->command->warn('MappingOperationSeeder: use createForGarage($garageId) to seed mappings for a specific garage.');
    }

    /**
     * Create the 10 default operation-to-waste-type mappings for a given garage.
     */
    public static function createForGarage(int $garageId): void
    {
        $mappings = [
            [
                'nom_operation' => 'Vidange moteur',
                'code_dechet' => '13 02 05*',
                'quantite_defaut' => 5,
            ],
            [
                'nom_operation' => 'Vidange boite',
                'code_dechet' => '13 02 06*',
                'quantite_defaut' => 2,
            ],
            [
                'nom_operation' => 'Filtre a huile',
                'code_dechet' => '16 01 07*',
                'quantite_defaut' => 0.5,
            ],
            [
                'nom_operation' => 'Filtre a carburant',
                'code_dechet' => '16 01 07*',
                'quantite_defaut' => 0.3,
            ],
            [
                'nom_operation' => 'Remplacement batterie',
                'code_dechet' => '16 06 01*',
                'quantite_defaut' => 1,
            ],
            [
                'nom_operation' => 'Remplacement pneu',
                'code_dechet' => '16 01 03',
                'quantite_defaut' => 1,
            ],
            [
                'nom_operation' => 'Purge liquide de frein',
                'code_dechet' => '16 01 13*',
                'quantite_defaut' => 0.5,
            ],
            [
                'nom_operation' => 'Vidange liquide de refroidissement',
                'code_dechet' => '16 01 14*',
                'quantite_defaut' => 5,
            ],
            [
                'nom_operation' => 'Recharge climatisation',
                'code_dechet' => '14 06 01*',
                'quantite_defaut' => 0.2,
            ],
            [
                'nom_operation' => 'Remplacement plaquettes de frein',
                'code_dechet' => '16 01 12',
                'quantite_defaut' => 1,
            ],
        ];

        foreach ($mappings as $mapping) {
            // Look up the system-level type_dechet by its waste code
            $typeDechet = TypeDechet::where('code_europeen', $mapping['code_dechet'])
                ->whereNull('entreprise_id')
                ->first();

            if (! $typeDechet) {
                continue; // Skip if the waste type hasn't been seeded yet
            }

            MappingOperation::updateOrCreate(
                [
                    'garage_id' => $garageId,
                    'nom_operation' => $mapping['nom_operation'],
                ],
                [
                    'type_dechet_id' => $typeDechet->id,
                    'quantite_defaut' => $mapping['quantite_defaut'],
                    'unite' => $typeDechet->unite_mesure,
                    'actif' => true,
                ]
            );
        }
    }
}
