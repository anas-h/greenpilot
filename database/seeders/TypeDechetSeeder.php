<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeDechetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the 24 system-level waste types (entreprise_id = null)
     * based on section 3 of the CDC.
     */
    public function run(): void
    {
        $now = now();

        $types = [
            // DD - Dechets Dangereux
            ['denomination' => 'Huiles usagees moteur', 'code_europeen' => '13 02 05*', 'categorie' => 'huile', 'unite_mesure' => 'litres', 'dangereux' => true, 'filiere_rep' => 'CYCLEVIA'],
            ['denomination' => 'Huiles usagees boite/transmission', 'code_europeen' => '13 02 06*', 'categorie' => 'huile', 'unite_mesure' => 'litres', 'dangereux' => true, 'filiere_rep' => 'CYCLEVIA'],
            ['denomination' => 'Huiles usagees hydrauliques', 'code_europeen' => '13 02 07*', 'categorie' => 'huile', 'unite_mesure' => 'litres', 'dangereux' => true, 'filiere_rep' => 'CYCLEVIA'],
            ['denomination' => 'Huiles usagees autres', 'code_europeen' => '13 02 08*', 'categorie' => 'huile', 'unite_mesure' => 'litres', 'dangereux' => true, 'filiere_rep' => 'CYCLEVIA'],
            ['denomination' => 'Liquide de refroidissement (dangereux)', 'code_europeen' => '16 01 14*', 'categorie' => 'fluide', 'unite_mesure' => 'litres', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Liquide de frein', 'code_europeen' => '16 01 13*', 'categorie' => 'fluide', 'unite_mesure' => 'litres', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Filtres a huile et carburant', 'code_europeen' => '16 01 07*', 'categorie' => 'filtre', 'unite_mesure' => 'kg', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Batteries au plomb', 'code_europeen' => '16 06 01*', 'categorie' => 'batterie', 'unite_mesure' => 'unites', 'dangereux' => true, 'filiere_rep' => 'REP batteries'],
            ['denomination' => 'Solvants et degraissants', 'code_europeen' => '14 06 03*', 'categorie' => 'solvant', 'unite_mesure' => 'litres', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Aerosols', 'code_europeen' => '16 05 04*', 'categorie' => 'autre', 'unite_mesure' => 'unites', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Absorbants / chiffons souilles', 'code_europeen' => '15 02 02*', 'categorie' => 'absorbant', 'unite_mesure' => 'kg', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Emballages contamines', 'code_europeen' => '15 01 10*', 'categorie' => 'emballage', 'unite_mesure' => 'kg', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Peintures, diluants (carrosserie)', 'code_europeen' => '08 01 11*', 'categorie' => 'peinture', 'unite_mesure' => 'kg', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Mastics, adhesifs (carrosserie)', 'code_europeen' => '08 01 13*', 'categorie' => 'peinture', 'unite_mesure' => 'kg', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Fluides frigorigenes (climatisation)', 'code_europeen' => '14 06 01*', 'categorie' => 'fluide', 'unite_mesure' => 'kg', 'dangereux' => true, 'filiere_rep' => null],
            ['denomination' => 'Plaquettes de frein amiantees', 'code_europeen' => '16 01 11*', 'categorie' => 'autre', 'unite_mesure' => 'kg', 'dangereux' => true, 'filiere_rep' => null],

            // DND - Dechets Non Dangereux
            ['denomination' => 'Pneumatiques usages', 'code_europeen' => '16 01 03', 'categorie' => 'pneu', 'unite_mesure' => 'unites', 'dangereux' => false, 'filiere_rep' => 'Aliapur'],
            ['denomination' => 'Ferraille / pieces metalliques', 'code_europeen' => '16 01 17', 'categorie' => 'metal', 'unite_mesure' => 'kg', 'dangereux' => false, 'filiere_rep' => null],
            ['denomination' => 'Metaux non ferreux (aluminium, cuivre)', 'code_europeen' => '16 01 18', 'categorie' => 'metal', 'unite_mesure' => 'kg', 'dangereux' => false, 'filiere_rep' => null],
            ['denomination' => 'Pare-brise / vitrage', 'code_europeen' => '16 01 20', 'categorie' => 'verre', 'unite_mesure' => 'kg', 'dangereux' => false, 'filiere_rep' => null],
            ['denomination' => 'Plastiques (pare-chocs)', 'code_europeen' => '16 01 19', 'categorie' => 'plastique', 'unite_mesure' => 'kg', 'dangereux' => false, 'filiere_rep' => null],
            ['denomination' => 'Cartons d\'emballage', 'code_europeen' => '15 01 01', 'categorie' => 'emballage', 'unite_mesure' => 'kg', 'dangereux' => false, 'filiere_rep' => null],
            ['denomination' => 'Liquide refroidissement sans substance dangereuse', 'code_europeen' => '16 01 15', 'categorie' => 'fluide', 'unite_mesure' => 'litres', 'dangereux' => false, 'filiere_rep' => null],
            ['denomination' => 'Plaquettes de frein (non amiantees)', 'code_europeen' => '16 01 12', 'categorie' => 'autre', 'unite_mesure' => 'kg', 'dangereux' => false, 'filiere_rep' => null],
        ];

        foreach ($types as $type) {
            DB::table('types_dechets')->updateOrInsert(
                ['code_europeen' => $type['code_europeen'], 'entreprise_id' => null],
                array_merge($type, ['entreprise_id' => null, 'created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
