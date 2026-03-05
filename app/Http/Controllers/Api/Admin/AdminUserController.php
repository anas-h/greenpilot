<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $query = User::with(['entreprise', 'roles', 'garages']);

        if ($request->has('entreprise_id')) {
            $query->where('entreprise_id', $request->entreprise_id);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    public function update(Request $request, int $userId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $user = User::findOrFail($userId);

        $data = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'telephone' => 'sometimes|nullable|string|max:20',
            'password' => 'sometimes|nullable|string|min:6',
            'role' => 'sometimes|in:admin,chef_atelier,mecanicien,comptable',
            'actif' => 'sometimes|boolean',
            'garages_ids' => 'sometimes|array',
            'garages_ids.*' => 'integer|exists:garages,id',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $garagesIds = $data['garages_ids'] ?? null;
        unset($data['garages_ids']);

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        if ($garagesIds !== null) {
            $user->garages()->sync($garagesIds);
        }

        return response()->json(['data' => $user->load('garages', 'roles')]);
    }

    public function destroy(Request $request, int $userId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $user = User::findOrFail($userId);

        if ($user->isSuperAdmin()) {
            return response()->json(['message' => 'Impossible de supprimer un super administrateur.'], 422);
        }

        // Detach garages, revoke tokens, remove roles
        $user->garages()->detach();
        $user->tokens()->delete();
        $user->syncRoles([]);
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprime.']);
    }

    public function loginAs(Request $request, int $userId): JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $user = User::findOrFail($userId);

        // Don't allow impersonating other super admins
        if ($user->isSuperAdmin()) {
            return response()->json(['message' => 'Impossible d\'usurper un super administrateur.'], 422);
        }

        // Create a temporary token for impersonation
        $token = $user->createToken('impersonation', ['*'], now()->addHour())->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('entreprise'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'impersonating' => true,
        ]);
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        if (! $request->user()->isSuperAdmin()) {
            abort(403, 'Acces reserve aux super administrateurs.');
        }
    }
}
