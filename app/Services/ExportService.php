<?php

namespace App\Services;

use App\Models\Enlevement;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportService
{
    /**
     * Generate bon d'enlevement PDF with garage info, collecteur info, lines, signature placeholders.
     */
    public function generateBonEnlevementPdf(Enlevement $enlevement)
    {
        $enlevement->loadMissing(['collecteur', 'lignes.typeDechet', 'lignes.conteneur', 'garage.entreprise']);

        $garage = $enlevement->garage;
        $entreprise = $garage->entreprise;
        $collecteur = $enlevement->collecteur;

        $html = $this->buildBonEnlevementHtml($enlevement, $garage, $entreprise, $collecteur);

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Build the HTML for the bon d'enlevement.
     */
    private function buildBonEnlevementHtml(Enlevement $enlevement, $garage, $entreprise, $collecteur): string
    {
        $lignesHtml = '';
        $totalCout = 0;
        $totalRevenu = 0;

        foreach ($enlevement->lignes as $ligne) {
            $type = $ligne->typeDechet;
            $conteneurNom = $ligne->conteneur ? $ligne->conteneur->nom : '-';
            $qteEffective = $ligne->quantite_effective ?? '-';
            $montant = $ligne->montant ? number_format($ligne->montant, 2, ',', ' ').' EUR' : '-';
            $dangereux = $type && $type->dangereux ? 'Oui' : 'Non';

            if ($ligne->est_rachat) {
                $totalRevenu += $ligne->montant ?? 0;
            } else {
                $totalCout += $ligne->montant ?? 0;
            }

            $lignesHtml .= "
                <tr>
                    <td>{$type->denomination}</td>
                    <td>{$type->code_europeen}</td>
                    <td>{$dangereux}</td>
                    <td>{$conteneurNom}</td>
                    <td style='text-align:right'>{$ligne->quantite_prevue} {$ligne->unite}</td>
                    <td style='text-align:right'>{$qteEffective} {$ligne->unite}</td>
                    <td style='text-align:right'>{$montant}</td>
                </tr>";
        }

        $totalCoutFormatted = number_format($totalCout, 2, ',', ' ');
        $totalRevenuFormatted = number_format($totalRevenu, 2, ',', ' ');
        $soldeNetFormatted = number_format($totalRevenu - $totalCout, 2, ',', ' ');

        $datePrevue = $enlevement->date_prevue ? $enlevement->date_prevue->format('d/m/Y') : '-';
        $dateEffective = $enlevement->date_effective ? $enlevement->date_effective->format('d/m/Y H:i') : '-';
        $dateGeneration = now()->format('d/m/Y H:i');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: sans-serif; font-size: 11px; color: #333; margin: 20px; }
    h1 { font-size: 18px; color: #2E7D32; margin-bottom: 5px; }
    h2 { font-size: 14px; color: #555; margin-top: 20px; margin-bottom: 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
    .info-block { margin-bottom: 15px; }
    .info-block p { margin: 2px 0; }
    .info-block strong { color: #2E7D32; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background-color: #2E7D32; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
    td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .totals { margin-top: 15px; text-align: right; }
    .totals p { margin: 3px 0; font-size: 12px; }
    .signatures { margin-top: 40px; display: flex; justify-content: space-between; }
    .signature-block { width: 45%; border-top: 1px solid #333; padding-top: 5px; text-align: center; }
    .footer { margin-top: 30px; font-size: 9px; color: #888; text-align: center; border-top: 1px solid #ccc; padding-top: 5px; }
    .two-columns { display: flex; gap: 40px; }
    .two-columns > div { flex: 1; }
</style>
</head>
<body>
    <h1>Bon d'Enlevement</h1>
    <p style="font-size: 13px; color: #666;">N&deg; {$enlevement->numero}</p>

    <div class="two-columns">
        <div class="info-block">
            <h2>Producteur (Garage)</h2>
            <p><strong>Entreprise :</strong> {$entreprise->raison_sociale}</p>
            <p><strong>Etablissement :</strong> {$garage->nom}</p>
            <p><strong>SIRET :</strong> {$garage->siret_etablissement}</p>
            <p><strong>Adresse :</strong> {$garage->adresse}</p>
            <p><strong>CP / Ville :</strong> {$garage->code_postal} {$garage->ville}</p>
            <p><strong>Tel :</strong> {$garage->telephone}</p>
        </div>

        <div class="info-block">
            <h2>Collecteur / Transporteur</h2>
            <p><strong>Raison sociale :</strong> {$collecteur->raison_sociale}</p>
            <p><strong>SIRET :</strong> {$collecteur->siret}</p>
            <p><strong>Adresse :</strong> {$collecteur->adresse}</p>
            <p><strong>CP / Ville :</strong> {$collecteur->code_postal} {$collecteur->ville}</p>
            <p><strong>N&deg; autorisation :</strong> {$collecteur->numero_autorisation}</p>
            <p><strong>Contact :</strong> {$collecteur->contact_nom} - {$collecteur->contact_telephone}</p>
        </div>
    </div>

    <div class="info-block">
        <h2>Informations enlevement</h2>
        <p><strong>Date prevue :</strong> {$datePrevue}</p>
        <p><strong>Date effective :</strong> {$dateEffective}</p>
        <p><strong>N&deg; bon :</strong> {$enlevement->numero_bon_enlevement}</p>
    </div>

    <h2>Detail des dechets</h2>
    <table>
        <thead>
            <tr>
                <th>Type de dechet</th>
                <th>Code europeen</th>
                <th>Dangereux</th>
                <th>Conteneur</th>
                <th style="text-align:right">Qte prevue</th>
                <th style="text-align:right">Qte effective</th>
                <th style="text-align:right">Montant</th>
            </tr>
        </thead>
        <tbody>
            {$lignesHtml}
        </tbody>
    </table>

    <div class="totals">
        <p><strong>Cout total :</strong> {$totalCoutFormatted} EUR</p>
        <p><strong>Revenu total (rachats) :</strong> {$totalRevenuFormatted} EUR</p>
        <p style="font-size: 13px;"><strong>Solde net :</strong> {$soldeNetFormatted} EUR</p>
    </div>

    <div class="signatures">
        <div class="signature-block">
            <p>Signature du producteur</p>
            <br><br><br>
            <p>Date : ___/___/______</p>
        </div>
        <div class="signature-block">
            <p>Signature du collecteur</p>
            <br><br><br>
            <p>Date : ___/___/______</p>
        </div>
    </div>

    <div class="footer">
        <p>Document genere le {$dateGeneration} par GreenPilot - Gestion des dechets automobile</p>
        <p>Ce document ne se substitue pas au Bordereau de Suivi des Dechets (BSD) obligatoire pour les dechets dangereux.</p>
    </div>
</body>
</html>
HTML;
    }
}
