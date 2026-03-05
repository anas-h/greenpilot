<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="GreenPilot - Solution de gestion des dechets pour garages automobiles. Conformite ICPE, BSD Trackdechets, suivi en temps reel.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GreenPilot - Gestion des dechets automobile')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; color: #1a1a1a; background: #FAFAF8; }

        /* Nav */
        .nav-transparent { background: transparent; }
        .nav-solid { background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); box-shadow: 0 1px 3px rgba(0,0,0,0.06); }

        /* Scroll animations */
        .reveal { opacity: 0; transform: translateY(32px); transition: opacity 0.7s ease, transform 0.7s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        /* Marquee */
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        .marquee-track { display: flex; animation: marquee 40s linear infinite; }
        .marquee-track:hover { animation-play-state: paused; }
        .marquee-fade-l { position: absolute; left: 0; top: 0; bottom: 0; width: 80px; background: linear-gradient(to right, #FAFAF8, transparent); z-index: 2; pointer-events: none; }
        .marquee-fade-r { position: absolute; right: 0; top: 0; bottom: 0; width: 80px; background: linear-gradient(to left, #FAFAF8, transparent); z-index: 2; pointer-events: none; }

        /* Accordion */
        .faq-body { max-height: 0; overflow: hidden; transition: max-height 0.35s ease; }
        .faq-item.open .faq-body { max-height: 300px; }
        .faq-item.open .faq-chevron { transform: rotate(180deg); }
        .faq-chevron { transition: transform 0.3s ease; }

        /* Mobile menu */
        .mobile-menu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .mobile-menu.open { max-height: 400px; }
    </style>
</head>
<body class="antialiased">

    @include('vitrine.partials.nav')

    <main>
        @yield('content')
    </main>

    @include('vitrine.partials.footer')

    <script>
        // Nav scroll effect
        const nav = document.getElementById('main-nav');
        const heroHeight = 80;
        function updateNav() {
            if (window.scrollY > heroHeight) {
                nav.classList.remove('nav-transparent');
                nav.classList.add('nav-solid');
            } else {
                nav.classList.add('nav-transparent');
                nav.classList.remove('nav-solid');
            }
        }
        window.addEventListener('scroll', updateNav, { passive: true });
        updateNav();

        // Scroll reveal
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        // FAQ accordion
        document.querySelectorAll('.faq-item').forEach(item => {
            item.querySelector('.faq-header').addEventListener('click', () => {
                item.classList.toggle('open');
            });
        });

        // Mobile menu
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if (menuBtn) {
            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('open');
            });
        }
    </script>
</body>
</html>
