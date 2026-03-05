<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfigTrackdechets;
use App\Services\TrackdechetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfigTrackdechetsController extends Controller
{
    /**
     * Show the Trackdechets config for the current user's entreprise.
     */
    public function show(Request $request): JsonResponse
    {
        $entrepriseId = $request->current_garage->entreprise_id;

        $config = ConfigTrackdechets::where('entreprise_id', $entrepriseId)->first();

        if (! $config) {
            return response()->json([
                'configured' => false,
                'config' => null,
            ]);
        }

        // Mask the API token (show only last 8 chars)
        $maskedToken = str_repeat('*', max(0, strlen($config->api_token) - 8))
            .substr($config->api_token, -8);

        return response()->json([
            'configured' => true,
            'config' => [
                'id' => $config->id,
                'entreprise_id' => $config->entreprise_id,
                'environnement' => $config->environnement,
                'api_token_masked' => $maskedToken,
                'actif' => $config->actif,
                'siret_verifie' => $config->siret_verifie,
                'derniere_synchro' => $config->derniere_synchro,
                'created_at' => $config->created_at,
                'updated_at' => $config->updated_at,
            ],
        ]);
    }

    /**
     * Create or update the Trackdechets config for the current user's entreprise.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'api_token' => 'required|string|min:10',
            'environnement' => 'required|in:sandbox,production',
            'actif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $entrepriseId = $request->current_garage->entreprise_id;

        $config = ConfigTrackdechets::updateOrCreate(
            ['entreprise_id' => $entrepriseId],
            [
                'api_token' => $request->input('api_token'),
                'environnement' => $request->input('environnement'),
                'actif' => $request->input('actif', true),
            ]
        );

        return response()->json([
            'message' => 'Configuration Trackdechets enregistree.',
            'config' => [
                'id' => $config->id,
                'entreprise_id' => $config->entreprise_id,
                'environnement' => $config->environnement,
                'actif' => $config->actif,
            ],
        ]);
    }

    /**
     * Search companies via Trackdechets API.
     */
    public function searchCompanies(Request $request): JsonResponse
    {
        $request->validate([
            'clue' => 'required|string|min:2',
        ]);

        $entrepriseId = $request->current_garage->entreprise_id;

        $config = ConfigTrackdechets::where('entreprise_id', $entrepriseId)->where('actif', true)->first();

        if (! $config) {
            return response()->json([
                'message' => 'Integration Trackdechets non configuree pour cette entreprise.',
            ], 422);
        }

        $service = new TrackdechetsService($config);

        try {
            $results = $service->searchCompanies($request->input('clue'));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la recherche Trackdechets.',
                'error' => $e->getMessage(),
            ], 502);
        }

        return response()->json($results);
    }

    /**
     * Test the Trackdechets connection.
     */
    public function test(Request $request): JsonResponse
    {
        $entrepriseId = $request->current_garage->entreprise_id;

        $config = ConfigTrackdechets::where('entreprise_id', $entrepriseId)->first();

        if (! $config) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune configuration Trackdechets trouvee pour cette entreprise. Enregistrez d\'abord vos parametres.',
            ], 404);
        }

        $service = new TrackdechetsService($config);

        try {
            $result = $service->testConnection();

            // Update verification status
            $config->update([
                'siret_verifie' => true,
                'derniere_synchro' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connexion a Trackdechets reussie.',
                'companies' => $result['me']['companies'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Echec de la connexion: '.$e->getMessage(),
            ], 422);
        }
    }
}
