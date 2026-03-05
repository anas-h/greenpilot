<?php

namespace App\Http\Controllers\ApiPublic;

use App\Http\Controllers\Controller;
use App\Models\TypeDechet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeDechetPublicController extends Controller
{
    /**
     * List available waste types.
     *
     * Returns all active waste types with their codes and categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TypeDechet::query();

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('denomination', 'like', "%{$search}%")
                    ->orWhere('code_europeen', 'like', "%{$search}%");
            });
        }

        $types = $query->orderBy('denomination')->get();

        $data = $types->map(function ($type) {
            return [
                'id' => $type->id,
                'nom' => $type->denomination,
                'code' => $type->code_europeen,
                'code_dechet' => $type->code_europeen,
                'categorie' => $type->categorie,
                'dangereux' => $type->dangereux,
                'unite_mesure' => $type->unite_mesure,
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $data->count(),
        ]);
    }
}
