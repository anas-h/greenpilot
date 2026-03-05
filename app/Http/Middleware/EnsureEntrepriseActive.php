<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEntrepriseActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Super admins bypass enterprise active check
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $entreprise = $user->entreprise;

        if (! $entreprise || ! $entreprise->actif) {
            return response()->json([
                'message' => 'Votre compte entreprise est desactive.',
            ], 403);
        }

        return $next($request);
    }
}
