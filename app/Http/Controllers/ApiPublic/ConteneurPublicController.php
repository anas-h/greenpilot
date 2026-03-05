<?php

namespace App\Http\Controllers\ApiPublic;

use App\Http\Controllers\Controller;
use App\Models\Conteneur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConteneurPublicController extends Controller
{
    /**
     * List conteneurs and their fill levels for the authenticated garage.
     */
    public function index(Request $request): JsonResponse
    {
        $garage = $request->user()->currentGarage ?? $request->user()->garages()->first();
        if (! $garage) {
            return response()->json(['message' => 'Aucun garage associe a ce token.'], 403);
        }

        $query = Conteneur::where('garage_id', $garage->id)
            ->with('typeDechet', 'zone');

        if ($request->filled('type_dechet_id')) {
            $query->where('type_dechet_id', $request->type_dechet_id);
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $conteneurs = $query->orderBy('nom')->get();

        $data = $conteneurs->map(function ($conteneur) {
            return [
                'id' => $conteneur->id,
                'nom' => $conteneur->nom,
                'type_dechet' => $conteneur->typeDechet ? [
                    'id' => $conteneur->typeDechet->id,
                    'nom' => $conteneur->typeDechet->denomination,
                    'code' => $conteneur->typeDechet->code_europeen,
                ] : null,
                'zone' => $conteneur->zone ? [
                    'id' => $conteneur->zone->id,
                    'nom' => $conteneur->zone->nom,
                ] : null,
                'capacite' => $conteneur->capacite,
                'unite' => $conteneur->unite,
                'quantite_actuelle' => $conteneur->quantite_actuelle,
                'taux_remplissage' => $conteneur->capacite > 0
                    ? round(($conteneur->quantite_actuelle / $conteneur->capacite) * 100, 1)
                    : 0,
                'seuil_alerte' => $conteneur->seuil_alerte,
                'en_alerte' => $conteneur->capacite > 0
                    && $conteneur->seuil_alerte > 0
                    && ($conteneur->quantite_actuelle / $conteneur->capacite * 100) >= $conteneur->seuil_alerte,
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $data->count(),
        ]);
    }
}
