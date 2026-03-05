<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only block write operations
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Super admins bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $entreprise = $user->entreprise;

        if ($entreprise && $entreprise->isReadOnly()) {
            return response()->json([
                'message' => 'Votre essai a expire. Souscrivez un abonnement pour continuer.',
                'read_only' => true,
            ], 403);
        }

        return $next($request);
    }
}
