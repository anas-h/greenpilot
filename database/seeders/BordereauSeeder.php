<?php

namespace Database\Seeders;

use App\Models\Bordereau;
use App\Models\Collecteur;
use App\Models\Garage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BordereauSeeder extends Seeder
{
    public function run(): void
    {
        $garage = Garage::first();
        $user = User::first();

        if (! $garage || ! $user) {
            $this->command->warn('Garage ou utilisateur manquant, seeder ignore.');

            return;
        }

        $collecteurs = Collecteur::where('garage_id', $garage->id)->get();
        if ($collecteurs->isEmpty()) {
            $this->command->warn('Aucun collecteur, seeder ignore.');

            return;
        }

        $bordereaux = [
            // BSDD - Huiles moteur usagees (traite)
            [
                'type_bsd' => 'BSDD',
                'statut' => 'PROCESSED',
                'statut_sync' => 'synchronise',
                'code_dechet' => '13 02 05*',
                'denomination_dechet' => 'Huiles moteur usagees non chlorees',
                'quantite' => 520.000,
                'unite' => 'kg',
                'collecteur_id' => $collecteurs->where('raison_sociale', 'Chimirec Ile-de-France')->first()?->id ?? $collecteurs->first()->id,
                'transporteur_siret' => '55208131700048',
                'transporteur_raison_sociale' => 'Chimirec Ile-de-France',
                'destination_siret' => '30276839400032',
                'destination_raison_sociale' => 'Osilub SAS',
                'destination_adresse' => 'ZI, Rue de l\'industrie, 76700 Gonfreville-l\'Orcher',
                'code_traitement' => 'R9',
                'cap' => 'CAP-2025-0891',
                'date_emission' => Carbon::now()->subDays(45),
                'date_signature_producteur' => Carbon::now()->subDays(44),
                'date_enlevement' => Carbon::now()->subDays(43),
                'date_reception' => Carbon::now()->subDays(41),
                'date_traitement' => Carbon::now()->subDays(35),
                'trackdechets_numero' => 'BSD-20250112-ABCDE',
                'trackdechets_id' => 'cl_td_fake_001',
            ],
            // BSDD - Filtres a huile (envoye)
            [
                'type_bsd' => 'BSDD',
                'statut' => 'SENT',
                'statut_sync' => 'synchronise',
                'code_dechet' => '16 01 07*',
                'denomination_dechet' => 'Filtres a huile',
                'quantite' => 85.500,
                'unite' => 'kg',
                'collecteur_id' => $collecteurs->where('raison_sociale', 'Veolia Environnement')->first()?->id ?? $collecteurs->first()->id,
                'transporteur_siret' => '40321072200053',
                'transporteur_raison_sociale' => 'Veolia Environnement',
                'destination_siret' => '44945359400028',
                'destination_raison_sociale' => 'Tredi Salaise',
                'destination_adresse' => 'Chemin du Pible, 38150 Salaise-sur-Sanne',
                'code_traitement' => 'D10',
                'date_emission' => Carbon::now()->subDays(10),
                'date_signature_producteur' => Carbon::now()->subDays(9),
                'date_enlevement' => Carbon::now()->subDays(8),
                'trackdechets_numero' => 'BSD-20250215-FGHIJ',
                'trackdechets_id' => 'cl_td_fake_002',
            ],
            // BSDD - Liquide de refroidissement (signe)
            [
                'type_bsd' => 'BSDD',
                'statut' => 'SIGNED_BY_PRODUCER',
                'statut_sync' => 'synchronise',
                'code_dechet' => '16 01 14*',
                'denomination_dechet' => 'Liquide de refroidissement (dangereux)',
                'quantite' => 120.000,
                'unite' => 'litres',
                'collecteur_id' => $collecteurs->where('raison_sociale', 'Suez Recyclage')->first()?->id ?? $collecteurs->first()->id,
                'transporteur_siret' => '43379578500031',
                'transporteur_raison_sociale' => 'Suez Recyclage',
                'destination_siret' => '38393655100015',
                'destination_raison_sociale' => 'Sevia Normandie',
                'destination_adresse' => 'Zone portuaire, 76430 Sandouville',
                'code_traitement' => 'D9',
                'date_emission' => Carbon::now()->subDays(3),
                'date_signature_producteur' => Carbon::now()->subDays(2),
                'trackdechets_numero' => 'BSD-20250222-KLMNO',
                'trackdechets_id' => 'cl_td_fake_003',
            ],
            // BSDD - Batteries plomb (recu)
            [
                'type_bsd' => 'BSDD',
                'statut' => 'RECEIVED',
                'statut_sync' => 'synchronise',
                'code_dechet' => '16 06 01*',
                'denomination_dechet' => 'Batteries au plomb',
                'quantite' => 210.000,
                'unite' => 'kg',
                'collecteur_id' => $collecteurs->where('raison_sociale', 'Veolia Environnement')->first()?->id ?? $collecteurs->first()->id,
                'transporteur_siret' => '40321072200053',
                'transporteur_raison_sociale' => 'Veolia Environnement',
                'destination_siret' => '56205442300029',
                'destination_raison_sociale' => 'Stcm Metaleurop',
                'destination_adresse' => 'Usine de Noyelles-Godault, 62950',
                'code_traitement' => 'R4',
                'date_emission' => Carbon::now()->subDays(20),
                'date_signature_producteur' => Carbon::now()->subDays(19),
                'date_enlevement' => Carbon::now()->subDays(18),
                'date_reception' => Carbon::now()->subDays(16),
                'trackdechets_numero' => 'BSD-20250205-PQRST',
                'trackdechets_id' => 'cl_td_fake_004',
            ],
            // BSDD - Liquide de frein (brouillon)
            [
                'type_bsd' => 'BSDD',
                'statut' => 'DRAFT',
                'statut_sync' => 'en_attente',
                'code_dechet' => '16 01 13*',
                'denomination_dechet' => 'Liquide de frein',
                'quantite' => 45.000,
                'unite' => 'litres',
                'collecteur_id' => $collecteurs->where('raison_sociale', 'Chimirec Ile-de-France')->first()?->id ?? $collecteurs->first()->id,
                'transporteur_siret' => '55208131700048',
                'transporteur_raison_sociale' => 'Chimirec Ile-de-France',
                'destination_siret' => '44945359400028',
                'destination_raison_sociale' => 'Tredi Salaise',
                'destination_adresse' => 'Chemin du Pible, 38150 Salaise-sur-Sanne',
                'code_traitement' => 'D10',
                'date_emission' => Carbon::now(),
            ],
            // BSDD - Aerosols (refuse)
            [
                'type_bsd' => 'BSDD',
                'statut' => 'REFUSED',
                'statut_sync' => 'synchronise',
                'code_dechet' => '16 05 04*',
                'denomination_dechet' => 'Aerosols contenant des substances dangereuses',
                'quantite' => 30.000,
                'unite' => 'kg',
                'collecteur_id' => $collecteurs->where('raison_sociale', 'Suez Recyclage')->first()?->id ?? $collecteurs->first()->id,
                'transporteur_siret' => '43379578500031',
                'transporteur_raison_sociale' => 'Suez Recyclage',
                'destination_siret' => '38393655100015',
                'destination_raison_sociale' => 'Sevia Normandie',
                'destination_adresse' => 'Zone portuaire, 76430 Sandouville',
                'code_traitement' => 'D10',
                'date_emission' => Carbon::now()->subDays(25),
                'date_signature_producteur' => Carbon::now()->subDays(24),
                'date_enlevement' => Carbon::now()->subDays(23),
                'date_refus' => Carbon::now()->subDays(20),
                'motif_refus' => 'Conditionnement non conforme, aerosols non percutes',
                'trackdechets_numero' => 'BSD-20250131-UVWXY',
                'trackdechets_id' => 'cl_td_fake_005',
            ],
            // BSVHU - Vehicule hors d'usage (traite)
            [
                'type_bsd' => 'BSVHU',
                'statut' => 'PROCESSED',
                'statut_sync' => 'synchronise',
                'code_dechet' => '16 01 06',
                'denomination_dechet' => 'Vehicule hors d\'usage depollue',
                'quantite' => 1150.000,
                'unite' => 'kg',
                'collecteur_id' => $collecteurs->where('raison_sociale', 'FerPro Recyclage')->first()?->id ?? $collecteurs->first()->id,
                'transporteur_siret' => $collecteurs->where('raison_sociale', 'FerPro Recyclage')->first()?->siret ?? '00000000000000',
                'transporteur_raison_sociale' => 'FerPro Recyclage',
                'destination_siret' => '82134567800012',
                'destination_raison_sociale' => 'GDE Recyclage',
                'destination_adresse' => '14, rue de la Casse, 93300 Aubervilliers',
                'code_traitement' => 'R4',
                'cap' => 'CAP-VHU-2025-012',
                'date_emission' => Carbon::now()->subDays(60),
                'date_signature_producteur' => Carbon::now()->subDays(59),
                'date_enlevement' => Carbon::now()->subDays(58),
                'date_reception' => Carbon::now()->subDays(55),
                'date_traitement' => Carbon::now()->subDays(50),
                'trackdechets_numero' => 'VHU-20241227-ZZZZZ',
                'trackdechets_id' => 'cl_td_fake_006',
            ],
        ];

        foreach ($bordereaux as $data) {
            $data['garage_id'] = $garage->id;
            $data['cree_par'] = $user->id;
            Bordereau::create($data);
        }

        $this->command->info('7 bordereaux crees.');
    }
}
