<?php

namespace App\Services;

use App\Models\Alerte;
use App\Models\Bordereau;
use App\Models\Collecteur;
use App\Models\ConfigTrackdechets;
use App\Models\Conteneur;
use App\Models\FicheSecurite;
use App\Models\Garage;
use App\Models\Production;
use App\Models\TypeDechet;
use App\Models\User;
use App\Notifications\AlerteCritiqueNotification;
use Carbon\Carbon;

class AlerteService
{
    /**
     * Run all alert checks for a given garage.
     */
    public function checkAllForGarage(int $garageId): array
    {
        $created = [];
        $created = array_merge($created, $this->checkConteneurs($garageId));
        $created = array_merge($created, $this->checkStockage($garageId));
        $created = array_merge($created, $this->checkAutorisations($garageId));
        $created = array_merge($created, $this->checkBsd($garageId));
        $created = array_merge($created, $this->checkFds($garageId));
        $created = array_merge($created, $this->checkConformiteConteneurs($garageId));
        $created = array_merge($created, $this->checkTrackdechets($garageId));
        $created = array_merge($created, $this->checkProductionsNonValidees($garageId));
        $created = array_merge($created, $this->checkEnlevementsProches($garageId));
        $created = array_merge($created, $this->checkDdSansBsd($garageId));

        return $created;
    }

    /**
     * Check conteneur fill levels: 80% and 95% thresholds.
     */
    public function checkConteneurs(int $garageId): array
    {
        $created = [];

        $conteneurs = Conteneur::where('garage_id', $garageId)
            ->where('actif', true)
            ->whereNotNull('capacite')
            ->where('capacite', '>', 0)
            ->get();

        foreach ($conteneurs as $conteneur) {
            $fillPercent = ($conteneur->niveau_actuel / $conteneur->capacite) * 100;

            // 95% threshold - critique
            if ($fillPercent >= 95) {
                $alerte = $this->createAlerteIfNotExists(
                    $garageId,
                    'conteneur_95',
                    'critique',
                    'Conteneur quasi plein - '.$conteneur->nom,
                    sprintf(
                        'Le conteneur "%s" (type: %s) est rempli a %.0f%%. Capacite: %.2f / %.2f %s. Un enlevement est necessaire en urgence.',
                        $conteneur->nom,
                        $conteneur->typeDechet->denomination ?? 'inconnu',
                        $fillPercent,
                        $conteneur->niveau_actuel,
                        $conteneur->capacite,
                        $conteneur->unite ?? ''
                    ),
                    'conteneur',
                    $conteneur->id
                );
                if ($alerte) {
                    $created[] = $alerte;
                }
            }
            // 80% threshold - haute
            elseif ($fillPercent >= 80) {
                $alerte = $this->createAlerteIfNotExists(
                    $garageId,
                    'conteneur_80',
                    'haute',
                    'Conteneur bientot plein - '.$conteneur->nom,
                    sprintf(
                        'Le conteneur "%s" (type: %s) est rempli a %.0f%%. Capacite: %.2f / %.2f %s. Planifiez un enlevement.',
                        $conteneur->nom,
                        $conteneur->typeDechet->denomination ?? 'inconnu',
                        $fillPercent,
                        $conteneur->niveau_actuel,
                        $conteneur->capacite,
                        $conteneur->unite ?? ''
                    ),
                    'conteneur',
                    $conteneur->id
                );
                if ($alerte) {
                    $created[] = $alerte;
                }
            }
        }

        return $created;
    }

