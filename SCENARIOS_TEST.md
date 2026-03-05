# Scenarios de Test - GreenPilot

> Document genere le 26/02/2026
> Total : ~85 scenarios couvrant tous les modules

---

## 1. Authentification & Onboarding

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 1.1 | Connexion valide | Email: `anas.test@email.com`, MDP: `password` → Connexion | Redirection vers le dashboard | ☐ |
| 1.2 | Connexion MDP errone | Saisir un mauvais mot de passe | Message "Identifiants incorrects" | ☐ |
| 1.3 | Connexion email inconnu | Saisir un email inexistant | Message "Identifiants incorrects" | ☐ |
| 1.4 | Deconnexion | Cliquer sur avatar → Deconnexion | Retour a la page login | ☐ |
| 1.5 | Inscription nouveau compte | Remplir formulaire Register (nom, prenom, email, MDP, raison sociale, SIRET) | Compte cree, redirection onboarding | ☐ |
| 1.6 | Onboarding complet | Suivre les 4 etapes (garage, types dechets, conteneurs, confirmation) | Redirection dashboard, garage configure | ☐ |
| 1.7 | Inscription email deja utilise | Utiliser un email existant | Erreur de validation sur le champ email | ☐ |

---

## 2. Dashboard

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 2.1 | Affichage KPIs | Ouvrir le dashboard | 4 cartes KPI visibles (productions du mois, enlevements prevus, alertes actives, score conformite) | ☐ |
| 2.2 | Alertes recentes | Verifier la section alertes | Les 3 alertes les plus prioritaires s'affichent | ☐ |
| 2.3 | Conteneurs les plus remplis | Verifier la section conteneurs | Top 5 conteneurs avec jauge de remplissage | ☐ |
| 2.4 | Prochains enlevements | Verifier la section enlevements | 5 prochains enlevements planifies | ☐ |
| 2.5 | Dashboard responsive | Ouvrir sur mobile (375px) | Cartes empilees, pas de debordement horizontal | ☐ |

---

## 3. Conteneurs

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 3.1 | Creer un conteneur | Cliquer "Nouveau conteneur" → Remplir nom, type dechet, capacite, unite, emplacement → Enregistrer | Conteneur cree avec QR code auto-genere (ECO-xxx) | ☐ |
| 3.2 | Modifier un conteneur | Cliquer icone crayon sur un conteneur → Modifier le nom → Enregistrer | Nom mis a jour | ☐ |
| 3.3 | Supprimer un conteneur | Cliquer icone poubelle → Confirmer | Conteneur supprime de la liste | ☐ |
| 3.4 | Rechercher un conteneur | Taper un nom dans le champ recherche | Liste filtree en temps reel | ☐ |
| 3.5 | Filtrer par type de dechet | Selectionner un type dans le filtre | Seuls les conteneurs de ce type s'affichent | ☐ |
| 3.6 | Basculer vue grille/liste | Cliquer sur les icones grille/liste | La vue change correctement | ☐ |
| 3.7 | Voir le detail | Cliquer sur un conteneur | Dialog avec jauge, infos, et historique | ☐ |
| 3.8 | Telecharger QR code | Cliquer icone QR sur un conteneur | Fichier PNG telecharge | ☐ |
| 3.9 | Scanner un QR code | Cliquer "Scanner QR" → Scanner un code | Dialog detail du conteneur correspondant | ☐ |
| 3.10 | Conteneur responsive | Ouvrir sur mobile | Grille en 1 colonne, boutons wrappent sous le titre | ☐ |

---

## 4. Productions

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 4.1 | Creer une production | Cliquer "Nouvelle production" → Remplir type dechet, quantite, unite, date → Creer | Production enregistree, niveau conteneur mis a jour | ☐ |
| 4.2 | Creer avec conteneur | Selectionner un type dechet → Choisir un conteneur compatible → Enregistrer | Production liee au conteneur | ☐ |
| 4.3 | Modifier une production | Cliquer modifier → Changer la quantite → Enregistrer | Quantite mise a jour | ☐ |
| 4.4 | Supprimer une production | Cliquer supprimer → Confirmer | Production supprimee | ☐ |
| 4.5 | Saisie groupee (batch) | Onglet "Saisie groupee" → Ajouter 3 lignes → "Enregistrer tout" | 3 productions creees d'un coup | ☐ |
| 4.6 | Filtrer par date | Selectionner une plage de dates | Seules les productions de la periode s'affichent | ☐ |
| 4.7 | Filtrer par type dechet | Selectionner un type dans le filtre | Liste filtree | ☐ |
| 4.8 | Filtrer par statut validation | Filtrer valide/invalide | Productions filtrees | ☐ |
| 4.9 | Scan QR production | Onglet "Scan QR" → Scanner un conteneur | Formulaire pre-rempli avec le type du conteneur | ☐ |
| 4.10 | Validation production | Selectionner des productions → Valider | Statut passe a "valide" | ☐ |
| 4.11 | Dialog fullscreen mobile | Ouvrir le dialog sur mobile (<600px) | Dialog en plein ecran | ☐ |
| 4.12 | Tableau batch scrollable | Ouvrir saisie groupee sur mobile | Scroll horizontal dans le tableau | ☐ |

