@extends('vitrine.layout')

@section('title', 'GreenPilot - Gestion des dechets automobile')

@section('content')

{{-- ============ HERO ============ --}}
<section class="relative overflow-hidden pt-20" style="background: linear-gradient(180deg, #E8F5E9 0%, #FAFAF8 100%);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-20 md:pt-24 md:pb-32">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-100 text-green-800 text-xs font-semibold mb-6">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    Conforme ICPE & Trackdechets
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-gray-900 leading-[1.1]">
                    Gerez vos dechets<br>
                    <span class="text-green-700">automobile</span> en toute conformite
                </h1>
                <p class="mt-6 text-lg text-gray-600 leading-relaxed max-w-lg">
                    La plateforme tout-en-un pour les garages : suivi des dechets, bordereaux BSD automatises, eco-contributions et conformite reglementaire.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="/register" class="inline-flex items-center px-7 py-3.5 text-base font-semibold text-white bg-green-700 rounded-full hover:bg-green-800 transition-all duration-200 hover:scale-[1.02] shadow-lg shadow-green-700/25">
                        Essai gratuit 14 jours
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </a>
                    <a href="{{ route('fonctionnalites') }}" class="inline-flex items-center px-7 py-3.5 text-base font-semibold text-gray-700 bg-white rounded-full border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Decouvrir
                    </a>
                </div>
                <div class="mt-10 flex items-center gap-6 text-sm text-gray-500">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        14 jours gratuits
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Sans carte bancaire
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Support inclus
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-gray-200 bg-white" style="aspect-ratio: 16/10;">
                    <div class="absolute inset-0 bg-gradient-to-br from-green-50 to-gray-50 flex items-center justify-center">
                        <div class="text-center px-8">
                            <div class="w-16 h-16 rounded-2xl bg-green-100 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            </div>
                            <p class="text-lg font-semibold text-gray-800">Dashboard GreenPilot</p>
                            <p class="text-sm text-gray-500 mt-1">Vue d'ensemble temps reel</p>
                        </div>
                    </div>
                </div>
                {{-- Floating cards --}}
                <div class="absolute -bottom-4 -left-4 bg-white rounded-xl shadow-lg border border-gray-100 px-4 py-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800">BSD synchronise</p>
                        <p class="text-xs text-gray-500">Trackdechets</p>
                    </div>
                </div>
                <div class="absolute -top-4 -right-4 bg-white rounded-xl shadow-lg border border-gray-100 px-4 py-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-800">Score ICPE</p>
                        <p class="text-xs text-green-600 font-semibold">92% conforme</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ STATS BANNER ============ --}}
<section class="py-12 border-b border-gray-100">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <p class="text-3xl font-extrabold text-green-700">500+</p>
                <p class="text-sm text-gray-500 mt-1">Garages connectes</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-green-700">15 000+</p>
                <p class="text-sm text-gray-500 mt-1">BSD generes</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-green-700">100%</p>
                <p class="text-sm text-gray-500 mt-1">Conforme ICPE</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-green-700">24/7</p>
                <p class="text-sm text-gray-500 mt-1">Support disponible</p>
            </div>
        </div>
    </div>
</section>

