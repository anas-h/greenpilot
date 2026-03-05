@extends('vitrine.layout')

@section('title', 'Fonctionnalites - GreenPilot')

@section('content')

{{-- Hero --}}
<section class="pt-32 pb-16" style="background: linear-gradient(180deg, #E8F5E9 0%, #FAFAF8 100%);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-sm font-semibold text-green-700 uppercase tracking-wider mb-3">Fonctionnalites</p>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900">Tout pour gerer vos dechets automobile</h1>
        <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">Decouvrez en detail chaque module d'GreenPilot et comment il simplifie votre quotidien.</p>
    </div>
</section>

@php
$modules = [
    [
        'id' => 'dechets',
        'badge' => 'Module central',
        'title' => 'Gestion des dechets',
        'desc' => 'Suivez chaque type de dechet produit par votre garage : huiles, filtres, batteries, pneus, solvants, et plus. Definissez vos conteneurs, suivez les niveaux de remplissage en temps reel et recevez des alertes automatiques.',
        'features' => [
            'Types de dechets personnalisables (code europeen, dangereux/non dangereux)',
            'Conteneurs avec suivi de niveau et QR codes',
            'Saisie rapide des productions (unitaire ou par lot)',
            'Alertes automatiques : conteneur plein, seuil de capacite atteint',
            'Historique complet par conteneur et type de dechet',
        ],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />',
        'color' => 'green',
    ],
    [
        'id' => 'bsd',
        'badge' => 'Trackdechets',
        'title' => 'Bordereaux de suivi des dechets',
        'desc' => 'Generez vos BSD (BSDD, BSDA, BSVHU) et synchronisez-les automatiquement avec la plateforme nationale Trackdechets. Fini la double saisie et les erreurs.',
        'features' => [
            'Creation automatique a partir des enlevements de dechets dangereux',
            'Synchronisation bidirectionnelle avec l\'API Trackdechets',
            'Signature electronique du producteur integree',
            'Suivi des statuts en temps reel (brouillon → signe → envoye → traite)',
            'Retry automatique en cas d\'erreur avec alerte admin',
            'Export PDF de chaque bordereau',
        ],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
        'color' => 'blue',
    ],
    [
        'id' => 'conformite',
        'badge' => 'Reglementaire',
        'title' => 'Conformite ICPE',
        'desc' => 'Evaluez en continu votre conformite aux installations classees pour la protection de l\'environnement. Identifiez les points faibles et corrigez-les avant les controles.',
        'features' => [
            'Score de conformite calcule en temps reel',
            'Fiches de securite (FDS) centralisees par dechet',
            'Registre des dechets conforme a l\'arrete du 31/05/2021',
            'Export registre au format reglementaire',
            'Alertes sur les echeances reglementaires',
        ],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'color' => 'orange',
    ],
    [
        'id' => 'enlevements',
        'badge' => 'Logistique',
        'title' => 'Planification des enlevements',
        'desc' => 'Organisez vos collectes de dechets avec un calendrier visuel, gerez les collecteurs, et generez des bons d\'enlevement PDF automatiquement.',
        'features' => [
            'Calendrier mensuel avec vue des enlevements planifies',
            'Gestion des collecteurs et de leurs autorisations',
            'Recurrences automatiques (hebdo, mensuel, trimestriel...)',
            'Completion avec calcul automatique des couts et rachats',
            'Bons d\'enlevement PDF professionnels',
            'Comparatif collecteurs (prix, performance)',
        ],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />',
        'color' => 'purple',
    ],
    [
        'id' => 'eco',
        'badge' => 'Financier',
        'title' => 'Eco-contributions',
        'desc' => 'Calculez automatiquement vos eco-contributions pour chaque filiere REP (pneus, batteries, huiles, etc.) et suivez vos declarations et paiements.',
        'features' => [
            'Calcul automatique par filiere et par quantite',
            'Suivi des declarations (brouillon, declare, paye)',
            'Historique des paiements par periode',
            'Alertes sur les echeances de declaration',
        ],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />',
        'color' => 'teal',
    ],
    [
        'id' => 'multi',
        'badge' => 'Organisation',
        'title' => 'Multi-garages & utilisateurs',
        'desc' => 'Pilotez l\'ensemble de vos etablissements depuis une interface unifiee avec une gestion fine des droits d\'acces.',
        'features' => [
            'Vue entreprise consolidee sur tous vos garages',
            'Roles granulaires : admin, chef d\'atelier, mecanicien, comptable',
            'Affectation des utilisateurs par garage',
            'Dashboard administrateur avec statistiques globales',
            'Impersonation pour le support technique',
        ],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />',
        'color' => 'indigo',
    ],
];
$colorMap = [
    'green' => ['bg-green-50','bg-green-100','text-green-700','border-green-200'],
    'blue' => ['bg-blue-50','bg-blue-100','text-blue-700','border-blue-200'],
    'orange' => ['bg-orange-50','bg-orange-100','text-orange-700','border-orange-200'],
    'purple' => ['bg-purple-50','bg-purple-100','text-purple-700','border-purple-200'],
    'teal' => ['bg-teal-50','bg-teal-100','text-teal-700','border-teal-200'],
    'indigo' => ['bg-indigo-50','bg-indigo-100','text-indigo-700','border-indigo-200'],
];
@endphp

@foreach($modules as $i => $m)
@php $c = $colorMap[$m['color']]; $reverse = $i % 2 !== 0; @endphp
<section id="{{ $m['id'] }}" class="py-20 {{ $i % 2 === 0 ? '' : 'bg-white' }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div class="{{ $reverse ? 'lg:order-2' : '' }}">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ $c[1] }} {{ $c[2] }} text-xs font-semibold mb-4">{{ $m['badge'] }}</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-4">{{ $m['title'] }}</h2>
                <p class="text-gray-600 leading-relaxed mb-8">{{ $m['desc'] }}</p>
                <ul class="space-y-3">
                    @foreach($m['features'] as $f)
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 {{ $c[2] }} mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        <span class="text-sm text-gray-700">{{ $f }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="{{ $reverse ? 'lg:order-1' : '' }}">
                <div class="rounded-2xl {{ $c[0] }} border {{ $c[3] }} p-10 flex items-center justify-center" style="aspect-ratio: 4/3;">
                    <div class="text-center">
                        <div class="w-24 h-24 rounded-3xl {{ $c[1] }} flex items-center justify-center mx-auto mb-5">
                            <svg class="w-12 h-12 {{ $c[2] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">{!! $m['icon'] !!}</svg>
                        </div>
                        <p class="text-xl font-bold text-gray-800">{{ $m['title'] }}</p>
                        <p class="text-sm text-gray-500 mt-2">Module GreenPilot</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endforeach

{{-- CTA --}}
<section class="py-20" style="background: linear-gradient(135deg, #1b5e20 0%, #2E7D32 50%, #388E3C 100%);">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">Convaincu ?</h2>
        <p class="text-lg text-green-100 mb-8">Testez GreenPilot gratuitement pendant 14 jours. Aucune carte bancaire requise.</p>
        <a href="/register" class="inline-flex items-center px-8 py-4 text-base font-semibold text-green-900 bg-white rounded-full hover:bg-green-50 transition-all duration-200 hover:scale-[1.02] shadow-lg">
            Demarrer gratuitement
            <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
        </a>
    </div>
</section>

@endsection
