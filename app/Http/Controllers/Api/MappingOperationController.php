<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MappingOperation;
use App\Services\MappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MappingOperationController extends Controller
{
    public function __construct(
        private MappingService $mappingService
    ) {}

    /**
     * List mappings for current garage.
     */
    public function index(Request $request): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        $query = MappingOperation::where('garage_id', $garageId)
            ->with('typeDechet');

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        if ($request->filled('search')) {
            $query->where('mot_cle', 'like', '%'.$request->search.'%');
        }

        $mappings = $query->orderBy('mot_cle')
            ->paginate($request->get('per_page', 50));

        return response()->json($mappings);
    }

    /**
     * Create a new mapping.
     */
    public function store(Request $request): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        $validated = $request->validate([
            'mot_cle' => 'required|string|max:255',
            'type_dechet_id' => 'required|exists:types_dechets,id',
            'quantite_defaut' => 'required|numeric|min:0',
            'unite' => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
            'actif' => 'boolean',
        ]);

        // Check duplicate mot_cle for this garage
        $existing = MappingOperation::where('garage_id', $garageId)
            ->where('mot_cle', $validated['mot_cle'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Un mapping avec ce mot-cle existe deja pour ce garage.',
            ], 422);
        }

        $mapping = MappingOperation::create(array_merge($validated, [
            'garage_id' => $garageId,
            'actif' => $validated['actif'] ?? true,
        ]));

        $mapping->load('typeDechet');

        return response()->json($mapping, 201);
    }

    /**
     * Update a mapping.
     */
    public function update(Request $request, MappingOperation $mapping): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        if ($mapping->garage_id != $garageId) {
            return response()->json(['message' => 'Non autorise.'], 403);
        }

        $validated = $request->validate([
            'mot_cle' => 'sometimes|string|max:255',
            'type_dechet_id' => 'sometimes|exists:types_dechets,id',
            'quantite_defaut' => 'sometimes|numeric|min:0',
            'unite' => 'sometimes|string|max:20',
            'description' => 'nullable|string|max:500',
            'actif' => 'sometimes|boolean',
        ]);

        // Check duplicate mot_cle if changed
        if (isset($validated['mot_cle']) && $validated['mot_cle'] !== $mapping->mot_cle) {
            $existing = MappingOperation::where('garage_id', $garageId)
                ->where('mot_cle', $validated['mot_cle'])
                ->where('id', '!=', $mapping->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'Un mapping avec ce mot-cle existe deja pour ce garage.',
                ], 422);
            }
        }

        $mapping->update($validated);
        $mapping->load('typeDechet');

        return response()->json($mapping);
    }

    /**
     * Delete a mapping.
     */
    public function destroy(Request $request, MappingOperation $mapping): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        if ($mapping->garage_id != $garageId) {
            return response()->json(['message' => 'Non autorise.'], 403);
        }

        $mapping->delete();

        return response()->json(null, 204);
    }

    /**
     * Given a text input, find matching mappings and return suggested productions.
     */
    public function suggest(Request $request): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        $validated = $request->validate([
            'description' => 'required|string|max:1000',
        ]);

        $suggestions = $this->mappingService->findSuggestions(
            (int) $garageId,
            $validated['description']
        );

        return response()->json([
            'suggestions' => $suggestions,
            'count' => count($suggestions),
        ]);
    }
}
