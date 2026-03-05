<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\FiltersParGarage;
use App\Models\Conteneur;
use App\Services\ConteneurService;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConteneurController extends Controller
{
    use FiltersParGarage;

    public function __construct(
        private ConteneurService $conteneurService,
        private QrCodeService $qrCodeService,
    ) {}

    /**
     * List conteneurs for current garage with type_dechet relation and fill percentage.
     */
    public function index(Request $request): JsonResponse
    {
        $garageId = $this->validateGarageAccess($request);

        $query = Conteneur::where('garage_id', $garageId)
            ->with('typeDechet')
            ->where('actif', true);

        if ($request->has('type_dechet_id')) {
            $query->where('type_dechet_id', $request->type_dechet_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('code_qr', 'like', "%{$search}%")
                    ->orWhere('emplacement', 'like', "%{$search}%");
            });
        }

        $conteneurs = $query->orderBy('nom')->get();

        // Append pourcentage remplissage
        $conteneurs->each(function ($conteneur) {
            $conteneur->pourcentage_remplissage = $conteneur->getPourcentageRemplissage();
        });

        return response()->json(['data' => $conteneurs]);
    }

    /**
     * Create a conteneur with auto-generated QR code.
     */
    public function store(Request $request): JsonResponse
    {
        $garageId = $this->validateGarageAccess($request);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'type_dechet_id' => 'required|exists:types_dechets,id',
            'capacite' => 'required|numeric|min:0.01',
            'unite' => 'required|string|in:kg,litres,unites,tonnes',
            'emplacement' => 'nullable|string|max:255',
            'mixte' => 'boolean',
            'date_mise_en_service' => 'nullable|date',
        ]);

        $conteneur = Conteneur::create(array_merge($validated, [
            'garage_id' => $garageId,
            'niveau_actuel' => 0,
            'actif' => true,
            'code_qr' => '', // Temporary, will be set after creation
        ]));

        // Generate QR code: ECO-{garage_id}-{id}-{random6}
        $random = strtoupper(Str::random(6));
        $codeQr = "ECO-{$garageId}-{$conteneur->id}-{$random}";
        $conteneur->update(['code_qr' => $codeQr]);

        return response()->json(['data' => $conteneur->load('typeDechet')], 201);
    }

    /**
     * Show a single conteneur with historique.
     */
    public function show(Request $request, Conteneur $conteneur): JsonResponse
    {
        $this->authorizeConteneur($request, $conteneur);

        $conteneur->load(['typeDechet', 'historiques' => function ($q) {
            $q->with('user')->orderByDesc('date_evenement')->limit(20);
        }]);

        $conteneur->pourcentage_remplissage = $conteneur->getPourcentageRemplissage();

        return response()->json(['data' => $conteneur]);
    }

    /**
     * Update a conteneur.
     */
    public function update(Request $request, Conteneur $conteneur): JsonResponse
    {
        $this->authorizeConteneur($request, $conteneur);

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'type_dechet_id' => 'sometimes|required|exists:types_dechets,id',
            'capacite' => 'sometimes|required|numeric|min:0.01',
            'unite' => 'sometimes|required|string|in:kg,litres,unites,tonnes',
            'emplacement' => 'nullable|string|max:255',
            'mixte' => 'boolean',
            'date_mise_en_service' => 'nullable|date',
            'actif' => 'boolean',
        ]);

        $conteneur->update($validated);

        return response()->json(['data' => $conteneur->load('typeDechet')]);
    }

    /**
     * Soft delete a conteneur.
     */
    public function destroy(Request $request, Conteneur $conteneur): JsonResponse
    {
        $this->authorizeConteneur($request, $conteneur);

        $conteneur->update(['actif' => false]);
        $conteneur->delete();

        return response()->json(['message' => 'Conteneur supprime.']);
    }

    /**
     * Find conteneur by QR code, return with type info.
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'code_qr' => 'required|string',
        ]);

        $conteneur = Conteneur::with('typeDechet', 'garage')
            ->where('code_qr', $request->code_qr)
            ->where('actif', true)
            ->first();

        if (! $conteneur) {
            return response()->json(['message' => 'Conteneur non trouve.'], 404);
        }

        // Verify user has access to this garage
        $user = $request->user();
        if ($conteneur->garage->entreprise_id !== $user->entreprise_id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $conteneur->pourcentage_remplissage = $conteneur->getPourcentageRemplissage();

        return response()->json(['data' => $conteneur]);
    }

    /**
     * Paginated history for a conteneur.
     */
    public function historique(Request $request, Conteneur $conteneur): JsonResponse
    {
        $this->authorizeConteneur($request, $conteneur);

        $historique = $conteneur->historiques()
            ->with('user')
            ->orderByDesc('date_evenement')
            ->paginate($request->input('per_page', 20));

        return response()->json($historique);
    }

    /**
     * Generate QR code PNG image for download.
     */
    public function downloadQr(Request $request, Conteneur $conteneur): \Illuminate\Http\Response
    {
        $this->authorizeConteneur($request, $conteneur);

        $size = $request->input('size', 300);
        $svg = $this->qrCodeService->generateForConteneur($conteneur->code_qr, (int) $size);

        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', "attachment; filename=\"qr-{$conteneur->code_qr}.svg\"");
    }

    /**
     * Check if garage has minimum required containers per category.
     */
    public function conformite(Request $request): JsonResponse
    {
        $garageId = $this->validateGarageAccess($request);

        $result = $this->conteneurService->checkConformiteGarage($garageId);

        return response()->json(['data' => $result]);
    }

    /**
     * Authorize that the user has access to the conteneur's garage.
     */
    private function authorizeConteneur(Request $request, Conteneur $conteneur): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        $conteneur->loadMissing('garage');

        if ($conteneur->garage->entreprise_id !== $user->entreprise_id) {
            abort(403, 'Acces refuse.');
        }

        if (! $user->hasAccessToGarage($conteneur->garage_id)) {
            abort(403, 'Acces refuse a ce garage.');
        }
    }
}
