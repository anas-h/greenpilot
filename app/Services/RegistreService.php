<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RegistreService
{
    /**
     * Build the registre des dechets per arrete du 31 mai 2021.
     *
     * Columns: date, nature_dechet, code_dechet, dangereux, quantite_tonnes,
     * collecteur, siret_collecteur, numero_autorisation, destination, siret_destination,
     * adresse_destination, code_traitement, numero_bsd
     */
    public function buildRegistre(array $filters = []): array
    {
        $query = DB::table('lignes_enlevement')
            ->join('enlevements', 'lignes_enlevement.enlevement_id', '=', 'enlevements.id')
            ->join('types_dechets', 'lignes_enlevement.type_dechet_id', '=', 'types_dechets.id')
            ->join('collecteurs', 'enlevements.collecteur_id', '=', 'collecteurs.id')
            ->leftJoin('bordereaux', 'lignes_enlevement.id', '=', 'bordereaux.ligne_enlevement_id')
            ->where('enlevements.statut', 'complete')
            ->whereNull('enlevements.deleted_at')
            ->select([
                'enlevements.date_effective as date',
                'types_dechets.denomination as nature_dechet',
                'types_dechets.code_europeen as code_dechet',
                'types_dechets.dangereux',
                DB::raw('CASE
                    WHEN lignes_enlevement.unite = \'kg\' THEN lignes_enlevement.quantite_effective / 1000
                    WHEN lignes_enlevement.unite = \'unites\' THEN lignes_enlevement.quantite_effective
                    ELSE lignes_enlevement.quantite_effective
                END as quantite_tonnes'),
                'collecteurs.raison_sociale as collecteur',
                'collecteurs.siret as siret_collecteur',
                'collecteurs.numero_autorisation',
                'bordereaux.destination_raison_sociale as destination',
                'bordereaux.destination_siret as siret_destination',
                'bordereaux.destination_adresse as adresse_destination',
                'bordereaux.code_traitement',
                'bordereaux.trackdechets_numero as numero_bsd',
                'enlevements.garage_id',
                'enlevements.numero as numero_enlevement',
                'lignes_enlevement.quantite_effective',
                'lignes_enlevement.unite',
                'lignes_enlevement.est_rachat',
                'lignes_enlevement.montant',
            ]);

        // Apply filters
        if (! empty($filters['garage_id'])) {
            $query->where('enlevements.garage_id', $filters['garage_id']);
        }

        if (! empty($filters['garage_ids'])) {
            $query->whereIn('enlevements.garage_id', $filters['garage_ids']);
        }

        if (! empty($filters['date_debut'])) {
            $query->where('enlevements.date_effective', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->where('enlevements.date_effective', '<=', $filters['date_fin']);
        }

        if (! empty($filters['type_dechet_id'])) {
            $query->where('lignes_enlevement.type_dechet_id', $filters['type_dechet_id']);
        }

        if (isset($filters['dangereux'])) {
            $query->where('types_dechets.dangereux', $filters['dangereux']);
        }

        if (! empty($filters['collecteur_id'])) {
            $query->where('enlevements.collecteur_id', $filters['collecteur_id']);
        }

        $results = $query->orderBy('enlevements.date_effective', 'desc')
            ->orderBy('types_dechets.denomination')
            ->get();

        return $results->map(function ($row) {
            return [
                'date' => $row->date,
                'nature_dechet' => $row->nature_dechet,
                'code_dechet' => $row->code_dechet,
                'dangereux' => (bool) $row->dangereux,
                'quantite_tonnes' => round($row->quantite_tonnes, 3),
                'collecteur' => $row->collecteur,
                'siret_collecteur' => $row->siret_collecteur,
                'numero_autorisation' => $row->numero_autorisation,
                'destination' => $row->destination,
                'siret_destination' => $row->siret_destination,
                'adresse_destination' => $row->adresse_destination,
                'code_traitement' => $row->code_traitement,
                'numero_bsd' => $row->numero_bsd,
                'numero_enlevement' => $row->numero_enlevement,
                'quantite_effective' => $row->quantite_effective,
                'unite' => $row->unite,
                'est_rachat' => (bool) $row->est_rachat,
                'montant' => $row->montant,
            ];
        })->toArray();
    }
}
