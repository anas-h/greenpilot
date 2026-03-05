<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\FiltersParGarage;
use App\Models\Alerte;
use App\Models\Conteneur;
use App\Models\Enlevement;
use App\Models\LigneEnlevement;
use App\Models\Production;
use App\Services\ConformiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use FiltersParGarage;

    public function index(Request $request): JsonResponse
    {
        $garageId = $this->getGarageId($request);

        $productionsMois = Production::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->whereMonth('date_production', now()->month)
            ->whereYear('date_production', now()->year)
            ->count();

        $enlevementsPlanifies = Enlevement::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->whereIn('statut', ['planifie', 'brouillon'])
            ->count();

        $alertesActives = Alerte::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->nonResolues()
            ->count();

        $alertes = Alerte::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->nonResolues()
            ->orderByRaw("CASE priorite WHEN 'critique' THEN 1 WHEN 'haute' THEN 2 WHEN 'moyenne' THEN 3 WHEN 'basse' THEN 4 ELSE 5 END")
            ->limit(3)
            ->get();

        $conteneurs = Conteneur::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->where('actif', true)
            ->where('capacite', '>', 0)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nom' => $c->nom,
                'pourcentage' => $c->getPourcentageRemplissage(),
            ])
            ->sortByDesc('pourcentage')
            ->take(5)
            ->values();

        $enlevementsProchains = Enlevement::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->whereIn('statut', ['planifie', 'brouillon'])
            ->where('date_prevue', '>=', now())
            ->with('collecteur:id,raison_sociale')
            ->orderBy('date_prevue')
            ->limit(5)
            ->get();

        $scoreConformite = 0;
        $effectiveGarageId = $garageId ?? $request->user()?->current_garage_id;
        if ($effectiveGarageId) {
            $conformiteService = new ConformiteService;
            $conformite = $conformiteService->calculateScore($effectiveGarageId);
            $scoreConformite = $conformite['score_global'];
        }

        return response()->json([
            'kpis' => [
                'productions_mois' => $productionsMois,
                'enlevements_planifies' => $enlevementsPlanifies,
                'alertes_actives' => $alertesActives,
                'score_conformite' => $scoreConformite,
            ],
            'alertes' => $alertes,
            'conteneurs' => $conteneurs,
            'enlevements' => $enlevementsProchains,
        ]);
    }

    public function economie(Request $request): JsonResponse
    {
        $garageId = $this->getGarageId($request);

        // KPIs: totals from completed enlevements
        $totals = Enlevement::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->where('statut', 'complete')
            ->selectRaw('COALESCE(SUM(cout_total), 0) as cout_total, COALESCE(SUM(revenu_total), 0) as revenu_total')
            ->first();

        $coutTotal = (float) $totals->cout_total;
        $revenuTotal = (float) $totals->revenu_total;

        // Evolution: monthly costs & revenues over last 12 months
        $labels = [];
        $couts = [];
        $revenus = [];

        $moisFr = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Avr', 5 => 'Mai', 6 => 'Juin',
            7 => 'Juil', 8 => 'Aout', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

        $monthly = Enlevement::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->where('statut', 'complete')
            ->where('date_effective', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('EXTRACT(YEAR FROM date_effective)::int as annee, EXTRACT(MONTH FROM date_effective)::int as mois, COALESCE(SUM(cout_total), 0) as couts, COALESCE(SUM(revenu_total), 0) as revenus')
            ->groupByRaw('EXTRACT(YEAR FROM date_effective), EXTRACT(MONTH FROM date_effective)')
            ->orderByRaw('EXTRACT(YEAR FROM date_effective), EXTRACT(MONTH FROM date_effective)')
            ->get()
            ->keyBy(fn ($row) => $row->annee.'-'.$row->mois);

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->year.'-'.$date->month;
            $labels[] = $moisFr[$date->month].' '.$date->year;
            $couts[] = isset($monthly[$key]) ? (float) $monthly[$key]->couts : 0;
            $revenus[] = isset($monthly[$key]) ? (float) $monthly[$key]->revenus : 0;
        }

        // Repartition: costs by waste type
        $repartitionData = LigneEnlevement::join('enlevements', 'lignes_enlevement.enlevement_id', '=', 'enlevements.id')
            ->join('types_dechets', 'lignes_enlevement.type_dechet_id', '=', 'types_dechets.id')
            ->when($garageId, fn ($q) => $q->where('enlevements.garage_id', $garageId))
            ->where('enlevements.statut', 'complete')
            ->selectRaw('types_dechets.denomination as label, COALESCE(SUM(lignes_enlevement.montant), 0) as total')
            ->groupBy('types_dechets.denomination')
            ->orderByDesc('total')
            ->get();

        // Comparatif: collector comparison
        $comparatifItems = Enlevement::when($garageId, fn ($q) => $q->where('enlevements.garage_id', $garageId))
            ->where('enlevements.statut', 'complete')
            ->join('collecteurs', 'enlevements.collecteur_id', '=', 'collecteurs.id')
            ->selectRaw('collecteurs.raison_sociale as nom, COUNT(enlevements.id) as nb_collectes, COALESCE(SUM(enlevements.cout_total), 0) as cout_total, COALESCE(SUM(enlevements.revenu_total), 0) as revenu_total, COALESCE(AVG(enlevements.cout_total), 0) as cout_moyen')
            ->groupBy('collecteurs.id', 'collecteurs.raison_sociale')
            ->orderByDesc('nb_collectes')
            ->get()
            ->map(fn ($row) => [
                'nom' => $row->nom,
                'nb_collectes' => (int) $row->nb_collectes,
                'cout_total' => round((float) $row->cout_total, 2),
                'revenu_total' => round((float) $row->revenu_total, 2),
                'cout_moyen' => round((float) $row->cout_moyen, 2),
            ]);

        return response()->json([
            'kpis' => [
                'cout_total' => $coutTotal,
                'revenu_total' => $revenuTotal,
                'cout_net' => $coutTotal - $revenuTotal,
            ],
            'evolution' => [
                'labels' => $labels,
                'couts' => $couts,
                'revenus' => $revenus,
            ],
            'repartition' => [
                'labels' => $repartitionData->pluck('label')->values()->toArray(),
                'values' => $repartitionData->pluck('total')->map(fn ($v) => round((float) $v, 2))->values()->toArray(),
            ],
            'comparatif' => [
                'headers' => [
                    ['title' => 'Collecteur', 'key' => 'nom'],
                    ['title' => 'Nb collectes', 'key' => 'nb_collectes', 'align' => 'center'],
                    ['title' => 'Cout total', 'key' => 'cout_total', 'align' => 'end'],
                    ['title' => 'Revenu total', 'key' => 'revenu_total', 'align' => 'end'],
                    ['title' => 'Cout moyen', 'key' => 'cout_moyen', 'align' => 'end'],
                ],
                'items' => $comparatifItems->toArray(),
            ],
        ]);
    }
}
