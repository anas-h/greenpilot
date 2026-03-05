<?php

namespace Database\Seeders;

use App\Models\Conteneur;
use App\Models\Entreprise;
use App\Models\Garage;
use App\Models\TypeDechet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ConteneurSeeder extends Seeder
{
    public function run(): void
    {
        $entreprise = Entreprise::firstOrFail();

        // Creer un garage s'il n'en existe pas
        $garage = Garage::firstOrCreate(
            ['entreprise_id' => $entreprise->id, 'nom' => 'Garage Central'],
            [
                'siret_etablissement' => '36252187900015',
                'adresse' => '12 rue de la Mecanique',
                'code_postal' => '75012',
                'ville' => 'Paris',
                'telephone' => '01 43 00 00 00',
                'surface_atelier' => 350,
                'regime_icpe' => 'declaration',
                'activites' => ['mecanique', 'carrosserie', 'pneumatique'],
                'actif' => true,
            ]
        );

        // Associer les types de dechets au garage (table pivot sans timestamps)
        $typesDechets = TypeDechet::whereNull('entreprise_id')->get();
        foreach ($typesDechets as $td) {
            \DB::table('garage_type_dechet')->updateOrInsert(
                ['garage_id' => $garage->id, 'type_dechet_id' => $td->id],
                ['actif' => true]
            );
        }

        // Definir les conteneurs
        $conteneurs = [
            // Huiles
            [
                'nom' => 'Cuve huiles usagees moteur',
                'type_code' => '13 02 05*',
                'capacite' => 1000,
                'unite' => 'litres',
                'niveau_actuel' => 720,
                'emplacement' => 'Zone stockage - Exterieur',
            ],
            [
                'nom' => 'Fut huiles boite/transmission',
                'type_code' => '13 02 06*',
                'capacite' => 200,
                'unite' => 'litres',
                'niveau_actuel' => 45,
                'emplacement' => 'Zone stockage - Exterieur',
            ],
            // Filtres
            [
                'nom' => 'Benne filtres a huile',
                'type_code' => '16 01 07*',
                'capacite' => 300,
                'unite' => 'kg',
                'niveau_actuel' => 285,
                'emplacement' => 'Atelier - Fosse',
            ],
            // Batteries
            [
                'nom' => 'Bac batteries usagees',
                'type_code' => '16 06 01*',
                'capacite' => 20,
                'unite' => 'unites',
                'niveau_actuel' => 12,
                'emplacement' => 'Zone stockage - Interieur',
            ],
            // Pneus
            [
                'nom' => 'Rack pneumatiques usages',
                'type_code' => '16 01 03',
                'capacite' => 100,
                'unite' => 'unites',
                'niveau_actuel' => 67,
                'emplacement' => 'Zone stockage - Exterieur',
            ],
            // Liquide de frein
            [
                'nom' => 'Bidon liquide de frein',
                'type_code' => '16 01 13*',
                'capacite' => 60,
                'unite' => 'litres',
                'niveau_actuel' => 58,
                'emplacement' => 'Atelier - Fosse',
            ],
            // Liquide de refroidissement
            [
                'nom' => 'Cuve liquide refroidissement',
                'type_code' => '16 01 14*',
                'capacite' => 200,
                'unite' => 'litres',
                'niveau_actuel' => 30,
                'emplacement' => 'Atelier - Fosse',
            ],
            // Solvants
            [
                'nom' => 'Fut solvants et degraissants',
                'type_code' => '14 06 03*',
                'capacite' => 100,
                'unite' => 'litres',
                'niveau_actuel' => 82,
                'emplacement' => 'Local produits dangereux',
            ],
            // Absorbants
            [
                'nom' => 'Conteneur absorbants souilles',
                'type_code' => '15 02 02*',
                'capacite' => 200,
                'unite' => 'kg',
                'niveau_actuel' => 55,
                'emplacement' => 'Atelier principal',
            ],
            // Ferraille
            [
                'nom' => 'Benne ferraille',
                'type_code' => '16 01 17',
                'capacite' => 2000,
                'unite' => 'kg',
                'niveau_actuel' => 1450,
                'emplacement' => 'Zone stockage - Exterieur',
            ],
            // Cartons
            [
                'nom' => 'Benne cartons d\'emballage',
                'type_code' => '15 01 01',
                'capacite' => 500,
                'unite' => 'kg',
                'niveau_actuel' => 190,
                'emplacement' => 'Zone stockage - Exterieur',
            ],
            // Aerosols
            [
                'nom' => 'Bac aerosols vides',
                'type_code' => '16 05 04*',
                'capacite' => 50,
                'unite' => 'unites',
                'niveau_actuel' => 48,
                'emplacement' => 'Local produits dangereux',
            ],
        ];

        foreach ($conteneurs as $data) {
            $typeDechet = TypeDechet::where('code_europeen', $data['type_code'])
                ->whereNull('entreprise_id')
                ->first();

            Conteneur::firstOrCreate(
                ['garage_id' => $garage->id, 'nom' => $data['nom']],
                [
                    'type_dechet_id' => $typeDechet?->id,
                    'code_qr' => 'ECO-'.Str::upper(Str::random(8)),
                    'capacite' => $data['capacite'],
                    'unite' => $data['unite'],
                    'niveau_actuel' => $data['niveau_actuel'],
                    'emplacement' => $data['emplacement'],
                    'mixte' => false,
                    'date_mise_en_service' => now()->subMonths(rand(3, 18)),
                    'actif' => true,
                ]
            );
        }

        $this->command->info('Garage + '.count($conteneurs).' conteneurs crees avec succes.');
    }
}
