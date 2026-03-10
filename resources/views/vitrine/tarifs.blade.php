@extends('vitrine.layout')

@section('title', 'Tarifs - GreenPilot')

@section('content')

{{-- Hero --}}
<section class="pt-32 pb-16" style="background: linear-gradient(180deg, #E8F5E9 0%, #FAFAF8 100%);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-sm font-semibold text-green-700 uppercase tracking-wider mb-3">Tarifs</p>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900">Simple, transparent, sans surprise</h1>
        <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">Essai gratuit de 14 jours, sans carte bancaire. Evoluez au rythme de votre activite.</p>
    </div>
</section>

{{-- Plans --}}
<section class="py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @php
            $plans = [
                [
                    'name' => 'Standard',
                    'price' => '49',
                    'desc' => 'Pour les garages qui veulent etre conformes',
                    'featured' => true,
                    'cta' => 'Essai gratuit 14 jours',
                    'href' => '/register',
                    'sections' => [
                        'Limites' => [
                            ['text' => '1 garage', 'included' => true],
                            ['text' => '5 utilisateurs max', 'included' => true],
                        ],
                        'Gestion des dechets' => [
                            ['text' => 'Types de dechets', 'included' => true],
                            ['text' => 'Conteneurs avec QR codes', 'included' => true],
                            ['text' => 'Saisie des productions', 'included' => true],
                            ['text' => 'Alertes conteneurs', 'included' => true],
                        ],
                        'Reglementaire' => [
                            ['text' => 'Registre des dechets', 'included' => true],
                            ['text' => 'BSD automatises', 'included' => true],
                            ['text' => 'Sync Trackdechets', 'included' => true],
                            ['text' => 'Score conformite ICPE', 'included' => true],
                            ['text' => 'Fiches de securite', 'included' => true],
                        ],
                        'Logistique' => [
                            ['text' => 'Enlevements complets', 'included' => true],
                            ['text' => 'Calendrier', 'included' => true],
                            ['text' => 'Recurrences', 'included' => true],
                            ['text' => 'Bons PDF', 'included' => true],
                        ],
                        'Support' => [
                            ['text' => 'Email', 'included' => true],
                            ['text' => 'Prioritaire', 'included' => true],
                            ['text' => 'Account manager', 'included' => false],
                        ],
                    ],
                ],
                [
                    'name' => 'Premium',
                    'price' => null,
                    'desc' => 'Pour les groupes multi-sites',
                    'featured' => false,
                    'cta' => 'Demander un devis',
                    'href' => '/contact',
                    'sections' => [
                        'Limites' => [
                            ['text' => 'Garages illimites', 'included' => true],
                            ['text' => 'Utilisateurs illimites', 'included' => true],
                        ],
                        'Gestion des dechets' => [
                            ['text' => 'Types de dechets', 'included' => true],
                            ['text' => 'Conteneurs avec QR codes', 'included' => true],
                            ['text' => 'Saisie des productions', 'included' => true],
                            ['text' => 'Alertes conteneurs', 'included' => true],
                        ],
                        'Reglementaire' => [
                            ['text' => 'Registre des dechets', 'included' => true],
                            ['text' => 'BSD automatises', 'included' => true],
                            ['text' => 'Sync Trackdechets', 'included' => true],
                            ['text' => 'Score conformite ICPE', 'included' => true],
                            ['text' => 'Fiches de securite', 'included' => true],
                        ],
                        'Logistique' => [
                            ['text' => 'Enlevements complets', 'included' => true],
                            ['text' => 'Calendrier', 'included' => true],
                            ['text' => 'Recurrences', 'included' => true],
                            ['text' => 'Bons PDF', 'included' => true],
                        ],
                        'Support' => [
                            ['text' => 'Email', 'included' => true],
                            ['text' => 'Prioritaire', 'included' => true],
                            ['text' => 'Account manager dedie', 'included' => true],
                        ],
                    ],
                ],
            ];
            @endphp

            @foreach($plans as $plan)
            <div class="reveal relative rounded-2xl border {{ $plan['featured'] ? 'border-green-600 ring-2 ring-green-600/20 shadow-xl' : 'border-gray-200' }} bg-white flex flex-col overflow-hidden">
                @if($plan['featured'])
                <div class="bg-green-700 text-center py-2">
                    <span class="text-xs font-semibold text-white uppercase tracking-wider">Le plus populaire</span>
                </div>
                @endif
                <div class="p-8 flex-1 flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-gray-900">{{ $plan['name'] }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $plan['desc'] }}</p>
                    </div>
                    <div class="mb-8">
                        @if($plan['price'])
                        <span class="text-5xl font-extrabold text-gray-900">{{ $plan['price'] }}&euro;</span>
                        <span class="text-base text-gray-500"> /mois</span>
                        @else
                        <span class="text-3xl font-extrabold text-gray-900">Sur devis</span>
                        @endif
                    </div>

                    <div class="flex-1 space-y-6">
                        @foreach($plan['sections'] as $sectionName => $items)
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ $sectionName }}</p>
                            <ul class="space-y-2.5">
                                @foreach($items as $item)
                                <li class="flex items-center gap-2.5">
                                    @if($item['included'])
                                    <svg class="w-4.5 h-4.5 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    <span class="text-sm text-gray-700">{{ $item['text'] }}</span>
                                    @else
                                    <svg class="w-4.5 h-4.5 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    <span class="text-sm text-gray-400">{{ $item['text'] }}</span>
                                    @endif
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>

                    <a href="{{ $plan['href'] }}" class="mt-8 block text-center px-6 py-3.5 rounded-full text-sm font-semibold transition-all duration-200 hover:scale-[1.02] {{ $plan['featured'] ? 'bg-green-700 text-white hover:bg-green-800 shadow-lg shadow-green-700/25' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                        {{ $plan['cta'] }}
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ tarifs --}}
<section class="py-20 bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-extrabold text-gray-900 text-center mb-10 reveal">Questions sur les tarifs</h2>
        <div class="space-y-3 reveal">
            @php
            $faqsTarifs = [
                ['q' => 'Puis-je changer de plan a tout moment ?', 'a' => 'Oui. Vous pouvez upgrader ou downgrader votre plan a tout moment. Le changement prend effet immediatement et la facturation est proratisee.'],
                ['q' => 'L\'essai gratuit est-il vraiment sans engagement ?', 'a' => 'Absolument. Aucune carte bancaire n\'est demandee. A la fin des 14 jours, votre compte passe en mode lecture seule si vous ne souscrivez pas. Vos donnees sont conservees.'],
                ['q' => 'Que se passe-t-il apres l\'essai gratuit ?', 'a' => 'Votre compte passe en mode lecture seule : vous pouvez toujours consulter vos donnees, mais vous ne pouvez plus creer ou modifier des elements. Souscrivez a tout moment pour retrouver un acces complet.'],
                ['q' => 'Y a-t-il des frais caches ?', 'a' => 'Non. Le prix affiche inclut tout : hebergement, mises a jour, support et stockage des donnees. Pas de frais de mise en service ni de frais par BSD.'],
                ['q' => 'Proposez-vous un tarif annuel ?', 'a' => 'Oui. Le paiement annuel vous fait beneficier de 2 mois offerts. Contactez-nous pour en savoir plus.'],
            ];
            @endphp
            @foreach($faqsTarifs as $faq)
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

{{-- CTA --}}
<section class="py-20" style="background: linear-gradient(135deg, #1b5e20 0%, #2E7D32 50%, #388E3C 100%);">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center reveal">
        <h2 class="text-3xl font-extrabold text-white mb-4">Besoin d'un devis personnalise ?</h2>
        <p class="text-lg text-green-100 mb-8">Notre equipe est a votre disposition pour construire une offre adaptee a votre structure.</p>
        <a href="{{ route('contact') }}" class="inline-flex items-center px-8 py-4 text-base font-semibold text-green-900 bg-white rounded-full hover:bg-green-50 transition-all duration-200 hover:scale-[1.02] shadow-lg">
            Nous contacter
            <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
        </a>
    </div>
</section>

@endsection