{{-- ============ FEATURES GRID ============ --}}
<section class="py-20 md:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <p class="text-sm font-semibold text-green-700 uppercase tracking-wider mb-3">Fonctionnalites</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">Tout ce dont votre garage a besoin</h2>
            <p class="mt-4 text-lg text-gray-500">Une solution complete pour simplifier la gestion reglementaire de vos dechets automobiles.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $features = [
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />', 'color' => 'green', 'title' => 'Gestion des dechets', 'desc' => 'Suivez vos conteneurs, productions et types de dechets en temps reel avec des alertes automatiques.'],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />', 'color' => 'blue', 'title' => 'BSD automatises', 'desc' => 'Generation et synchronisation automatique des bordereaux avec Trackdechets. Signature electronique integree.'],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />', 'color' => 'orange', 'title' => 'Conformite ICPE', 'desc' => 'Score de conformite en temps reel, fiches de securite, registre des dechets et alertes reglementaires.'],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />', 'color' => 'purple', 'title' => 'Enlevements', 'desc' => 'Planification des collectes, calendrier visuel, bons d\'enlevement PDF et gestion des recurrences.'],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />', 'color' => 'teal', 'title' => 'Eco-contributions', 'desc' => 'Calcul automatique, suivi des declarations et paiements pour chaque filiere REP.'],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />', 'color' => 'indigo', 'title' => 'Multi-garages', 'desc' => 'Gerez tous vos etablissements depuis une seule interface avec des roles et permissions granulaires.'],
            ];
            $colorMap = ['green' => ['bg-green-100','text-green-700'], 'blue' => ['bg-blue-100','text-blue-700'], 'orange' => ['bg-orange-100','text-orange-700'], 'purple' => ['bg-purple-100','text-purple-700'], 'teal' => ['bg-teal-100','text-teal-700'], 'indigo' => ['bg-indigo-100','text-indigo-700']];
            @endphp

            @foreach($features as $i => $f)
            <div class="reveal group bg-white rounded-2xl border border-gray-100 p-7 hover:shadow-lg hover:scale-[1.02] transition-all duration-200 cursor-default" style="transition-delay: {{ $i * 80 }}ms;">
                <div class="w-12 h-12 rounded-xl {{ $colorMap[$f['color']][0] }} flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 {{ $colorMap[$f['color']][1] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">{!! $f['icon'] !!}</svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $f['title'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ SHOWCASE ALTERNATING ============ --}}
<section class="py-20 md:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-24">
        @php
        $showcases = [
            [
                'badge' => 'Dashboard',
                'title' => 'Vue d\'ensemble en un coup d\'oeil',
                'desc' => 'Visualisez la production de dechets, les prochains enlevements, le score de conformite et les alertes critiques depuis un tableau de bord unifie.',
                'points' => ['Graphiques de production temps reel', 'Alertes conteneurs pleins', 'Score conformite ICPE', 'Economie circulaire chiffree'],
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
                'color' => 'green',
            ],
            [
                'badge' => 'Bordereaux',
                'title' => 'BSD conformes en quelques clics',
                'desc' => 'Creez, signez et transmettez vos bordereaux de suivi des dechets directement a Trackdechets sans ressaisie manuelle.',
                'points' => ['Sync automatique Trackdechets', 'Signature electronique integree', 'Suivi du statut en temps reel', 'Retry automatique en cas d\'erreur'],
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
                'color' => 'blue',
            ],
            [
                'badge' => 'Calendrier',
                'title' => 'Planifiez vos enlevements simplement',
                'desc' => 'Organisez les collectes de dechets avec un calendrier visuel, des recurrences automatiques et des bons d\'enlevement PDF professionnels.',
                'points' => ['Calendrier visuel mensuel', 'Recurrences automatiques', 'Bons PDF professionnels', 'Suivi des couts et rachats'],
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />',
                'color' => 'orange',
            ],
        ];
        $showcaseColors = ['green' => ['bg-green-50','bg-green-100','text-green-700','text-green-600'], 'blue' => ['bg-blue-50','bg-blue-100','text-blue-700','text-blue-600'], 'orange' => ['bg-orange-50','bg-orange-100','text-orange-700','text-orange-600']];
        @endphp

        @foreach($showcases as $i => $s)
        @php $c = $showcaseColors[$s['color']]; $reverse = $i % 2 !== 0; @endphp
        <div class="reveal grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div class="{{ $reverse ? 'lg:order-2' : '' }}">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ $c[1] }} {{ $c[2] }} text-xs font-semibold mb-4">{{ $s['badge'] }}</span>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-4">{{ $s['title'] }}</h3>
                <p class="text-gray-600 leading-relaxed mb-6">{{ $s['desc'] }}</p>
                <ul class="space-y-3">
                    @foreach($s['points'] as $point)
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 {{ $c[3] }} mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="text-sm text-gray-700">{{ $point }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="{{ $reverse ? 'lg:order-1' : '' }}">
                <div class="rounded-2xl {{ $c[0] }} border border-gray-100 p-8 flex items-center justify-center" style="aspect-ratio: 4/3;">
                    <div class="text-center">
                        <div class="w-20 h-20 rounded-2xl {{ $c[1] }} flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 {{ $c[2] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">{!! $s['icon'] !!}</svg>
                        </div>
                        <p class="text-lg font-bold text-gray-800">{{ $s['badge'] }}</p>
                        <p class="text-sm text-gray-500 mt-1">Interface GreenPilot</p>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- ============ TESTIMONIALS MARQUEE ============ --}}
