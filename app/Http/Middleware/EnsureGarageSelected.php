<?php

namespace App\Http\Middleware;

use App\Models\Garage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGarageSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $garageId = $request->header('X-Garage-Id');

        if (! $garageId) {
            return response()->json([
                'message' => 'A garage must be selected. Please provide X-Garage-Id header.',
            ], 400);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Super admins can access any garage across all enterprises
        if ($user->isSuperAdmin()) {
            $garage = Garage::where('id', $garageId)->where('actif', true)->first();
        } else {
            $garage = Garage::where('id', $garageId)
                ->where('entreprise_id', $user->entreprise_id)
                ->where('actif', true)
                ->first();
        }

        if (! $garage) {
            return response()->json([
                'message' => 'Garage not found or inactive.',
            ], 404);
        }

        // Check user has access to this garage
        if (! $user->hasAccessToGarage($garageId)) {
            return response()->json([
                'message' => 'You do not have access to this garage.',
            ], 403);
        }

        // Store garage in request and set current_garage_id on user for later use
        $request->merge(['current_garage' => $garage]);
        $user->current_garage_id = (int) $garage->id;

        return $next($request);
    }
}
