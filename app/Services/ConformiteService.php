<?php

namespace App\Services;

use App\Models\Bordereau;
use App\Models\Collecteur;
use App\Models\ConfigTrackdechets;
use App\Models\Conteneur;
use App\Models\FicheSecurite;
use App\Models\Garage;
use App\Models\LigneEnlevement;
use App\Models\Production;
use App\Models\TypeDechet;

class ConformiteService
{
    /**
     * Calculate the overall conformity score for a garage.
     *
     * Returns a weighted score (0-100) across 6 criteria:
     * - Conteneurs conformes (20%)
     * - BSD a jour (25%)
     * - Registre complet (20%)
     * - Autorisations valides (15%)
     * - FDS completes (10%)
     * - Trackdechets actif (10%)
     */
    public function calculateScore(int $garageId): array
    {
        $scores = [];

        // 1. Conteneurs conformes (20%)
        $scores['conteneurs'] = $this->scoreConteneurs($garageId);

        // 2. BSD a jour (25%)
        $scores['bsd'] = $this->scoreBsd($garageId);

        // 3. Registre complet (20%)
        $scores['registre'] = $this->scoreRegistre($garageId);

        // 4. Autorisations valides (15%)
        $scores['autorisations'] = $this->scoreAutorisations($garageId);

        // 5. FDS completes (10%)
        $scores['fds'] = $this->scoreFds($garageId);

        // 6. Trackdechets actif (10%)
        $scores['trackdechets'] = $this->scoreTrackdechets($garageId);

        // Calculate weighted total
        $total = 0;
        foreach ($scores as $s) {
            $total += ($s['poids'] / 100) * $s['score'];
        }

        return [
            'score_global' => round($total),
            'details' => $scores,
        ];
    }

    /**
     * Conteneurs conformes (20% weight).
     * Checks fill level and pickup freshness.
     */
    private function scoreConteneurs(int $garageId): array
    {
        $conteneurs = Conteneur::where('garage_id', $garageId)
            ->where('actif', true)
            ->get();

        if ($conteneurs->isEmpty()) {
            return [
                'poids' => 20,
                'score' => 100,
                'details' => ['message' => 'Aucun conteneur actif'],
            ];
        }

        $totalConteneurs = $conteneurs->count();
        $conformes = 0;
        $nonConformes = [];

        foreach ($conteneurs as $conteneur) {
            $isConforme = true;
            $issues = [];

            // Check fill level (> 95% is non-conforme for safety)
            if ($conteneur->capacite && $conteneur->capacite > 0) {
                $fillPercent = ($conteneur->niveau_actuel / $conteneur->capacite) * 100;
                if ($fillPercent > 95) {
                    $isConforme = false;
                    $issues[] = 'surcharge';
                }
            }

            // Check last pickup date (more than 6 months ago)
            if ($conteneur->date_dernier_enlevement) {
                $moisDepuis = \Carbon\Carbon::parse($conteneur->date_dernier_enlevement)->diffInMonths(now());
                if ($moisDepuis > 6) {
                    $isConforme = false;
                    $issues[] = 'enlevement perme';
                }
            }

            if ($isConforme) {
                $conformes++;
            } else {
                $nonConformes[] = [
                    'conteneur' => $conteneur->nom,
                    'issues' => $issues,
                ];
            }
        }

        $score = $totalConteneurs > 0 ? round(($conformes / $totalConteneurs) * 100) : 100;

        return [
            'poids' => 20,
            'score' => $score,
            'details' => [
                'total' => $totalConteneurs,
                'conformes' => $conformes,
                'non_conformes' => $nonConformes,
            ],
        ];
    }

    /**
     * BSD a jour (25% weight).
     * Checks for REFUSED BSD and SENT > 15 days.
     */
    private function scoreBsd(int $garageId): array
    {
        $bsdAnomalie = Bordereau::where('garage_id', $garageId)
            ->where(function ($q) {
                $q->where('statut', 'REFUSED')
                    ->orWhere(function ($q2) {
                        $q2->where('statut', 'SENT')
                            ->where('date_enlevement', '<', now()->subDays(15));
                    });
            })->count();

        $totalActiveBsd = Bordereau::where('garage_id', $garageId)
            ->whereNotIn('statut', ['CANCELED', 'DRAFT'])
            ->count();

        $score = $bsdAnomalie === 0 ? 100 : max(0, 100 - ($bsdAnomalie * 25));

        return [
            'poids' => 25,
            'score' => $score,
            'details' => [
                'anomalies' => $bsdAnomalie,
                'total_actifs' => $totalActiveBsd,
            ],
        ];
    }

