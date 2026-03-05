<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CollecteurRequest;
use App\Http\Traits\FiltersParGarage;
use App\Models\Collecteur;
use App\Models\TypeDechet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollecteurController extends Controller
{
    use FiltersParGarage;

    /**
     * List collecteurs for the current garage with types_dechets pivot (tarifs), filterable.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Collecteur::query();
        $this->scopeByGarage($query, $request);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('raison_sociale', 'like', "%{$search}%")
                    ->orWhere('siret', 'like', "%{$search}%")
                    ->orWhere('ville', 'like', "%{$search}%");
            });
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        if ($request->filled('eco_organisme')) {
            $query->where('eco_organisme', $request->input('eco_organisme'));
        }

        if ($request->filled('autorisation_valide')) {
            if ($request->boolean('autorisation_valide')) {
                $query->where('date_validite_autorisation', '>=', now());
            } else {
                $query->where(function ($q) {
                    $q->whereNull('date_validite_autorisation')
                        ->orWhere('date_validite_autorisation', '<', now());
                });
            }
        }

        $collecteurs = $query->with('typesDechets')->orderBy('raison_sociale')->get();

        return response()->json(['data' => $collecteurs]);
    }

    /**
     * Create a collecteur with validation (ADR required if DD types).
     */
    public function store(CollecteurRequest $request): JsonResponse
    {
        $garageId = $this->validateGarageAccess($request);

        $data = $request->validated();
        $tarifs = $data['tarifs'] ?? [];
        unset($data['tarifs']);

        // Check if any of the tarif types are dangereux and require ADR
        if (! empty($tarifs)) {
            $typeIds = array_column($tarifs, 'type_dechet_id');
            $hasDangereux = TypeDechet::whereIn('id', $typeIds)->where('dangereux', true)->exists();

            if ($hasDangereux && empty($data['autorisation_adr'])) {
                return response()->json([
                    'message' => 'L\'autorisation ADR est requise pour les collecteurs de dechets dangereux.',
                    'errors' => ['autorisation_adr' => ['L\'autorisation ADR est requise pour les types de dechets dangereux.']],
                ], 422);
            }
        }

        $data['garage_id'] = $garageId;
        $collecteur = Collecteur::create($data);

        // Attach tarifs
        if (! empty($tarifs)) {
            $pivotData = [];
            foreach ($tarifs as $tarif) {
                $pivotData[$tarif['type_dechet_id']] = [
                    'prix_unitaire' => $tarif['prix_unitaire'] ?? null,
                    'prix_forfaitaire' => $tarif['prix_forfaitaire'] ?? null,
                    'est_rachat' => $tarif['est_rachat'] ?? false,
                    'date_effet' => now(),
                ];
            }
            $collecteur->typesDechets()->attach($pivotData);
        }

        return response()->json(['data' => $collecteur->load('typesDechets')], 201);
    }

    /**
     * Show a collecteur with tarifs and enlevements history.
     */
    public function show(Request $request, Collecteur $collecteur): JsonResponse
    {
        $this->scopeByGarage(Collecteur::where('id', $collecteur->id), $request)->firstOrFail();

        $collecteur->load([
            'typesDechets',
            'enlevements' => function ($q) {
                $q->orderByDesc('date_prevue')->limit(20);
            },
            'enlevements.lignes.typeDechet',
        ]);

        return response()->json(['data' => $collecteur]);
    }

    /**
     * Update a collecteur.
     */
    public function update(CollecteurRequest $request, Collecteur $collecteur): JsonResponse
    {
        $this->scopeByGarage(Collecteur::where('id', $collecteur->id), $request)->firstOrFail();

        $data = $request->validated();
        $tarifs = $data['tarifs'] ?? null;
        unset($data['tarifs']);

        $collecteur->update($data);

        // Sync tarifs if provided
        if ($tarifs !== null) {
            $pivotData = [];
            foreach ($tarifs as $tarif) {
                $pivotData[$tarif['type_dechet_id']] = [
                    'prix_unitaire' => $tarif['prix_unitaire'] ?? null,
                    'prix_forfaitaire' => $tarif['prix_forfaitaire'] ?? null,
                    'est_rachat' => $tarif['est_rachat'] ?? false,
                    'date_effet' => now(),
                ];
            }
            $collecteur->typesDechets()->sync($pivotData);
        }

        return response()->json(['data' => $collecteur->load('typesDechets')]);
    }

    /**
     * Delete a collecteur.
     */
    public function destroy(Request $request, Collecteur $collecteur): JsonResponse
    {
        $this->scopeByGarage(Collecteur::where('id', $collecteur->id), $request)->firstOrFail();

        $collecteur->delete();

        return response()->json(['message' => 'Collecteur supprime.']);
    }

    /**
     * Return price comparison matrix: types x collecteurs with prices.
     */
    public function comparatif(Request $request): JsonResponse
    {
        $garageId = $this->getGarageId($request);

        $collecteurs = Collecteur::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->where('actif', true)
            ->with('typesDechets')
            ->orderBy('raison_sociale')
            ->get();

        // Get all type_dechets that have at least one tarif
        $typeIds = $collecteurs->flatMap(fn ($c) => $c->typesDechets->pluck('id'))->unique()->values();
        $types = TypeDechet::whereIn('id', $typeIds)->orderBy('denomination')->get();

        // Build matrix
        $matrix = [];
        foreach ($types as $type) {
            $row = [
                'type_dechet_id' => $type->id,
                'denomination' => $type->denomination,
                'code_europeen' => $type->code_europeen,
                'dangereux' => $type->dangereux,
                'tarifs' => [],
            ];

            $bestPrice = null;
            $bestCollecteurId = null;

            foreach ($collecteurs as $collecteur) {
                $pivot = $collecteur->typesDechets->firstWhere('id', $type->id);
                $tarif = null;

                if ($pivot) {
                    $tarif = [
                        'collecteur_id' => $collecteur->id,
                        'prix_unitaire' => $pivot->pivot->prix_unitaire,
                        'prix_forfaitaire' => $pivot->pivot->prix_forfaitaire,
                        'est_rachat' => $pivot->pivot->est_rachat,
                    ];

                    // Determine best price (lowest cost or highest rachat)
                    $price = $pivot->pivot->prix_unitaire;
                    if ($price !== null) {
                        if ($pivot->pivot->est_rachat) {
                            // For rachat, higher is better (revenue)
                            if ($bestPrice === null || $price > $bestPrice) {
                                $bestPrice = $price;
                                $bestCollecteurId = $collecteur->id;
                            }
                        } else {
                            // For cost, lower is better
                            if ($bestPrice === null || $price < $bestPrice) {
                                $bestPrice = $price;
                                $bestCollecteurId = $collecteur->id;
                            }
                        }
                    }
                }

                $row['tarifs'][] = $tarif;
            }

            $row['best_collecteur_id'] = $bestCollecteurId;
            $matrix[] = $row;
        }

        return response()->json([
            'collecteurs' => $collecteurs->map(fn ($c) => [
                'id' => $c->id,
                'raison_sociale' => $c->raison_sociale,
                'autorisation_valide' => $c->isAutorisationValide(),
            ]),
            'matrix' => $matrix,
        ]);
    }
}
