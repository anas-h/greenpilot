<?php

namespace Database\Seeders;

use App\Models\Collecteur;
use App\Models\Conteneur;
use App\Models\Enlevement;
use App\Models\Garage;
use App\Models\LigneEnlevement;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnlevementSeeder extends Seeder
{
    public function run(): void
    {
        $garage = Garage::firstOrFail();
        $user = User::firstOrFail();
        $collecteurs = Collecteur::where('garage_id', $garage->id)
            ->where('actif', true)
            ->with('typesDechets')
            ->get();

        if ($collecteurs->isEmpty()) {
            $this->command->warn('Aucun collecteur trouve. Lancez CollecteurSeeder d\'abord.');

            return;
        }

        $conteneurs = Conteneur::where('garage_id', $garage->id)->with('typeDechet')->get();
        $numero = 1;

        // --- Enlevements passes (completes) sur les 3 derniers mois ---
        $enlevementsPasses = [
            ['jours_ago' => 75, 'collecteur' => 'Veolia Environnement', 'statut' => 'complete'],
            ['jours_ago' => 60, 'collecteur' => 'Chimirec Ile-de-France', 'statut' => 'complete'],
            ['jours_ago' => 52, 'collecteur' => 'FerPro Recyclage', 'statut' => 'complete'],
            ['jours_ago' => 45, 'collecteur' => 'Aliapur Collecte', 'statut' => 'complete'],
            ['jours_ago' => 38, 'collecteur' => 'Suez Recyclage', 'statut' => 'complete'],
            ['jours_ago' => 25, 'collecteur' => 'Veolia Environnement', 'statut' => 'complete'],
            ['jours_ago' => 18, 'collecteur' => 'Chimirec Ile-de-France', 'statut' => 'complete'],
            ['jours_ago' => 10, 'collecteur' => 'FerPro Recyclage', 'statut' => 'complete'],
            ['jours_ago' => 5,  'collecteur' => 'Suez Recyclage', 'statut' => 'complete'],
        ];

        // --- Enlevements en cours et planifies ---
        $enlevementsFuturs = [
            ['jours' => 0,  'collecteur' => 'Veolia Environnement', 'statut' => 'en_cours'],
            ['jours' => 5,  'collecteur' => 'Aliapur Collecte', 'statut' => 'planifie'],
            ['jours' => 8,  'collecteur' => 'Chimirec Ile-de-France', 'statut' => 'planifie'],
            ['jours' => 15, 'collecteur' => 'Suez Recyclage', 'statut' => 'planifie'],
            ['jours' => 22, 'collecteur' => 'Veolia Environnement', 'statut' => 'planifie'],
            ['jours' => 30, 'collecteur' => 'FerPro Recyclage', 'statut' => 'planifie'],
        ];

        // Creer les enlevements passes
        foreach ($enlevementsPasses as $data) {
            $collecteur = $collecteurs->firstWhere('raison_sociale', $data['collecteur']);
            if (! $collecteur) {
                continue;
            }

            $datePrevue = now()->subDays($data['jours_ago']);
            $dateEffective = $datePrevue->copy()->addHours(rand(8, 14));

            $enlevement = Enlevement::create([
                'garage_id' => $garage->id,
                'numero' => 'ENL-'.now()->format('Y').'-'.str_pad($numero++, 4, '0', STR_PAD_LEFT),
                'collecteur_id' => $collecteur->id,
                'date_prevue' => $datePrevue->format('Y-m-d'),
                'date_effective' => $dateEffective,
                'statut' => $data['statut'],
                'numero_bon_enlevement' => 'BON-'.strtoupper(substr(md5(rand()), 0, 8)),
                'cout_total' => 0,
                'revenu_total' => 0,
                'notes' => null,
                'cree_par' => $user->id,
            ]);

            // Creer 2-4 lignes par enlevement avec les types du collecteur
            $typesDisponibles = $collecteur->typesDechets;
            $nbLignes = min($typesDisponibles->count(), rand(2, 4));
            $typesChoisis = $typesDisponibles->random($nbLignes);

            $coutTotal = 0;
            $revenuTotal = 0;

            foreach ($typesChoisis as $type) {
                $conteneur = $conteneurs->firstWhere('type_dechet_id', $type->id);

                $quantite = match ($type->categorie) {
                    'huile' => round(rand(100, 500) / 10, 1),
                    'filtre' => round(rand(50, 200) / 10, 1),
                    'batterie' => rand(3, 10),
                    'pneu' => rand(20, 60),
                    'fluide' => round(rand(20, 100) / 10, 1),
                    'solvant' => round(rand(20, 60) / 10, 1),
                    'absorbant' => round(rand(30, 100) / 10, 1),
                    'metal' => round(rand(200, 800) / 10, 1),
                    'emballage' => round(rand(50, 200) / 10, 1),
                    default => round(rand(10, 50) / 10, 1),
                };

                $prixUnitaire = $type->pivot->prix_unitaire ?? 0;
                $estRachat = $type->pivot->est_rachat ?? false;
                $montant = round($quantite * $prixUnitaire, 2);

                if ($estRachat) {
                    $revenuTotal += $montant;
                } else {
                    $coutTotal += $montant;
                }

                LigneEnlevement::create([
                    'enlevement_id' => $enlevement->id,
                    'type_dechet_id' => $type->id,
                    'conteneur_id' => $conteneur?->id,
                    'quantite_prevue' => $quantite,
                    'quantite_effective' => round($quantite * (rand(90, 105) / 100), 1),
                    'unite' => $type->unite_mesure,
                    'prix_unitaire' => $prixUnitaire,
                    'est_rachat' => $estRachat,
                    'montant' => $montant,
                ]);
            }

            $enlevement->update([
                'cout_total' => $coutTotal,
                'revenu_total' => $revenuTotal,
            ]);
        }

        // Creer les enlevements futurs/en cours
        foreach ($enlevementsFuturs as $data) {
            $collecteur = $collecteurs->firstWhere('raison_sociale', $data['collecteur']);
            if (! $collecteur) {
                continue;
            }

            $datePrevue = now()->addDays($data['jours']);

            $enlevement = Enlevement::create([
                'garage_id' => $garage->id,
                'numero' => 'ENL-'.now()->format('Y').'-'.str_pad($numero++, 4, '0', STR_PAD_LEFT),
                'collecteur_id' => $collecteur->id,
                'date_prevue' => $datePrevue->format('Y-m-d'),
                'date_effective' => null,
                'statut' => $data['statut'],
                'cout_total' => 0,
                'revenu_total' => 0,
                'notes' => null,
                'cree_par' => $user->id,
            ]);

            // Lignes prevues
            $typesDisponibles = $collecteur->typesDechets;
            $nbLignes = min($typesDisponibles->count(), rand(2, 4));
            $typesChoisis = $typesDisponibles->random($nbLignes);

            foreach ($typesChoisis as $type) {
                $conteneur = $conteneurs->firstWhere('type_dechet_id', $type->id);

                $quantite = match ($type->categorie) {
                    'huile' => round(rand(100, 500) / 10, 1),
                    'filtre' => round(rand(50, 200) / 10, 1),
                    'batterie' => rand(3, 10),
                    'pneu' => rand(20, 60),
                    'fluide' => round(rand(20, 100) / 10, 1),
                    'solvant' => round(rand(20, 60) / 10, 1),
                    'absorbant' => round(rand(30, 100) / 10, 1),
                    'metal' => round(rand(200, 800) / 10, 1),
                    'emballage' => round(rand(50, 200) / 10, 1),
                    default => round(rand(10, 50) / 10, 1),
                };

                LigneEnlevement::create([
                    'enlevement_id' => $enlevement->id,
                    'type_dechet_id' => $type->id,
                    'conteneur_id' => $conteneur?->id,
                    'quantite_prevue' => $quantite,
                    'quantite_effective' => null,
                    'unite' => $type->unite_mesure,
                    'prix_unitaire' => $type->pivot->prix_unitaire ?? 0,
                    'est_rachat' => $type->pivot->est_rachat ?? false,
                    'montant' => 0,
                ]);
            }
        }

        $totalEnlevements = count($enlevementsPasses) + count($enlevementsFuturs);
        $this->command->info($totalEnlevements.' enlevements crees (9 completes + 6 planifies/en cours).');
    }
}
