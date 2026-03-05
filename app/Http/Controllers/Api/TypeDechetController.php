<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\FiltersParGarage;
use App\Models\Garage;
use App\Models\TypeDechet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeDechetController extends Controller
{
    use FiltersParGarage;

    /**
     * List all system types + enterprise custom types.
     * Filterable by categorie, dangereux, actif.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = TypeDechet::where(function ($q) use ($user) {
            // System types (no entreprise_id)
            $q->whereNull('entreprise_id')
              // Enterprise custom types
                ->orWhere('entreprise_id', $user->entreprise_id);
        });

        // Filters
        if ($request->has('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->has('dangereux')) {
            $query->where('dangereux', filter_var($request->dangereux, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('actif')) {
            $garageId = $this->getGarageId($request);
            if ($garageId) {
                $actif = filter_var($request->actif, FILTER_VALIDATE_BOOLEAN);
                $query->whereHas('garages', function ($q) use ($garageId, $actif) {
                    $q->where('garages.id', $garageId)
                        ->where('garage_type_dechet.actif', $actif);
                });
            }
        }

        $types = $query->orderBy('categorie')->orderBy('denomination')->get();

        // Attach pivot info if garage is selected
        $garageId = $this->getGarageId($request);
        if ($garageId) {
            $garage = Garage::with('typesDechets')->find($garageId);
            $pivotMap = [];
            if ($garage) {
                foreach ($garage->typesDechets as $td) {
                    $pivotMap[$td->id] = [
                        'actif' => $td->pivot->actif,
                        'quantite_defaut' => $td->pivot->quantite_defaut,
                    ];
                }
            }
            $types->each(function ($type) use ($pivotMap) {
                $type->garage_config = $pivotMap[$type->id] ?? null;
            });
        }

        return response()->json(['data' => $types]);
    }

    /**
     * Create a custom type for the enterprise.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'code_europeen' => 'nullable|string|max:20',
            'denomination' => 'required|string|max:255',
            'dangereux' => 'required|boolean',
            'unite_mesure' => 'required|string|in:kg,litres,unites,tonnes',
            'categorie' => 'required|string|max:100',
            'filiere_rep' => 'nullable|string|max:100',
            'pictogrammes_danger' => 'nullable|array',
            'consignes_stockage' => 'nullable|string',
            'consignes_tri' => 'nullable|string',
        ]);

        $type = TypeDechet::create(array_merge($validated, [
            'entreprise_id' => $user->entreprise_id,
        ]));

        return response()->json(['data' => $type], 201);
    }

    /**
     * Update a custom type (only enterprise types, not system types).
     */
    public function update(Request $request, TypeDechet $typeDechet): JsonResponse
    {
        $user = $request->user();

        if ($typeDechet->entreprise_id === null) {
            return response()->json(['message' => 'Les types systeme ne peuvent pas etre modifies.'], 403);
        }

        if ($typeDechet->entreprise_id !== $user->entreprise_id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $validated = $request->validate([
            'code_europeen' => 'nullable|string|max:20',
            'denomination' => 'sometimes|required|string|max:255',
            'dangereux' => 'sometimes|required|boolean',
            'unite_mesure' => 'sometimes|required|string|in:kg,litres,unites,tonnes',
            'categorie' => 'sometimes|required|string|max:100',
            'filiere_rep' => 'nullable|string|max:100',
            'pictogrammes_danger' => 'nullable|array',
            'consignes_stockage' => 'nullable|string',
            'consignes_tri' => 'nullable|string',
        ]);

        $typeDechet->update($validated);

        return response()->json(['data' => $typeDechet]);
    }

    /**
     * Soft delete a custom type (only enterprise types).
     */
    public function destroy(Request $request, TypeDechet $typeDechet): JsonResponse
    {
        $user = $request->user();

        if ($typeDechet->entreprise_id === null) {
            return response()->json(['message' => 'Les types systeme ne peuvent pas etre supprimes.'], 403);
        }

        if ($typeDechet->entreprise_id !== $user->entreprise_id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $typeDechet->delete();

        return response()->json(['message' => 'Type de dechet supprime.']);
    }

    /**
     * List only system types (for onboarding).
     */
    public function systeme(Request $request): JsonResponse
    {
        $types = TypeDechet::whereNull('entreprise_id')
            ->orderBy('categorie')
            ->orderBy('denomination')
            ->get();

        return response()->json(['data' => $types]);
    }

    /**
     * Toggle a type for the current garage (garage_type_dechet pivot).
     */
    public function toggleActivation(Request $request, TypeDechet $typeDechet): JsonResponse
    {
        $garageId = $this->validateGarageAccess($request);

        $garage = Garage::findOrFail($garageId);

        // Check if the type is already attached
        $existing = $garage->typesDechets()->where('type_dechet_id', $typeDechet->id)->first();

        if ($existing) {
            // Toggle the actif flag
            $newActif = ! $existing->pivot->actif;
            $garage->typesDechets()->updateExistingPivot($typeDechet->id, ['actif' => $newActif]);
            $status = $newActif ? 'active' : 'desactive';
        } else {
            // Attach as active
            $garage->typesDechets()->attach($typeDechet->id, ['actif' => true]);
            $status = 'active';
        }

        return response()->json([
            'message' => "Type de dechet {$status} pour ce garage.",
            'actif' => $status === 'active',
        ]);
    }
}
