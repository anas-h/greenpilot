<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GarageRequest;
use App\Http\Traits\FiltersParGarage;
use App\Models\Garage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GarageController extends Controller
{
    use FiltersParGarage;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Super admin can see all garages across all enterprises
        if ($user->isSuperAdmin()) {
            $query = Garage::where('actif', true);
        } else {
            $query = Garage::where('entreprise_id', $user->entreprise_id)->where('actif', true);
        }

        if (! in_array($user->role, ['admin', 'super_admin'])) {
            $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(GarageRequest $request): JsonResponse
    {
        $user = $request->user();

        // Check plan limits
        $entreprise = $user->entreprise;
        if (! $entreprise->canAddGarage()) {
            return response()->json([
                'message' => 'Limite de garages atteinte pour votre plan. Passez a un plan superieur.',
            ], 422);
        }

        $garage = Garage::create(array_merge($request->validated(), [
            'entreprise_id' => $user->entreprise_id,
        ]));

        // Auto-determine ICPE regime based on surface
        if ($garage->surface_atelier) {
            if ($garage->surface_atelier >= 5000) {
                $garage->regime_icpe = 'enregistrement';
            } elseif ($garage->surface_atelier >= 2000) {
                $garage->regime_icpe = 'declaration';
            } else {
                $garage->regime_icpe = 'non_classe';
            }
            $garage->save();
        }

        // Assign garage to creator via pivot table
        $user->garages()->syncWithoutDetaching([$garage->id]);

        return response()->json(['data' => $garage], 201);
    }

    public function show(Request $request, Garage $garage): JsonResponse
    {
        $this->authorizeGarage($request, $garage);

        return response()->json(['data' => $garage->load('entreprise', 'typesDechets')]);
    }

    public function update(GarageRequest $request, Garage $garage): JsonResponse
    {
        $this->authorizeGarage($request, $garage);

        $garage->update($request->validated());

        return response()->json(['data' => $garage]);
    }

    public function destroy(Request $request, Garage $garage): JsonResponse
    {
        $this->authorizeGarage($request, $garage);

        $garage->delete();

        return response()->json(['message' => 'Garage supprime.']);
    }

    private function authorizeGarage(Request $request, Garage $garage): void
    {
        $user = $request->user();
        if ($garage->entreprise_id !== $user->entreprise_id) {
            abort(403, 'Access denied.');
        }
    }
}