---

## 5. Collecteurs

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 5.1 | Creer un collecteur | Cliquer "Ajouter" → Remplir raison sociale, SIRET (14 car.), adresse, contact → Creer | Collecteur cree | ☐ |
| 5.2 | SIRET invalide | Saisir un SIRET de 10 caracteres | Erreur validation "Le SIRET doit faire 14 caracteres" | ☐ |
| 5.3 | Ajouter des tarifs | Dans le dialog → "Ajouter" tarif → Selectionner type, prix unitaire, prix forfaitaire, rachat → Enregistrer | Tarifs enregistres | ☐ |
| 5.4 | Modifier un collecteur | Ouvrir un collecteur → Modifier l'adresse → Enregistrer | Adresse mise a jour | ☐ |
| 5.5 | Supprimer un collecteur | Cliquer supprimer → Confirmer | Collecteur supprime | ☐ |
| 5.6 | Comparatif tarifs | Cliquer "Comparatif tarifs" | Tableau comparatif des prix par collecteur | ☐ |
| 5.7 | Filtrer par statut actif | Filtrer actif/inactif | Liste filtree | ☐ |
| 5.8 | Autorisation ADR | Cocher "Autorisation ADR" → Remplir numero ADR | Champ ADR active et sauvegarde | ☐ |
| 5.9 | Indicateur validite autorisation | Creer un collecteur avec date validite passee | Icone rouge dans la liste des collecteurs | ☐ |
| 5.10 | Dialog responsive | Ouvrir le dialog sur mobile | Fullscreen + tableau tarifs scrollable | ☐ |

---

## 6. Enlevements

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 6.1 | Creer un enlevement | Cliquer "Nouvel enlevement" → Choisir collecteur, date, ajouter lignes (type, conteneur, quantite, unite) → Creer | Enlevement cree (statut brouillon), numero ENL-YYYY-XXXXX | ☐ |
| 6.2 | Sans ligne | Creer sans ajouter de ligne → Enregistrer | Message "Au moins une ligne est requise" | ☐ |
| 6.3 | Modifier un enlevement | Ouvrir un enlevement brouillon → Modifier la date → Enregistrer | Date mise a jour | ☐ |
| 6.4 | Supprimer un enlevement | Supprimer un brouillon | Enlevement supprime | ☐ |
| 6.5 | Completer un enlevement | Cliquer "Completer" → Renseigner date effective, quantites effectives → Completer | Statut passe a "complete", estimation des couts affichee | ☐ |
| 6.6 | Vue calendrier | Onglet "Calendrier" | Enlevements affiches sur le calendrier aux bonnes dates | ☐ |
| 6.7 | Recurrence | Creer avec recurrence "Mensuel" | Prochaine date auto-calculee | ☐ |
| 6.8 | Telecharger bon PDF | Sur un enlevement complete → Telecharger PDF | Fichier PDF du bon d'enlevement telecharge | ☐ |
| 6.9 | Filtrer par collecteur | Selectionner un collecteur dans les filtres | Seuls ses enlevements s'affichent | ☐ |
| 6.10 | Filtrer par statut | Filtrer "planifie" | Seuls les enlevements planifies s'affichent | ☐ |
| 6.11 | Dialog completion responsive | Completer sur mobile | Dialog fullscreen, tableau scrollable | ☐ |

---

