<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;

trait FiltersParGarage
{
    protected function getGarageId(Request $request): ?int
    {
        return $request->header('X-Garage-Id') ? (int) $request->header('X-Garage-Id') : null;
    }

    protected function scopeByGarage($query, Request $request)
    {
        $garageId = $this->getGarageId($request);
        if ($garageId) {
            return $query->where('garage_id', $garageId);
        }
        // If no garage selected, scope to user's accessible garages
        $user = $request->user();
        if ($user && $user->isSuperAdmin()) {
            return $query; // Super admin sees everything
        }
        if ($user && ! in_array($user->role, ['admin'])) {
            $garageIds = $user->garages()->pluck('garages.id');

            return $query->whereIn('garage_id', $garageIds);
        }
        // Admin without garage filter: scope to enterprise garages
        if ($user) {
            $garageIds = \App\Models\Garage::where('entreprise_id', $user->entreprise_id)->pluck('id');

            return $query->whereIn('garage_id', $garageIds);
        }

        return $query->whereRaw('1 = 0'); // No access
    }

    protected function validateGarageAccess(Request $request): int
    {
        $garageId = $this->getGarageId($request);
        $user = $request->user();

        if (! $garageId) {
            abort(400, 'X-Garage-Id header is required');
        }

        if (! $user->hasAccessToGarage($garageId)) {
            abort(403, 'Access denied to this garage');
        }

        return $garageId;
    }
}