    /**
     * Check storage duration: alert at 10 months and 11 months (12 month legal limit for DD).
     */
    public function checkStockage(int $garageId): array
    {
        $created = [];

        // Conteneurs with dangerous waste that have been active for too long
        $conteneurs = Conteneur::where('garage_id', $garageId)
            ->where('actif', true)
            ->where('niveau_actuel', '>', 0)
            ->whereNotNull('date_mise_en_service')
            ->whereHas('typeDechet', fn ($q) => $q->where('dangereux', true))
            ->get();

        foreach ($conteneurs as $conteneur) {
            $moisStockage = Carbon::parse($conteneur->date_mise_en_service)->diffInMonths(now());

            // 11 months - critique (approaching legal limit)
            if ($moisStockage >= 11) {
                $alerte = $this->createAlerteIfNotExists(
                    $garageId,
                    'stockage_11mois',
                    'critique',
                    'Limite de stockage imminente - '.$conteneur->nom,
                    sprintf(
                        'Le conteneur "%s" contient des dechets dangereux stockes depuis %d mois. La limite legale de 12 mois est presque atteinte. Enlevement immediat requis.',
                        $conteneur->nom,
                        $moisStockage
                    ),
                    'conteneur',
                    $conteneur->id
                );
                if ($alerte) {
                    $created[] = $alerte;
                }
            }
            // 10 months - haute
            elseif ($moisStockage >= 10) {
                $alerte = $this->createAlerteIfNotExists(
                    $garageId,
                    'stockage_10mois',
                    'haute',
                    'Stockage prolonge - '.$conteneur->nom,
                    sprintf(
                        'Le conteneur "%s" contient des dechets dangereux stockes depuis %d mois. Planifiez un enlevement avant la limite de 12 mois.',
                        $conteneur->nom,
                        $moisStockage
                    ),
                    'conteneur',
                    $conteneur->id
                );
                if ($alerte) {
                    $created[] = $alerte;
                }
            }
        }

        return $created;
    }

    /**
     * Check collecteur authorization expiry: 30 days before and expired.
     */
    public function checkAutorisations(int $garageId): array
    {
        $created = [];

        $collecteurs = Collecteur::where('garage_id', $garageId)
            ->where('actif', true)
            ->whereNotNull('date_validite_autorisation')
            ->get();

        foreach ($collecteurs as $collecteur) {
            $dateExpiration = Carbon::parse($collecteur->date_validite_autorisation);

            // Already expired - critique
            if ($dateExpiration->isPast()) {
                $alerte = $this->createAlerteIfNotExists(
                    $garageId,
                    'autorisation_expiree',
                    'critique',
                    'Autorisation expiree - '.$collecteur->raison_sociale,
                    sprintf(
                        'L\'autorisation du collecteur "%s" (SIRET: %s) a expire le %s. Il ne doit plus effectuer de collecte tant que son autorisation n\'est pas renouvelee.',
                        $collecteur->raison_sociale,
                        $collecteur->siret ?? '',
                        $dateExpiration->format('d/m/Y')
                    ),
                    'collecteur',
                    $collecteur->id
                );
                if ($alerte) {
                    $created[] = $alerte;
                }
            }
            // Expires within 30 days - haute
            elseif ($dateExpiration->diffInDays(now()) <= 30) {
                $alerte = $this->createAlerteIfNotExists(
                    $garageId,
                    'autorisation_30j',
                    'haute',
                    'Autorisation bientot expiree - '.$collecteur->raison_sociale,
                    sprintf(
                        'L\'autorisation du collecteur "%s" (SIRET: %s) expire le %s (dans %d jours). Contactez le collecteur pour renouvellement.',
                        $collecteur->raison_sociale,
                        $collecteur->siret ?? '',
                        $dateExpiration->format('d/m/Y'),
                        $dateExpiration->diffInDays(now())
                    ),
                    'collecteur',
                    $collecteur->id
                );
                if ($alerte) {
                    $created[] = $alerte;
                }
            }
        }

        return $created;
    }

