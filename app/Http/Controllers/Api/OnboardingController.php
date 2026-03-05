<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\FiltersParGarage;
use App\Models\Conteneur;
use App\Models\TypeDechet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    use FiltersParGarage;

    public function complete(Request $request): JsonResponse
    {
        $request->validate([
            'type_dechet_ids' => 'required|array',
            'type_dechet_ids.*' => 'exists:types_dechets,id',
        ]);

        $garageId = $this->validateGarageAccess($request);

        // Activate selected types for the garage
        $pivotData = [];
        foreach ($request->type_dechet_ids as $typeId) {
            $pivotData[$typeId] = ['actif' => true];
        }

        $garage = $request->current_garage ?? \App\Models\Garage::findOrFail($garageId);
        $garage->typesDechets()->syncWithoutDetaching($pivotData);

        // Create default containers for each selected type
        $types = TypeDechet::whereIn('id', $request->type_dechet_ids)->get();
        foreach ($types as $type) {
            $defaultCapacity = match ($type->unite_mesure) {
                'litres' => 200,
                'kg' => 100,
                'unites' => 50,
                default => 100,
            };

            Conteneur::create([
                'garage_id' => $garageId,
                'type_dechet_id' => $type->id,
                'nom' => $type->denomination,
                'code_qr' => 'ECO-'.$garageId.'-'.Str::random(8),
                'capacite' => $defaultCapacity,
                'unite' => $type->unite_mesure,
                'niveau_actuel' => 0,
                'mixte' => false,
                'actif' => true,
                'date_mise_en_service' => now(),
            ]);
        }

        // Create default mappings
        \Database\Seeders\MappingOperationSeeder::createForGarage($garageId);

        // Auto-assign the garage to the current user via pivot
        $request->user()->garages()->syncWithoutDetaching([$garageId]);

        return response()->json(['message' => 'Configuration terminee.']);
    }
}
