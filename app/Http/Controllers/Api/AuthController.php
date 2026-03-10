<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $plan = $request->plan;
            $limits = match ($plan) {
                'premium' => ['max_garages' => 999, 'max_users' => 999],
                default => ['max_garages' => 1, 'max_users' => 5],
            };

            $entreprise = Entreprise::create([
                'raison_sociale' => $request->raison_sociale,
                'siret' => $request->siret,
                'adresse' => $request->adresse ?? '',
                'code_postal' => $request->code_postal ?? '',
                'ville' => $request->ville ?? '',
                'plan' => $plan,
                'trial_ends_at' => now()->addDays(14),
                'max_garages' => $limits['max_garages'],
                'max_users' => $limits['max_users'],
            ]);

            // Create Stripe customer
            $entreprise->createAsStripeCustomer([
                'name' => $entreprise->raison_sociale,
                'metadata' => [
                    'entreprise_id' => $entreprise->id,
                    'siret' => $entreprise->siret,
                ],
            ]);

            $user = User::create([
                'entreprise_id' => $entreprise->id,
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'password' => $request->password,
                'role' => 'admin',
                'actif' => true,
                'preferences_notifications' => [
                    'email_alertes_critiques' => true,
                    'email_rappels' => true,
                ],
            ]);

            $user->assignRole('admin');

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user->load('entreprise'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'trial' => [
                    'on_trial' => true,
                    'days_remaining' => 14,
                    'trial_ends_at' => $entreprise->trial_ends_at,
                ],
            ], 201);
        });
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->where('actif', true)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('entreprise'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('entreprise');

        return response()->json([
            'user' => $user,
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,'.$user->id,
            'telephone' => 'sometimes|nullable|string|max:20',
            'preferences_notifications' => 'sometimes|array',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profil mis a jour.',
            'user' => $user->fresh()->load('entreprise'),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $user->update(['password' => $request->password]);

        return response()->json(['message' => 'Mot de passe modifie.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Deconnexion reussie.']);
    }
}
