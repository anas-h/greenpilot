<?php

namespace App\Http\Controllers\ApiPublic;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Services\MappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductionPublicController extends Controller
{
    public function __construct(
        private MappingService $mappingService
    ) {}

    /**
     * Create a single production from external DMS/ERP.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type_dechet_id' => 'required|exists:types_dechets,id',
            'quantite' => 'required|numeric|min:0.01',
            'unite' => 'required|string|max:20',
            'conteneur_id' => 'nullable|exists:conteneurs,id',
            'reference_externe' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date_production' => 'nullable|date',
        ]);

        $garage = $request->user()->currentGarage ?? $request->user()->garages()->first();
        if (! $garage) {
            return response()->json(['message' => 'Aucun garage associe a ce token.'], 403);
        }

        $production = Production::create([
            'garage_id' => $garage->id,
            'type_dechet_id' => $validated['type_dechet_id'],
            'quantite' => $validated['quantite'],
            'unite' => $validated['unite'],
            'conteneur_id' => $validated['conteneur_id'] ?? null,
            'reference_externe' => $validated['reference_externe'] ?? null,
            'description' => $validated['description'] ?? null,
            'date_production' => $validated['date_production'] ?? now(),
            'source' => 'api',
            'created_by' => $request->user()->id,
        ]);

        $production->load('typeDechet', 'conteneur');

        return response()->json([
            'message' => 'Production creee avec succes.',
            'data' => $production,
        ], 201);
    }

    /**
     * Create multiple productions in batch.
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $request->validate([
            'productions' => 'required|array|min:1|max:100',
            'productions.*.type_dechet_id' => 'required|exists:types_dechets,id',
            'productions.*.quantite' => 'required|numeric|min:0.01',
            'productions.*.unite' => 'required|string|max:20',
            'productions.*.conteneur_id' => 'nullable|exists:conteneurs,id',
            'productions.*.reference_externe' => 'nullable|string|max:255',
            'productions.*.description' => 'nullable|string|max:1000',
            'productions.*.date_production' => 'nullable|date',
        ]);

        $garage = $request->user()->currentGarage ?? $request->user()->garages()->first();
        if (! $garage) {
            return response()->json(['message' => 'Aucun garage associe a ce token.'], 403);
        }

        $created = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->productions as $index => $prodData) {
                $validator = Validator::make($prodData, [
                    'type_dechet_id' => 'required|exists:types_dechets,id',
                    'quantite' => 'required|numeric|min:0.01',
                    'unite' => 'required|string|max:20',
                ]);

                if ($validator->fails()) {
                    $errors[] = [
                        'index' => $index,
                        'errors' => $validator->errors(),
                    ];

                    continue;
                }

                $production = Production::create([
                    'garage_id' => $garage->id,
                    'type_dechet_id' => $prodData['type_dechet_id'],
                    'quantite' => $prodData['quantite'],
                    'unite' => $prodData['unite'],
                    'conteneur_id' => $prodData['conteneur_id'] ?? null,
                    'reference_externe' => $prodData['reference_externe'] ?? null,
                    'description' => $prodData['description'] ?? null,
                    'date_production' => $prodData['date_production'] ?? now(),
                    'source' => 'api',
                    'created_by' => $request->user()->id,
                ]);

                $created[] = $production;
            }

            if (! empty($errors) && empty($created)) {
                DB::rollBack();

                return response()->json([
                    'message' => 'Aucune production creee. Voir les erreurs.',
                    'errors' => $errors,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'message' => count($created).' production(s) creee(s) avec succes.',
                'data' => $created,
                'errors' => $errors,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la creation des productions.',
            ], 500);
        }
    }

    /**
     * Suggest mappings for a given operation description.
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:1000',
        ]);

        $garage = $request->user()->currentGarage ?? $request->user()->garages()->first();
        if (! $garage) {
            return response()->json(['message' => 'Aucun garage associe a ce token.'], 403);
        }

        $suggestions = $this->mappingService->findSuggestions(
            $garage->id,
            $validated['description']
        );

        return response()->json([
            'suggestions' => $suggestions,
            'count' => count($suggestions),
        ]);
    }
}
