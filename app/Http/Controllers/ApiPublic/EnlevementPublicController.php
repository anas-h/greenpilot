<?php

namespace App\Http\Controllers\ApiPublic;

use App\Http\Controllers\Controller;
use App\Models\Enlevement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnlevementPublicController extends Controller
{
    /**
     * List enlevements for the authenticated garage.
     */
    public function index(Request $request): JsonResponse
    {
        $garage = $request->user()->currentGarage ?? $request->user()->garages()->first();
        if (! $garage) {
            return response()->json(['message' => 'Aucun garage associe a ce token.'], 403);
        }

        $query = Enlevement::where('garage_id', $garage->id)
            ->with('collecteur', 'typeDechet', 'conteneur');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('collecteur_id')) {
            $query->where('collecteur_id', $request->collecteur_id);
        }

        if ($request->filled('type_dechet_id')) {
            $query->where('type_dechet_id', $request->type_dechet_id);
        }

        if ($request->filled('date_debut')) {
            $query->where('date_enlevement', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->where('date_enlevement', '<=', $request->date_fin);
        }

        $enlevements = $query->orderByDesc('date_enlevement')
            ->paginate($request->get('per_page', 20));

        return response()->json($enlevements);
    }
}
