<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductionBatchRequest;
use App\Http\Requests\ProductionRequest;
use App\Http\Traits\FiltersParGarage;
use App\Models\Conteneur;
use App\Models\MappingOperation;
use App\Models\Production;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    use FiltersParGarage;

    /**
     * List productions filterable by date range, type_dechet, valide, source_type.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Production::query();
        $this->scopeByGarage($query, $request);

        if ($request->filled('date_debut')) {
            $query->where('date_production', '>=', $request->input('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->where('date_production', '<=', $request->input('date_fin'));
        }
        if ($request->filled('type_dechet_id')) {
            $query->where('type_dechet_id', $request->input('type_dechet_id'));
        }
        if ($request->has('valide')) {
            $query->where('valide', $request->boolean('valide'));
        }
        if ($request->filled('source_type')) {
            $query->where('source_type', $request->input('source_type'));
        }

        $productions = $query->with(['typeDechet', 'conteneur', 'employe:id,nom,prenom'])
            ->orderByDesc('date_production')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json($productions);
    }

    /**
     * Create a production and update container level via transaction.
     */
    public function store(ProductionRequest $request): JsonResponse
    {
        $garageId = $this->validateGarageAccess($request);

        $data = $request->validated();
        $data['garage_id'] = $garageId;
        $data['employe_id'] = $request->user()->id;

        $production = DB::transaction(function () use ($data) {
            $production = Production::create($data);

            // Update container level if linked
            if ($production->conteneur_id) {
                $conteneur = Conteneur::lockForUpdate()->find($production->conteneur_id);
                if ($conteneur) {
                    $conteneur->niveau_actuel = min(
                        $conteneur->niveau_actuel + $production->quantite,
                        $conteneur->capacite > 0 ? $conteneur->capacite : PHP_FLOAT_MAX
                    );
                    $conteneur->save();
                }
            }

            return $production;
        });

        return response()->json(['data' => $production->load(['typeDechet', 'conteneur'])], 201);
    }

    /**
     * Batch create multiple productions at once.
     */
    public function storeBatch(ProductionBatchRequest $request): JsonResponse
    {
        $garageId = $this->validateGarageAccess($request);
        $userId = $request->user()->id;

        $productions = DB::transaction(function () use ($request, $garageId, $userId) {
            $results = [];

            foreach ($request->validated()['productions'] as $prodData) {
                $prodData['garage_id'] = $garageId;
                $prodData['employe_id'] = $userId;

                $production = Production::create($prodData);

                // Update container level if linked
                if ($production->conteneur_id) {
                    $conteneur = Conteneur::lockForUpdate()->find($production->conteneur_id);
                    if ($conteneur) {
                        $conteneur->niveau_actuel = min(
                            $conteneur->niveau_actuel + $production->quantite,
                            $conteneur->capacite > 0 ? $conteneur->capacite : PHP_FLOAT_MAX
                        );
                        $conteneur->save();
                    }
                }

                $results[] = $production;
            }

            return $results;
        });

        return response()->json([
            'data' => collect($productions)->load(['typeDechet', 'conteneur']),
            'count' => count($productions),
        ], 201);
    }

    /**
     * Show a single production.
     */
    public function show(Request $request, Production $production): JsonResponse
    {
        $this->scopeByGarage(Production::where('id', $production->id), $request)->firstOrFail();

        $production->load(['typeDechet', 'conteneur', 'employe:id,nom,prenom', 'validePar:id,nom,prenom', 'corrections']);

        return response()->json(['data' => $production]);
    }

    /**
     * Update a production.
     */
    public function update(ProductionRequest $request, Production $production): JsonResponse
    {
        $this->scopeByGarage(Production::where('id', $production->id), $request)->firstOrFail();

        $oldQuantite = $production->quantite;
        $oldContId = $production->conteneur_id;

        $production->update($request->validated());

        // Adjust container levels if quantity or container changed
        DB::transaction(function () use ($production, $oldQuantite, $oldContId) {
            // Revert old container level
            if ($oldContId) {
                $oldConteneur = Conteneur::lockForUpdate()->find($oldContId);
                if ($oldConteneur) {
                    $oldConteneur->niveau_actuel = max(0, $oldConteneur->niveau_actuel - $oldQuantite);
                    $oldConteneur->save();
                }
            }

            // Apply to new/current container
            if ($production->conteneur_id) {
                $conteneur = Conteneur::lockForUpdate()->find($production->conteneur_id);
                if ($conteneur) {
                    $conteneur->niveau_actuel = min(
                        $conteneur->niveau_actuel + $production->quantite,
                        $conteneur->capacite > 0 ? $conteneur->capacite : PHP_FLOAT_MAX
                    );
                    $conteneur->save();
                }
            }
        });

        return response()->json(['data' => $production->load(['typeDechet', 'conteneur'])]);
    }

    /**
     * Delete a production.
     */
    public function destroy(Request $request, Production $production): JsonResponse
    {
        $this->scopeByGarage(Production::where('id', $production->id), $request)->firstOrFail();

        DB::transaction(function () use ($production) {
            // Revert container level
            if ($production->conteneur_id) {
                $conteneur = Conteneur::lockForUpdate()->find($production->conteneur_id);
                if ($conteneur) {
                    $conteneur->niveau_actuel = max(0, $conteneur->niveau_actuel - $production->quantite);
                    $conteneur->save();
                }
            }

            $production->delete();
        });

        return response()->json(['message' => 'Production supprimee.']);
    }

    /**
     * Validate one or multiple productions.
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:productions,id',
        ]);

        $query = Production::whereIn('id', $request->input('ids'));
        $this->scopeByGarage($query, $request);

        $count = $query->where('valide', false)->update([
            'valide' => true,
            'valide_par' => $request->user()->id,
            'date_validation' => now(),
        ]);

        return response()->json([
            'message' => "{$count} production(s) validee(s).",
            'count' => $count,
        ]);
    }

    /**
     * Scan a product barcode and suggest waste type mapping.
     */
    public function scanBarcode(Request $request): JsonResponse
    {
        $request->validate([
            'ean' => 'required|string|regex:/^\d{8,14}$/',
        ]);

        $garageId = $request->current_garage->id;
        $service = new \App\Services\BarcodeService();

        $product = $service->lookupProduct($request->input('ean'));

        if (!$product) {
            return response()->json([
                'found' => false,
                'message' => 'Produit non trouve dans la base de donnees.',
            ]);
        }

        $categorie = $service->matchCategory($product);
        $typeDechet = $service->findTypeDechet($categorie, $garageId);
        $unite = $typeDechet?->unite_mesure ?? 'kg';
        $quantite = $service->estimateQuantity($product, $unite);

        return response()->json([
            'found' => true,
            'product' => [
                'name' => $product['product_name'] ?? $product['product_name_fr'] ?? null,
                'brand' => $product['brands'] ?? null,
                'categories' => $product['categories'] ?? null,
                'quantity' => $product['quantity'] ?? null,
                'ean' => $request->input('ean'),
            ],
            'suggestion' => [
                'categorie' => $categorie,
                'type_dechet_id' => $typeDechet?->id,
                'type_dechet_denomination' => $typeDechet?->denomination,
                'unite' => $unite,
                'quantite' => $quantite,
            ],
        ]);
    }

    /**
     * Given a text description, find matching mappings and suggest productions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'texte' => 'required|string|min:2',
        ]);

        $garageId = $this->getGarageId($request);
        $texte = mb_strtolower($request->input('texte'));

        $mappings = MappingOperation::when($garageId, fn ($q) => $q->where('garage_id', $garageId))
            ->where('actif', true)
            ->with('typeDechet')
            ->get()
            ->filter(function ($mapping) use ($texte) {
                return str_contains($texte, mb_strtolower($mapping->mot_cle));
            })
            ->values();

        $suggestions = $mappings->map(fn ($m) => [
            'mapping_id' => $m->id,
            'mot_cle' => $m->mot_cle,
            'type_dechet_id' => $m->type_dechet_id,
            'type_dechet' => $m->typeDechet,
            'quantite_defaut' => $m->quantite_defaut,
            'unite' => $m->unite,
        ]);

        return response()->json(['data' => $suggestions]);
    }
}
