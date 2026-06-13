@props([
    'variant' => 'page',
])

@php
    $playStoreUrl = $playStoreUrl ?? 'https://play.google.com/store/apps/details?id=com.diet.watchers.app';
    $appStoreUrl = $appStoreUrl ?? 'https://apps.apple.com/sa/app/diet-watchers-eat-healthy/id6460976436';
    $siteLogo = $siteLogo ?? asset('assets/images/logo.png');
    $cookieName = 'acc_app_banner_dismissed';
@endphp

@if($variant === 'sidebar')
    <div class="acc-app-card">
        <div class="acc-app-card__icon">
            <img src="{{ $siteLogo }}" alt="{{ __('Diet Watchers') }}" width="36" height="36" class="rounded-lg bg-white/90 p-1" />
        </div>
        <p class="acc-app-card__title">{{ __('account.app_control_title') }}</p>
        <p class="acc-app-card__text">{{ __('account.app_control_subtitle') }}</p>
        <div class="acc-app-card__stores">
            <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener" class="acc-app-store-link" aria-label="Google Play">
                <img src="{{ asset('assets/images/play.png') }}" alt="Google Play" height="32" />
            </a>
            <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener" class="acc-app-store-link" aria-label="App Store">
                <img src="{{ asset('assets/images/store.png') }}" alt="App Store" height="32" />
            </a>
        </div>
    </div>
@else
    <div
        class="acc-app-banner"
        x-data="{ dismissed: document.cookie.includes('{{ $cookieName }}=1') }"
        x-show="!dismissed"
        x-cloak
    >
        <div class="acc-app-banner__inner">
            <div class="acc-app-banner__content">
                <img src="{{ $siteLogo }}" alt="" class="acc-app-banner__logo" width="44" height="44" />
                <div class="min-w-0">
                    <p class="acc-app-banner__title">{{ __('account.app_control_title') }}</p>
                    <p class="acc-app-banner__subtitle">{{ __('account.app_control_subtitle') }}</p>
                </div>
            </div>
            <div class="acc-app-banner__actions">
                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener" class="acc-app-store-link" aria-label="Google Play">
                    <img src="{{ asset('assets/images/play.png') }}" alt="Google Play" height="36" />
                </a>
                <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener" class="acc-app-store-link" aria-label="App Store">
                    <img src="{{ asset('assets/images/store.png') }}" alt="App Store" height="36" />
                </a>
                <button
                    type="button"
                    class="acc-app-banner__dismiss"
                    @click="document.cookie='{{ $cookieName }}=1;path=/;max-age=2592000;SameSite=Lax'; dismissed=true"
                    aria-label="{{ __('account.app_control_dismiss') }}"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>
@endif