    /**
     * Check BSD issues: SENT for more than 15 days and REFUSED.
     */
    public function checkBsd(int $garageId): array
    {
        $created = [];

        // BSD SENT for more than 15 days without reception
        $bsdSent = Bordereau::where('garage_id', $garageId)
            ->where('statut', 'SENT')
            ->where('date_enlevement', '<', now()->subDays(15))
            ->get();

        foreach ($bsdSent as $bsd) {
            $jours = Carbon::parse($bsd->date_enlevement)->diffInDays(now());
            $alerte = $this->createAlerteIfNotExists(
                $garageId,
                'bsd_sent_15j',
                'haute',
                'BSD en transit depuis '.$jours.' jours',
                sprintf(
                    'Le bordereau %s (%s) est en statut "Envoye" depuis %d jours sans confirmation de reception. Contactez le transporteur ou le destinataire.',
                    $bsd->trackdechets_numero ?? '#'.$bsd->id,
                    $bsd->denomination_dechet,
                    $jours
                ),
                'bordereau',
                $bsd->id
            );
            if ($alerte) {
                $created[] = $alerte;
            }
        }

        // BSD REFUSED (critical)
        $bsdRefused = Bordereau::where('garage_id', $garageId)
            ->where('statut', 'REFUSED')
            ->get();

        foreach ($bsdRefused as $bsd) {
            $alerte = $this->createAlerteIfNotExists(
                $garageId,
                'bsd_refused',
                'critique',
                'BSD refuse - '.($bsd->trackdechets_numero ?? '#'.$bsd->id),
                sprintf(
                    'Le bordereau %s (%s) a ete refuse par le destinataire "%s". %s Action requise: dupliquer le BSD et organiser un nouvel enlevement.',
                    $bsd->trackdechets_numero ?? '#'.$bsd->id,
                    $bsd->denomination_dechet,
                    $bsd->destination_raison_sociale ?? 'inconnu',
                    $bsd->motif_refus ? 'Motif: '.$bsd->motif_refus.'.' : ''
                ),
                'bordereau',
                $bsd->id
            );
            if ($alerte) {
                $created[] = $alerte;
            }
        }

        return $created;
    }

    /**
     * Check FDS: missing for dangerous waste types, and expired FDS.
     */
    public function checkFds(int $garageId): array
    {
        $created = [];

        // Get all active dangerous waste types for this garage
        $typesDechetsDD = TypeDechet::whereHas('garages', function ($q) use ($garageId) {
            $q->where('garages.id', $garageId)->where('garage_type_dechet.actif', true);
        })->where('dangereux', true)->get();

        foreach ($typesDechetsDD as $typeDechet) {
            // Check if FDS exists and is active
            $fds = FicheSecurite::where('garage_id', $garageId)
                ->where('type_dechet_id', $typeDechet->id)
                ->where('actif', true)
                ->first();

            if (! $fds) {
                // FDS missing
                $alerte = $this->createAlerteIfNotExists(
                    $garageId,
                    'fds_manquante',
                    'haute',
                    'FDS manquante - '.$typeDechet->denomination,
                    sprintf(
                        'Aucune fiche de donnees de securite (FDS) n\'est enregistree pour le dechet dangereux "%s" (code: %s). La FDS est obligatoire pour les dechets dangereux.',
                        $typeDechet->denomination,
                        $typeDechet->code_europeen ?? ''
                    ),
                    'type_dechet',
                    $typeDechet->id
                );
                if ($alerte) {
                    $created[] = $alerte;
                }
            } elseif ($fds->date_validite && Carbon::parse($fds->date_validite)->isPast()) {
                // FDS expired
                $alerte = $this->createAlerteIfNotExists(
                    $garageId,
                    'fds_expiree',
                    'moyenne',
                    'FDS expiree - '.$typeDechet->denomination,
                    sprintf(
                        'La fiche de donnees de securite pour "%s" (%s) a expire le %s. Mettez-la a jour aupres du fournisseur.',
                        $typeDechet->denomination,
                        $fds->nom_produit ?? '',
                        Carbon::parse($fds->date_validite)->format('d/m/Y')
                    ),
                    'fiche_securite',
                    $fds->id
                );
                if ($alerte) {
                    $created[] = $alerte;
                }
            }
        }

        return $created;
    }

