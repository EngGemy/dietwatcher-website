@extends('layouts.app')

@section('title', ($success ? __('payment.success_title') : __('payment.failed_title')) . ' | ' . $siteName)

@section('content')
<section class="bg-gray-200 pt-10 pb-28">
    <div class="container max-w-[1080px]">
        @if($success)
            @php
                $isAr = app()->getLocale() === 'ar';
                $firstItem = collect($payment->cart_items ?? [])->first();
                $planTitle = $firstItem['name'] ?? __('payment.subscription_plan');
                $itemOptions = $firstItem['options'] ?? [];
                $mealType = trim((string) ($itemOptions['mealType'] ?? ''));
                if ($mealType === '') {
                    $mealType = __('payment.mixed');
                }
                $calories = $itemOptions['calories'] ?? null;
                $durationRaw = strtolower((string) ($payment->duration ?? ''));
                $durationMap = [
                    'once' => __('Once'),
                    'weekly' => __('Weekly'),
                    'monthly' => __('Monthly'),
                    '3months' => __('3 Months'),
                ];
                $durationLabel = $durationMap[$durationRaw] ?? ($payment->duration ? __(ucfirst($payment->duration)) : __('Monthly'));
                $startDateLabel = $payment->start_date ?: now()->format('d M Y');
                $deliveryLabel = $payment->delivery_type === 'pickup' ? __('Pickup from Branch') : __('Home Delivery');
                $caloriesLabel = $calories ? ((string) $calories . ' ' . __('kcal')) : __('payment.as_selected');
            @endphp
            <div class="confirm-page space-y-8" data-confirm-page>
                <header class="confirm-hero text-center md:text-start">
                    <img src="{{ asset('assets/images/icons/check-success.svg') }}" class="confirm-check mb-3 size-24 md:size-32" alt="" />
                    <h2 class="section-header__title">{{ __('payment.confirmed_title') }}</h2>
                    <p class="section-header__desc max-w-none">
                        {{ __('payment.confirmed_subtitle') }}
                    </p>
                </header>

                <div class="confirm-summary rounded-md bg-white p-6 shadow-sm">
                    <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <h3 class="text-xl font-bold text-gray-900 md:text-2xl">{{ $planTitle }}</h3>
                        <p class="text-lg md:text-xl">
                            {{ __('payment.total_paid') }}:
                            <span class="text-green font-semibold">SAR {{ number_format($payment->amount_in_sar, 2) }}</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-y-5 md:grid-cols-2 md:gap-x-10">
                        <div class="space-y-3">
                            <div class="confirm-row">
                                <span class="confirm-row__label">{{ __('payment.meal_type') }}</span>
                                <span class="confirm-row__value">{{ $mealType }}</span>
                            </div>
                            <div class="confirm-row">
                                <span class="confirm-row__label">{{ __('Calories') }}</span>
                                <span class="confirm-row__value" dir="ltr">{{ $caloriesLabel }}</span>
                            </div>
                            <div class="confirm-row">
                                <span class="confirm-row__label">{{ __('payment.order_number_label') }}</span>
                                <span class="confirm-row__value font-mono" dir="ltr">{{ $payment->order_number }}</span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="confirm-row">
                                <span class="confirm-row__label">{{ __('Duration') }}</span>
                                <span class="confirm-row__value">{{ $durationLabel }}</span>
                            </div>
                            <div class="confirm-row">
                                <span class="confirm-row__label">{{ __('Start Date') }}</span>
                                <span class="confirm-row__value" dir="ltr">{{ $startDateLabel }}</span>
                            </div>
                            <div class="confirm-row">
                                <span class="confirm-row__label">{{ __('Delivery') }}</span>
                                <span class="confirm-row__value">{{ $deliveryLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="confirm-alert bg-red/20 flex gap-4 rounded-md px-6 py-4">
                    <svg class="size-8 shrink-0 text-black"><use href="{{ asset('assets/images/icons/sprite.svg#messages') }}"></use></svg>
                    <p class="text-lg">{{ __('payment.confirmed_notice') }}</p>
                </div>

                <div class="confirm-app bg-yellow grid items-center gap-6 rounded-md px-8 py-10 text-white md:grid-cols-2 md:px-20">
                    <div>
                        <h2 class="section-header__title">{{ __('payment.manage_meals_title') }}</h2>
                        <p class="section-header__desc text-white !max-w-none">
                            {{ __('payment.manage_meals_subtitle') }}
                        </p>
                        <div class="mt-6 md:mt-12">
                            <p class="mb-4 text-lg font-semibold md:text-2xl">{{ __('Download app') }}</p>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener" class="confirm-store">
                                    <img src="{{ asset('assets/images/play.png') }}" alt="{{ __('Google Play') }}" />
                                </a>
                                <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener" class="confirm-store">
                                    <img src="{{ asset('assets/images/store.png') }}" alt="{{ __('App Store') }}" />
                                </a>
                            </div>
                        </div>
                    </div>
                    <div>
                        <img src="{{ asset('assets/images/app-screens-2.png') }}" class="confirm-phone mx-auto w-full max-w-[415px]" alt="{{ __('App') }}" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('account.dashboard') }}" class="btn btn--primary btn--md btn--arrow">
                        {{ __('account.go_to_dashboard') }}
                    </a>
                    <a href="{{ route('payment.invoice', ['order' => $payment->order_number]) }}" class="btn btn--outline btn--md" id="invoice-download-link">
                        {{ __('payment.download_invoice') }}
                    </a>
                    <a href="{{ route('home') }}" class="btn btn--outline btn--md">{{ __('payment.back_to_home') }}</a>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-gray-200 bg-white p-6 md:p-10 shadow-sm text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-10 w-10 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('payment.failed_heading') }}</h1>
                <p class="text-gray-600 mb-6">{{ $errorMessage ?? __('payment.failed_message') }}</p>
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a href="{{ route('payment.form', ['order' => $payment->order_number]) }}" class="btn btn--primary btn--md">{{ __('payment.try_again') }}</a>
                    <a href="{{ route('checkout.index') }}" class="btn btn--outline btn--md">{{ __('payment.back_to_checkout') }}</a>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
    .confirm-page {
        position: relative;
    }
    .confirm-page::before {
        content: "";
        position: absolute;
        inset: -24px -18px auto;
        height: 220px;
        background: radial-gradient(circle at 50% 0%, rgba(39,159,249,.16), transparent 70%);
        pointer-events: none;
        z-index: 0;
    }
    .confirm-page > * {
        position: relative;
        z-index: 1;
    }
    .confirm-page [data-row] { opacity: 0; transform: translateY(14px); }
    .confirm-hero, .confirm-summary, .confirm-alert, .confirm-app { opacity: 0; transform: translateY(20px); }
    .confirm-check { opacity: 0; transform: scale(.85); }
    .confirm-row {
        display: grid;
        grid-template-columns: minmax(130px, 42%) minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        color:#374151;
        font-size:.98rem;
        border-bottom: 1px dashed rgba(209, 213, 219, .8);
        padding: 8px 0;
    }
    .confirm-row__label {
        color: #6b7280;
        font-weight: 600;
    }
    .confirm-row__value {
        color: #111827;
        font-weight: 700;
        text-align: start;
    }
    html[dir="rtl"] .confirm-row__value {
        text-align: start;
    }
    .confirm-summary {
        border: 1px solid rgba(209,213,219,.85);
        background: linear-gradient(180deg, #fff 0%, #fdfefe 100%);
    }
    .confirm-store { display:inline-block; transition: transform .28s cubic-bezier(.16,1,.3,1), filter .28s ease; }
    .confirm-store:hover { transform: translateY(-4px) scale(1.03); filter: drop-shadow(0 10px 20px rgba(0,0,0,.18)); }
    .confirm-phone { opacity:0; transform: translateY(24px) scale(.96); animation: confirmFloat 5.5s ease-in-out 2.2s infinite; }
    .confirm-summary { transition: transform .35s cubic-bezier(.16,1,.3,1), box-shadow .35s ease; }
    .confirm-summary:hover { transform: translateY(-4px); box-shadow: 0 14px 34px rgba(15,23,42,.12); }
    .confirm-alert {
        border: 1px solid rgba(251,113,133,.24);
        box-shadow: 0 6px 20px rgba(244,63,94,.08);
    }
    .confirm-app {
        box-shadow: 0 20px 40px rgba(0,0,0,.14);
        overflow: hidden;
        position: relative;
    }
    .confirm-app::after {
        content: "";
        position: absolute;
        inset-inline-end: -40px;
        inset-block-start: -40px;
        width: 160px;
        height: 160px;
        border-radius: 999px;
        background: rgba(255,255,255,.12);
        filter: blur(2px);
        pointer-events: none;
    }
    @keyframes confirmFloat { 0%,100%{ transform:translateY(0) scale(1);} 50%{ transform:translateY(-10px) scale(1.01);} }
    @media (prefers-reduced-motion: reduce) {
        .confirm-hero, .confirm-summary, .confirm-alert, .confirm-app, .confirm-check, .confirm-phone, .confirm-store { animation:none !important; transition:none !important; }
    }
    @media (max-width: 768px) {
        .confirm-row {
            grid-template-columns: 1fr;
            gap: 4px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const root = document.querySelector('[data-confirm-page]');
        if (!root || reduce) return;

        const hero = root.querySelector('.confirm-hero');
        const check = root.querySelector('.confirm-check');
        const summary = root.querySelector('.confirm-summary');
        const alert = root.querySelector('.confirm-alert');
        const app = root.querySelector('.confirm-app');
        const phone = root.querySelector('.confirm-phone');

        const reveal = (el, delay = 0, y = 20) => {
            if (!el) return;
            el.animate([
                { opacity: 0, transform: `translateY(${y}px)` },
                { opacity: 1, transform: 'translateY(0)' }
            ], { duration: 680, delay, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', fill: 'forwards' });
        };

        if (check) {
            check.animate([
                { opacity: 0, transform: 'scale(.8)' },
                { opacity: 1, transform: 'scale(1)' }
            ], { duration: 620, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', fill: 'forwards' });
        }

        reveal(hero, 120, 18);
        reveal(summary, 260, 20);
        reveal(alert, 420, 20);
        reveal(app, 560, 22);
        if (phone) {
            phone.animate([
                { opacity: 0, transform: 'translateY(24px) scale(.96)' },
                { opacity: 1, transform: 'translateY(0) scale(1)' }
            ], { duration: 860, delay: 760, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', fill: 'forwards' });
        }

        const dl = document.getElementById('invoice-download-link');
        const autoDownload = @json($autoDownloadInvoice ?? true);
        const orderKey = @json($payment->order_number ?? '');
        if (dl && autoDownload && orderKey) {
            const lockKey = 'dw_invoice_downloaded_' + orderKey;
            if (!sessionStorage.getItem(lockKey)) {
                setTimeout(() => {
                    dl.click();
                    sessionStorage.setItem(lockKey, '1');
                }, 900);
            }
        }
    })();
</script>
@endpush
