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

    {{-- Livewire Scripts --}}
    @livewireScripts

    @stack('scripts')
</body>

</html>