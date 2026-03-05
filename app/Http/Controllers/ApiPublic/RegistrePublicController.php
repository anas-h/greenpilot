<?php

namespace App\Http\Controllers\ApiPublic;

use App\Http\Controllers\Controller;
use App\Models\Enlevement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrePublicController extends Controller
{
    /**
     * Export registre data (registre des dechets) for the authenticated garage.
     *
     * Returns a structured list of waste tracking entries based on completed enlevements.
     */
    public function index(Request $request): JsonResponse
    {
        $garage = $request->user()->currentGarage ?? $request->user()->garages()->first();
        if (! $garage) {
            return response()->json(['message' => 'Aucun garage associe a ce token.'], 403);
        }

        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'type_dechet_id' => 'nullable|exists:types_dechets,id',
            'format' => 'nullable|string|in:json,csv',
        ]);

        $query = Enlevement::where('garage_id', $garage->id)
            ->whereIn('statut', ['enleve', 'termine'])
            ->with('collecteur', 'typeDechet', 'bsd');

        if ($request->filled('date_debut')) {
            $query->where('date_enlevement', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->where('date_enlevement', '<=', $request->date_fin);
        }

        if ($request->filled('type_dechet_id')) {
            $query->where('type_dechet_id', $request->type_dechet_id);
        }

        $enlevements = $query->orderBy('date_enlevement')->get();

        $registre = $enlevements->map(function ($enlevement) {
            return [
                'date_enlevement' => $enlevement->date_enlevement,
                'type_dechet' => $enlevement->typeDechet ? [
                    'nom' => $enlevement->typeDechet->denomination,
                    'code' => $enlevement->typeDechet->code_europeen,
                    'code_dechet' => $enlevement->typeDechet->code_europeen,
                ] : null,
                'quantite' => $enlevement->quantite,
                'unite' => $enlevement->unite,
                'collecteur' => $enlevement->collecteur ? [
                    'nom' => $enlevement->collecteur->nom,
                    'siret' => $enlevement->collecteur->siret,
                    'numero_agrement' => $enlevement->collecteur->numero_agrement,
                ] : null,
                'bsd_numero' => $enlevement->bsd?->numero,
                'destination' => $enlevement->destination,
                'traitement' => $enlevement->traitement,
                'numero_notification' => $enlevement->numero_notification,
            ];
        });

        // CSV export
        if ($request->get('format') === 'csv') {
            $csv = $this->toCsv($registre);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="registre_dechets.csv"',
            ]);
        }

        return response()->json([
            'data' => $registre,
            'total' => $registre->count(),
            'garage' => [
                'id' => $garage->id,
                'nom' => $garage->nom,
            ],
        ]);
    }

    /**
     * Convert registre data to CSV format.
     */
    private function toCsv($registre): string
    {
        $headers = [
            'Date enlevement',
            'Type dechet',
            'Code dechet',
            'Quantite',
            'Unite',
            'Collecteur',
            'SIRET collecteur',
            'Numero agrement',
            'Numero BSD',
            'Destination',
            'Traitement',
        ];

        $lines = [implode(';', $headers)];

        foreach ($registre as $entry) {
            $lines[] = implode(';', [
                $entry['date_enlevement'] ?? '',
                $entry['type_dechet']['nom'] ?? '',
                $entry['type_dechet']['code_dechet'] ?? '',
                $entry['quantite'] ?? '',
                $entry['unite'] ?? '',
                $entry['collecteur']['nom'] ?? '',
                $entry['collecteur']['siret'] ?? '',
                $entry['collecteur']['numero_agrement'] ?? '',
                $entry['bsd_numero'] ?? '',
                $entry['destination'] ?? '',
                $entry['traitement'] ?? '',
            ]);
        }

        return "\xEF\xBB\xBF".implode("\r\n", $lines);
    }
}
