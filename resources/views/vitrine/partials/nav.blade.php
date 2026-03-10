<nav id="main-nav" class="fixed top-0 left-0 right-0 z-50 nav-transparent transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-600 to-green-700 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 20c0 0 2-7 7-11c2-1.3 5-2 7-1c-1 4-3.5 7-7 9c-2 1.2-4 1.5-5 1.5" />
                        <path stroke-linecap="round" stroke-width="1.5" d="M12 9c0 0-1 4.5-1 7.5c0 1.5.5 2.5 1 3.5" />
                        <path stroke-linecap="round" stroke-width="1.5" d="M9 14c2 0 3.5.5 4.5 2" />
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-900">Green<span class="text-green-700">Pilot</span></span>
            </a>

            {{-- Desktop menu --}}
            <div class="hidden md:flex items-center gap-8">
                <a href="{{ route('fonctionnalites') }}" class="text-sm font-medium text-gray-600 hover:text-green-700 transition-colors">Fonctionnalites</a>
                <a href="{{ route('tarifs') }}" class="text-sm font-medium text-gray-600 hover:text-green-700 transition-colors">Tarifs</a>
                <a href="{{ route('contact') }}" class="text-sm font-medium text-gray-600 hover:text-green-700 transition-colors">Contact</a>
            </div>

            {{-- CTA --}}
            <div class="hidden md:flex items-center gap-3">
                <a href="/dashboard" id="nav-dashboard-desktop" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-green-700 rounded-full hover:bg-green-800 transition-all duration-200 hover:scale-[1.02] shadow-sm" style="display:none;">
                    Tableau de bord
                    <svg class="w-4 h-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </a>
                <a href="/login" id="nav-login-desktop" class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors px-4 py-2">Se connecter</a>
                <a href="/register" id="nav-register-desktop" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-green-700 rounded-full hover:bg-green-800 transition-all duration-200 hover:scale-[1.02] shadow-sm">
                    Essai gratuit
                    <svg class="w-4 h-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </a>
            </div>

            {{-- Mobile hamburger --}}
            <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div id="mobile-menu" class="mobile-menu md:hidden bg-white border-t border-gray-100">
        <div class="px-4 py-4 space-y-3">
            <a href="{{ route('fonctionnalites') }}" class="block text-sm font-medium text-gray-700 py-2">Fonctionnalites</a>
            <a href="{{ route('tarifs') }}" class="block text-sm font-medium text-gray-700 py-2">Tarifs</a>
            <a href="{{ route('contact') }}" class="block text-sm font-medium text-gray-700 py-2">Contact</a>
            <div class="pt-3 border-t border-gray-100 flex flex-col gap-2">
                <a href="/dashboard" id="nav-dashboard-mobile" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-green-700 rounded-full" style="display:none;">Tableau de bord</a>
                <a href="/login" id="nav-login-mobile" class="text-sm font-medium text-gray-700 py-2">Se connecter</a>
                <a href="/register" id="nav-register-mobile" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-green-700 rounded-full">Essai gratuit</a>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var token = localStorage.getItem('token');
    if (token) {
        document.getElementById('nav-dashboard-desktop').style.display = '';
        document.getElementById('nav-login-desktop').style.display = 'none';
        document.getElementById('nav-register-desktop').style.display = 'none';
        document.getElementById('nav-dashboard-mobile').style.display = '';
        document.getElementById('nav-login-mobile').style.display = 'none';
        document.getElementById('nav-register-mobile').style.display = 'none';
    }
});
</script>
