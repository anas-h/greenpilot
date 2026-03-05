@extends('vitrine.layout')

@section('title', 'Contact - GreenPilot')

@section('content')

<section class="pt-32 pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
            {{-- Left: info --}}
            <div>
                <p class="text-sm font-semibold text-green-700 uppercase tracking-wider mb-3">Contact</p>
                <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mb-6">Parlons de votre projet</h1>
                <p class="text-lg text-gray-600 leading-relaxed mb-10">Une question, une demande de demo ou un devis personnalise ? Notre equipe vous repond sous 24h.</p>

                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Email</h3>
                            <p class="text-sm text-gray-500 mt-0.5">contact@greenpilot.fr</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Telephone</h3>
                            <p class="text-sm text-gray-500 mt-0.5">01 23 45 67 89</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Horaires</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Lundi - Vendredi, 9h - 18h</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: form --}}
            <div>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
                    @if(session('success'))
                    <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200">
                        <ul class="text-sm text-red-700 space-y-1">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('contact.send') }}" method="POST" class="space-y-5">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="nom" class="block text-sm font-medium text-gray-700 mb-1.5">Nom *</label>
                                <input type="text" name="nom" id="nom" value="{{ old('nom') }}" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600 transition-colors">
                            </div>
                            <div>
                                <label for="prenom" class="block text-sm font-medium text-gray-700 mb-1.5">Prenom *</label>
                                <input type="text" name="prenom" id="prenom" value="{{ old('prenom') }}" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600 transition-colors">
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email *</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600 transition-colors">
                        </div>

                        <div>
                            <label for="telephone" class="block text-sm font-medium text-gray-700 mb-1.5">Telephone</label>
                            <input type="tel" name="telephone" id="telephone" value="{{ old('telephone') }}" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600 transition-colors">
                        </div>

                        <div>
                            <label for="entreprise" class="block text-sm font-medium text-gray-700 mb-1.5">Nom de l'entreprise</label>
                            <input type="text" name="entreprise" id="entreprise" value="{{ old('entreprise') }}" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600 transition-colors">
                        </div>

                        <div>
                            <label for="sujet" class="block text-sm font-medium text-gray-700 mb-1.5">Sujet *</label>
                            <select name="sujet" id="sujet" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600 transition-colors bg-white">
                                <option value="">Choisir un sujet</option>
                                <option value="demo" {{ old('sujet') === 'demo' ? 'selected' : '' }}>Demande de demo</option>
                                <option value="devis" {{ old('sujet') === 'devis' ? 'selected' : '' }}>Devis personnalise</option>
                                <option value="support" {{ old('sujet') === 'support' ? 'selected' : '' }}>Support technique</option>
                                <option value="partenariat" {{ old('sujet') === 'partenariat' ? 'selected' : '' }}>Partenariat</option>
                                <option value="autre" {{ old('sujet') === 'autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1.5">Message *</label>
                            <textarea name="message" id="message" rows="5" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600 transition-colors resize-none">{{ old('message') }}</textarea>
                        </div>

                        <button type="submit" class="w-full px-6 py-3.5 text-sm font-semibold text-white bg-green-700 rounded-full hover:bg-green-800 transition-all duration-200 hover:scale-[1.01] shadow-lg shadow-green-700/25">
                            Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