## 7. Bordereaux (BSD)

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 7.1 | Creer un BSD | Cliquer "Nouveau BSD" → Remplir type BSD, code dechet, denomination, quantite, transporteur, destination → Enregistrer | BSD cree en statut DRAFT | ☐ |
| 7.2 | Modifier un brouillon | Ouvrir un BSD DRAFT → Modifier → Enregistrer | Modification sauvegardee | ☐ |
| 7.3 | Publier un BSD | Sur un DRAFT → Cliquer "Publier" | Statut passe a SEALED, edition verrouilee | ☐ |
| 7.4 | Signer un BSD | Sur un SEALED → Cliquer "Signer" | Statut passe a SIGNED_BY_PRODUCER | ☐ |
| 7.5 | Annuler un BSD | Sur n'importe quel statut → Annuler avec motif | Statut CANCELED, motif enregistre | ☐ |
| 7.6 | Dupliquer un BSD refuse | Sur un BSD REFUSED → Cliquer "Dupliquer" | Nouveau BSD DRAFT cree avec les memes donnees | ☐ |
| 7.7 | Modifier un BSD SEALED | Tenter de modifier un BSD publie | Champs en lecture seule, pas de bouton enregistrer | ☐ |
| 7.8 | Supprimer un DRAFT | Supprimer un brouillon | BSD supprime | ☐ |
| 7.9 | Supprimer un non-DRAFT | Tenter de supprimer un BSD SEALED | Action impossible / bouton absent | ☐ |
| 7.10 | Telecharger PDF | Cliquer "PDF" sur un BSD | PDF CERFA telecharge | ☐ |
| 7.11 | Filtrer multi-statut | Filtrer par DRAFT + SEALED | Les deux statuts s'affichent | ☐ |
| 7.12 | Filtrer par type BSD | Filtrer "BSDD" | Seuls les BSDD s'affichent | ☐ |
| 7.13 | Recherche texte | Chercher un numero de BSD | Resultat trouve | ☐ |
| 7.14 | Split panel responsive | Ouvrir BSD sur mobile → Selectionner un BSD | Liste et detail empiles verticalement | ☐ |

---

## 8. Registre des Dechets

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 8.1 | Affichage registre | Ouvrir la page Registre | Tableau avec toutes les colonnes reglementaires | ☐ |
| 8.2 | Filtrer par date | Selectionner une plage de dates | Donnees filtrees | ☐ |
| 8.3 | Filtrer DD uniquement | Cocher "Dangereux" | Seuls les dechets dangereux s'affichent | ☐ |
| 8.4 | Filtrer par collecteur | Selectionner un collecteur | Donnees filtrees | ☐ |
| 8.5 | Export CSV | Cliquer Exporter → CSV | Fichier CSV telecharge avec les bonnes colonnes | ☐ |
| 8.6 | Export Excel | Cliquer Exporter → Excel | Fichier Excel telecharge | ☐ |
| 8.7 | Export PDF | Cliquer Exporter → PDF | Fichier PDF telecharge | ☐ |
| 8.8 | Registre vide | Filtrer une periode sans donnees | Message "Aucune donnee" | ☐ |

---

## 9. Alertes

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 9.1 | Affichage alertes | Ouvrir la page Alertes | Cartes resume par priorite (critique, haute, moyenne, basse) | ☐ |
| 9.2 | Marquer comme lue | Cliquer sur une alerte non lue | Alerte marquee comme lue | ☐ |
| 9.3 | Tout marquer comme lu | Cliquer "Tout marquer comme lu" | Toutes les alertes passent en "lue" | ☐ |
| 9.4 | Resoudre une alerte | Cliquer "Resoudre" sur une alerte | Alerte resolue avec date et utilisateur | ☐ |
| 9.5 | Filtrer par priorite | Filtrer "Critique" | Seules les alertes critiques s'affichent | ☐ |
| 9.6 | Filtrer par type | Filtrer par type (conformite, conteneur...) | Alertes filtrees | ☐ |
| 9.7 | Badge notification | Verifier le badge cloche dans le header | Nombre correct d'alertes non lues | ☐ |
| 9.8 | Recherche | Chercher un mot dans le titre/message | Resultats filtres | ☐ |

---

## 10. Conformite

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 10.1 | Score de conformite | Ouvrir la page Conformite | Score affiche (0-100) avec detail des criteres | ☐ |
| 10.2 | Upload FDS | Onglet FDS → Uploader un PDF | FDS enregistree et liee au type de dechet | ☐ |
| 10.3 | FDS expiree | Creer une FDS avec date de validite passee | Alerte "FDS expiree" generee | ☐ |
| 10.4 | Supprimer une FDS | Supprimer une fiche de securite | FDS supprimee, score recalcule | ☐ |
| 10.5 | FDS manquante | Avoir un type DD sans FDS | Score impacte, alerte "FDS manquante" | ☐ |
| 10.6 | Filtrer FDS | Filtrer par statut (presente, expiree, manquante) | Liste filtree | ☐ |

---

## 11. Suivi Economique

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 11.1 | KPIs economiques | Ouvrir la page Economie | 3 cartes : cout collecte, revenu revente, cout net | ☐ |
| 11.2 | Graphique evolution | Onglet "Evolution mensuelle" | Courbe couts/revenus sur les derniers mois | ☐ |
| 11.3 | Repartition par type | Onglet "Repartition par type" | Graphique camembert | ☐ |
| 11.4 | Comparatif collecteurs | Onglet "Comparatif collecteurs" | Graphique barres comparant les couts | ☐ |
| 11.5 | Charts responsive | Ouvrir sur mobile | Graphiques redimensionnes, pas de debordement | ☐ |

