<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('Diet Watchers'))</title>

    {{-- Almarai Font for Arabic --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/styles/main.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/styles/duration-pills.css') }}" />

    {{-- Livewire Styles --}}
    @livewireStyles
    
    {{-- Hide Livewire loading indicators --}}
    <style>
        .lw-loading-bar,
        [wire\:loading].lw-loading,
        .livewire-loading {
            display: none !important;
        }
    </style>

    {{-- Apply Almarai font to Arabic --}}
    <style>
        [lang="ar"],
        [dir="rtl"] {
            font-family: 'Almarai', ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        }
    </style>

    {{-- ─── Premium wow styles: CTA, app-store badges, social icons, ambient ─── --}}
    <style>
        /* Global smooth scroll + reduced-motion respect */
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }

        /* ─── Premium CTA buttons: gradient border + shine + idle pulse + arrow slide ─── */
        .btn--primary {
            position: relative;
            isolation: isolate;
            background-image: linear-gradient(120deg, #3B82F6 0%, #2563EB 50%, #1D4ED8 100%);
            background-size: 200% 100%;
            animation: ctaGradient 6s ease-in-out infinite, ctaIdlePulse 3.2s ease-in-out 1s infinite;
        }
        @keyframes ctaGradient {
            0%,100% { background-position: 0% 0%; }
            50%     { background-position: 100% 0%; }
        }
        @keyframes ctaIdlePulse {
            0%,100% { box-shadow: 0 8px 20px -10px rgba(37,99,235,.4), 0 0 0 0 rgba(37,99,235,.25); }
            50%     { box-shadow: 0 12px 30px -8px rgba(37,99,235,.55), 0 0 0 8px rgba(37,99,235,0); }
        }
        .btn--primary:hover {
            transform: translateY(-3px) scale(1.02);
            filter: brightness(1.06) saturate(1.15);
            animation-play-state: paused;
        }

        /* Arrow slide on buttons that want it — opt-in via .btn--arrow */
        .btn--arrow::after {
            content: "→";
            display: inline-block;
            margin-inline-start: .5rem;
            transition: transform .35s cubic-bezier(.16,1,.3,1), letter-spacing .35s;
            will-change: transform;
        }
        [dir="rtl"] .btn--arrow::after { content: "←"; }
        .btn--arrow:hover::after { transform: translateX(6px); letter-spacing: .1em; }
        [dir="rtl"] .btn--arrow:hover::after { transform: translateX(-6px); }

        /* ─── App-store badges: neon glow pulse, magnetic float, icon tilt ─── */
        .hero-app-badge, .app-store-badge {
            display: inline-block;
            position: relative;
            transition: transform .35s cubic-bezier(.16,1,.3,1), filter .35s ease;
            will-change: transform;
        }
        .hero-app-badge img, .app-store-badge img {
            transition: transform .4s cubic-bezier(.16,1,.3,1), filter .35s ease;
        }
        .hero-app-badge::before, .app-store-badge::before {
            content: "";
            position: absolute;
            inset: -8px;
            border-radius: 14px;
            background: radial-gradient(ellipse at center, rgba(59,130,246,.45), transparent 70%);
            opacity: 0;
            filter: blur(14px);
            transition: opacity .4s ease;
            z-index: -1;
        }
        .hero-app-badge:hover, .app-store-badge:hover { transform: translateY(-4px) scale(1.04); }
        .hero-app-badge:hover::before, .app-store-badge:hover::before { opacity: 1; animation: badgeNeon 1.8s ease-in-out infinite; }
        .hero-app-badge:hover img, .app-store-badge:hover img { transform: rotate(-2deg); filter: drop-shadow(0 10px 18px rgba(0,0,0,.25)); }
        @keyframes badgeNeon {
            0%,100% { filter: blur(14px); opacity: .6; }
            50%     { filter: blur(18px); opacity: 1; }
        }

        /* ─── Social icons (footer): bounce-in, neon glow, magnetic lift, tooltip ─── */
        .footer__social-link--wow {
            position: relative;
            overflow: visible;
            transition: transform .35s cubic-bezier(.16,1,.3,1), background .35s ease, color .35s ease, box-shadow .35s ease;
            opacity: 0;
            transform: translateY(14px) scale(.8);
            animation: socialBounceIn .7s cubic-bezier(.34,1.56,.64,1) forwards;
            will-change: transform;
        }
        .footer__social-link--wow:nth-child(1) { animation-delay: .15s; }
        .footer__social-link--wow:nth-child(2) { animation-delay: .28s; }
        .footer__social-link--wow:nth-child(3) { animation-delay: .41s; }
        .footer__social-link--wow:nth-child(4) { animation-delay: .54s; }
        @keyframes socialBounceIn {
            0%   { opacity: 0; transform: translateY(14px) scale(.8); }
            60%  { opacity: 1; transform: translateY(-4px) scale(1.08); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .footer__social-link--wow .icon { transition: transform .4s cubic-bezier(.16,1,.3,1); }
        .footer__social-link--wow:hover { transform: translateY(-4px) scale(1.12); }
        .footer__social-link--wow:hover .icon { transform: rotate(-8deg); }

        /* Platform-specific hover colors + neon glow */
        .footer__social-link--wow[data-platform="instagram"]:hover { color: #E1306C; box-shadow: 0 0 0 0 rgba(225,48,108,.6), 0 0 25px 4px rgba(225,48,108,.55); }
        .footer__social-link--wow[data-platform="facebook"]:hover  { color: #1877F2; box-shadow: 0 0 25px 4px rgba(24,119,242,.55); }
        .footer__social-link--wow[data-platform="twitter"]:hover   { color: #1DA1F2; box-shadow: 0 0 25px 4px rgba(29,161,242,.55); }
        .footer__social-link--wow[data-platform="youtube"]:hover   { color: #FF0000; box-shadow: 0 0 25px 4px rgba(255,0,0,.5); }

        /* Tooltip */
        .footer__social-link--wow::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%) translateY(6px);
            background: rgba(15,23,42,.95);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease, transform .25s ease;
            z-index: 20;
        }
        .footer__social-link--wow::before {
            content: "";
            position: absolute;
            bottom: calc(100% + 5px);
            left: 50%;
            border: 5px solid transparent;
            border-top-color: rgba(15,23,42,.95);
            transform: translateX(-50%) translateY(6px);
            opacity: 0;
            transition: opacity .25s ease, transform .25s ease;
        }
        .footer__social-link--wow:hover::after,
        .footer__social-link--wow:hover::before {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Footer columns cinematic reveal */
        .footer__column, .footer__brand {
            opacity: 0;
            transform: translateY(20px);
            animation: footerUp .8s cubic-bezier(.16,1,.3,1) forwards;
        }
        .footer__brand       { animation-delay: .05s; }
        .footer__column:nth-of-type(1) { animation-delay: .15s; }
        .footer__column:nth-of-type(2) { animation-delay: .25s; }
        .footer__column:nth-of-type(3) { animation-delay: .35s; }
        @keyframes footerUp { to { opacity: 1; transform: translateY(0); } }

        /* Footer links underline reveal */
        .footer__link {
            position: relative;
            transition: color .3s ease, transform .3s ease;
        }
        .footer__link::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: -2px;
            height: 1px;
            background: currentColor;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .35s cubic-bezier(.16,1,.3,1);
        }
        [dir="rtl"] .footer__link::after { transform-origin: right; }
        .footer__link:hover::after { transform: scaleX(1); }
        .footer__link:hover { transform: translateX(3px); }
        [dir="rtl"] .footer__link:hover { transform: translateX(-3px); }

        /* ─── Ambient floating blurred blobs (hero ambient) ─── */
        .wow-ambient {
            pointer-events: none;
            position: absolute;
            inset: 0;
            overflow: hidden;
            z-index: 0;
        }
        .wow-ambient__blob {
            position: absolute;
            width: 420px; height: 420px;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .45;
            mix-blend-mode: multiply;
            will-change: transform;
            animation: blobDrift 18s ease-in-out infinite;
        }
        .wow-ambient__blob--a { top: -120px; left: -80px;  background: #86EFAC; }
        .wow-ambient__blob--b { bottom: -140px; right: -60px; background: #93C5FD; animation-delay: -6s; animation-duration: 22s; }
        .wow-ambient__blob--c { top: 30%; left: 40%; width: 300px; height: 300px; background: #FDE68A; animation-delay: -12s; animation-duration: 26s; }
        @keyframes blobDrift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%      { transform: translate(40px, -30px) scale(1.08); }
            66%      { transform: translate(-30px, 40px) scale(.95); }
        }
    </style>

    {{-- ─── Wow button interactions (ripple + press + shine) ─── --}}
    <style>
        .btn {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            transition: transform .18s cubic-bezier(.16,1,.3,1),
                        box-shadow .25s ease,
                        filter .25s ease;
            will-change: transform;
        }
        .btn::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,.45) 50%, transparent 70%);
            transform: translateX(-120%);
            transition: transform .7s cubic-bezier(.22,1,.36,1);
            pointer-events: none;
            z-index: 1;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -12px rgba(37, 99, 235, .35);
        }
        .btn:hover::before { transform: translateX(120%); }
        .btn:active {
            transform: translateY(0) scale(.96);
            transition-duration: .08s;
        }
        .btn--primary:hover { filter: brightness(1.05) saturate(1.1); }

        /* Ripple effect */
        .btn .btn-ripple {
            position: absolute;
            border-radius: 9999px;
            transform: scale(0);
            animation: btnRipple .65s cubic-bezier(.22,1,.36,1) forwards;
            background: rgba(255,255,255,.55);
            pointer-events: none;
            z-index: 2;
            mix-blend-mode: screen;
        }
        @keyframes btnRipple {
            to { transform: scale(3); opacity: 0; }
        }

        /* Press burst on click */
        @keyframes btnBurst {
            0%   { box-shadow: 0 0 0 0 rgba(59,130,246,.55); }
            100% { box-shadow: 0 0 0 18px rgba(59,130,246,0); }
        }
        .btn.is-pressed { animation: btnBurst .55s ease-out; }

        /* Touch-friendly tap on mobile */
        @media (hover: none) {
            .btn:active { transform: scale(.94); }
        }

        @media (prefers-reduced-motion: reduce) {
            .btn, .btn::before, .btn .btn-ripple { animation: none !important; transition: none !important; }
        }
    </style>

    @stack('styles')
    @stack('head')
</head>

<body>
    {{-- Notifications --}}
    <x-notifications />
    
    <div id="app">
        @include('partials.header')

        <main class="main">
            @yield('content')
        </main>

        @include('partials.footer')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.min.js"></script>

    {{-- ─── Wow button ripple + press burst ─── --}}
    <script>
        (function() {
            document.addEventListener('pointerdown', function(e) {
                var btn = e.target.closest('.btn');
                if (!btn) return;
                var rect = btn.getBoundingClientRect();
                var size = Math.max(rect.width, rect.height);
                var ripple = document.createElement('span');
                ripple.className = 'btn-ripple';
                ripple.style.width  = size + 'px';
                ripple.style.height = size + 'px';
                ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                ripple.style.top  = (e.clientY - rect.top  - size / 2) + 'px';
                btn.appendChild(ripple);
                btn.classList.add('is-pressed');
                setTimeout(function() { ripple.remove(); }, 700);
                setTimeout(function() { btn.classList.remove('is-pressed'); }, 600);
            }, { passive: true });
        })();
    </script>

    {{-- Page scripts first so globals (e.g. checkoutPage) exist before Alpine boots via Livewire --}}
    @stack('scripts')

    {{-- Livewire (includes Alpine.js) --}}
    @livewireScripts
</body>

</html>