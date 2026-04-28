<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('account.dashboard_title')) — {{ __('Diet Watchers') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/styles/main.css') }}" />

    @livewireStyles
    <style>
        [lang="ar"], [dir="rtl"] {
            font-family: 'Almarai', ui-sans-serif, system-ui, sans-serif;
        }
        body { background:#F6F7FB; color:#1F2937; }
        .acc-shell { display:grid; grid-template-columns: 280px 1fr; min-height:100vh; }
        @media (max-width: 960px) { .acc-shell { grid-template-columns: 1fr; } }

        .acc-sidebar {
            background:#0F172A;
            color:#E5E7EB;
            display:flex;
            flex-direction:column;
            position:sticky;
            top:0;
            height:100vh;
            padding:1.5rem 1.1rem;
            gap:1.2rem;
            overflow-y:auto;
            z-index:20;
        }
        @media (max-width: 960px) {
            .acc-sidebar {
                position:fixed;
                inset-inline-start:0;
                inset-inline-end:auto;
                height:100vh;
                width:280px;
                transform:translateX(-100%);
                transition:transform .25s ease;
            }
            [dir="rtl"] .acc-sidebar { transform:translateX(100%); }
            .acc-sidebar.is-open { transform:translateX(0); }
        }

        .acc-logo {
            display:flex; align-items:center; gap:.6rem;
            padding:.4rem .4rem 1rem;
            border-bottom:1px solid rgba(255,255,255,.08);
        }
        .acc-logo__badge {
            width:40px; height:40px; border-radius:12px;
            background:linear-gradient(135deg,#3B82F6,#22D3EE);
            display:flex; align-items:center; justify-content:center;
            box-shadow:0 8px 25px -8px rgba(59,130,246,.7);
            color:#fff; font-weight:800;
        }
        .acc-logo__title { font-weight:800; font-size:1.05rem; color:#fff; }
        .acc-logo__sub { font-size:.72rem; opacity:.6; letter-spacing:.05em; }

        .acc-nav { display:flex; flex-direction:column; gap:.15rem; }
        .acc-nav__group-title {
            font-size:.72rem; letter-spacing:.1em; text-transform:uppercase;
            color:#64748B; font-weight:700; padding:0 .7rem; margin:.5rem 0 .3rem;
        }
        .acc-nav a {
            display:flex; align-items:center; gap:.7rem;
            padding:.65rem .8rem;
            border-radius:10px;
            color:#CBD5E1;
            font-size:.92rem;
            font-weight:500;
            transition:background .15s, color .15s;
            text-decoration:none;
        }
        .acc-nav a:hover {
            background:rgba(255,255,255,.06);
            color:#fff;
        }
        .acc-nav a.is-active {
            background:linear-gradient(120deg, rgba(59,130,246,.18), rgba(59,130,246,.04));
            color:#fff;
            box-shadow:inset 3px 0 0 #3B82F6;
        }
        [dir="rtl"] .acc-nav a.is-active {
            box-shadow:inset -3px 0 0 #3B82F6;
        }
        .acc-nav svg { width:18px; height:18px; flex-shrink:0; opacity:.85; }

        .acc-main { min-width:0; display:flex; flex-direction:column; }
        .acc-topbar {
            background:#fff; border-bottom:1px solid #E5E7EB;
            padding:.9rem 1.25rem;
            display:flex; align-items:center; justify-content:space-between; gap:1rem;
            position:sticky; top:0; z-index:10;
        }
        .acc-topbar__title { font-weight:700; font-size:1.1rem; color:#111827; }
        .acc-topbar__user { display:flex; align-items:center; gap:.6rem; }
        .acc-avatar {
            width:36px; height:36px; border-radius:50%;
            background:linear-gradient(135deg,#3B82F6,#1D4ED8);
            color:#fff; font-weight:700;
            display:flex; align-items:center; justify-content:center;
        }
        .acc-logout {
            background:#F3F4F6; border:1px solid #E5E7EB;
            padding:.45rem .9rem; border-radius:8px;
            font-size:.82rem; font-weight:600; color:#374151;
            cursor:pointer; transition:background .15s;
            text-decoration:none;
            display:inline-flex; align-items:center; gap:.4rem;
        }
        .acc-logout:hover { background:#E5E7EB; color:#111827; }

        .acc-content { padding: 1.5rem 1.25rem; flex:1; max-width: 1400px; width:100%; margin: 0 auto; }
        @media (min-width: 768px) { .acc-content { padding: 2rem; } }

        .acc-card {
            background:#fff;
            border-radius:16px;
            box-shadow:0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04);
            border:1px solid #EEF0F4;
        }
        .acc-card-head { padding:1.1rem 1.25rem; border-bottom:1px solid #F1F5F9; font-weight:700; color:#0F172A; display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
        .acc-card-body { padding:1.25rem; }

        .acc-stat {
            display:flex; flex-direction:column; gap:.4rem;
            padding:1.1rem 1.25rem;
            border-radius:16px;
            background:#fff;
            border:1px solid #EEF0F4;
            box-shadow:0 1px 3px rgba(15,23,42,.05);
        }
        .acc-stat__label { font-size:.82rem; color:#64748B; font-weight:600; }
        .acc-stat__value { font-size:1.55rem; font-weight:800; color:#0F172A; font-variant-numeric:tabular-nums; }
        .acc-stat__meta { font-size:.78rem; color:#94A3B8; }

        .acc-chip {
            display:inline-flex; align-items:center; gap:.35rem;
            padding:.2rem .6rem; border-radius:999px;
            font-size:.72rem; font-weight:700;
            background:#EEF2FF; color:#3730A3;
        }
        .acc-chip--success { background:#ECFDF5; color:#047857; }
        .acc-chip--warn    { background:#FFFBEB; color:#92400E; }
        .acc-chip--danger  { background:#FEF2F2; color:#B91C1C; }
        .acc-chip--muted   { background:#F1F5F9; color:#475569; }

        .acc-btn {
            display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
            padding:.55rem .95rem; border-radius:10px;
            font-weight:600; font-size:.86rem;
            border:1px solid transparent; cursor:pointer;
            transition:background .15s, border-color .15s, color .15s, transform .1s;
            text-decoration:none;
        }
        .acc-btn:active { transform:translateY(1px); }
        .acc-btn--primary { background:linear-gradient(120deg, #3B82F6 0%, #2563EB 100%); color:#fff; box-shadow:0 8px 18px -8px rgba(37,99,235,.5); }
        .acc-btn--primary:hover { filter:brightness(1.05); }
        .acc-btn--ghost   { background:#fff; color:#2563EB; border-color:#DBEAFE; }
        .acc-btn--ghost:hover { background:#EFF6FF; }
        .acc-btn--muted   { background:#F8FAFC; color:#334155; border-color:#E2E8F0; }
        .acc-btn--muted:hover { background:#EEF2F7; }
        .acc-btn--danger  { background:#fff; color:#B91C1C; border-color:#FECACA; }
        .acc-btn--danger:hover { background:#FEF2F2; }
        .acc-btn--sm      { padding:.35rem .7rem; font-size:.78rem; }

        .acc-empty {
            padding:2.5rem 1.25rem; text-align:center;
            color:#64748B; font-size:.92rem;
        }
        .acc-empty__icon {
            width:56px; height:56px; margin:0 auto .7rem;
            border-radius:50%; background:#F1F5F9;
            display:flex; align-items:center; justify-content:center;
            color:#94A3B8;
        }

        .acc-table { width:100%; border-collapse:collapse; }
        .acc-table th, .acc-table td { padding:.8rem 1rem; text-align:start; font-size:.88rem; }
        .acc-table thead th { color:#64748B; font-weight:600; font-size:.76rem; text-transform:uppercase; letter-spacing:.05em; background:#F8FAFC; border-bottom:1px solid #EEF0F4; }
        .acc-table tbody tr { border-bottom:1px solid #F1F5F9; }
        .acc-table tbody tr:last-child { border-bottom:0; }
        .acc-table tbody tr:hover { background:#F8FAFC; }

        .acc-tab-group { display:flex; gap:.35rem; padding:.25rem; background:#F1F5F9; border-radius:12px; }
        .acc-tab { padding:.45rem .9rem; border-radius:10px; font-weight:600; font-size:.82rem; color:#64748B; cursor:pointer; background:transparent; border:0; transition:background .15s, color .15s; }
        .acc-tab.is-active { background:#fff; color:#0F172A; box-shadow:0 1px 2px rgba(15,23,42,.08); }

        .acc-mobile-toggle {
            display:none;
            background:none; border:0;
            padding:.35rem;
            color:#111827; cursor:pointer;
        }
        @media (max-width: 960px) {
            .acc-mobile-toggle { display:inline-flex; }
            .acc-backdrop {
                position:fixed; inset:0; background:rgba(15,23,42,.5); z-index:15;
            }
        }

        [x-cloak] { display:none !important; }
    </style>

    @stack('styles')
</head>
<body>
<div class="acc-shell"
     x-data="{ sidebarOpen: false }"
     @keydown.escape.window="sidebarOpen = false">

    @php
        $active = trim((string) request()->route()?->getName());
        $isActive = fn(string $name) => $active === $name;
        $profile = (array) session('external_api_profile', []);
        $displayName = trim((string) ($profile['name'] ?? '')) ?: __('account.customer');
        $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
    @endphp

    {{-- Sidebar --}}
    <aside class="acc-sidebar" :class="{ 'is-open': sidebarOpen }">
        <div class="acc-logo">
            <div class="acc-logo__badge">DW</div>
            <div>
                <div class="acc-logo__title">{{ __('Diet Watchers') }}</div>
                <div class="acc-logo__sub">{{ __('account.my_account') }}</div>
            </div>
        </div>

        <nav class="acc-nav">
            <a href="{{ route('account.dashboard') }}" class="{{ $isActive('account.dashboard') ? 'is-active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.5 1.5 0 012.121 0L22.28 12M4.5 9.75v10.125a1.125 1.125 0 001.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125a1.125 1.125 0 001.125-1.125V9.75"/></svg>
                {{ __('account.dashboard') }}
            </a>

            <div class="acc-nav__group-title">{{ __('account.group_subscriptions') }}</div>
            <a href="{{ route('account.subscriptions.index') }}" class="{{ str_starts_with($active, 'account.subscriptions') ? 'is-active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/></svg>
                {{ __('account.subscriptions') }}
            </a>
            <a href="{{ route('account.orders.index') }}" class="{{ str_starts_with($active, 'account.orders') ? 'is-active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                {{ __('account.orders') }}
            </a>

            <div class="acc-nav__group-title">{{ __('account.group_finance') }}</div>
            <a href="{{ route('account.wallet') }}" class="{{ $isActive('account.wallet') ? 'is-active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/></svg>
                {{ __('account.wallet') }}
            </a>

            <div class="acc-nav__group-title">{{ __('account.group_settings') }}</div>
            <a href="{{ route('account.profile') }}" class="{{ $isActive('account.profile') ? 'is-active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                {{ __('account.profile') }}
            </a>

            <div class="mt-auto pt-6 border-t border-white/10">
                <form action="{{ route('account.logout') }}" method="POST" class="px-1">
                    @csrf
                    <button type="submit" class="acc-nav-logout flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-rose-300 hover:bg-rose-500/10 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        {{ __('account.logout') }}
                    </button>
                </form>
            </div>
        </nav>
    </aside>

    <div class="acc-backdrop" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak></div>

    {{-- Main --}}
    <div class="acc-main">
        <header class="acc-topbar">
            <div class="flex items-center gap-3">
                <button type="button" class="acc-mobile-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                    </svg>
                </button>
                <div class="acc-topbar__title">@yield('page-title', __('account.dashboard'))</div>
            </div>
            <div class="acc-topbar__user">
                <a href="{{ route('home') }}" class="acc-btn acc-btn--muted acc-btn--sm" title="{{ __('account.back_to_site') }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                    <span class="hidden sm:inline">{{ __('account.back_to_site') }}</span>
                </a>
                <div class="acc-avatar" title="{{ $displayName }}">{{ $initial }}</div>
            </div>
        </header>

        <main class="acc-content">
            @if(session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
            {{ $slot ?? '' }}
        </main>
    </div>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
