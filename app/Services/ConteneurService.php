<?php

namespace App\Services;

use App\Models\Conteneur;
use App\Models\HistoriqueConteneur;
use Illuminate\Support\Facades\DB;

class ConteneurService
{
    public function adjustNiveau(Conteneur $conteneur, float $quantite, string $typeEvenement, ?int $userId = null, ?string $referenceType = null, ?int $referenceId = null): Conteneur
    {
        return DB::transaction(function () use ($conteneur, $quantite, $typeEvenement, $userId, $referenceType, $referenceId) {
            $conteneur->lockForUpdate();
            $conteneur->refresh();

            $niveauAvant = (float) $conteneur->niveau_actuel;
            $niveauApres = $niveauAvant + $quantite;

            // Clamp between 0 and capacity
            $niveauApres = max(0, min($niveauApres, (float) $conteneur->capacite));

            $conteneur->update(['niveau_actuel' => $niveauApres]);

            HistoriqueConteneur::create([
                'conteneur_id' => $conteneur->id,
                'type_evenement' => $typeEvenement,
                'quantite' => $quantite,
                'niveau_avant' => $niveauAvant,
                'niveau_apres' => $niveauApres,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => $userId ?? auth()->id(),
                'date_evenement' => now(),
            ]);

            return $conteneur->fresh();
        });
    }

    public function checkSeuils(Conteneur $conteneur): ?array
    {
        $pourcentage = $conteneur->getPourcentageRemplissage();

        if ($pourcentage >= 95) {
            return ['type' => 'conteneur_95', 'priorite' => 'haute', 'pourcentage' => $pourcentage];
        }
        if ($pourcentage >= 80) {
            return ['type' => 'conteneur_80', 'priorite' => 'moyenne', 'pourcentage' => $pourcentage];
        }

        return null;
    }

    public function checkConformiteGarage(int $garageId): array
    {
        $garage = \App\Models\Garage::with('typesDechets')->findOrFail($garageId);
        $activites = $garage->activites ?? [];
        $issues = [];

        $conteneurs = Conteneur::where('garage_id', $garageId)->where('actif', true)->get();

        // Minimum DD containers for mecanique
        if (in_array('mecanique', $activites)) {
            $requiredDD = ['fluide', 'filtre', 'absorbant', 'emballage', 'autre', 'batterie'];
            foreach ($requiredDD as $cat) {
                $count = $conteneurs->filter(fn ($c) => $c->typeDechet && $c->typeDechet->dangereux && $c->typeDechet->categorie === $cat)->count();
                if ($count < 1) {
                    $issues[] = "Conteneur manquant pour categorie DD: {$cat}";
                }
            }
        }

        // Additional for carrosserie
        if (in_array('carrosserie', $activites)) {
            $peinture = $conteneurs->filter(fn ($c) => $c->typeDechet && $c->typeDechet->categorie === 'peinture')->count();
            if ($peinture < 1) {
                $issues[] = 'Conteneur manquant pour peintures/diluants (carrosserie)';
            }
        }

        return [
            'conforme' => empty($issues),
            'issues' => $issues,
            'total_conteneurs' => $conteneurs->count(),
        ];
    }
}