    /**
     * Check conteneur conformity (labeling, condition, etc.).
     */
    public function checkConformiteConteneurs(int $garageId): array
    {
        $created = [];

        $conteneurs = Conteneur::where('garage_id', $garageId)
            ->where('actif', true)
            ->get();

        foreach ($conteneurs as $conteneur) {
            $nonConforme = false;
            $details = [];

            // Check date dernier enlevement (> 6 mois = potentiel stockage trop long)
            if ($conteneur->date_dernier_enlevement) {
                $moisDepuisEnlevement = Carbon::parse($conteneur->date_dernier_enlevement)->diffInMonths(now());
                if ($moisDepuisEnlevement > 6) {
                    $nonConforme = true;
                    $details[] = 'dernier enlevement il y a '.$moisDepuisEnlevement.' mois';
                }
            }

            if ($nonConforme) {
                $alerte = $this->createAlerteIfNotExists(
                    $garageId,
                    'conformite_conteneurs',
                    'moyenne',
                    'Non-conformite conteneur - '.$conteneur->nom,
                    sprintf(
                        'Le conteneur "%s" (type: %s) presente des non-conformites: %s.',
                        $conteneur->nom,
                        $conteneur->typeDechet->denomination ?? 'inconnu',
                        implode(', ', $details)
                    ),
                    'conteneur',
                    $conteneur->id
                );
                if ($alerte) {
                    $created[] = $alerte;
                }
            }
        }

        return $created;
    }

    /**
     * Check if Trackdechets integration is inactive for garage.
     */
    public function checkTrackdechets(int $garageId): array
    {
        $created = [];

        $entrepriseId = Garage::where('id', $garageId)->value('entreprise_id');
        $configExists = $entrepriseId && ConfigTrackdechets::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->exists();

        if (! $configExists) {
            $alerte = $this->createAlerteIfNotExists(
                $garageId,
                'trackdechets_off',
                'moyenne',
                'Trackdechets non configure',
                'L\'integration Trackdechets n\'est pas configuree ou inactive pour ce garage. Les BSD ne seront pas synchronises avec la plateforme nationale.',
                'garage',
                $garageId
            );
            if ($alerte) {
                $created[] = $alerte;
            }
        }

        return $created;
    }

    /**
     * Check for non-validated productions older than 48h.
     */
    public function checkProductionsNonValidees(int $garageId): array
    {
        $created = [];

        $productions = Production::where('garage_id', $garageId)
            ->where('valide', false)
            ->where('created_at', '<', now()->subHours(48))
            ->get();

        foreach ($productions as $production) {
            $alerte = $this->createAlerteIfNotExists(
                $garageId,
                'production_non_validee',
                'basse',
                'Production non validee depuis 48h',
                sprintf(
                    'La production du %s (type: %s, quantite: %s) n\'a pas ete validee depuis plus de 48h.',
                    Carbon::parse($production->date_production)->format('d/m/Y'),
                    $production->typeDechet->denomination ?? 'inconnu',
                    $production->quantite.' '.($production->unite ?? '')
                ),
                'production',
                $production->id
            );
            if ($alerte) {
                $created[] = $alerte;
            }
        }

        return $created;
    }

    /**
     * Check for enlevements scheduled in the next 3 days.
     */
    public function checkEnlevementsProches(int $garageId): array
    {
        $created = [];

        // Look for upcoming scheduled pickups (in next 3 days)
        $enlevements = \App\Models\Enlevement::where('garage_id', $garageId)
            ->where('statut', 'planifie')
            ->whereBetween('date_prevue', [now(), now()->addDays(3)])
            ->get();

        foreach ($enlevements as $enlevement) {
            $jours = now()->diffInDays(Carbon::parse($enlevement->date_prevue));
            $alerte = $this->createAlerteIfNotExists(
                $garageId,
                'enlevement_j3',
                'moyenne',
                'Enlevement prevu dans '.$jours.' jour(s)',
                sprintf(
                    'Un enlevement par "%s" est prevu le %s. Assurez-vous que les conteneurs et les BSD sont prets.',
                    $enlevement->collecteur->raison_sociale ?? 'collecteur inconnu',
                    Carbon::parse($enlevement->date_prevue)->format('d/m/Y a H:i')
                ),
                'enlevement',
                $enlevement->id
            );
            if ($alerte) {
                $created[] = $alerte;
            }
        }

        return $created;
    }

