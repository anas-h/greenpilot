<?php

namespace Database\Seeders;

use App\Models\FicheSecurite;
use App\Models\Garage;
use App\Models\TypeDechet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FicheSecuriteSeeder extends Seeder
{
    public function run(): void
    {
        $garage = Garage::first();
        $user = User::first();

        if (! $garage || ! $user) {
            $this->command->warn('Garage ou utilisateur manquant, seeder ignore.');

            return;
        }

        $fiches = [
            // Huiles moteur usagees
            [
                'code_europeen' => '13 02 05*',
                'nom_produit' => 'Huile moteur 5W40 Castrol EDGE',
                'fournisseur' => 'Castrol',
                'date_emission' => Carbon::now()->subMonths(8),
                'date_validite' => Carbon::now()->addMonths(16),
                'actif' => true,
            ],
            // Liquide de refroidissement
            [
                'code_europeen' => '16 01 14*',
                'nom_produit' => 'Liquide de refroidissement G12 EVO',
                'fournisseur' => 'Glysantin / BASF',
                'date_emission' => Carbon::now()->subMonths(6),
                'date_validite' => Carbon::now()->addMonths(18),
                'actif' => true,
            ],
            // Liquide de frein
            [
                'code_europeen' => '16 01 13*',
                'nom_produit' => 'Liquide de frein DOT 4 ESP',
                'fournisseur' => 'Bosch / TRW',
                'date_emission' => Carbon::now()->subMonths(10),
                'date_validite' => Carbon::now()->addMonths(14),
                'actif' => true,
            ],
            // Batteries plomb
            [
                'code_europeen' => '16 06 01*',
                'nom_produit' => 'Batterie plomb-acide 12V',
                'fournisseur' => 'Varta / Exide',
                'date_emission' => Carbon::now()->subMonths(12),
                'date_validite' => Carbon::now()->addMonths(12),
                'actif' => true,
            ],
            // Solvants
            [
                'code_europeen' => '14 06 03*',
                'nom_produit' => 'Solvant de nettoyage frein Berner',
                'fournisseur' => 'Berner',
                'date_emission' => Carbon::now()->subMonths(4),
                'date_validite' => Carbon::now()->addMonths(20),
                'actif' => true,
            ],
            // Aerosols
            [
                'code_europeen' => '16 05 04*',
                'nom_produit' => 'Aerosol degrippant WD-40',
                'fournisseur' => 'WD-40 Company',
                'date_emission' => Carbon::now()->subMonths(14),
                'date_validite' => Carbon::now()->addMonths(10),
                'actif' => true,
            ],
            // Absorbants souilles
            [
                'code_europeen' => '15 02 02*',
                'nom_produit' => 'Absorbant granule Terre de diatomee',
                'fournisseur' => 'Ikasorb',
                'date_emission' => Carbon::now()->subMonths(18),
                'date_validite' => Carbon::now()->addMonths(6),
                'actif' => true,
            ],
            // Peinture (expiree)
            [
                'code_europeen' => '08 01 11*',
                'nom_produit' => 'Peinture carrosserie base solvantee',
                'fournisseur' => 'Axalta / Cromax',
                'date_emission' => Carbon::now()->subMonths(30),
                'date_validite' => Carbon::now()->subMonths(6),
                'actif' => true,
            ],
            // Plaquettes frein (non dangereux)
            [
                'code_europeen' => '16 01 12',
                'nom_produit' => 'Plaquettes de frein ceramique',
                'fournisseur' => 'Brembo',
                'date_emission' => Carbon::now()->subMonths(5),
                'date_validite' => Carbon::now()->addMonths(19),
                'actif' => true,
            ],
            // FDS inactive (ancienne version huile)
            [
                'code_europeen' => '13 02 05*',
                'nom_produit' => 'Huile moteur 10W40 Total Quartz',
                'fournisseur' => 'TotalEnergies',
                'date_emission' => Carbon::now()->subMonths(36),
                'date_validite' => Carbon::now()->subMonths(12),
                'actif' => false,
            ],
        ];

        foreach ($fiches as $data) {
            $typeDechet = TypeDechet::where('code_europeen', $data['code_europeen'])->first();

            if (! $typeDechet) {
                $this->command->warn("Type dechet {$data['code_europeen']} non trouve, ligne ignoree.");

                continue;
            }

            FicheSecurite::create([
                'garage_id' => $garage->id,
                'type_dechet_id' => $typeDechet->id,
                'nom_produit' => $data['nom_produit'],
                'fournisseur' => $data['fournisseur'],
                'fichier_path' => "fds/{$garage->id}/fds_".str_replace(' ', '_', $data['code_europeen']).'.pdf',
                'date_emission' => $data['date_emission'],
                'date_validite' => $data['date_validite'],
                'actif' => $data['actif'],
                'cree_par' => $user->id,
            ]);
        }

        $this->command->info('10 fiches de securite creees.');
    }
}
