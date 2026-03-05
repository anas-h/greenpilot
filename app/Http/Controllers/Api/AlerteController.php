<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlerteController extends Controller
{
    /**
     * List alertes filterable by type, priorite, lue, resolue.
     */
    public function index(Request $request): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;

        $query = Alerte::where('garage_id', $garageId)
            ->orderByRaw("CASE priorite WHEN 'critique' THEN 1 WHEN 'haute' THEN 2 WHEN 'moyenne' THEN 3 WHEN 'basse' THEN 4 ELSE 5 END")
            ->orderBy('created_at', 'desc');

        // Filter by type (single or comma-separated)
        if ($request->filled('type')) {
            $types = explode(',', $request->input('type'));
            $query->whereIn('type', $types);
        }

        // Filter by priorite (single or comma-separated)
        if ($request->filled('priorite')) {
            $priorites = explode(',', $request->input('priorite'));
            $query->whereIn('priorite', $priorites);
        }

        // Filter by lue
        if ($request->has('lue')) {
            $query->where('lue', filter_var($request->input('lue'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by resolue
        if ($request->has('resolue')) {
            $query->where('resolue', filter_var($request->input('resolue'), FILTER_VALIDATE_BOOLEAN));
        }

        // Search in titre and message
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $alertes = $query->paginate($request->input('per_page', 50));

        return response()->json($alertes);
    }

    /**
     * Count non-read non-resolved alertes.
     */
    public function count(Request $request): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;

        $total = Alerte::where('garage_id', $garageId)
            ->where('resolue', false)
            ->where('lue', false)
            ->count();

        $byCriticity = Alerte::where('garage_id', $garageId)
            ->where('resolue', false)
            ->where('lue', false)
            ->selectRaw('priorite, COUNT(*) as count')
            ->groupBy('priorite')
            ->pluck('count', 'priorite')
            ->toArray();

        return response()->json([
            'total' => $total,
            'critique' => $byCriticity['critique'] ?? 0,
            'haute' => $byCriticity['haute'] ?? 0,
            'moyenne' => $byCriticity['moyenne'] ?? 0,
            'basse' => $byCriticity['basse'] ?? 0,
        ]);
    }

    /**
     * Mark one or all alertes as read.
     */
    public function markRead(Request $request): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;

        if ($request->filled('id')) {
            // Mark a specific alert as read
            $alerte = Alerte::where('garage_id', $garageId)->findOrFail($request->input('id'));
            $alerte->update([
                'lue' => true,
                'date_lecture' => now(),
            ]);

            return response()->json([
                'message' => 'Alerte marquee comme lue.',
                'alerte' => $alerte,
            ]);
        }

        // Mark all unread alerts as read
        $count = Alerte::where('garage_id', $garageId)
            ->where('lue', false)
            ->update([
                'lue' => true,
                'date_lecture' => now(),
            ]);

        return response()->json([
            'message' => "{$count} alerte(s) marquee(s) comme lue(s).",
            'count' => $count,
        ]);
    }

    /**
     * Mark an alert as resolved.
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $garageId = $request->user()->current_garage_id;
        $alerte = Alerte::where('garage_id', $garageId)->findOrFail($id);

        if ($alerte->resolue) {
            return response()->json([
                'message' => 'Cette alerte est deja resolue.',
            ], 422);
        }

        $alerte->update([
            'resolue' => true,
            'date_resolution' => now(),
            'resolue_par' => Auth::id(),
            'lue' => true,
            'date_lecture' => $alerte->date_lecture ?? now(),
        ]);

        return response()->json([
            'message' => 'Alerte marquee comme resolue.',
            'alerte' => $alerte,
        ]);
    }
}
