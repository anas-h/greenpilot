<?php

namespace Database\Seeders;

use App\Models\Alerte;
use Illuminate\Database\Seeder;

class TestAlertesSeeder extends Seeder
{
    public function run(): void
    {
        $garageId = 1;

        // Résoudre toutes les anciennes alertes pour repartir propre
        Alerte::where('garage_id', $garageId)
            ->where('resolue', false)
            ->update(['resolue' => true, 'date_resolution' => now()]);

        $alertes = [
            [
                'type' => 'conteneur_80',
                'priorite' => 'haute',
                'titre' => 'Conteneur rempli a 80%',
                'message' => 'Le conteneur "Bac huiles usagees" a atteint 80% de sa capacite. Planifiez un enlevement.',
            ],
            [
                'type' => 'conteneur_95',
                'priorite' => 'critique',
                'titre' => 'Conteneur quasi plein - 95%',
                'message' => 'Le conteneur "Benne filtres a huile" est a 95%. Enlevement urgent necessaire !',
            ],
            [
                'type' => 'stockage_10mois',
                'priorite' => 'haute',
                'titre' => 'Limite de stockage approche - 10 mois',
                'message' => 'Le conteneur "Cuve huiles usagees moteur" stocke des dechets dangereux depuis 10 mois. Limite reglementaire a 12 mois.',
            ],
            [
                'type' => 'stockage_11mois',
                'priorite' => 'critique',
                'titre' => 'Limite de stockage imminente - 11 mois',
                'message' => 'Le conteneur "Fut solvants" stocke des dechets dangereux depuis 11 mois. Enlevement URGENT avant depassement reglementaire !',
            ],
            [
                'type' => 'autorisation_30j',
                'priorite' => 'moyenne',
                'titre' => 'Autorisation collecteur expire dans 30 jours',
                'message' => 'L\'autorisation du collecteur "FerPro Recyclage" expire le 10/04/2026. Pensez a demander le renouvellement.',
            ],
            [
                'type' => 'autorisation_expiree',
                'priorite' => 'critique',
                'titre' => 'Autorisation collecteur expiree',
                'message' => 'L\'autorisation du collecteur "ChimPro Traitement" est expiree depuis le 01/03/2026. Collecteur non utilisable !',
            ],
            [
                'type' => 'bsd_sent_15j',
                'priorite' => 'haute',
                'titre' => 'BSD sans retour depuis 15 jours',
                'message' => 'Le bordereau BSD-2026-0042 a ete envoye il y a 15 jours sans retour du collecteur. Relancez le collecteur.',
            ],
            [
                'type' => 'bsd_refused',
                'priorite' => 'critique',
                'titre' => 'BSD refuse par le collecteur',
                'message' => 'Le bordereau BSD-2026-0038 a ete refuse par "Veolia Environnement". Motif: informations incompletes.',
            ],
            [
                'type' => 'fds_manquante',
                'priorite' => 'moyenne',
                'titre' => 'FDS manquante',
                'message' => 'La fiche de donnees de securite pour "Liquide de frein" est manquante. Document obligatoire pour les dechets dangereux.',
            ],
            [
                'type' => 'fds_expiree',
                'priorite' => 'moyenne',
                'titre' => 'FDS expiree',
                'message' => 'La FDS du produit "Huile moteur 5W30" a expire le 15/01/2026. Demandez la version mise a jour au fournisseur.',
            ],
            [
                'type' => 'conformite_conteneurs',
                'priorite' => 'haute',
                'titre' => 'Conteneur non conforme - pas d\'enlevement depuis 6 mois',
                'message' => 'Le conteneur "Benne ferraille" n\'a pas eu d\'enlevement depuis plus de 6 mois. Verifiez la conformite.',
            ],
            [
                'type' => 'trackdechets_off',
                'priorite' => 'basse',
                'titre' => 'Trackdechets non configure',
                'message' => 'L\'integration Trackdechets n\'est pas active pour ce garage. La dematerialisation des BSD est obligatoire.',
            ],
            [
                'type' => 'production_non_validee',
                'priorite' => 'basse',
                'titre' => 'Production non validee depuis 48h',
                'message' => '3 productions de dechets sont en attente de validation depuis plus de 48h. Pensez a les valider.',
            ],
            [
                'type' => 'enlevement_j3',
                'priorite' => 'moyenne',
                'titre' => 'Enlevement prevu dans 3 jours',
                'message' => 'Un enlevement par "Suez Recyclage" est prevu le 13/03/2026. Preparez les conteneurs et bordereaux.',
            ],
            [
                'type' => 'dd_sans_bsd',
                'priorite' => 'haute',
                'titre' => 'Dechets dangereux sans BSD',
                'message' => '2 productions de dechets dangereux datant de plus de 7 jours n\'ont pas de bordereau de suivi associe.',
            ],
        ];

        foreach ($alertes as $alerte) {
            Alerte::create(array_merge($alerte, [
                'garage_id' => $garageId,
                'lue' => false,
                'resolue' => false,
            ]));
        }

        $this->command->info('15 alertes de test creees pour Garage Central (ID: 1)');
    }
}
