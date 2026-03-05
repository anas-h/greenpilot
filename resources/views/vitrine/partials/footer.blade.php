<footer class="bg-gray-900 text-gray-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
            {{-- Brand --}}
            <div class="md:col-span-1">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 20c0 0 2-7 7-11c2-1.3 5-2 7-1c-1 4-3.5 7-7 9c-2 1.2-4 1.5-5 1.5" />
                            <path stroke-linecap="round" stroke-width="1.5" d="M12 9c0 0-1 4.5-1 7.5c0 1.5.5 2.5 1 3.5" />
                            <path stroke-linecap="round" stroke-width="1.5" d="M9 14c2 0 3.5.5 4.5 2" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-white">Green<span class="text-green-400">Pilot</span></span>
                </div>
                <p class="text-sm text-gray-400 leading-relaxed">La solution complete de gestion des dechets pour les professionnels de l'automobile.</p>
            </div>

            {{-- Produit --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Produit</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('fonctionnalites') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Fonctionnalites</a></li>
                    <li><a href="{{ route('tarifs') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Tarifs</a></li>
                    <li><a href="/login" class="text-sm text-gray-400 hover:text-white transition-colors">Se connecter</a></li>
                </ul>
            </div>

            {{-- Ressources --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Ressources</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('contact') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Contact</a></li>
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">Centre d'aide</a></li>
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">Blog</a></li>
                </ul>
            </div>

            {{-- Legal --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Legal</h4>
                <ul class="space-y-3">
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">Mentions legales</a></li>
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">Politique de confidentialite</a></li>
                    <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">CGU</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-500">&copy; {{ date('Y') }} GreenPilot. Tous droits reserves.</p>
            <div class="flex items-center gap-4">
                <a href="#" class="text-gray-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                </a>
                <a href="#" class="text-gray-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                </a>
            </div>
        </div>
    </div>
</footer>
