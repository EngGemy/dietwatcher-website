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