    /**
     * Check for dangerous waste without associated BSD.
     */
    public function checkDdSansBsd(int $garageId): array
    {
        $created = [];

        // Find productions of dangerous waste older than 7 days
        // Note: no direct bordereaux relationship, check via type_dechet BSD existence
        $productions = Production::where('garage_id', $garageId)
            ->whereHas('typeDechet', fn ($q) => $q->where('dangereux', true))
            ->where('date_production', '<', now()->subDays(7))
            ->get()
            ->filter(function ($production) use ($garageId) {
                // Check if there's a BSD for this waste type in the same period
                return ! Bordereau::where('garage_id', $garageId)
                    ->where('code_dechet', $production->typeDechet->code_europeen ?? '')
                    ->where('date_emission', '>=', $production->date_production)
                    ->exists();
            });

        foreach ($productions as $production) {
            $jours = Carbon::parse($production->date_production)->diffInDays(now());
            $alerte = $this->createAlerteIfNotExists(
                $garageId,
                'dd_sans_bsd',
                'haute',
                'Dechet dangereux sans BSD depuis '.$jours.' jours',
                sprintf(
                    'La production de "%s" du %s (quantite: %s) n\'a pas de bordereau de suivi associe. Un BSD est obligatoire pour les dechets dangereux.',
                    $production->typeDechet->denomination ?? 'inconnu',
                    Carbon::parse($production->date_production)->format('d/m/Y'),
                    $production->quantite.' '.($production->unite ?? '')
                ),
                'production',
                $production->id
            );
            if ($alerte) {
                $created[] = $alerte;
            }
        }

        return $created;
    }

    /**
     * Create an alert only if no unresolved alert of the same type+entity already exists.
     */
    private function createAlerteIfNotExists(
        int $garageId,
        string $type,
        string $priorite,
        string $titre,
        string $message,
        string $entiteType,
        int $entiteId
    ): ?Alerte {
        $exists = Alerte::where('garage_id', $garageId)
            ->where('type', $type)
            ->where('entite_type', $entiteType)
            ->where('entite_id', $entiteId)
            ->where('resolue', false)
            ->exists();

        if ($exists) {
            return null;
        }

        $alerte = Alerte::create([
            'garage_id' => $garageId,
            'type' => $type,
            'priorite' => $priorite,
            'titre' => $titre,
            'message' => $message,
            'entite_type' => $entiteType,
            'entite_id' => $entiteId,
            'lue' => false,
            'resolue' => false,
        ]);

        // Send email notification for critical alerts
        if ($priorite === 'critique') {
            $this->notifyUsersForCriticalAlert($alerte);
        }

        return $alerte;
    }

    /**
     * Notify users who opted in for critical alert emails.
     */
    private function notifyUsersForCriticalAlert(Alerte $alerte): void
    {
        $garage = Garage::find($alerte->garage_id);
        if (! $garage) {
            return;
        }

        // Find users linked to this garage with email_alertes_critiques enabled
        $users = User::where('entreprise_id', $garage->entreprise_id)
            ->where('actif', true)
            ->whereJsonContains('preferences_notifications->email_alertes_critiques', true)
            ->get()
            ->filter(fn (User $user) => $user->hasAccessToGarage($alerte->garage_id));

        foreach ($users as $user) {
            $user->notify(new AlerteCritiqueNotification($alerte));
        }
    }
}
