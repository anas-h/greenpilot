<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\FiltersParGarage;
use App\Models\Garage;
use App\Services\RegistreService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class RegistreController extends Controller
{
    use FiltersParGarage;

    public function __construct(
        private RegistreService $registreService
    ) {}

    /**
     * Generate registre from enlevements/productions, filterable by period, type, dangereux, collecteur.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->buildFilters($request);

        $registre = $this->registreService->buildRegistre($filters);

        return response()->json(['data' => $registre]);
    }

    /**
     * Export registre in CSV, Excel, or PDF format.
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,excel,pdf',
        ]);

        $filters = $this->buildFilters($request);
        $registre = $this->registreService->buildRegistre($filters);
        $format = $request->input('format');

        $headings = [
            'Date',
            'Nature du dechet',
            'Code dechet',
            'Dangereux',
            'Quantite (tonnes)',
            'Collecteur',
            'SIRET collecteur',
            'N° autorisation',
            'Destination',
            'SIRET destination',
            'Adresse destination',
            'Code traitement',
            'N° BSD',
        ];

        $rows = array_map(function ($entry) {
            return [
                $entry['date'],
                $entry['nature_dechet'],
                $entry['code_dechet'],
                $entry['dangereux'] ? 'Oui' : 'Non',
                $entry['quantite_tonnes'],
                $entry['collecteur'],
                $entry['siret_collecteur'],
                $entry['numero_autorisation'],
                $entry['destination'] ?? '',
                $entry['siret_destination'] ?? '',
                $entry['adresse_destination'] ?? '',
                $entry['code_traitement'] ?? '',
                $entry['numero_bsd'] ?? '',
            ];
        }, $registre);

        if ($format === 'csv' || $format === 'excel') {
            $export = new class($headings, $rows) implements FromArray, WithHeadings
            {
                public function __construct(
                    private array $headings,
                    private array $rows
                ) {}

                public function array(): array
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return $this->headings;
                }
            };

            $extension = $format === 'csv' ? 'csv' : 'xlsx';
            $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

            return Excel::download($export, "registre-dechets.{$extension}", $writerType);
        }

        // PDF format
        $html = $this->buildRegistrePdfHtml($registre, $headings, $request);

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'sans-serif',
            ])
            ->download('registre-dechets.pdf');
    }

    /**
     * Build filter array from request.
     */
    private function buildFilters(Request $request): array
    {
        $filters = [];

        $garageId = $this->getGarageId($request);
        if ($garageId) {
            $filters['garage_id'] = $garageId;
        } else {
            $user = $request->user();
            if ($user && $user->role !== 'admin') {
                $filters['garage_ids'] = $user->garages_ids ?? [];
            } elseif ($user) {
                $filters['garage_ids'] = Garage::where('entreprise_id', $user->entreprise_id)->pluck('id')->toArray();
            }
        }

        if ($request->filled('date_debut')) {
            $filters['date_debut'] = $request->input('date_debut');
        }
        if ($request->filled('date_fin')) {
            $filters['date_fin'] = $request->input('date_fin');
        }
        if ($request->filled('type_dechet_id')) {
            $filters['type_dechet_id'] = $request->input('type_dechet_id');
        }
        if ($request->has('dangereux')) {
            $filters['dangereux'] = $request->boolean('dangereux');
        }
        if ($request->filled('collecteur_id')) {
            $filters['collecteur_id'] = $request->input('collecteur_id');
        }

        return $filters;
    }

    /**
     * Build PDF HTML for registre export.
     */
    private function buildRegistrePdfHtml(array $registre, array $headings, Request $request): string
    {
        $garageId = $this->getGarageId($request);
        $garage = $garageId ? Garage::with('entreprise')->find($garageId) : null;

        $dateDebut = $request->input('date_debut', '-');
        $dateFin = $request->input('date_fin', '-');
        $dateGeneration = now()->format('d/m/Y H:i');

        $garageInfo = '';
        if ($garage) {
            $garageInfo = "<p><strong>Etablissement :</strong> {$garage->nom} - {$garage->adresse}, {$garage->code_postal} {$garage->ville}</p>";
            if ($garage->entreprise) {
                $garageInfo = "<p><strong>Entreprise :</strong> {$garage->entreprise->raison_sociale}</p>".$garageInfo;
            }
        }

        $headingsHtml = '<tr>'.implode('', array_map(fn ($h) => "<th>{$h}</th>", $headings)).'</tr>';

        $rowsHtml = '';
        foreach ($registre as $entry) {
            $dangereux = $entry['dangereux'] ? '<span style="color:red;font-weight:bold">Oui</span>' : 'Non';
            $rowsHtml .= "<tr>
                <td>{$entry['date']}</td>
                <td>{$entry['nature_dechet']}</td>
                <td>{$entry['code_dechet']}</td>
                <td>{$dangereux}</td>
                <td style='text-align:right'>{$entry['quantite_tonnes']}</td>
                <td>{$entry['collecteur']}</td>
                <td>{$entry['siret_collecteur']}</td>
                <td>{$entry['numero_autorisation']}</td>
                <td>".($entry['destination'] ?? '').'</td>
                <td>'.($entry['siret_destination'] ?? '').'</td>
                <td>'.($entry['adresse_destination'] ?? '').'</td>
                <td>'.($entry['code_traitement'] ?? '').'</td>
                <td>'.($entry['numero_bsd'] ?? '').'</td>
            </tr>';
        }

        $count = count($registre);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: sans-serif; font-size: 9px; color: #333; margin: 15px; }
    h1 { font-size: 16px; color: #2E7D32; margin-bottom: 5px; }
    .subtitle { font-size: 11px; color: #666; margin-bottom: 15px; }
    .info p { margin: 2px 0; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background-color: #2E7D32; color: white; padding: 4px 5px; text-align: left; font-size: 8px; white-space: nowrap; }
    td { padding: 3px 5px; border-bottom: 1px solid #ddd; font-size: 8px; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .footer { margin-top: 20px; font-size: 8px; color: #888; text-align: center; border-top: 1px solid #ccc; padding-top: 5px; }
</style>
</head>
<body>
    <h1>Registre des Dechets</h1>
    <p class="subtitle">Conform&eacute;ment &agrave; l'arr&ecirc;t&eacute; du 31 mai 2021</p>

    <div class="info">
        {$garageInfo}
        <p><strong>Periode :</strong> du {$dateDebut} au {$dateFin}</p>
        <p><strong>Nombre d'enregistrements :</strong> {$count}</p>
    </div>

    <table>
        <thead>{$headingsHtml}</thead>
        <tbody>{$rowsHtml}</tbody>
    </table>

    <div class="footer">
        <p>Document genere le {$dateGeneration} par GreenPilot - Registre des dechets</p>
        <p>Registre tenu conformement aux articles R. 541-43 et suivants du code de l'environnement.</p>
    </div>
</body>
</html>
HTML;
    }
}
