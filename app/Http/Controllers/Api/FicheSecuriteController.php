<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FicheSecurite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FicheSecuriteController extends Controller
{
    /**
     * List FDS for the current garage.
     */
    public function index(Request $request): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;

        $query = FicheSecurite::where('garage_id', $garageId)
            ->with(['typeDechet']);

        // Filter by type_dechet_id
        if ($request->filled('type_dechet_id')) {
            $query->where('type_dechet_id', $request->input('type_dechet_id'));
        }

        // Filter by actif
        if ($request->has('actif')) {
            $query->where('actif', filter_var($request->input('actif'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by status (present, expired, missing is handled client-side)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nom_produit', 'like', "%{$search}%")
                    ->orWhere('fournisseur', 'like', "%{$search}%")
                    ->orWhereHas('typeDechet', fn ($q2) => $q2->where('denomination', 'like', "%{$search}%"));
            });
        }

        $query->orderBy('updated_at', 'desc');

        $fiches = $query->paginate($request->input('per_page', 50));

        return response()->json($fiches);
    }

    /**
     * Show a single FDS.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;

        $fiche = FicheSecurite::where('garage_id', $garageId)
            ->with(['typeDechet'])
            ->findOrFail($id);

        return response()->json($fiche);
    }

    /**
     * Store a new FDS with PDF upload.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type_dechet_id' => 'required|exists:types_dechets,id',
            'nom_produit' => 'required|string|max:255',
            'fournisseur' => 'nullable|string|max:255',
            'date_emission' => 'nullable|date',
            'date_validite' => 'nullable|date|after_or_equal:date_emission',
            'fichier' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $garageId = $request->user()->current_garage_id;

        // Store the PDF file
        $file = $request->file('fichier');
        $path = $file->store("fds/{$garageId}", 'local');

        // Deactivate any existing FDS for same type_dechet in this garage
        FicheSecurite::where('garage_id', $garageId)
            ->where('type_dechet_id', $request->input('type_dechet_id'))
            ->where('actif', true)
            ->update(['actif' => false]);

        $fiche = FicheSecurite::create([
            'garage_id' => $garageId,
            'type_dechet_id' => $request->input('type_dechet_id'),
            'nom_produit' => $request->input('nom_produit'),
            'fournisseur' => $request->input('fournisseur'),
            'date_emission' => $request->input('date_emission'),
            'date_validite' => $request->input('date_validite'),
            'fichier_path' => $path,
            'actif' => true,
            'cree_par' => $request->user()->id,
        ]);

        $fiche->load('typeDechet');

        return response()->json($fiche, 201);
    }

    /**
     * Update an existing FDS.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $fiche = FicheSecurite::where('garage_id', $garageId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nom_produit' => 'sometimes|string|max:255',
            'fournisseur' => 'nullable|string|max:255',
            'date_emission' => 'nullable|date',
            'date_validite' => 'nullable|date',
            'fichier' => 'nullable|file|mimes:pdf|max:10240',
            'actif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except(['fichier']);

        // Handle new file upload
        if ($request->hasFile('fichier')) {
            // Delete old file
            if ($fiche->fichier_path && Storage::disk('local')->exists($fiche->fichier_path)) {
                Storage::disk('local')->delete($fiche->fichier_path);
            }

            $file = $request->file('fichier');
            $path = $file->store("fds/{$garageId}", 'local');

            $data['fichier_path'] = $path;
        }

        $fiche->update($data);
        $fiche->load('typeDechet');

        return response()->json($fiche);
    }

    /**
     * Delete an FDS.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $fiche = FicheSecurite::where('garage_id', $garageId)->findOrFail($id);

        // Delete the file
        if ($fiche->fichier_path && Storage::disk('local')->exists($fiche->fichier_path)) {
            Storage::disk('local')->delete($fiche->fichier_path);
        }

        $fiche->delete();

        return response()->json(['message' => 'Fiche de securite supprimee.']);
    }

    /**
     * Download the FDS PDF file.
     */
    public function download(Request $request, int $id)
    {
        $garageId = $request->user()->current_garage_id;
        $fiche = FicheSecurite::where('garage_id', $garageId)->findOrFail($id);

        if (! $fiche->fichier_path || ! Storage::disk('local')->exists($fiche->fichier_path)) {
            return response()->json(['message' => 'Fichier non trouve.'], 404);
        }

        return Storage::disk('local')->download(
            $fiche->fichier_path,
            $fiche->nom_produit.'.pdf'
        );
    }
}
