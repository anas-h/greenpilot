<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CreateBsdTrackdechetsJob;
use App\Jobs\SignBsdTrackdechetsJob;
use App\Models\Bordereau;
use App\Services\TrackdechetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BordereauController extends Controller
{
    /**
     * Statut transitions map: current => allowed next statuses.
     */
    private const TRANSITIONS = [
        'DRAFT' => ['SEALED', 'CANCELED'],
        'SEALED' => ['SIGNED_BY_PRODUCER', 'CANCELED'],
        'SIGNED_BY_PRODUCER' => ['SENT', 'CANCELED'],
        'SENT' => ['RECEIVED', 'REFUSED', 'CANCELED'],
        'RECEIVED' => ['PROCESSED', 'REFUSED', 'CANCELED'],
        'PROCESSED' => [],
        'REFUSED' => [],
        'CANCELED' => [],
    ];

    /**
     * List BSD filterable by statut, type_bsd, date range.
     */
    public function index(Request $request): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;

        $query = Bordereau::where('garage_id', $garageId)
            ->with(['garage', 'collecteur', 'creePar']);

        // Filter by statut (single or comma-separated list)
        if ($request->filled('statut')) {
            $statuts = explode(',', $request->input('statut'));
            $query->whereIn('statut', $statuts);
        }

        // Filter by type_bsd
        if ($request->filled('type_bsd')) {
            $query->where('type_bsd', $request->input('type_bsd'));
        }

        // Filter by date range
        if ($request->filled('date_debut')) {
            $query->where('date_emission', '>=', $request->input('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->where('date_emission', '<=', $request->input('date_fin'));
        }

        // Search by trackdechets_numero
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('trackdechets_numero', 'like', "%{$search}%")
                    ->orWhere('denomination_dechet', 'like', "%{$search}%")
                    ->orWhere('transporteur_raison_sociale', 'like', "%{$search}%")
                    ->orWhere('destination_raison_sociale', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $bordereaux = $query->paginate($request->input('per_page', 20));

        return response()->json($bordereaux);
    }

    /**
     * Create a BSD in DRAFT status.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type_bsd' => 'required|in:BSDD,BSDA,BSVHU',
            'type_dechet_id' => 'required|exists:types_dechets,id',
            'code_dechet' => 'required|string|max:20',
            'denomination_dechet' => 'required|string|max:255',
            'quantite' => 'nullable|numeric|min:0',
            'unite' => 'nullable|in:kg,litres',
            'consistance' => 'nullable|in:SOLIDE,LIQUIDE,GAZEUX,PATEUX',
            'conditionnement' => 'nullable|string|max:100',
            'nombre_colis' => 'nullable|integer|min:0',
            'transporteur_siret' => 'nullable|string|max:14',
            'transporteur_raison_sociale' => 'nullable|string|max:255',
            'transporteur_adresse' => 'nullable|string|max:500',
            'transporteur_recepisse' => 'nullable|string|max:50',
            'destination_siret' => 'nullable|string|max:14',
            'destination_raison_sociale' => 'nullable|string|max:255',
            'destination_adresse' => 'nullable|string|max:500',
            'cap' => 'nullable|string|max:50',
            'code_traitement' => 'nullable|string|max:10',
            'collecteur_id' => 'nullable|exists:collecteurs,id',
            'conteneur_ids' => 'nullable|array',
            'conteneur_ids.*' => 'exists:conteneurs,id',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $garageId = $request->user()->current_garage_id;

        $bordereau = DB::transaction(function () use ($request, $garageId) {
            $data = $request->except(['conteneur_ids']);
            $data['garage_id'] = $garageId;
            $data['statut'] = 'DRAFT';
            $data['cree_par'] = Auth::id();
            $data['date_emission'] = now();

            $bordereau = Bordereau::create($data);

            return $bordereau;
        });

        $bordereau->load(['garage', 'collecteur', 'creePar']);

        return response()->json($bordereau, 201);
    }

    /**
     * Show a single BSD with full details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;

        $bordereau = Bordereau::where('garage_id', $garageId)
            ->with([
                'garage.entreprise',
                'collecteur',
                'creePar',
            ])
            ->findOrFail($id);

        return response()->json($bordereau);
    }

    /**
     * Update a BSD - only allowed in DRAFT status.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $bordereau = Bordereau::where('garage_id', $garageId)->findOrFail($id);

        if ($bordereau->statut !== 'DRAFT') {
            return response()->json([
                'message' => 'Seuls les bordereaux en brouillon peuvent etre modifies.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'type_bsd' => 'sometimes|in:BSDD,BSDA,BSVHU',
            'type_dechet_id' => 'sometimes|exists:types_dechets,id',
            'code_dechet' => 'sometimes|string|max:20',
            'denomination_dechet' => 'sometimes|string|max:255',
            'quantite' => 'nullable|numeric|min:0',
            'unite' => 'nullable|in:kg,litres',
            'consistance' => 'nullable|in:SOLIDE,LIQUIDE,GAZEUX,PATEUX',
            'conditionnement' => 'nullable|string|max:100',
            'nombre_colis' => 'nullable|integer|min:0',
            'transporteur_siret' => 'nullable|string|max:14',
            'transporteur_raison_sociale' => 'nullable|string|max:255',
            'transporteur_adresse' => 'nullable|string|max:500',
            'transporteur_recepisse' => 'nullable|string|max:50',
            'destination_siret' => 'nullable|string|max:14',
            'destination_raison_sociale' => 'nullable|string|max:255',
            'destination_adresse' => 'nullable|string|max:500',
            'cap' => 'nullable|string|max:50',
            'code_traitement' => 'nullable|string|max:10',
            'collecteur_id' => 'nullable|exists:collecteurs,id',
            'conteneur_ids' => 'nullable|array',
            'conteneur_ids.*' => 'exists:conteneurs,id',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bordereau->update($request->except(['conteneur_ids', 'statut']));

        $bordereau->load(['garage', 'collecteur', 'creePar']);

        return response()->json($bordereau);
    }

    /**
     * Soft delete a BSD - only DRAFT status.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $bordereau = Bordereau::where('garage_id', $garageId)->findOrFail($id);

        if ($bordereau->statut !== 'DRAFT') {
            return response()->json([
                'message' => 'Seuls les bordereaux en brouillon peuvent etre supprimes.',
            ], 422);
        }

        $bordereau->delete();

        return response()->json(['message' => 'Bordereau supprime.']);
    }

    /**
     * Publish: DRAFT -> SEALED. Validates all required fields are present.
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $bordereau = Bordereau::where('garage_id', $garageId)->findOrFail($id);

        if ($bordereau->statut !== 'DRAFT') {
            return response()->json([
                'message' => 'Seuls les bordereaux en brouillon peuvent etre scelles.',
            ], 422);
        }

        // Validate all required fields for sealing
        $requiredFields = [
            'type_bsd', 'code_dechet', 'denomination_dechet',
            'transporteur_siret', 'transporteur_raison_sociale',
            'destination_siret', 'destination_raison_sociale',
            'code_traitement',
        ];

        $missing = [];
        foreach ($requiredFields as $field) {
            if (empty($bordereau->{$field})) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            return response()->json([
                'message' => 'Champs obligatoires manquants pour sceller le bordereau.',
                'champs_manquants' => $missing,
            ], 422);
        }

        DB::transaction(function () use ($bordereau) {
            $oldStatut = $bordereau->statut;
            $bordereau->update(['statut' => 'SEALED']);
            $this->recordStatutHistory($bordereau, $oldStatut, 'SEALED');
        });

        // Dispatch Trackdechets creation job if config exists
        CreateBsdTrackdechetsJob::dispatch($bordereau->id);

        $bordereau->load(['garage', 'collecteur', 'creePar']);

        return response()->json($bordereau);
    }

    /**
     * Sign: SEALED -> SIGNED_BY_PRODUCER.
     */
    public function sign(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $bordereau = Bordereau::where('garage_id', $garageId)->findOrFail($id);

        if ($bordereau->statut !== 'SEALED') {
            return response()->json([
                'message' => 'Seuls les bordereaux scelles peuvent etre signes.',
            ], 422);
        }

        DB::transaction(function () use ($bordereau) {
            $oldStatut = $bordereau->statut;
            $bordereau->update([
                'statut' => 'SIGNED_BY_PRODUCER',
                'date_signature_producteur' => now(),
            ]);
            $this->recordStatutHistory($bordereau, $oldStatut, 'SIGNED_BY_PRODUCER');
        });

        // Dispatch Trackdechets sign job
        if ($bordereau->trackdechets_id) {
            SignBsdTrackdechetsJob::dispatch($bordereau->id, 'EMISSION');
        }

        $bordereau->load(['garage', 'collecteur', 'creePar']);

        return response()->json($bordereau);
    }

    /**
     * Cancel a BSD -> CANCELED.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $bordereau = Bordereau::where('garage_id', $garageId)->findOrFail($id);

        $cancelable = ['DRAFT', 'SEALED', 'SIGNED_BY_PRODUCER', 'SENT'];
        if (! in_array($bordereau->statut, $cancelable)) {
            return response()->json([
                'message' => 'Ce bordereau ne peut pas etre annule dans son statut actuel.',
            ], 422);
        }

        DB::transaction(function () use ($bordereau) {
            $oldStatut = $bordereau->statut;
            $bordereau->update([
                'statut' => 'CANCELED',
            ]);
            $this->recordStatutHistory($bordereau, $oldStatut, 'CANCELED');
        });

        return response()->json($bordereau);
    }

    /**
     * Duplicate a BSD (used for REFUSED BSD rework).
     */
    public function dupliquer(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $bordereau = Bordereau::where('garage_id', $garageId)->findOrFail($id);

        $newBordereau = DB::transaction(function () use ($bordereau) {
            $data = $bordereau->replicate([
                'id',
                'trackdechets_id',
                'trackdechets_numero',
                'statut',
                'statut_sync',
                'date_emission',
                'date_signature_producteur',
                'date_enlevement',
                'date_reception',
                'date_traitement',
                'date_refus',
                'motif_refus',
                'pdf_cerfa_path',
                'derniere_sync',
                'erreur_sync',
                'created_at',
                'updated_at',
                'deleted_at',
            ])->toArray();

            $data['statut'] = 'DRAFT';
            $data['date_emission'] = now();
            $data['cree_par'] = Auth::id();

            $newBsd = Bordereau::create($data);

            return $newBsd;
        });

        $newBordereau->load(['garage', 'collecteur', 'creePar']);

        return response()->json($newBordereau, 201);
    }

    /**
     * Return allowed next statuses for the current BSD.
     */
    public function getStatutTransitions(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $bordereau = Bordereau::where('garage_id', $garageId)->findOrFail($id);

        $currentStatut = $bordereau->statut;
        $transitions = self::TRANSITIONS[$currentStatut] ?? [];

        return response()->json([
            'statut_actuel' => $currentStatut,
            'transitions_possibles' => $transitions,
        ]);
    }

    /**
     * Download the BSD PDF from Trackdechets.
     */
    public function pdf(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $bordereau = Bordereau::where('garage_id', $garageId)->findOrFail($id);

        if (! $bordereau->trackdechets_id) {
            return response()->json([
                'message' => 'Ce bordereau n\'a pas encore ete synchronise avec Trackdechets.',
            ], 422);
        }

        $service = TrackdechetsService::forGarage($bordereau->garage_id);

        if (! $service) {
            return response()->json([
                'message' => 'Aucune configuration Trackdechets active pour ce garage.',
            ], 422);
        }

        try {
            $downloadLink = $service->getBsdPdf($bordereau);

            if (! $downloadLink) {
                return response()->json([
                    'message' => 'Le PDF n\'est pas encore disponible.',
                ], 404);
            }

            return response()->json(['url' => $downloadLink]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la recuperation du PDF: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record a status change (log for now).
     */
    private function recordStatutHistory(Bordereau $bordereau, string $oldStatut, string $newStatut): void
    {
        \Illuminate\Support\Facades\Log::info('BSD statut change', [
            'bordereau_id' => $bordereau->id,
            'ancien_statut' => $oldStatut,
            'nouveau_statut' => $newStatut,
            'user_id' => Auth::id(),
        ]);
    }
}
