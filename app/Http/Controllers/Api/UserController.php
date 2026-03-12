<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::where('entreprise_id', $request->user()->entreprise_id)
            ->with('roles')
            ->get();

        return response()->json(['data' => $users]);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $currentUser = $request->user();

        // Check plan limits
        if (! $currentUser->entreprise->canAddUser()) {
            return response()->json([
                'message' => 'Limite d\'utilisateurs atteinte pour votre plan. Passez a un plan superieur.',
            ], 422);
        }

        $user = User::create(array_merge($request->validated(), [
            'entreprise_id' => $currentUser->entreprise_id,
            'actif' => true,
            'preferences_notifications' => [
                'email_alertes_critiques' => true,
                'email_rappels' => true,
            ],
        ]));

        $user->assignRole($request->role);

        // Assign garages via pivot table
        if ($request->has('garages_ids')) {
            $user->garages()->sync($request->garages_ids);
        }

        return response()->json(['data' => $user->load('garages')], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeUser($request, $user);

        return response()->json(['data' => $user->load('roles')]);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $this->authorizeUser($request, $user);

        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        // Sync garages via pivot table
        if ($request->has('garages_ids')) {
            $user->garages()->sync($request->garages_ids);
        }

        return response()->json(['data' => $user->load('garages')]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeUser($request, $user);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprime.']);
    }

    private function authorizeUser(Request $request, User $user): void
    {
        if ($user->entreprise_id !== $request->user()->entreprise_id) {
            abort(403, 'Access denied.');
        }
    }
}
