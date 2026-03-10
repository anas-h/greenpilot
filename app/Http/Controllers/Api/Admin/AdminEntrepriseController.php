<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CreateBsdTrackdechetsJob;
use App\Models\Bordereau;
use App\Models\ConfigTrackdechets;
use App\Models\Entreprise;
use App\Models\Garage;
use App\Models\User;
use App\Services\TrackdechetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEntrepriseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $query = Entreprise::withCount(['garages', 'users']);

        if ($request->has('plan')) {
            $query->where('plan', $request->plan);
        }
        if ($request->has('actif')) {
            $query->where('actif', filter_var($request->actif, FILTER_VALIDATE_BOOLEAN));
        }

        // Archive filter: default excludes archived, "true" shows only archived, "all" shows everything
        if ($request->get('archived') === 'true') {
            $query->whereNotNull('archived_at');
        } elseif ($request->get('archived') === 'all') {
            // no filter — show all
        } else {
            $query->whereNull('archived_at');
        }

        $entreprises = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($entreprises);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $entreprise = Entreprise::withCount(['garages', 'users'])->findOrFail($id);
        $entreprise->load(['garages', 'users.roles', 'users.garages']);

        return response()->json([
            'data' => $entreprise,
            'subscription' => [
                'on_trial' => $entreprise->isOnTrial(),
                'subscribed' => $entreprise->hasActiveSubscription(),
                'read_only' => $entreprise->isReadOnly(),
                'trial_days_remaining' => $entreprise->trialDaysRemaining(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'raison_sociale' => 'required|string|max:255',
            'siret' => 'required|string|max:20',
            'forme_juridique' => 'nullable|string|max:50',
            'adresse' => 'nullable|string|max:255',
            'code_postal' => 'nullable|string|max:10',
            'ville' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email_contact' => 'nullable|email|max:255',
            'plan' => 'sometimes|in:standard,premium',
            'max_garages' => 'sometimes|integer|min:1',
            'max_users' => 'sometimes|integer|min:1',
        ]);

        $entreprise = Entreprise::create(array_merge($data, [
            'actif' => true,
            'plan' => $data['plan'] ?? 'standard',
            'trial_ends_at' => now()->addDays(14),
            'max_garages' => $data['max_garages'] ?? 1,
            'max_users' => $data['max_users'] ?? 5,
        ]));

        return response()->json(['data' => $entreprise], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $entreprise = Entreprise::findOrFail($id);

        $data = $request->validate([
            'raison_sociale' => 'sometimes|string|max:255',
            'siret' => 'sometimes|string|max:20',
            'forme_juridique' => 'sometimes|nullable|string|max:50',
            'adresse' => 'sometimes|nullable|string|max:255',
            'code_postal' => 'sometimes|nullable|string|max:10',
            'ville' => 'sometimes|nullable|string|max:255',
            'telephone' => 'sometimes|nullable|string|max:20',
            'email_contact' => 'sometimes|nullable|email|max:255',
            'plan' => 'sometimes|in:standard,premium',
            'max_garages' => 'sometimes|integer|min:1',
            'max_users' => 'sometimes|integer|min:1',
            'actif' => 'sometimes|boolean',
            'trial_ends_at' => 'sometimes|nullable|date',
        ]);

        $entreprise->update($data);

        return response()->json(['data' => $entreprise]);
    }

    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $entreprise = Entreprise::findOrFail($id);
        $entreprise->update(['actif' => ! $entreprise->actif]);

        return response()->json([
            'data' => $entreprise,
            'message' => $entreprise->actif ? 'Entreprise activee.' : 'Entreprise desactivee.',
        ]);
    }

    public function toggleArchive(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $entreprise = Entreprise::findOrFail($id);
        $entreprise->update([
            'archived_at' => $entreprise->archived_at ? null : now(),
        ]);

        return response()->json([
            'data' => $entreprise,
            'message' => $entreprise->archived_at ? 'Entreprise archivee.' : 'Entreprise desarchivee.',
        ]);
    }

    public function storeGarage(Request $request, int $entrepriseId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $entreprise = Entreprise::findOrFail($entrepriseId);

        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'siret_etablissement' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            'code_postal' => 'nullable|string|max:10',
            'ville' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'surface_atelier' => 'nullable|numeric|min:0',
            'activites' => 'nullable|array',
        ]);

        $garage = Garage::create(array_merge($data, [
            'entreprise_id' => $entreprise->id,
            'actif' => true,
        ]));

        // Auto-determine ICPE regime
        if ($garage->surface_atelier) {
            if ($garage->surface_atelier >= 5000) {
                $garage->regime_icpe = 'enregistrement';
            } elseif ($garage->surface_atelier >= 2000) {
                $garage->regime_icpe = 'declaration';
            } else {
                $garage->regime_icpe = 'non_classe';
            }
            $garage->save();
        }

        return response()->json(['data' => $garage], 201);
    }

    public function storeUser(Request $request, int $entrepriseId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $entreprise = Entreprise::findOrFail($entrepriseId);

        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'telephone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,chef_atelier,mecanicien,comptable',
            'garages_ids' => 'nullable|array',
            'garages_ids.*' => 'integer|exists:garages,id',
        ]);

        $user = User::create([
            'entreprise_id' => $entreprise->id,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'password' => $data['password'],
            'telephone' => $data['telephone'] ?? null,
            'role' => $data['role'],
            'actif' => true,
            'preferences_notifications' => [
                'email_alertes_critiques' => true,
                'email_rappels' => true,
            ],
        ]);

        $user->assignRole($data['role']);

        if (! empty($data['garages_ids'])) {
            $user->garages()->sync($data['garages_ids']);
        }

        return response()->json(['data' => $user->load('garages', 'roles')], 201);
    }

    public function updateGarage(Request $request, int $garageId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $garage = Garage::findOrFail($garageId);

        $data = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'siret_etablissement' => 'sometimes|nullable|string|max:20',
            'adresse' => 'sometimes|nullable|string|max:255',
            'code_postal' => 'sometimes|nullable|string|max:10',
            'ville' => 'sometimes|nullable|string|max:255',
            'telephone' => 'sometimes|nullable|string|max:20',
            'surface_atelier' => 'sometimes|nullable|numeric|min:0',
            'activites' => 'sometimes|nullable|array',
            'actif' => 'sometimes|boolean',
        ]);

        $garage->update($data);

        return response()->json(['data' => $garage]);
    }

    public function destroyGarage(Request $request, int $garageId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $garage = Garage::findOrFail($garageId);

        // Detach users from pivot
        $garage->users()->detach();
        $garage->delete();

        return response()->json(['message' => 'Garage supprime.']);
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        // Count subscription statuses
        $allEntreprises = Entreprise::all();
        $enTrial = $allEntreprises->filter(fn ($e) => $e->isOnTrial())->count();
        $abonnes = $allEntreprises->filter(fn ($e) => $e->hasActiveSubscription())->count();
        $expires = $allEntreprises->filter(fn ($e) => $e->isReadOnly())->count();

        $stats = [
            'total_entreprises' => Entreprise::count(),
            'total_garages' => Garage::count(),
            'total_users' => User::count(),
            'par_plan' => [
                'standard' => Entreprise::where('plan', 'standard')->count(),
                'premium' => Entreprise::where('plan', 'premium')->count(),
            ],
            'en_trial' => $enTrial,
            'abonnes' => $abonnes,
            'expires' => $expires,
            'entreprises_actives' => Entreprise::where('actif', true)->count(),
            'entreprises_inactives' => Entreprise::where('actif', false)->count(),
            'entreprises_archivees' => Entreprise::whereNotNull('archived_at')->count(),
            'entreprises_recentes' => Entreprise::withCount(['garages', 'users'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'bordereaux_en_erreur' => Bordereau::where('statut_sync', 'erreur')->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    public function getTrackdechetsConfig(Request $request, int $entrepriseId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        Entreprise::findOrFail($entrepriseId);
        $config = ConfigTrackdechets::where('entreprise_id', $entrepriseId)->first();

        if (! $config) {
            return response()->json(['config' => null]);
        }

        $maskedToken = str_repeat('*', max(0, strlen($config->api_token) - 8))
            .substr($config->api_token, -8);

        return response()->json([
            'config' => [
                'id' => $config->id,
                'entreprise_id' => $config->entreprise_id,
                'api_token_masked' => $maskedToken,
                'environnement' => $config->environnement,
                'actif' => $config->actif,
                'derniere_sync_reussie' => $config->derniere_sync_reussie,
            ],
        ]);
    }

    public function saveTrackdechetsConfig(Request $request, int $entrepriseId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        Entreprise::findOrFail($entrepriseId);

        $data = $request->validate([
            'api_token' => 'required|string|min:10',
            'environnement' => 'required|in:sandbox,production',
            'actif' => 'sometimes|boolean',
        ]);

        $config = ConfigTrackdechets::updateOrCreate(
            ['entreprise_id' => $entrepriseId],
            $data
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

    public function testTrackdechetsConfig(Request $request, int $entrepriseId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $config = ConfigTrackdechets::where('entreprise_id', $entrepriseId)->first();

        if (! $config) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune configuration Trackdechets pour cette entreprise.',
            ], 404);
        }

        $service = new TrackdechetsService($config);

        try {
            $result = $service->testConnection();

            $config->update([
                'derniere_sync_reussie' => now(),
                'derniere_erreur' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connexion reussie.',
                'companies' => $result['me']['companies'] ?? [],
            ]);
        } catch (\Exception $e) {
            $config->update(['derniere_erreur' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Echec: '.$e->getMessage(),
            ], 422);
        }
    }

    public function bordereaux(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $query = Bordereau::with(['garage.entreprise', 'collecteur']);

        if ($request->has('statut_sync') && $request->statut_sync !== 'tous') {
            $query->where('statut_sync', $request->statut_sync);
        }

        if ($request->has('entreprise_id')) {
            $query->whereHas('garage', function ($q) use ($request) {
                $q->where('entreprise_id', $request->entreprise_id);
            });
        }

        $bordereaux = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($bordereaux);
    }

    public function retryBsd(Request $request, int $bordereauId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $bordereau = Bordereau::findOrFail($bordereauId);

        if ($bordereau->trackdechets_id) {
            return response()->json([
                'message' => 'Ce bordereau a deja un identifiant Trackdechets.',
            ], 422);
        }

        $bordereau->update([
            'statut_sync' => 'en_attente',
            'erreur_sync' => null,
        ]);

        CreateBsdTrackdechetsJob::dispatch($bordereau->id);

        return response()->json([
            'message' => 'Le job de synchronisation a ete relance.',
            'data' => $bordereau->fresh(),
        ]);
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        if (! $request->user()->isSuperAdmin()) {
            abort(403, 'Acces reserve aux super administrateurs.');
        }
    }
}