<section class="py-20 md:py-28 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-12">
        <div class="text-center reveal">
            <p class="text-sm font-semibold text-green-700 uppercase tracking-wider mb-3">Temoignages</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">Ils nous font confiance</h2>
        </div>
    </div>

    <div class="relative">
        <div class="marquee-fade-l"></div>
        <div class="marquee-fade-r"></div>
        <div class="marquee-track" style="width: max-content;">
            @php
            $testimonials = [
                ['name' => 'Marc Dupont', 'role' => 'Gerant', 'company' => 'Garage Dupont & Fils', 'text' => 'Avant GreenPilot, on passait des heures sur la paperasse BSD. Maintenant tout est automatise, on se concentre sur notre metier.'],
                ['name' => 'Sophie Martin', 'role' => 'Responsable qualite', 'company' => 'Auto Service Pro', 'text' => 'Le score de conformite ICPE nous a permis d\'identifier des lacunes qu\'on ignorait. Indispensable pour dormir tranquille.'],
                ['name' => 'Jean-Pierre Roux', 'role' => 'Directeur', 'company' => 'Groupe Roux Auto', 'text' => 'Avec 8 garages, la vue multi-sites est un game changer. On pilote tout depuis un seul ecran.'],
                ['name' => 'Claire Fernandez', 'role' => 'Comptable', 'company' => 'Carrosserie Fernandez', 'text' => 'Les eco-contributions etaient un casse-tete. GreenPilot calcule tout automatiquement, je n\'ai plus qu\'a valider.'],
                ['name' => 'Thomas Leclerc', 'role' => 'Chef d\'atelier', 'company' => 'Meca Plus', 'text' => 'Le calendrier des enlevements et les alertes conteneurs pleins, c\'est exactement ce qu\'il nous fallait.'],
                ['name' => 'Nadia Benali', 'role' => 'Gerante', 'company' => 'Garage de la Gare', 'text' => 'Interface super intuitive. Mes mecaniciens l\'utilisent sans aucune formation, meme depuis leur telephone.'],
            ];
            @endphp
            @for($dup = 0; $dup < 2; $dup++)
            @foreach($testimonials as $t)
            <div class="flex-shrink-0 w-[380px] mx-3 bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-md transition-shadow duration-200">
                <p class="text-sm text-gray-600 leading-relaxed mb-5">"{{ $t['text'] }}"</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                        <span class="text-sm font-bold text-green-700">{{ substr($t['name'], 0, 1) }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $t['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ $t['role'] }} - {{ $t['company'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
            @endfor
        </div>
    </div>
</section>

{{-- ============ PRICING ============ --}}
<section id="tarifs" class="py-20 md:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <p class="text-sm font-semibold text-green-700 uppercase tracking-wider mb-3">Tarifs</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">Un plan adapte a votre activite</h2>
            <p class="mt-4 text-lg text-gray-500">Essai gratuit de 14 jours, sans carte bancaire.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            @php
            $plans = [
                ['name' => 'Standard', 'price' => '49', 'desc' => 'Pour les garages actifs', 'features' => ['1 garage', '5 utilisateurs', 'Suivi complet des dechets', 'Sync Trackdechets', 'Eco-contributions', 'Calendrier enlevements', 'BSD automatises', 'Support prioritaire'], 'cta' => 'Essai gratuit 14 jours', 'href' => '/register', 'featured' => true],
                ['name' => 'Premium', 'price' => null, 'desc' => 'Pour les groupes multi-sites', 'features' => ['Garages illimites', 'Utilisateurs illimites', 'Tout du plan Standard', 'API personnalisee', 'Formations incluses', 'Account manager dedie', 'SLA garanti'], 'cta' => 'Demander un devis', 'href' => route('contact'), 'featured' => false],
            ];
            @endphp

            @foreach($plans as $plan)
            <div class="reveal relative rounded-2xl border {{ $plan['featured'] ? 'border-green-600 ring-2 ring-green-600/20 shadow-xl' : 'border-gray-200' }} bg-white p-8 flex flex-col">
                @if($plan['featured'])
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span class="inline-flex items-center px-4 py-1 rounded-full bg-green-700 text-white text-xs font-semibold">Populaire</span>
                </div>
                @endif
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900">{{ $plan['name'] }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $plan['desc'] }}</p>
                </div>
                <div class="mb-6">
                    @if($plan['price'])
                    <span class="text-4xl font-extrabold text-gray-900">{{ $plan['price'] }}&euro;</span>
                    <span class="text-sm text-gray-500"> /mois</span>
                    @else
                    <span class="text-3xl font-extrabold text-gray-900">Sur devis</span>
                    @endif
                </div>
                <ul class="space-y-3 mb-8 flex-1">
                    @foreach($plan['features'] as $feature)
                    <li class="flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        <span class="text-sm text-gray-600">{{ $feature }}</span>
                    </li>
                    @endforeach
                </ul>
                <a href="{{ $plan['href'] }}" class="block text-center px-6 py-3 rounded-full text-sm font-semibold transition-all duration-200 hover:scale-[1.02] {{ $plan['featured'] ? 'bg-green-700 text-white hover:bg-green-800 shadow-lg shadow-green-700/25' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                    {{ $plan['cta'] }}
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section class="py-20 md:py-28">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 reveal">
            <p class="text-sm font-semibold text-green-700 uppercase tracking-wider mb-3">FAQ</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">Questions frequentes</h2>
        </div>

        <div class="space-y-3 reveal">
            @php
            $faqs = [
                ['q' => 'GreenPilot est-il conforme a la reglementation ICPE ?', 'a' => 'Oui. GreenPilot integre les exigences des rubriques ICPE applicables aux garages automobiles (2712, 2718, 2710). Le score de conformite vous indique en temps reel votre niveau de conformite et les actions correctives a mener.'],
                ['q' => 'Comment fonctionne la synchronisation avec Trackdechets ?', 'a' => 'GreenPilot se connecte directement a l\'API Trackdechets via votre token personnel. Les BSD sont crees, publies et signes automatiquement. En cas d\'erreur reseau, le systeme retente 3 fois avant de vous alerter.'],
                ['q' => 'Puis-je gerer plusieurs garages ?', 'a' => 'Oui. Les plans Standard et Premium permettent de gerer plusieurs etablissements avec des utilisateurs, roles et permissions specifiques a chaque garage.'],
                ['q' => 'Que se passe-t-il a la fin de l\'essai gratuit ?', 'a' => 'Votre compte passe en mode lecture seule. Vos donnees sont conservees et vous pouvez les consulter, mais vous devez souscrire un abonnement pour creer ou modifier des elements. Aucune carte bancaire n\'est requise pendant l\'essai.'],
                ['q' => 'GreenPilot fonctionne-t-il sur mobile ?', 'a' => 'Oui. L\'application est responsive et optimisee pour tablettes et smartphones. Vos equipes peuvent saisir les productions et scanner les QR codes des conteneurs directement depuis l\'atelier.'],
                ['q' => 'Comment sont securisees mes donnees ?', 'a' => 'Vos donnees sont hebergees en France, chiffrees en transit (TLS) et au repos. Les tokens Trackdechets sont chiffres en base de donnees. Des sauvegardes automatiques sont effectuees quotidiennement.'],
            ];
            @endphp

            @foreach($faqs as $faq)
            <div class="faq-item border border-gray-200 rounded-xl bg-white overflow-hidden">
                <button class="faq-header w-full flex items-center justify-between px-6 py-5 text-left">
                    <span class="text-sm font-semibold text-gray-900 pr-4">{{ $faq['q'] }}</span>
                    <svg class="faq-chevron w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div class="faq-body">
                    <div class="px-6 pb-5 text-sm text-gray-600 leading-relaxed">{{ $faq['a'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ CTA FINAL ============ --}}
<section class="py-20 md:py-24" style="background: linear-gradient(135deg, #1b5e20 0%, #2E7D32 50%, #388E3C 100%);">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">Pret a simplifier votre gestion des dechets ?</h2>
        <p class="text-lg text-green-100 mb-8 max-w-xl mx-auto">Rejoignez les garages qui ont deja adopte GreenPilot. Essai gratuit de 14 jours, sans engagement.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="/register" class="inline-flex items-center px-8 py-4 text-base font-semibold text-green-900 bg-white rounded-full hover:bg-green-50 transition-all duration-200 hover:scale-[1.02] shadow-lg">
                Essai gratuit 14 jours
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center px-8 py-4 text-base font-semibold text-white rounded-full border-2 border-white/30 hover:bg-white/10 transition-all duration-200">
                Nous contacter
            </a>
        </div>
    </div>
</section>

@endsection
