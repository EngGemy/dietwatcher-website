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
            background:#fff;
            color:#334155;
            display:flex;
            flex-direction:column;
            position:sticky;
            top:0;
            height:100vh;
            padding:1.25rem 1rem;
            gap:1rem;
            overflow-y:auto;
            z-index:20;
            border-inline-end:1px solid #E5E7EB;
            box-shadow:0 1px 3px rgba(15,23,42,.04);
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
            display:flex; align-items:center; gap:.75rem;
            padding:.25rem .25rem 1rem;
            border-bottom:1px solid #EEF0F4;
        }
        .acc-logo img {
            height:40px; width:auto; max-width:140px; object-fit:contain;
        }
        .acc-logo__title { font-weight:800; font-size:1rem; color:#111827; }
        .acc-logo__sub { font-size:.72rem; color:#64748B; letter-spacing:.04em; }

        .acc-nav { display:flex; flex-direction:column; gap:.15rem; }
        .acc-nav__group-title {
            font-size:.72rem; letter-spacing:.08em; text-transform:uppercase;
            color:#94A3B8; font-weight:700; padding:0 .7rem; margin:.5rem 0 .3rem;
        }
        .acc-nav a {
            display:flex; align-items:center; gap:.7rem;
            padding:.65rem .8rem;
            border-radius:12px;
            color:#475569;
            font-size:.92rem;
            font-weight:600;
            transition:background .15s, color .15s;
            text-decoration:none;
        }
        .acc-nav a:hover {
            background:#F0F9FF;
            color:#279ff9;
        }
        .acc-nav a.is-active {
            background:linear-gradient(120deg, rgba(39,159,249,.12), rgba(63,181,54,.08));
            color:#279ff9;
            box-shadow:inset 3px 0 0 #279ff9;
        }
        [dir="rtl"] .acc-nav a.is-active {
            box-shadow:inset -3px 0 0 #3B82F6;
        }
        .acc-nav svg { width:18px; height:18px; flex-shrink:0; opacity:.85; }
        .acc-nav__badge {
            margin-inline-start:auto;
            min-width:1.25rem; height:1.25rem; padding:0 .35rem;
            border-radius:999px; background:#EF4444; color:#fff;
            font-size:.68rem; font-weight:800; line-height:1.25rem; text-align:center;
        }

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
            background:linear-gradient(135deg,#279ff9,#3fb536);
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
        .acc-btn--primary { background:linear-gradient(135deg, #279ff9 0%, #3fb536 100%); color:#fff; box-shadow:0 8px 18px -8px rgba(39,159,249,.45); }
        .acc-btn--primary:hover { filter:brightness(1.04); }
        .acc-btn--ghost   { background:#fff; color:#279ff9; border-color:#BFDBFE; }
        .acc-btn--ghost:hover { background:#F0F9FF; }
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
        .acc-tab.is-active { background:#fff; color:#279ff9; box-shadow:0 1px 2px rgba(15,23,42,.08); }

        .acc-record-list { display:flex; flex-direction:column; gap:.75rem; padding:1rem; }
        .acc-record {
            border:1px solid #EEF0F4; border-radius:14px; padding:1rem 1.1rem;
            background:#fff; transition:border-color .15s, box-shadow .15s;
        }
        .acc-record:hover { border-color:#BFDBFE; box-shadow:0 4px 14px rgba(39,159,249,.08); }
        .acc-record__head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; margin-bottom:.75rem; }
        .acc-record__id { font-weight:800; color:#0F172A; font-size:.95rem; letter-spacing:.01em; }
        .acc-record__meta { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.65rem .85rem; }
        @media (min-width: 640px) { .acc-record__meta { grid-template-columns:repeat(4, minmax(0, 1fr)); } }
        .acc-record__field label { display:block; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94A3B8; margin-bottom:.15rem; }
        .acc-record__field span { font-size:.88rem; font-weight:600; color:#334155; }
        .acc-record__actions { margin-top:.85rem; display:flex; justify-content:flex-end; gap:.5rem; }

        .acc-address-card { padding:1.1rem 1.25rem; }
        .acc-address-card + .acc-address-card { border-top:1px solid #F1F5F9; }

        .acc-address-grid {
            display:grid;
            gap:1rem;
        }
        @media (min-width: 768px) {
            .acc-address-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        }
        .acc-address-tile {
            position:relative;
            background:#fff;
            border:1px solid #E8EDF3;
            border-radius:18px;
            padding:1.15rem 1.2rem 1rem;
            overflow:hidden;
            box-shadow:0 1px 2px rgba(15,23,42,.04);
            transition:border-color .2s, box-shadow .2s, transform .15s;
        }
        .acc-address-tile:hover {
            border-color:#BFDBFE;
            box-shadow:0 10px 28px -12px rgba(39,159,249,.22);
        }
        .acc-address-tile.is-active .acc-address-tile__accent {
            background:linear-gradient(180deg, #3fb536 0%, #279ff9 100%);
        }
        .acc-address-tile.is-locked {
            border-color:#FDE68A;
            background:linear-gradient(180deg, #fff 0%, #FFFBEB 120%);
        }
        .acc-address-tile.is-locked .acc-address-tile__accent {
            background:linear-gradient(180deg, #F59E0B 0%, #F97316 100%);
        }
        .acc-address-tile--skeleton { min-height:220px; }
        .acc-address-tile__accent {
            position:absolute; top:0; inset-inline-start:0;
            width:4px; height:100%;
            background:#CBD5E1;
            border-radius:18px 0 0 18px;
        }
        [dir="rtl"] .acc-address-tile__accent { border-radius:0 18px 18px 0; }
        .acc-address-tile__head {
            display:flex; align-items:flex-start; gap:.75rem;
            margin-bottom:.65rem;
        }
        .acc-address-tile__icon {
            width:40px; height:40px; border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            background:linear-gradient(135deg, rgba(39,159,249,.12), rgba(63,181,54,.1));
            color:#279ff9; flex-shrink:0;
        }
        .acc-address-tile__icon svg { width:20px; height:20px; }
        .acc-address-tile__title {
            font-size:1rem; font-weight:800; color:#0F172A; line-height:1.3;
        }
        .acc-address-tile__district {
            font-size:.78rem; color:#64748B; margin-top:.15rem;
        }
        .acc-address-tile__desc {
            font-size:.86rem; color:#475569; line-height:1.55;
            margin-bottom:.85rem;
            padding-inline-start:.15rem;
        }
        .acc-address-section {
            margin-top:.75rem;
            padding:.75rem .85rem;
            border-radius:14px;
            background:#F8FAFC;
            border:1px solid #EEF2F7;
        }
        .acc-address-section__label {
            display:flex; align-items:center; gap:.45rem;
            font-size:.72rem; font-weight:800;
            text-transform:uppercase; letter-spacing:.06em;
            color:#64748B; margin-bottom:.55rem;
        }
        .acc-address-section__icon { width:15px; height:15px; color:#279ff9; }
        .acc-address-section__value {
            font-size:.88rem; font-weight:700; color:#1E293B;
        }
        .acc-address-section__empty {
            font-size:.8rem; color:#94A3B8;
        }
        .acc-time-slots { display:flex; flex-wrap:wrap; gap:.4rem; }
        .acc-time-slot {
            display:inline-flex; align-items:center;
            padding:.35rem .65rem; border-radius:999px;
            font-size:.76rem; font-weight:700;
            border:1px solid #E2E8F0; background:#fff; color:#64748B;
        }
        .acc-time-slot.is-selected {
            background:linear-gradient(135deg, rgba(39,159,249,.14), rgba(63,181,54,.12));
            border-color:#7DD3FC; color:#0369A1;
            box-shadow:0 0 0 1px rgba(39,159,249,.12);
        }
        .acc-time-picker { display:flex; flex-direction:column; gap:.45rem; }
        .acc-time-picker__btn {
            display:flex; align-items:center; gap:.65rem;
            width:100%; text-align:start;
            padding:.7rem .85rem; border-radius:12px;
            border:1px solid #E2E8F0; background:#fff;
            font-size:.84rem; font-weight:600; color:#334155;
            cursor:pointer; transition:all .15s;
        }
        .acc-time-picker__btn:hover { border-color:#93C5FD; background:#F8FAFC; }
        .acc-time-picker__btn.is-selected {
            border-color:#279ff9;
            background:linear-gradient(135deg, rgba(39,159,249,.1), rgba(63,181,54,.08));
            color:#0369A1;
            box-shadow:0 0 0 1px rgba(39,159,249,.15);
        }
        .acc-time-picker__radio {
            width:16px; height:16px; border-radius:50%;
            border:2px solid #CBD5E1; flex-shrink:0;
            position:relative;
        }
        .acc-time-picker__btn.is-selected .acc-time-picker__radio {
            border-color:#279ff9;
            background:#279ff9;
            box-shadow:inset 0 0 0 3px #fff;
        }
        .acc-address-lock {
            display:flex; align-items:flex-start; gap:.5rem;
            margin-top:.85rem; padding:.65rem .75rem;
            border-radius:12px; background:#FFFBEB;
            border:1px solid #FDE68A;
            font-size:.78rem; font-weight:600; color:#92400E; line-height:1.45;
        }
        .acc-address-tile__actions {
            display:flex; flex-wrap:wrap; gap:.5rem;
            margin-top:.9rem; padding-top:.85rem;
            border-top:1px dashed #E2E8F0;
        }
        .acc-day-row { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.55rem; }
        .acc-day-pill {
            display:inline-flex; align-items:center; justify-content:center;
            min-width:2.4rem; padding:.28rem .55rem; border-radius:999px;
            font-size:.72rem; font-weight:700; border:1px solid #E2E8F0;
            background:#F8FAFC; color:#94A3B8;
        }
        .acc-day-pill.is-selected { background:#EFF6FF; border-color:#93C5FD; color:#1D4ED8; }
        .acc-day-picker { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.5rem; }
        @media (min-width: 480px) { .acc-day-picker { grid-template-columns:repeat(4, minmax(0, 1fr)); } }
        .acc-day-picker__btn {
            border:1px solid #E2E8F0; border-radius:12px; padding:.65rem .5rem;
            font-size:.82rem; font-weight:700; color:#64748B; background:#fff;
            cursor:pointer; transition:all .15s;
        }
        .acc-day-picker__btn.is-selected {
            background:linear-gradient(135deg, rgba(39,159,249,.12), rgba(63,181,54,.1));
            border-color:#279ff9; color:#0369A1;
            box-shadow:0 0 0 1px rgba(39,159,249,.15);
        }
        .acc-day-picker__btn:hover:not(.is-selected) { background:#F8FAFC; border-color:#CBD5E1; }

        .acc-desktop-only { display:none; }
        @media (min-width: 768px) { .acc-desktop-only { display:block; } .acc-mobile-only { display:none !important; } }
        @media (max-width: 767px) { .acc-mobile-only { display:block; } }

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

        .acc-skeleton {
            background:linear-gradient(90deg, #F1F5F9 25%, #E2E8F0 50%, #F1F5F9 75%);
            background-size:200% 100%;
            animation:acc-shimmer 1.2s ease-in-out infinite;
            border-radius:10px;
        }
        .acc-skeleton-line { height:14px; margin-bottom:.65rem; }
        .acc-skeleton-block { height:72px; margin-bottom:.75rem; }
        @keyframes acc-shimmer {
            0% { background-position:200% 0; }
            100% { background-position:-200% 0; }
        }

        .acc-app-banner {
            margin-bottom:1.25rem;
            border-radius:16px;
            background:linear-gradient(135deg, #279ff9 0%, #3fb536 100%);
            color:#fff;
            box-shadow:0 10px 30px -12px rgba(39,159,249,.45);
        }
        .acc-app-banner__inner {
            display:flex; align-items:center; justify-content:space-between;
            gap:1rem; flex-wrap:wrap; padding:1rem 1.15rem;
        }
        .acc-app-banner__content { display:flex; align-items:center; gap:.85rem; min-width:0; }
        .acc-app-banner__logo { border-radius:12px; background:#fff; padding:.35rem; flex-shrink:0; }
        .acc-app-banner__title { font-weight:800; font-size:1rem; line-height:1.3; }
        .acc-app-banner__subtitle { font-size:.82rem; opacity:.92; margin-top:.15rem; }
        .acc-app-banner__actions { display:flex; align-items:center; gap:.55rem; flex-shrink:0; }
        .acc-app-banner__dismiss {
            background:rgba(255,255,255,.18); border:0; color:#fff;
            width:32px; height:32px; border-radius:999px; cursor:pointer;
            display:inline-flex; align-items:center; justify-content:center;
        }
        .acc-app-banner__dismiss:hover { background:rgba(255,255,255,.28); }
        .acc-app-store-link { display:inline-flex; line-height:0; opacity:.95; transition:opacity .15s, transform .15s; }
        .acc-app-store-link:hover { opacity:1; transform:translateY(-1px); }

        .acc-app-card {
            margin-top:.75rem; padding:.9rem; border-radius:14px;
            background:linear-gradient(135deg, rgba(39,159,249,.08), rgba(63,181,54,.08));
            border:1px solid rgba(39,159,249,.15);
        }
        .acc-app-card__icon { margin-bottom:.5rem; }
        .acc-app-card__title { font-size:.82rem; font-weight:800; color:#0F172A; line-height:1.35; }
        .acc-app-card__text { font-size:.72rem; color:#64748B; margin-top:.25rem; line-height:1.45; }
        .acc-app-card__stores { display:flex; gap:.45rem; margin-top:.65rem; flex-wrap:wrap; }

        .acc-tips-ticker {
            display:flex; align-items:stretch;
            background:linear-gradient(90deg, #0F172A 0%, #1E3A5F 55%, #14532D 100%);
            color:#F8FAFC;
            border-bottom:1px solid rgba(255,255,255,.08);
            overflow:hidden;
            min-height:42px;
        }
        .acc-tips-ticker__badge {
            display:flex; align-items:center; gap:.4rem; flex-shrink:0;
            padding:.55rem .85rem;
            background:rgba(255,255,255,.08);
            font-size:.7rem; font-weight:800; letter-spacing:.04em;
            text-transform:uppercase;
            border-inline-end:1px solid rgba(255,255,255,.1);
        }
        .acc-tips-ticker__ai {
            font-size:.62rem; font-weight:700;
            padding:.12rem .4rem; border-radius:999px;
            background:rgba(39,159,249,.25); color:#BAE6FD;
            text-transform:none; letter-spacing:0;
        }
        .acc-tips-ticker__viewport { flex:1; overflow:hidden; position:relative; }
        .acc-tips-ticker__viewport::before,
        .acc-tips-ticker__viewport::after {
            content:''; position:absolute; top:0; bottom:0; width:2rem; z-index:1; pointer-events:none;
        }
        .acc-tips-ticker__viewport::before {
            inset-inline-start:0;
            background:linear-gradient(to right, #0F172A, transparent);
        }
        [dir="rtl"] .acc-tips-ticker__viewport::before {
            background:linear-gradient(to left, #0F172A, transparent);
        }
        .acc-tips-ticker__viewport::after {
            inset-inline-end:0;
            background:linear-gradient(to left, #14532D, transparent);
        }
        [dir="rtl"] .acc-tips-ticker__viewport::after {
            background:linear-gradient(to right, #14532D, transparent);
        }
        .acc-tips-ticker__track {
            display:flex; align-items:center; gap:2.5rem;
            width:max-content;
            padding:.65rem 1rem;
            animation:acc-tips-marquee 55s linear infinite;
        }
        [dir="rtl"] .acc-tips-ticker__track { animation-name:acc-tips-marquee-rtl; }
        .acc-tips-ticker:hover .acc-tips-ticker__track { animation-play-state:paused; }
        .acc-tips-ticker__item {
            display:inline-flex; align-items:center; gap:.55rem;
            font-size:.8rem; font-weight:600; white-space:nowrap; color:#E2E8F0;
        }
        .acc-tips-ticker__dot {
            width:6px; height:6px; border-radius:50%;
            background:linear-gradient(135deg, #279ff9, #3fb536);
            flex-shrink:0;
        }
        @keyframes acc-tips-marquee {
            0% { transform:translateX(0); }
            100% { transform:translateX(-50%); }
        }
        @keyframes acc-tips-marquee-rtl {
            0% { transform:translateX(0); }
            100% { transform:translateX(50%); }
        }

        .acc-dash-hero {
            position:relative; overflow:hidden;
            border-radius:20px;
            padding:1.5rem 1.35rem;
            background:linear-gradient(135deg, #0F172A 0%, #1E40AF 48%, #166534 100%);
            color:#fff;
            border:1px solid rgba(255,255,255,.12);
            box-shadow:0 20px 40px -24px rgba(15,23,42,.55);
        }
        .acc-dash-hero::after {
            content:''; position:absolute; inset:0;
            background:radial-gradient(circle at 85% 15%, rgba(63,181,54,.35), transparent 45%),
                        radial-gradient(circle at 10% 90%, rgba(39,159,249,.3), transparent 40%);
            pointer-events:none;
        }
        .acc-dash-hero > * { position:relative; z-index:1; }
        .acc-dash-hero__eyebrow { font-size:.75rem; font-weight:700; color:#BFDBFE; letter-spacing:.06em; text-transform:uppercase; }
        .acc-dash-hero__title { font-size:1.65rem; font-weight:800; margin-top:.35rem; line-height:1.25; }
        .acc-dash-hero__sub { font-size:.88rem; color:#CBD5E1; margin-top:.45rem; max-width:36rem; }
        .acc-dash-stat-grid { display:grid; gap:.85rem; grid-template-columns:repeat(2, minmax(0,1fr)); }
        @media (min-width: 1024px) { .acc-dash-stat-grid { grid-template-columns:repeat(4, minmax(0,1fr)); } }
        .acc-dash-stat {
            border-radius:16px; padding:1rem 1.05rem;
            background:#fff; border:1px solid #E8EDF3;
            box-shadow:0 1px 2px rgba(15,23,42,.04);
            transition:transform .15s, box-shadow .15s, border-color .15s;
        }
        .acc-dash-stat:hover { transform:translateY(-2px); border-color:#BFDBFE; box-shadow:0 12px 24px -16px rgba(39,159,249,.35); }
        .acc-dash-stat__icon {
            width:36px; height:36px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            margin-bottom:.55rem;
        }
        .acc-dash-stat__icon--blue { background:#EFF6FF; color:#2563EB; }
        .acc-dash-stat__icon--green { background:#ECFDF5; color:#059669; }
        .acc-dash-stat__icon--amber { background:#FFFBEB; color:#D97706; }
        .acc-dash-stat__icon--violet { background:#F5F3FF; color:#7C3AED; }
        .acc-dash-stat__label { font-size:.74rem; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:.05em; }
        .acc-dash-stat__value { font-size:1.35rem; font-weight:800; color:#0F172A; margin-top:.25rem; line-height:1.2; }
        .acc-dash-stat__meta { margin-top:.35rem; font-size:.74rem; }
        .acc-dash-stat__meta a { color:#279ff9; font-weight:700; text-decoration:none; }
        .acc-dash-alert {
            display:flex; gap:.75rem; align-items:flex-start;
            padding:.85rem 1rem; border-radius:14px;
            background:#FFFBEB; border:1px solid #FDE68A;
            color:#92400E; font-size:.84rem; line-height:1.5;
        }
        .acc-dash-quick {
            display:grid; gap:.75rem;
            grid-template-columns:repeat(2, minmax(0,1fr));
        }
        @media (min-width: 768px) { .acc-dash-quick { grid-template-columns:repeat(4, minmax(0,1fr)); } }
        .acc-dash-quick__item {
            display:flex; flex-direction:column; align-items:flex-start; gap:.45rem;
            padding:1rem; border-radius:14px; background:#fff;
            border:1px solid #E8EDF3; text-decoration:none; color:inherit;
            transition:border-color .15s, box-shadow .15s, transform .15s;
        }
        .acc-dash-quick__item:hover { border-color:#93C5FD; box-shadow:0 8px 20px -12px rgba(39,159,249,.3); transform:translateY(-1px); }
        .acc-dash-quick__icon {
            width:38px; height:38px; border-radius:11px;
            display:flex; align-items:center; justify-content:center;
        }
        .acc-dash-panel { border-radius:18px; border:1px solid #E8EDF3; background:#fff; overflow:hidden; }
        .acc-dash-panel__head {
            padding:1rem 1.15rem; border-bottom:1px solid #F1F5F9;
            display:flex; align-items:center; justify-content:space-between; gap:.75rem;
            font-weight:800; color:#0F172A;
        }
        .acc-dash-panel__body { padding:1.1rem 1.15rem; }

        .acc-wallet-hero {
            position:relative; overflow:hidden;
            border-radius:22px;
            padding:1.35rem 1.4rem 1.2rem;
            background:linear-gradient(135deg, #0B1F4A 0%, #1D4ED8 42%, #0EA5E9 78%, #14B8A6 100%);
            color:#fff;
            box-shadow:0 24px 48px -28px rgba(29,78,216,.65);
            border:1px solid rgba(255,255,255,.14);
            min-height:190px;
        }
        .acc-wallet-hero__glow {
            position:absolute; inset:-20% -10% auto auto;
            width:280px; height:280px; border-radius:50%;
            background:radial-gradient(circle, rgba(255,255,255,.22), transparent 68%);
            pointer-events:none;
        }
        .acc-wallet-hero__grid {
            position:absolute; inset:0; opacity:.12;
            background-image:linear-gradient(rgba(255,255,255,.7) 1px, transparent 1px),
                             linear-gradient(90deg, rgba(255,255,255,.7) 1px, transparent 1px);
            background-size:24px 24px;
            pointer-events:none;
        }
        .acc-wallet-hero__inner {
            position:relative; z-index:1;
            display:flex; flex-direction:column; justify-content:space-between;
            min-height:158px;
        }
        .acc-wallet-hero__chip {
            display:flex; gap:.35rem; margin-bottom:1rem;
        }
        .acc-wallet-hero__chip span {
            width:34px; height:26px; border-radius:8px;
            background:linear-gradient(135deg, #FDE68A, #F59E0B);
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.35);
        }
        .acc-wallet-hero__label {
            font-size:.78rem; font-weight:700; letter-spacing:.08em;
            text-transform:uppercase; color:rgba(255,255,255,.78);
        }
        .acc-wallet-hero__amount {
            display:flex; align-items:baseline; gap:.5rem;
            margin-top:.35rem;
        }
        .acc-wallet-hero__amount .sar-symbol { color:rgba(255,255,255,.88) !important; }
        .acc-wallet-hero__skeleton {
            display:inline-block; width:9rem; height:2.6rem; border-radius:12px;
            background:rgba(255,255,255,.18);
            animation:acc-shimmer 1.2s ease-in-out infinite;
        }
        .acc-wallet-hero__meta {
            margin-top:.55rem; font-size:.76rem; color:rgba(255,255,255,.72);
        }
        .acc-wallet-hero__brand {
            position:absolute; top:0; inset-inline-end:0;
            display:flex; flex-direction:column; align-items:flex-end; gap:.15rem;
        }
        .acc-wallet-hero__logo {
            width:42px; height:42px; border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            font-size:.82rem; font-weight:900; letter-spacing:.04em;
            background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22);
        }
        .acc-wallet-hero__network {
            font-size:.62rem; font-weight:800; letter-spacing:.22em;
            color:rgba(255,255,255,.55);
        }
        .acc-wallet-mini {
            border-radius:16px; padding:.95rem 1rem;
            background:#fff; border:1px solid #E8EDF3;
            box-shadow:0 1px 2px rgba(15,23,42,.04);
        }
        .acc-wallet-mini__label {
            font-size:.72rem; font-weight:700; color:#64748B;
            text-transform:uppercase; letter-spacing:.05em;
        }
        .acc-wallet-mini__value {
            margin-top:.35rem; font-size:1.15rem; font-weight:800;
        }
        .acc-wallet-panel {
            border-radius:18px; overflow:hidden;
            background:#fff; border:1px solid #E8EDF3;
            box-shadow:0 1px 3px rgba(15,23,42,.05);
        }
        .acc-wallet-panel__head {
            padding:1rem 1.15rem; border-bottom:1px solid #F1F5F9;
            display:flex; flex-direction:column; gap:.85rem;
        }
        @media (min-width: 768px) {
            .acc-wallet-panel__head {
                flex-direction:row; align-items:center; justify-content:space-between;
            }
        }
        .acc-wallet-panel__title { font-size:1rem; font-weight:800; color:#0F172A; }
        .acc-wallet-panel__hint { font-size:.78rem; color:#64748B; margin-top:.15rem; }
        .acc-wallet-panel__body { padding:.35rem 0; }
        .acc-wallet-panel__empty { padding:2rem 1.15rem; text-align:center; }
        .acc-wallet-tx-list { list-style:none; margin:0; padding:0; }
        .acc-wallet-tx {
            display:flex; align-items:flex-start; gap:.85rem;
            padding:1rem 1.15rem;
            border-bottom:1px solid #F1F5F9;
            transition:background .15s;
        }
        .acc-wallet-tx:last-child { border-bottom:0; }
        .acc-wallet-tx:hover { background:#F8FAFC; }
        .acc-wallet-tx__icon {
            width:42px; height:42px; border-radius:14px;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .acc-wallet-tx__icon svg { width:18px; height:18px; }
        .acc-wallet-tx.is-credit .acc-wallet-tx__icon {
            background:linear-gradient(135deg, #ECFDF5, #D1FAE5);
            color:#059669;
        }
        .acc-wallet-tx.is-debit .acc-wallet-tx__icon {
            background:linear-gradient(135deg, #FFF1F2, #FFE4E6);
            color:#E11D48;
        }
        .acc-wallet-tx__main { flex:1; min-width:0; }
        .acc-wallet-tx__row {
            display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem;
        }
        .acc-wallet-tx__row--meta { margin-top:.4rem; align-items:center; }
        .acc-wallet-tx__title {
            font-size:.92rem; font-weight:700; color:#0F172A; line-height:1.35;
        }
        .acc-wallet-tx__amount {
            display:flex; align-items:baseline; gap:.2rem; flex-shrink:0;
            font-size:.95rem; white-space:nowrap;
        }
        .acc-wallet-tx__badge {
            display:inline-flex; align-items:center;
            padding:.18rem .5rem; border-radius:999px;
            font-size:.68rem; font-weight:800; letter-spacing:.03em;
            background:#F1F5F9; color:#475569;
        }
        .acc-wallet-tx.is-credit .acc-wallet-tx__badge { background:#ECFDF5; color:#047857; }
        .acc-wallet-tx.is-debit .acc-wallet-tx__badge { background:#FFF1F2; color:#BE123C; }
        .acc-wallet-tx__date { font-size:.72rem; color:#94A3B8; }
    </style>

    @stack('styles')
</head>
<body>
<div class="acc-shell"
     x-data="{ sidebarOpen: false }"
     @keydown.escape.window="sidebarOpen = false">

    @php
        use App\Services\AccountApiService;

        $active = trim((string) request()->route()?->getName());
        $isActive = fn(string $name) => $active === $name;
        $profile = (array) session('external_api_profile', []);
        $displayName = trim((string) ($profile['name'] ?? '')) ?: __('account.customer');
        $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
        $unreadNotifications = app(AccountApiService::class)->unreadNotificationCount();
        $pageTitle = match (true) {
            str_starts_with($active, 'account.orders') => __('account.my_orders'),
            str_starts_with($active, 'account.invoices') => __('account.invoices'),
            str_starts_with($active, 'account.notifications') => __('account.notifications'),
            str_starts_with($active, 'account.addresses') => __('account.my_addresses'),
            str_starts_with($active, 'account.subscriptions') => __('account.subscriptions'),
            $active === 'account.wallet' => __('account.wallet'),
            $active === 'account.profile' => __('account.profile'),
            default => __('account.dashboard'),
        };
    @endphp

    {{-- Sidebar --}}
    <aside class="acc-sidebar" :class="{ 'is-open': sidebarOpen }">
        <div class="acc-logo">
            <img src="{{ $siteLogo }}" alt="{{ $siteName ?? __('Diet Watchers') }}" decoding="async" />
            <div class="hidden sm:block">
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
            <a href="{{ route('account.notifications.index') }}" class="{{ str_starts_with($active, 'account.notifications') ? 'is-active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                {{ __('account.notifications') }}
                @if($unreadNotifications > 0)
                    <span class="acc-nav__badge">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                @endif
            </a>
            <a href="{{ route('account.invoices.index') }}" class="{{ str_starts_with($active, 'account.invoices') ? 'is-active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                {{ __('account.invoices') }}
            </a>

            <div class="acc-nav__group-title">{{ __('account.group_settings') }}</div>
            <a href="{{ route('account.addresses.index') }}" class="{{ str_starts_with($active, 'account.addresses') ? 'is-active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                {{ __('account.my_addresses') }}
            </a>
            <a href="{{ route('account.profile') }}" class="{{ $isActive('account.profile') ? 'is-active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                {{ __('account.profile') }}
            </a>

            <div class="mt-auto pt-4 border-t border-gray-100">
                <x-account.app-control-banner variant="sidebar" />

                <form action="{{ route('account.logout') }}" method="POST" class="px-1 mt-3">
                    @csrf
                    <button type="submit" class="acc-nav-logout flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition">
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
                <div class="acc-topbar__title">{{ $pageTitle }}</div>
            </div>
            <div class="acc-topbar__user">
                <a href="{{ route('home') }}" class="acc-btn acc-btn--muted acc-btn--sm" title="{{ __('account.back_to_site') }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                    <span class="hidden sm:inline">{{ __('account.back_to_site') }}</span>
                </a>
                <div class="acc-avatar" title="{{ $displayName }}">{{ $initial }}</div>
            </div>
        </header>

        <x-account.daily-tips-ticker />

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

            <x-account.app-control-banner />

            @yield('content')
            {{ $slot ?? '' }}
        </main>
    </div>
</div>

<x-ai-assistant-fab />

@livewireScripts
@stack('scripts')
</body>
</html>