    /**
     * Registre complet (20% weight).
     * Checks that all dangerous waste types produced have at least one BSD.
     */
    private function scoreRegistre(int $garageId): array
    {
        // Dangerous waste types produced more than 7 days ago
        $typesDDProduits = Production::where('garage_id', $garageId)
            ->whereHas('typeDechet', fn ($q) => $q->where('dangereux', true))
            ->where('date_production', '<', now()->subDays(7))
            ->distinct()
            ->pluck('type_dechet_id');

        $totalDd = $typesDDProduits->count();

        if ($totalDd === 0) {
            return [
                'poids' => 20,
                'score' => 100,
                'details' => [
                    'dd_sans_bsd' => 0,
                    'total_dd' => 0,
                ],
            ];
        }

        // Waste types that have at least one BSD via lignes_enlevement
        $typesAvecBsd = LigneEnlevement::join('enlevements', 'lignes_enlevement.enlevement_id', '=', 'enlevements.id')
            ->where('enlevements.garage_id', $garageId)
            ->whereNotNull('lignes_enlevement.bordereau_id')
            ->whereIn('lignes_enlevement.type_dechet_id', $typesDDProduits)
            ->distinct()
            ->pluck('lignes_enlevement.type_dechet_id');

        $ddSansBsd = $typesDDProduits->diff($typesAvecBsd)->count();

        $score = $ddSansBsd === 0 ? 100 : max(0, 100 - ($ddSansBsd * 10));

        return [
            'poids' => 20,
            'score' => $score,
            'details' => [
                'dd_sans_bsd' => $ddSansBsd,
                'total_dd' => $totalDd,
            ],
        ];
    }

    /**
     * Autorisations valides (15% weight).
     * Checks collecteur authorization expiry.
     */
    private function scoreAutorisations(int $garageId): array
    {
        $collecteursActifs = Collecteur::where('garage_id', $garageId)
            ->where('actif', true)
            ->count();

        $collecteursExpires = Collecteur::where('garage_id', $garageId)
            ->where('actif', true)
            ->where('date_validite_autorisation', '<', now())
            ->count();

        $collecteursBientotExpires = Collecteur::where('garage_id', $garageId)
            ->where('actif', true)
            ->where('date_validite_autorisation', '>=', now())
            ->where('date_validite_autorisation', '<', now()->addDays(30))
            ->count();

        $score = $collecteursExpires === 0 ? 100 : max(0, 100 - ($collecteursExpires * 33));

        return [
            'poids' => 15,
            'score' => $score,
            'details' => [
                'total_actifs' => $collecteursActifs,
                'expires' => $collecteursExpires,
                'bientot_expires' => $collecteursBientotExpires,
            ],
        ];
    }

    /**
     * FDS completes (10% weight).
     * Checks if all dangerous waste types have an active FDS.
     */
    private function scoreFds(int $garageId): array
    {
        $typesDD = TypeDechet::whereHas('garages', function ($q) use ($garageId) {
            $q->where('garages.id', $garageId)->where('garage_type_dechet.actif', true);
        })->where('dangereux', true)->pluck('id');

        if ($typesDD->isEmpty()) {
            return [
                'poids' => 10,
                'score' => 100,
                'details' => [
                    'message' => 'Aucun dechet dangereux actif',
                    'requis' => 0,
                    'presentes' => 0,
                    'manquantes' => 0,
                ],
            ];
        }

        $fdsPresentes = FicheSecurite::where('garage_id', $garageId)
            ->where('actif', true)
            ->whereIn('type_dechet_id', $typesDD)
            ->distinct('type_dechet_id')
            ->count('type_dechet_id');

        $fdsExpirees = FicheSecurite::where('garage_id', $garageId)
            ->where('actif', true)
            ->whereIn('type_dechet_id', $typesDD)
            ->whereNotNull('date_validite')
            ->where('date_validite', '<', now())
            ->count();

        $fdsScore = $typesDD->count() > 0
            ? (($fdsPresentes - $fdsExpirees) / $typesDD->count()) * 100
            : 100;

        return [
            'poids' => 10,
            'score' => round(max(0, $fdsScore)),
            'details' => [
                'requis' => $typesDD->count(),
                'presentes' => $fdsPresentes,
                'expirees' => $fdsExpirees,
                'manquantes' => $typesDD->count() - $fdsPresentes,
            ],
        ];
    }

    /**
     * Trackdechets actif (10% weight).
     * Checks if Trackdechets integration is configured and active.
     */
    private function scoreTrackdechets(int $garageId): array
    {
        $entrepriseId = Garage::where('id', $garageId)->value('entreprise_id');
        $config = $entrepriseId
            ? ConfigTrackdechets::where('entreprise_id', $entrepriseId)->where('actif', true)->first()
            : null;

        $isActive = $config !== null;
        $isVerified = $config?->siret_verifie ?? false;

        $score = 0;
        if ($isActive && $isVerified) {
            $score = 100;
        } elseif ($isActive) {
            $score = 50; // Active but not verified
        }

        return [
            'poids' => 10,
            'score' => $score,
            'details' => [
                'configure' => $isActive,
                'verifie' => $isVerified,
                'derniere_synchro' => $config?->derniere_synchro,
            ],
        ];
    }
}
