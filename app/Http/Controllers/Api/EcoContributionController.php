<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EcoContribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcoContributionController extends Controller
{
    /**
     * List eco-contributions filterable by filiere, annee, trimestre, statut.
     */
    public function index(Request $request): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        $query = EcoContribution::where('garage_id', $garageId);

        if ($request->filled('filiere')) {
            $query->where('filiere', $request->filiere);
        }

        if ($request->filled('annee')) {
            $query->where('annee', $request->annee);
        }

        if ($request->filled('trimestre')) {
            $query->where('trimestre', $request->trimestre);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $contributions = $query->orderByDesc('annee')
            ->orderByDesc('trimestre')
            ->paginate($request->get('per_page', 20));

        return response()->json($contributions);
    }

    /**
     * Create a new eco-contribution.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filiere_id' => 'required|exists:filieres_rep,id',
            'annee' => 'required|integer|min:2020|max:2099',
            'trimestre' => 'required|integer|min:1|max:4',
            'montant' => 'required|numeric|min:0',
            'quantite_declaree' => 'required|numeric|min:0',
            'unite' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        $garageId = $request->header('X-Garage-Id');

        // Check for duplicate
        $existing = EcoContribution::where('garage_id', $garageId)
            ->where('filiere_id', $validated['filiere_id'])
            ->where('annee', $validated['annee'])
            ->where('trimestre', $validated['trimestre'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Une eco-contribution existe deja pour cette filiere, annee et trimestre.',
            ], 422);
        }

        $contribution = EcoContribution::create(array_merge($validated, [
            'garage_id' => $garageId,
            'statut' => 'brouillon',
            'created_by' => $request->user()->id,
        ]));

        return response()->json($contribution, 201);
    }

    /**
     * Update an eco-contribution (only if brouillon).
     */
    public function update(Request $request, EcoContribution $ecoContribution): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        if ($ecoContribution->garage_id != $garageId) {
            return response()->json(['message' => 'Non autorise.'], 403);
        }

        if ($ecoContribution->statut !== 'brouillon') {
            return response()->json([
                'message' => 'Seules les eco-contributions en brouillon peuvent etre modifiees.',
            ], 422);
        }

        $validated = $request->validate([
            'montant' => 'sometimes|numeric|min:0',
            'quantite_declaree' => 'sometimes|numeric|min:0',
            'unite' => 'sometimes|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        $ecoContribution->update($validated);

        return response()->json($ecoContribution);
    }

    /**
     * Mark an eco-contribution as declared.
     */
    public function declare(Request $request, EcoContribution $ecoContribution): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        if ($ecoContribution->garage_id != $garageId) {
            return response()->json(['message' => 'Non autorise.'], 403);
        }

        if ($ecoContribution->statut !== 'brouillon') {
            return response()->json([
                'message' => 'Seules les eco-contributions en brouillon peuvent etre declarees.',
            ], 422);
        }

        $ecoContribution->update([
            'statut' => 'declare',
            'date_declaration' => now(),
            'declared_by' => $request->user()->id,
        ]);

        return response()->json($ecoContribution);
    }

    /**
     * Mark an eco-contribution as paid.
     */
    public function payer(Request $request, EcoContribution $ecoContribution): JsonResponse
    {
        $garageId = $request->header('X-Garage-Id');

        if ($ecoContribution->garage_id != $garageId) {
            return response()->json(['message' => 'Non autorise.'], 403);
        }

        if ($ecoContribution->statut !== 'declare') {
            return response()->json([
                'message' => 'Seules les eco-contributions declarees peuvent etre marquees comme payees.',
            ], 422);
        }

        $validated = $request->validate([
            'reference_paiement' => 'nullable|string|max:100',
        ]);

        $ecoContribution->update([
            'statut' => 'paye',
            'date_paiement' => now(),
            'reference_paiement' => $validated['reference_paiement'] ?? null,
        ]);

        return response()->json($ecoContribution);
    }
}