---

## 12. Parametres

| # | Scenario | Etapes | Resultat attendu | Statut |
|---|----------|--------|-------------------|--------|
| 12.1 | Modifier infos garage | Onglet Garage → Modifier nom/adresse → Enregistrer | Infos mises a jour | ☐ |
| 12.2 | Creer un utilisateur | Onglet Utilisateurs → Ajouter → Remplir nom, email, role, garages → Creer | Utilisateur cree | ☐ |
| 12.3 | Desactiver un utilisateur | Basculer le statut "actif" d'un utilisateur | Utilisateur ne peut plus se connecter | ☐ |
| 12.4 | Configurer Trackdechets | Onglet Trackdechets → Saisir token API + SIRET → Tester connexion | Message "Connexion reussie" ou erreur | ☐ |
| 12.5 | Ajouter un mapping | Onglet Mappings → Ajouter mot-cle → Associer type dechet | Mapping cree, suggestion active lors de la saisie production | ☐ |
| 12.6 | Changer de garage | Cliquer sur le selecteur de garage dans le header → Choisir un autre garage | Toutes les donnees se rechargent pour le nouveau garage | ☐ |

---

## 13. Responsive (tests transversaux)

| # | Scenario | Largeur | Resultat attendu | Statut |
|---|----------|---------|-------------------|--------|
| 13.1 | Sidebar mobile | 375px | Sidebar en overlay avec hamburger menu | ☐ |
| 13.2 | Header mobile | 375px | Titre route et prenom masques, pas de debordement | ☐ |
| 13.3 | Toolbars toutes pages | 375px | Titre + boutons wrappent, pas de scroll horizontal | ☐ |
| 13.4 | Dialogs mobile | 375px | Tous les dialogs en fullscreen | ☐ |
| 13.5 | Tableaux dans dialogs | 375px | Scroll horizontal dans les tableaux | ☐ |
| 13.6 | BSD split panel mobile | 375px | Liste et detail empiles verticalement | ☐ |
| 13.7 | Conteneurs grille mobile | 375px | 1 colonne, cartes pleine largeur | ☐ |
| 13.8 | Charts mobile | 375px | Graphiques redimensionnes (50vh), lisibles | ☐ |
| 13.9 | Tablette portrait | 768px | Layout intermediaire, 2 colonnes conteneurs | ☐ |
| 13.10 | Tablette paysage | 1024px | Layout desktop normal | ☐ |

---

## 14. Workflows de bout en bout

| # | Scenario | Etapes | Statut |
|---|----------|--------|--------|
| 14.1 | **Cycle complet dechet** | Creer conteneur → Enregistrer production → Planifier enlevement → Completer enlevement → Verifier BSD auto-cree → Publier BSD → Signer BSD → Verifier registre | ☐ |
| 14.2 | **Dechet dangereux** | Creer type DD → Uploader FDS → Creer conteneur DD → Production DD → Enlevement avec collecteur ADR → BSD BSDD → Verifier conformite | ☐ |
| 14.3 | **Nouveau garage** | Inscription → Onboarding → Configuration types dechets → Creation conteneurs → Premiere production → Premier enlevement | ☐ |
| 14.4 | **Multi-garage** | Creer 2 garages → Basculer entre les deux → Verifier que les donnees sont separees | ☐ |
| 14.5 | **Refus BSD** | Creer BSD → Publier → Signer → Simuler refus → Dupliquer → Corriger → Republier | ☐ |

---

## Resume

| Module | Nb scenarios | Priorite |
|--------|-------------|----------|
| Authentification & Onboarding | 7 | Haute |
| Dashboard | 5 | Moyenne |
| Conteneurs | 10 | Haute |
| Productions | 12 | Haute |
| Collecteurs | 10 | Haute |
| Enlevements | 11 | Haute |
| Bordereaux (BSD) | 14 | Haute |
| Registre | 8 | Moyenne |
| Alertes | 8 | Moyenne |
| Conformite | 6 | Haute |
| Economie | 5 | Basse |
| Parametres | 6 | Moyenne |
| Responsive | 10 | Haute |
| Workflows E2E | 5 | Haute |
| **TOTAL** | **117** | |

---

## Instructions

1. Ouvrir Chrome DevTools (F12) pour les tests responsive
2. Tester chaque scenario et cocher la case correspondante
3. Pour les tests mobile : utiliser le mode responsive avec les largeurs 375px, 768px, 1024px
4. Pour les workflows E2E : suivre les etapes dans l'ordre, chaque etape depend de la precedente
5. Reporter tout bug ou comportement inattendu avec capture d'ecran
