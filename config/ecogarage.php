<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Container Thresholds (%)
    |--------------------------------------------------------------------------
    |
    | Fill-level percentages at which alerts are triggered for waste containers.
    | "warning" triggers a notification; "critical" triggers an urgent alert.
    |
    */
    'container_thresholds' => [
        'warning' => 80,
        'critical' => 95,
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Storage Duration (months)
    |--------------------------------------------------------------------------
    |
    | Regulatory limits for on-site waste storage before mandatory evacuation.
    | - elimination:   non-recoverable waste (ICPE default 12 months)
    | - valorisation:  recoverable / recyclable waste (up to 36 months)
    |
    */
    'storage_max_months' => [
        'elimination' => 12,
        'valorisation' => 36,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Check Schedule
    |--------------------------------------------------------------------------
    |
    | Cron-style schedule for the daily automated alert check.
    |
    */
    'alert_schedule' => [
        'time' => '08:00',
        'timezone' => 'Europe/Paris',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trackdechets API
    |--------------------------------------------------------------------------
    |
    | URLs for the French government Trackdechets (BSD) platform.
    |
    */
    'trackdechets' => [
        'production' => env('TRACKDECHETS_URL', 'https://api.trackdechets.beta.gouv.fr'),
        'sandbox' => env('TRACKDECHETS_SANDBOX_URL', 'https://api.sandbox.trackdechets.beta.gouv.fr'),
        'api_token' => env('TRACKDECHETS_API_TOKEN'),
        'use_sandbox' => env('TRACKDECHETS_SANDBOX', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Throttle limits for public-facing API endpoints.
    |
    */
    'rate_limits' => [
        'api_public' => [
            'max_attempts' => 1000,
            'decay_minutes' => 60, // per hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ICPE Thresholds (litres)
    |--------------------------------------------------------------------------
    |
    | Volume thresholds for ICPE (Installations Classees pour la Protection
    | de l'Environnement) regulatory regime classification.
    |
    */
    'icpe_thresholds' => [
        'declaration' => 2000,  // litres - regime de declaration
        'enregistrement' => 5000,  // litres - regime d'enregistrement
    ],

    /*
    |--------------------------------------------------------------------------
    | BSD Statuses
    |--------------------------------------------------------------------------
    |
    | Allowed statuses for Bordereau de Suivi de Dechets (BSD) tracking slips.
    | Follows the Trackdechets lifecycle.
    |
    */
    'bsd_statuses' => [
        'INITIAL',
        'SIGNED_BY_PRODUCER',
        'SENT',
        'RECEIVED',
        'ACCEPTED',
        'PROCESSED',
        'GROUPED',
        'NO_TRACEABILITY',
        'AWAITING_GROUP',
        'REFUSED',
        'TEMP_STORED',
        'TEMP_STORER_ACCEPTED',
        'RESEALED',
        'RESENT',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Mappings
    |--------------------------------------------------------------------------
    |
    | Default waste code mappings for common automotive garage waste types.
    | Keys are internal waste type identifiers; values are European Waste
    | Catalogue (EWC) codes.
    |
    */
    'default_mappings' => [
        'huiles_usagees' => '13 02 05*',  // huiles moteur, boite de vitesse, lubrification minerales
        'filtres_huile' => '16 01 07*',  // filtres a huile
        'liquide_refroidissement' => '16 01 14*', // antigels contenant des substances dangereuses
        'batteries' => '16 06 01*',  // accumulateurs au plomb
        'pneus' => '16 01 03',   // pneumatiques hors d'usage
        'liquide_frein' => '16 01 13*',  // liquides de frein
        'solvants' => '14 06 03*',  // autres solvants et melanges de solvants
        'aerosols' => '16 05 04*',  // gaz en recipients a pression contenant des substances dangereuses
        'chiffons_souilles' => '15 02 02*',  // absorbants, materiaux filtrants, chiffons d'essuyage
        'pare_brise' => '16 01 20',   // verre
        'carrosserie' => '16 01 17',   // metaux ferreux
        'plastiques' => '16 01 19',   // plastiques
        'cartons' => '15 01 01',   // emballages en papier/carton
    ],

    /*
    |--------------------------------------------------------------------------
    | REP Eco-Contribution Rates (EUR per unit/kg)
    |--------------------------------------------------------------------------
    |
    | Rates for REP (Responsabilite Elargie du Producteur) eco-contributions.
    | These rates are updated periodically by the relevant eco-organisations.
    |
    */
    'rep_eco_contributions' => [
        'huiles_usagees' => 0.0,    // collecte gratuite via Cyclevia (ex-ADEME)
        'batteries_plomb' => 0.0,    // valeur residuelle positive, pas de contribution
        'pneus' => 1.50,   // EUR/pneu VL - Aliapur
        'filtres_huile' => 0.10,   // EUR/unite
        'emballages' => 0.03,   // EUR/kg - Citeo
        'papier_carton' => 0.02,   // EUR/kg - Citeo
        'deee' => 0.05,   // EUR/kg - Ecosystem / Ecologic
    ],

];
