<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('account.login_title') }} — {{ __('Diet Watchers') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/styles/main.css') }}" />

    @livewireStyles
    <style>
        [lang="ar"], [dir="rtl"] {
            font-family: 'Almarai', ui-sans-serif, system-ui, sans-serif;
        }
        .acc-auth-bg {
            background:
                radial-gradient(1200px 600px at 20% -10%, rgba(59,130,246,.18), transparent 60%),
                radial-gradient(900px 500px at 100% 110%, rgba(16,185,129,.16), transparent 60%),
                linear-gradient(180deg,#F9FAFB 0%, #EEF2FF 100%);
            min-height: 100vh;
        }
        .acc-card {
            background:#fff;
            border:1px solid rgba(15,23,42,.06);
            border-radius:24px;
            box-shadow:0 30px 80px -30px rgba(15,23,42,.25), 0 8px 30px -12px rgba(15,23,42,.12);
        }
        .acc-input {
            width:100%;
            padding:.85rem 1rem;
            border:1.5px solid #E5E7EB;
            border-radius:12px;
            font-size:1rem;
            outline:none;
            transition:border-color .2s, box-shadow .2s, background .2s;
            background:#F9FAFB;
        }
        .acc-input:focus {
            border-color:#3B82F6;
            background:#fff;
            box-shadow:0 0 0 4px rgba(59,130,246,.12);
        }
        .acc-label { font-size:.84rem; font-weight:600; color:#374151; margin-bottom:.4rem; display:block; }
        .acc-btn {
            width:100%;
            padding:.9rem 1.1rem;
            border-radius:12px;
            background:linear-gradient(120deg, #3B82F6 0%, #2563EB 100%);
            color:#fff;
            font-weight:700;
            font-size:1rem;
            border:none;
            cursor:pointer;
            transition:transform .15s, box-shadow .2s, filter .2s;
            box-shadow:0 10px 25px -10px rgba(37,99,235,.55);
        }
        .acc-btn:hover:not(:disabled) { transform:translateY(-1px); filter:brightness(1.05); }
        .acc-btn:disabled { opacity:.55; cursor:not-allowed; }
        .acc-btn--ghost {
            background:transparent;
            color:#2563EB;
            box-shadow:none;
            font-weight:600;
            padding:.4rem .6rem;
            display:inline-flex;
            align-items:center;
            gap:.3rem;
        }
        .acc-btn--ghost:hover:not(:disabled) { filter:none; transform:none; text-decoration:underline; }
        .acc-otp {
            letter-spacing:.5em;
            text-align:center;
            font-size:1.3rem;
            font-weight:700;
            font-variant-numeric:tabular-nums;
        }
        [dir="rtl"] .acc-otp { letter-spacing:.5em; }
        .acc-err { color:#B91C1C; font-size:.86rem; margin-top:.4rem; }
        .acc-ok  { color:#047857; font-size:.86rem; margin-top:.4rem; }
        .acc-meta-link { font-size:.86rem; color:#6B7280; }
        .acc-meta-link a { color:#2563EB; font-weight:600; }
    </style>
</head>
<body class="acc-auth-bg">
<main class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-2 text-gray-600 hover:text-gray-900">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-sm font-semibold">{{ __('Diet Watchers') }}</span>
        </a>

        {{ $slot ?? '' }}
        @yield('content')
    </div>
</main>
@livewireScripts
</body>
</html>
