<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompletionEnlevementRequest;
use App\Http\Requests\EnlevementRequest;
use App\Http\Traits\FiltersParGarage;
use App\Models\Bordereau;
use App\Models\Conteneur;
use App\Models\Enlevement;
use App\Models\LigneEnlevement;
use App\Models\TypeDechet;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnlevementController extends Controller
{
    use FiltersParGarage;

    private function generateNumero(int $garageId): string
    {
        $year = now()->year;

        // Lock all rows for this year (constraint is global, not per-garage)
        DB::select(
            'SELECT id FROM enlevements WHERE numero LIKE ? FOR UPDATE',
            ["ENL-{$year}-%"]
        );

        $lastNum = Enlevement::withTrashed()
            ->where('numero', 'LIKE', "ENL-{$year}-%")
            ->max(DB::raw('CAST(RIGHT(numero, 5) AS INTEGER)'));

        $nextNum = ($lastNum ?? 0) + 1;

        return sprintf('ENL-%d-%05d', $year, $nextNum);
    }

    /**
     * List enlevements filterable by statut, date range, collecteur.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Enlevement::query();
        $this->scopeByGarage($query, $request);

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }
        if ($request->filled('date_debut')) {
            $query->where('date_prevue', '>=', $request->input('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->where('date_prevue', '<=', $request->input('date_fin'));
        }
        if ($request->filled('collecteur_id')) {
            $query->where('collecteur_id', $request->input('collecteur_id'));
        }

        $enlevements = $query->with(['collecteur:id,raison_sociale', 'lignes.typeDechet'])
            ->orderByDesc('date_prevue')
            ->paginate($request->input('per_page', 30));

        return response()->json($enlevements);
    }

    /**
     * Create an enlevement with lignes (auto-set numero ENL-YYYY-NNNNN).
     */
    public function store(EnlevementRequest $request): JsonResponse
    {
        $garageId = $this->validateGarageAccess($request);

        $data = $request->validated();
        $lignesData = $data['lignes'] ?? [];
        unset($data['lignes']);

        $enlevement = DB::transaction(function () use ($data, $lignesData, $garageId, $request) {
            $data['garage_id'] = $garageId;
            $data['numero'] = $this->generateNumero($garageId);
            $data['statut'] = 'brouillon';
            $data['cree_par'] = $request->user()->id;

            $enlevement = Enlevement::create($data);

            // Create lines
            foreach ($lignesData as $ligne) {
                $enlevement->lignes()->create([
                    'type_dechet_id' => $ligne['type_dechet_id'],
                    'conteneur_id' => $ligne['conteneur_id'] ?? null,
                    'quantite_prevue' => $ligne['quantite_prevue'],
                    'unite' => $ligne['unite'],
                ]);
            }

            return $enlevement;
        });

        return response()->json([
            'data' => $enlevement->load(['collecteur', 'lignes.typeDechet', 'lignes.conteneur']),
        ], 201);
    }

    /**
     * Show an enlevement with lignes and collecteur.
     */
    public function show(Request $request, Enlevement $enlevement): JsonResponse
    {
        $this->scopeByGarage(Enlevement::where('id', $enlevement->id), $request)->firstOrFail();

        $enlevement->load([
            'collecteur.typesDechets',
            'lignes.typeDechet',
            'lignes.conteneur',
            'lignes.bordereau',
            'creePar:id,nom,prenom',
        ]);

        return response()->json(['data' => $enlevement]);
    }

    /**
     * Update an enlevement (only if brouillon).
     */
    public function update(EnlevementRequest $request, Enlevement $enlevement): JsonResponse
    {
        $this->scopeByGarage(Enlevement::where('id', $enlevement->id), $request)->firstOrFail();

        if ($enlevement->statut !== 'brouillon') {
            return response()->json([
                'message' => 'Seuls les enlevements en brouillon peuvent etre modifies.',
            ], 422);
        }

        $data = $request->validated();
        $lignesData = $data['lignes'] ?? null;
        unset($data['lignes']);

        DB::transaction(function () use ($enlevement, $data, $lignesData) {
            $enlevement->update($data);

            if ($lignesData !== null) {
                // Remove old lines and recreate
                $enlevement->lignes()->delete();

                foreach ($lignesData as $ligne) {
                    $enlevement->lignes()->create([
                        'type_dechet_id' => $ligne['type_dechet_id'],
                        'conteneur_id' => $ligne['conteneur_id'] ?? null,
                        'quantite_prevue' => $ligne['quantite_prevue'],
                        'unite' => $ligne['unite'],
                    ]);
                }
            }
        });

        return response()->json([
            'data' => $enlevement->fresh()->load(['collecteur', 'lignes.typeDechet', 'lignes.conteneur']),
        ]);
    }

    /**
     * Delete an enlevement (only if brouillon).
     */
    public function destroy(Request $request, Enlevement $enlevement): JsonResponse
    {
        $this->scopeByGarage(Enlevement::where('id', $enlevement->id), $request)->firstOrFail();

        if ($enlevement->statut !== 'brouillon') {
            return response()->json([
                'message' => 'Seuls les enlevements en brouillon peuvent etre supprimes.',
            ], 422);
        }

        $enlevement->lignes()->delete();
        $enlevement->delete();

        return response()->json(['message' => 'Enlevement supprime.']);
    }

    /**
     * Complete an enlevement: saisie quantites effectives, update container levels,
     * calculate costs/revenues, create BSD for DD lines, handle recurrence.
     */
    public function complete(CompletionEnlevementRequest $request, Enlevement $enlevement): JsonResponse
    {
        $this->scopeByGarage(Enlevement::where('id', $enlevement->id), $request)->firstOrFail();

        if (! in_array($enlevement->statut, ['brouillon', 'planifie'])) {
            return response()->json([
                'message' => 'Cet enlevement ne peut pas etre complete dans son statut actuel.',
            ], 422);
        }

        $data = $request->validated();

        $enlevement = DB::transaction(function () use ($enlevement, $data) {
            $coutTotal = 0;
            $revenuTotal = 0;

            foreach ($data['lignes'] as $ligneData) {
                $ligne = LigneEnlevement::where('enlevement_id', $enlevement->id)
                    ->findOrFail($ligneData['ligne_id']);

                $ligne->quantite_effective = $ligneData['quantite_effective'];

                // Get price from collecteur tarif
                $collecteur = $enlevement->collecteur;
                $tarif = $collecteur->typesDechets->firstWhere('id', $ligne->type_dechet_id);

                if ($tarif) {
                    $ligne->prix_unitaire = $tarif->pivot->prix_unitaire;
                    $ligne->est_rachat = $tarif->pivot->est_rachat;

                    if ($tarif->pivot->prix_forfaitaire) {
                        $ligne->montant = $tarif->pivot->prix_forfaitaire;
                    } else {
                        $ligne->montant = $ligne->quantite_effective * ($tarif->pivot->prix_unitaire ?? 0);
                    }

                    if ($ligne->est_rachat) {
                        $revenuTotal += $ligne->montant;
                    } else {
                        $coutTotal += $ligne->montant;
                    }
                }

                $ligne->save();

                // Update container level (decrease after enlevement)
                if ($ligne->conteneur_id) {
                    $conteneur = Conteneur::lockForUpdate()->find($ligne->conteneur_id);
                    if ($conteneur) {
                        $conteneur->niveau_actuel = max(0, $conteneur->niveau_actuel - $ligne->quantite_effective);
                        $conteneur->date_dernier_enlevement = now();
                        $conteneur->save();
                    }
                }

                // Create BSD for dangerous waste lines
                $typeDechet = TypeDechet::find($ligne->type_dechet_id);
                if ($typeDechet && $typeDechet->dangereux) {
                    // BSD requires kg — convert if needed
                    $quantiteBsd = $ligne->quantite_effective;
                    $uniteBsd = $ligne->unite;
                    if (! in_array($uniteBsd, ['kg', 'litres'])) {
                        $uniteBsd = 'kg';
                    }

                    Bordereau::create([
                        'garage_id' => $enlevement->garage_id,
                        'type_bsd' => 'BSDD',
                        'statut' => 'DRAFT',
                        'statut_sync' => 'en_attente',
                        'code_dechet' => $typeDechet->code_europeen,
                        'denomination_dechet' => $typeDechet->denomination,
                        'quantite' => $quantiteBsd,
                        'unite' => $uniteBsd,
                        'collecteur_id' => $enlevement->collecteur_id,
                        'transporteur_siret' => $collecteur->siret,
                        'transporteur_raison_sociale' => $collecteur->raison_sociale,
                        'destination_siret' => $collecteur->siret,
                        'destination_raison_sociale' => $collecteur->raison_sociale,
                        'destination_adresse' => trim(($collecteur->adresse ?? '').', '.($collecteur->code_postal ?? '').' '.($collecteur->ville ?? ''), ', ') ?: null,
                        'code_traitement' => 'R13',
                        'date_emission' => now(),
                        'enlevement_id' => $enlevement->id,
                        'ligne_enlevement_id' => $ligne->id,
                        'cree_par' => $enlevement->cree_par,
                    ]);
                }
            }

            // Update enlevement
            $enlevement->update([
                'statut' => 'complete',
                'date_effective' => $data['date_effective'],
                'numero_bon_enlevement' => $data['numero_bon_enlevement'] ?? null,
                'cout_total' => $coutTotal,
                'revenu_total' => $revenuTotal,
            ]);

            // Handle recurrence
            if ($enlevement->recurrence) {
                $nextDate = $this->calculateNextDate($enlevement->date_prevue, $enlevement->recurrence);
                if ($nextDate) {
                    $enlevement->update(['prochaine_date' => $nextDate]);

                    // Create next enlevement
                    $nextEnlevement = Enlevement::create([
                        'garage_id' => $enlevement->garage_id,
                        'numero' => $this->generateNumero($enlevement->garage_id),
                        'collecteur_id' => $enlevement->collecteur_id,
                        'date_prevue' => $nextDate,
                        'statut' => 'planifie',
                        'recurrence' => $enlevement->recurrence,
                        'notes' => $enlevement->notes,
                        'cree_par' => $enlevement->cree_par,
                    ]);

                    // Copy lines
                    foreach ($enlevement->lignes as $ligne) {
                        $nextEnlevement->lignes()->create([
                            'type_dechet_id' => $ligne->type_dechet_id,
                            'conteneur_id' => $ligne->conteneur_id,
                            'quantite_prevue' => $ligne->quantite_prevue,
                            'unite' => $ligne->unite,
                        ]);
                    }
                }
            }

            return $enlevement->fresh();
        });

        return response()->json([
            'data' => $enlevement->load(['collecteur', 'lignes.typeDechet', 'lignes.conteneur', 'lignes.bordereau']),
        ]);
    }

    /**
     * List enlevements for calendar view.
     */
    public function calendrier(Request $request): JsonResponse
    {
        $query = Enlevement::query();
        $this->scopeByGarage($query, $request);

        if ($request->filled('mois') && $request->filled('annee')) {
            $query->whereMonth('date_prevue', $request->input('mois'))
                ->whereYear('date_prevue', $request->input('annee'));
        } elseif ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->whereBetween('date_prevue', [$request->input('date_debut'), $request->input('date_fin')]);
        }

        $enlevements = $query->with('collecteur:id,raison_sociale')
            ->select('id', 'numero', 'collecteur_id', 'date_prevue', 'date_effective', 'statut', 'cout_total', 'revenu_total')
            ->orderBy('date_prevue')
            ->get();

        return response()->json(['data' => $enlevements]);
    }

    /**
     * Generate bon d'enlevement PDF.
     */
    public function bonPdf(Request $request, Enlevement $enlevement)
    {
        $this->scopeByGarage(Enlevement::where('id', $enlevement->id), $request)->firstOrFail();

        $enlevement->load(['collecteur', 'lignes.typeDechet', 'lignes.conteneur', 'garage.entreprise']);

        $exportService = app(ExportService::class);
        $pdf = $exportService->generateBonEnlevementPdf($enlevement);

        return $pdf->download("bon-enlevement-{$enlevement->numero}.pdf");
    }

    /**
     * Calculate next date based on recurrence pattern.
     */
    private function calculateNextDate($currentDate, string $recurrence)
    {
        $date = \Carbon\Carbon::parse($currentDate);

        return match ($recurrence) {
            'hebdomadaire' => $date->addWeek(),
            'bimensuel' => $date->addWeeks(2),
            'mensuel' => $date->addMonth(),
            'bimestriel' => $date->addMonths(2),
            'trimestriel' => $date->addMonths(3),
            'semestriel' => $date->addMonths(6),
            'annuel' => $date->addYear(),
            default => null,
        };
    }
}
