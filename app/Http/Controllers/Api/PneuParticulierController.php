<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriseEnChargePneu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PneuParticulierController extends Controller
{
    /**
     * List prises en charge pneus with annual counter.
     */
    public function index(Request $request): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        $query = PriseEnChargePneu::where('garage_id', $garageId)
            ->with('user');

        if ($request->filled('annee')) {
            $query->whereYear('date_prise_en_charge', $request->annee);
        }

        $prises = $query->orderByDesc('date_prise_en_charge')
            ->paginate($request->get('per_page', 20));

        return response()->json($prises);
    }

    /**
     * Create a new prise en charge pneu.
     * Validate max 8 pneus per prise en charge.
     */
    public function store(Request $request): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        $validated = $request->validate([
            'nom_particulier' => 'required|string|max:255',
            'prenom_particulier' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'nombre_pneus' => 'required|integer|min:1|max:8',
            'types_pneus' => 'required|array|min:1',
            'types_pneus.*.type' => 'required|string|in:vl,utilitaire,moto,poids_lourd',
            'types_pneus.*.quantite' => 'required|integer|min:1',
            'date_prise_en_charge' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Validate total pneus does not exceed 8
        $totalPneus = collect($validated['types_pneus'])->sum('quantite');
        if ($totalPneus > 8) {
            return response()->json([
                'message' => 'Le nombre total de pneus ne peut pas depasser 8 par prise en charge.',
                'errors' => [
                    'nombre_pneus' => ['Maximum 8 pneus par prise en charge.'],
                ],
            ], 422);
        }

        // Validate nombre_pneus matches types_pneus total
        if ($totalPneus !== (int) $validated['nombre_pneus']) {
            return response()->json([
                'message' => 'Le nombre de pneus ne correspond pas au detail des types.',
                'errors' => [
                    'nombre_pneus' => ['Le total des types doit correspondre au nombre de pneus.'],
                ],
            ], 422);
        }

        $prise = PriseEnChargePneu::create([
            'garage_id' => $garageId,
            'user_id' => $request->user()->id,
            'nom_particulier' => $validated['nom_particulier'],
            'prenom_particulier' => $validated['prenom_particulier'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'nombre_pneus' => $validated['nombre_pneus'],
            'types_pneus' => $validated['types_pneus'],
            'date_prise_en_charge' => $validated['date_prise_en_charge'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($prise, 201);
    }

    /**
     * Return the annual counter for pneus prises en charge.
     */
    public function compteurAnnuel(Request $request): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');
        $annee = $request->get('annee', now()->year);

        $totalPrises = PriseEnChargePneu::where('garage_id', $garageId)
            ->whereYear('date_prise_en_charge', $annee)
            ->count();

        $totalPneus = PriseEnChargePneu::where('garage_id', $garageId)
            ->whereYear('date_prise_en_charge', $annee)
            ->sum('nombre_pneus');

        return response()->json([
            'annee' => (int) $annee,
            'total_prises_en_charge' => $totalPrises,
            'total_pneus' => (int) $totalPneus,
        ]);
    }
}
