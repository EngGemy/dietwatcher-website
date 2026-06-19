@extends('layouts.app')

@push('header-ticker')
    <x-daily-tips-ticker />
@endpush

@section('title', __('Diet Watchers'))

@section('content')
    {{-- Hero Section --}}
    <section class="hero-section">
        <div
            class="hero-shell relative container overflow-hidden rounded-md bg-gray-200">
            <div class="hero-cinematic" aria-hidden="true">
                <span class="hero-cinematic__vignette"></span>
                <span class="hero-cinematic__glow hero-cinematic__glow--blue"></span>
                <span class="hero-cinematic__glow hero-cinematic__glow--green"></span>
            </div>
            <div class="hero-grid relative z-20 mx-auto grid w-full max-w-[1500px] gap-10 lg:grid-cols-2 lg:gap-0">
                <div class="hero-copy md:pb-28">
                    <h1 class="hero-title mb-4 font-bold md:mb-6">
                        @if(app()->getLocale() === 'ar')
                            <span class="hero-line hero-line--1">
                                <span class="hero-line__brand">
                                    <span class="hero-line__phrase hero-line__phrase--blue text-blue">وجبات</span>
                                    <span class="hero-line__phrase hero-line__phrase--blue text-blue hero-smile-wrap">
                                        <span class="hero-smile-word">محسوبة</span>
                                        <img
                                            src="{{ asset('assets/images/icons/smile.svg') }}"
                                            class="hero-smile-icon"
                                            alt=""
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <span class="hero-line__phrase hero-line__phrase--blue text-blue">السعرات</span>
                                </span>
                            </span>
                            <span class="hero-line hero-line--2">تصلك يومياً.</span>
                            <span class="hero-line hero-line--3">
                                مصممة لتحقيق
                                <span class="hero-line__phrase hero-line__phrase--green text-green">أهدافك.</span>
                            </span>
                        @else
                            <span class="hero-line hero-line--1">
                                <span class="hero-line__brand">
                                    <span class="hero-line__phrase hero-line__phrase--blue text-blue">{{ __('Healthy') }}</span>
                                </span>
                                {{ __('Meals') }}
                            </span>
                            <span class="hero-line hero-line--2">{{ __('Delivered Daily.') }}</span>
                            <span class="hero-line hero-line--3">
                                {{ __('Designed for Your') }}
                                <span class="hero-line__phrase hero-line__phrase--green text-green">{{ __('Goals.') }}</span>
                            </span>
                        @endif
                    </h1>
                    <p class="hero-desc-anim mb-5 max-w-xl text-lg text-black/80 md:mb-12 lg:text-2xl">
                        {{ __('Chef-made, calorie-smart meals delivered in Saudi Arabia. Plans online, managed via our app.') }}
                    </p>

                    <a href="{{ route('meal-plans.index') }}" class="hero-btn-anim btn btn--primary mb-8 text-lg">
                        {{ __('Choose Meal Plans') }}
                    </a>

                    <div class="hero-apps-anim">
                        <p class="mb-2 text-lg">{{ __('Download app') }}</p>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener" class="hero-app-badge">
                                <img src="{{ asset('assets/images/play.png') }}" alt="{{ __('Google Play') }}" />
                            </a>
                            <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener" class="hero-app-badge">
                                <img src="{{ asset('assets/images/store.png') }}" alt="{{ __('App Store') }}" />
                            </a>
                        </div>
                    </div>
                </div>

{{--                <div class="relative mx-auto w-fit self-end">--}}
{{--                    <img src="{{ asset('assets/images/hero-img.png') }}"--}}
{{--                        class="hero-img-anim hero-float hero-parallax mx-auto w-full max-w-[600px] select-none md:max-w-[800px]" alt="{{ __('Hero') }}" />--}}
{{--                    <img src="{{ asset('assets/images/app-screens.png') }}"--}}
{{--                        class="hero-phone pointer-events-none select-none"--}}
{{--                        alt="{{ __('App Preview') }}" />--}}
{{--                </div>--}}

                <div class="hero-visual relative mx-auto w-fit self-end">
                    <div class="hero-stage hero-parallax">
                        <img src="{{ asset('assets/images/hero-img.png') }}"
                            class="hero-food hero-img-anim hero-float pointer-events-none select-none"
                            alt="{{ __('Healthy meal') }}" />
                        <img src="{{ asset('assets/images/app-screens.png') }}"
                            class="hero-phones pointer-events-none select-none"
                            alt="{{ __('App Preview') }}" />
                    </div>
                </div>
            </div>

            <img src="{{ asset('assets/images/hero-bg.png') }}"
                class="hero-bg absolute inset-y-0 start-0 hidden object-contain object-right select-none md:block rtl:-scale-x-100"
                alt="" />

            <x-trust-bar />
        </div>
    </section>

    {{-- Meal Plans Section --}}
    @push('styles')
        <style>
            @keyframes cat-icon-float {
                0%, 100% { transform: translateY(0) rotate(0deg); }
                50% { transform: translateY(-7px) rotate(-2deg); }
            }

            @keyframes cat-icon-pop {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.07); }
            }

            @keyframes cat-draw-line {
                from { stroke-dashoffset: 120; }
                to { stroke-dashoffset: 0; }
            }

            @keyframes cat-card-in {
                from {
                    opacity: 0;
                    transform: translateY(18px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .cat-section {
                position: relative;
                overflow: hidden;
                margin-top: -.5rem;
                background:
                    radial-gradient(ellipse 80% 55% at 50% -10%, rgba(39, 159, 249, 0.09), transparent 70%),
                    linear-gradient(180deg, #f8fbff 0%, #fff 42%, #fff 100%);
            }

            .cat-section::before {
                content: "";
                position: absolute;
                inset-inline: 0;
                top: 0;
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(39, 159, 249, 0.2), transparent);
                pointer-events: none;
            }

            .cat-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
                max-width: 72rem;
                margin-inline: auto;
            }

            .cat-carousel {
                position: relative;
            }

            .cat-carousel__fade {
                display: none;
                pointer-events: none;
            }

            .cat-carousel__nav {
                display: none;
            }

            /* Shared “elevated” card state (desktop hover + mobile focus) */
            .cat-card.is-mobile-active,
            .cat-card:active {
                border-color: transparent;
                background: var(--cat-accent);
                box-shadow: 0 18px 44px color-mix(in srgb, var(--cat-accent) 34%, transparent);
                color: #fff;
            }

            .cat-card.is-mobile-active .cat-card__title,
            .cat-card:active .cat-card__title {
                color: #fff;
            }

            .cat-card.is-mobile-active .cat-card__tagline,
            .cat-card:active .cat-card__tagline {
                color: rgba(255, 255, 255, 0.9);
            }

            .cat-card.is-mobile-active .cat-card__icon-wrap,
            .cat-card:active .cat-card__icon-wrap {
                background: rgba(255, 255, 255, 0.22);
                box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.35) inset;
            }

            .cat-card.is-mobile-active .cat-card__scribble path,
            .cat-card:active .cat-card__scribble path {
                stroke: #fff;
                stroke-dashoffset: 0;
            }

            .cat-card.is-mobile-active .cat-card__stat,
            .cat-card:active .cat-card__stat {
                background: rgba(255, 255, 255, 0.2);
            }

            .cat-card.is-mobile-active .cat-card__stat-value,
            .cat-card:active .cat-card__stat-value,
            .cat-card.is-mobile-active .cat-card__stat-unit,
            .cat-card:active .cat-card__stat-unit,
            .cat-card.is-mobile-active .cat-card__stat-soon,
            .cat-card:active .cat-card__stat-soon {
                color: #fff;
            }

            .cat-card.is-mobile-active .cat-card__hint,
            .cat-card:active .cat-card__hint {
                opacity: 1;
                transform: translateY(0);
                color: rgba(255, 255, 255, 0.9);
            }

            @media (max-width: 639px) {
                .cat-section {
                    padding-top: 3.5rem;
                    padding-bottom: 3.5rem;
                }

                .cat-section .container {
                    padding-inline: 1rem;
                }

                .cat-carousel {
                    margin-inline: -1rem;
                }

                .cat-carousel__fade {
                    display: block;
                    position: absolute;
                    top: 0;
                    bottom: 2.5rem;
                    width: 2.25rem;
                    z-index: 2;
                }

                .cat-carousel__fade--start {
                    inset-inline-start: 0;
                    background: linear-gradient(to var(--cat-fade-dir, right), #f8fbff 15%, transparent);
                }

                .cat-carousel__fade--end {
                    inset-inline-end: 0;
                    background: linear-gradient(to var(--cat-fade-dir-end, left), #fff 15%, transparent);
                }

                [dir="rtl"] .cat-carousel__fade--start {
                    --cat-fade-dir: left;
                }

                [dir="rtl"] .cat-carousel__fade--end {
                    --cat-fade-dir-end: right;
                }

                .cat-grid {
                    display: flex;
                    flex-wrap: nowrap;
                    gap: 0.85rem;
                    max-width: none;
                    margin-inline: 0;
                    padding: 0.35rem 1rem 0.85rem;
                    overflow-x: auto;
                    overflow-y: hidden;
                    scroll-snap-type: x mandatory;
                    scroll-padding-inline: 1rem;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                }

                .cat-grid::-webkit-scrollbar {
                    display: none;
                }

                .cat-card {
                    flex: 0 0 min(82vw, 18.5rem);
                    scroll-snap-align: center;
                    min-height: 15.5rem;
                    padding: 1.45rem 1.1rem 1.2rem;
                    transform: scale(0.96);
                    opacity: 0.82;
                    transition:
                        transform 0.35s cubic-bezier(0.22, 1, 0.36, 1),
                        opacity 0.35s ease,
                        box-shadow 0.35s ease,
                        background 0.35s ease,
                        border-color 0.25s ease;
                }

                .cat-card.is-mobile-active {
                    transform: scale(1);
                    opacity: 1;
                }

                .cat-card:not(.is-mobile-active) .cat-card__icon-wrap {
                    animation-play-state: paused;
                }

                .cat-card.is-mobile-active .cat-card__icon-wrap {
                    animation: cat-icon-pop 2.4s ease-in-out infinite;
                }

                .cat-card:active {
                    transform: scale(0.98);
                }

                .cat-card__icon-wrap {
                    width: 5.25rem;
                    height: 5.25rem;
                    margin-bottom: 0.85rem;
                }

                .cat-card__icon {
                    width: 3rem;
                    height: 3rem;
                }

                .cat-card__tagline {
                    min-height: auto;
                    font-size: 0.8rem;
                }

                .cat-card__scribble path {
                    stroke-dashoffset: 80;
                    opacity: 0.65;
                }

                .cat-card.is-mobile-active .cat-card__scribble path {
                    animation: cat-draw-line 0.8s ease forwards;
                }

                .cat-card__hint {
                    opacity: 0.72;
                    transform: translateY(0);
                }

                .cat-carousel__nav {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 0.65rem;
                    margin-top: 0.35rem;
                    padding-inline: 1rem;
                }

                .cat-carousel__dots {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.45rem;
                }

                .cat-carousel__dot {
                    width: 0.45rem;
                    height: 0.45rem;
                    padding: 0;
                    border: none;
                    border-radius: 999px;
                    background: rgba(46, 46, 48, 0.18);
                    transition:
                        width 0.28s ease,
                        background 0.28s ease;
                    cursor: pointer;
                }

                .cat-carousel__dot.is-active {
                    width: 1.35rem;
                    background: var(--color-blue);
                }

                .cat-carousel__dot--lifestyle.is-active {
                    background: #3fb536;
                }

                .cat-carousel__dot--medical.is-active {
                    background: #7c5cff;
                }

                .cat-carousel__swipe {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.4rem;
                    font-size: 0.75rem;
                    font-weight: 600;
                    color: rgba(46, 46, 48, 0.45);
                }

                .cat-carousel__swipe svg {
                    width: 1rem;
                    height: 1rem;
                    animation: cat-swipe-nudge 1.8s ease-in-out infinite;
                }
            }

            @keyframes cat-swipe-nudge {
                0%, 100% { transform: translateX(0); opacity: 0.55; }
                50% { transform: translateX(4px); opacity: 1; }
            }

            @keyframes cat-swipe-nudge-rtl {
                0%, 100% { transform: scaleX(-1) translateX(0); opacity: 0.55; }
                50% { transform: scaleX(-1) translateX(4px); opacity: 1; }
            }

            [dir="rtl"] .cat-carousel__swipe svg {
                animation: cat-swipe-nudge-rtl 1.8s ease-in-out infinite;
            }

            @media (min-width: 640px) {
                .cat-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 1.25rem;
                }
            }

            @media (min-width: 1024px) {
                .cat-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    gap: 1.35rem;
                }
            }

            .cat-card {
                --cat-accent: var(--color-blue);
                --cat-accent-soft: rgba(39, 159, 249, 0.12);
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                min-height: 17.5rem;
                padding: 1.65rem 1.15rem 1.35rem;
                border-radius: 1.4rem;
                border: 1.5px solid rgba(15, 23, 42, 0.07);
                background: #fff;
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.9) inset,
                    0 10px 30px rgba(15, 23, 42, 0.06);
                transition:
                    transform 0.28s cubic-bezier(0.22, 1, 0.36, 1),
                    border-color 0.22s ease,
                    background 0.28s ease,
                    box-shadow 0.28s ease,
                    color 0.2s ease;
                animation: cat-card-in 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
                animation-delay: calc(var(--cat-i, 0) * 90ms);
            }

            .cat-card--lifestyle {
                --cat-accent: #3fb536;
                --cat-accent-soft: rgba(63, 181, 54, 0.14);
            }

            .cat-card--medical {
                --cat-accent: #7c5cff;
                --cat-accent-soft: rgba(124, 92, 255, 0.13);
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover,
                .cat-card:focus-visible {
                    transform: translateY(-6px);
                }

                .cat-card:hover,
                .cat-card:focus-visible,
                .cat-card:hover:active,
                .cat-card:focus-visible:active {
                    border-color: transparent;
                    background: var(--cat-accent);
                    box-shadow: 0 18px 44px color-mix(in srgb, var(--cat-accent) 34%, transparent);
                    color: #fff;
                }

                .cat-card:hover .cat-card__title,
                .cat-card:focus-visible .cat-card__title {
                    color: #fff;
                }

                .cat-card:hover .cat-card__tagline,
                .cat-card:focus-visible .cat-card__tagline {
                    color: rgba(255, 255, 255, 0.9);
                }

                .cat-card:hover .cat-card__icon-wrap,
                .cat-card:focus-visible .cat-card__icon-wrap {
                    background: rgba(255, 255, 255, 0.22);
                    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.35) inset;
                    animation: cat-icon-pop 0.6s ease-in-out infinite;
                }

                .cat-card:hover .cat-card__scribble path,
                .cat-card:focus-visible .cat-card__scribble path {
                    stroke: #fff;
                    animation: cat-draw-line 0.65s ease forwards;
                }

                .cat-card:hover .cat-card__stat,
                .cat-card:focus-visible .cat-card__stat {
                    background: rgba(255, 255, 255, 0.2);
                }

                .cat-card:hover .cat-card__stat-value,
                .cat-card:focus-visible .cat-card__stat-value,
                .cat-card:hover .cat-card__stat-unit,
                .cat-card:focus-visible .cat-card__stat-unit,
                .cat-card:hover .cat-card__stat-soon,
                .cat-card:focus-visible .cat-card__stat-soon {
                    color: #fff;
                }

                .cat-card:hover .cat-card__hint,
                .cat-card:focus-visible .cat-card__hint {
                    opacity: 1;
                    transform: translateY(0);
                    color: rgba(255, 255, 255, 0.88);
                }
            }

            @media (min-width: 640px) {
                .cat-card:active {
                    background: #fff;
                    border-color: rgba(15, 23, 42, 0.07);
                    box-shadow:
                        0 1px 0 rgba(255, 255, 255, 0.9) inset,
                        0 10px 30px rgba(15, 23, 42, 0.06);
                    color: inherit;
                    transform: translateY(-6px) scale(0.99);
                }

                .cat-card:active .cat-card__title {
                    color: var(--color-black);
                }

                .cat-card:active .cat-card__tagline {
                    color: rgba(46, 46, 48, 0.58);
                }
            }

            /* Reset mobile-only active overrides on desktop */
            @media (min-width: 640px) {
                .cat-card.is-mobile-active {
                    transform: none;
                    opacity: 1;
                    background: #fff;
                    border-color: rgba(15, 23, 42, 0.07);
                    box-shadow:
                        0 1px 0 rgba(255, 255, 255, 0.9) inset,
                        0 10px 30px rgba(15, 23, 42, 0.06);
                    color: inherit;
                }

                .cat-card.is-mobile-active .cat-card__title {
                    color: var(--color-black);
                }

                .cat-card.is-mobile-active .cat-card__tagline {
                    color: rgba(46, 46, 48, 0.58);
                }

                .cat-card.is-mobile-active .cat-card__scribble path {
                    stroke: var(--cat-accent);
                    stroke-dashoffset: 120;
                    animation: none;
                }

                .cat-card.is-mobile-active .cat-card__stat {
                    background: var(--cat-accent-soft);
                }

                .cat-card.is-mobile-active .cat-card__stat-value {
                    color: var(--cat-accent);
                }

                .cat-card.is-mobile-active .cat-card__stat-unit,
                .cat-card.is-mobile-active .cat-card__stat-soon {
                    color: rgba(46, 46, 48, 0.55);
                }

                .cat-card.is-mobile-active .cat-card__hint {
                    opacity: 0;
                }
            }

            .cat-card:focus-visible {
                outline: 3px solid color-mix(in srgb, var(--cat-accent) 45%, transparent);
                outline-offset: 3px;
            }

            .cat-card__icon-wrap {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 5.75rem;
                height: 5.75rem;
                margin-bottom: 1rem;
                border-radius: 1.35rem;
                background: linear-gradient(145deg, #fff 0%, var(--cat-accent-soft) 100%);
                box-shadow:
                    0 0 0 1px rgba(255, 255, 255, 0.85) inset,
                    0 8px 20px rgba(15, 23, 42, 0.07);
            }

            @media (prefers-reduced-motion: no-preference) {
                .cat-card__icon-wrap {
                    animation: cat-icon-float 3.4s ease-in-out infinite;
                    animation-delay: calc(var(--cat-i, 0) * 120ms);
                }

                @media (hover: hover) and (pointer: fine) {
                    .cat-card:hover .cat-card__icon-wrap,
                    .cat-card:focus-visible .cat-card__icon-wrap {
                        animation: cat-icon-pop 0.6s ease-in-out infinite;
                    }
                }
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover .cat-card__icon-wrap,
                .cat-card:focus-visible .cat-card__icon-wrap {
                    background: rgba(255, 255, 255, 0.22);
                    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.35) inset;
                }
            }

            .cat-card__icon {
                width: 3.35rem;
                height: 3.35rem;
                object-fit: contain;
                filter: drop-shadow(0 4px 10px rgba(15, 23, 42, 0.14));
            }

            .cat-card__title {
                position: relative;
                margin: 0;
                padding-bottom: 0.15rem;
                font-size: 1.06rem;
                font-weight: 800;
                line-height: 1.4;
                color: var(--color-black);
            }

            @media (min-width: 768px) {
                .cat-card__title {
                    font-size: 1.16rem;
                }
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover .cat-card__title,
                .cat-card:focus-visible .cat-card__title {
                    color: #fff;
                }
            }

            .cat-card__title-text {
                position: relative;
                z-index: 1;
            }

            .cat-card__scribble {
                position: absolute;
                left: 50%;
                bottom: -0.2rem;
                width: calc(100% + 0.75rem);
                height: 0.6rem;
                transform: translateX(-50%);
                overflow: visible;
                pointer-events: none;
            }

            .cat-card__scribble path {
                fill: none;
                stroke: var(--cat-accent);
                stroke-width: 2.75;
                stroke-linecap: round;
                stroke-dasharray: 120;
                stroke-dashoffset: 120;
                opacity: 0.9;
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover .cat-card__scribble path,
                .cat-card:focus-visible .cat-card__scribble path {
                    stroke: #fff;
                    animation: cat-draw-line 0.65s ease forwards;
                }
            }

            .cat-card__tagline {
                margin: 0.65rem 0 0;
                min-height: 2.6em;
                font-size: 0.84rem;
                line-height: 1.55;
                color: rgba(46, 46, 48, 0.58);
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover .cat-card__tagline,
                .cat-card:focus-visible .cat-card__tagline {
                    color: rgba(255, 255, 255, 0.9);
                }
            }

            .cat-card__foot {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.65rem;
                margin-top: auto;
                padding-top: 1rem;
                width: 100%;
            }

            .cat-card__stat {
                display: inline-flex;
                align-items: baseline;
                justify-content: center;
                gap: 0.28rem;
                padding: 0.45rem 1rem;
                border-radius: 999px;
                background: var(--cat-accent-soft);
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover .cat-card__stat,
                .cat-card:focus-visible .cat-card__stat {
                    background: rgba(255, 255, 255, 0.2);
                }
            }

            .cat-card__stat-value {
                font-size: 1.65rem;
                font-weight: 800;
                line-height: 1;
                letter-spacing: -0.04em;
                color: var(--cat-accent);
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover .cat-card__stat-value,
                .cat-card:focus-visible .cat-card__stat-value {
                    color: #fff;
                }
            }

            .cat-card__stat-unit {
                font-size: 0.82rem;
                font-weight: 700;
                color: rgba(46, 46, 48, 0.55);
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover .cat-card__stat-unit,
                .cat-card:focus-visible .cat-card__stat-unit {
                    color: rgba(255, 255, 255, 0.86);
                }
            }

            .cat-card__stat-soon {
                font-size: 0.82rem;
                font-weight: 700;
                letter-spacing: 0.02em;
                color: rgba(46, 46, 48, 0.48);
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover .cat-card__stat-soon,
                .cat-card:focus-visible .cat-card__stat-soon {
                    color: rgba(255, 255, 255, 0.9);
                }
            }

            .cat-card__hint {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                font-size: 0.78rem;
                font-weight: 700;
                color: rgba(46, 46, 48, 0.42);
                opacity: 0;
                transform: translateY(4px);
                transition:
                    opacity 0.22s ease,
                    transform 0.22s ease,
                    color 0.2s ease;
            }

            @media (hover: hover) and (pointer: fine) {
                .cat-card:hover .cat-card__hint,
                .cat-card:focus-visible .cat-card__hint {
                    opacity: 1;
                    transform: translateY(0);
                    color: rgba(255, 255, 255, 0.88);
                }
            }

            .cat-card__hint svg {
                width: 0.9rem;
                height: 0.9rem;
            }

            [dir="rtl"] .cat-card__hint svg {
                transform: scaleX(-1);
            }

            @media (prefers-reduced-motion: reduce) {
                .cat-card,
                .cat-card__icon-wrap,
                .cat-card__scribble path,
                .cat-card__hint {
                    animation: none !important;
                    transition: none !important;
                }
            }
        </style>
    @endpush

    <section class="cat-section py-20 md:py-24">
        <div class="container">
            <header class="section-header section-header--center app-section-head" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ __('Meal Plan') }}</h4>
                <h2 class="section-header__title">{{ __('Meal Plans for Every Lifestyle') }}</h2>
                <p class="section-header__desc">
                    {{ __('Expert-designed plans with transparent calories and flexible pricing.') }}
                </p>
            </header>

            @php
                $__homepageMealPlanCardImage = static function (int $imgIdx): string {
                    $locale = strtok((string) app()->getLocale(), '_') ?: app()->getLocale();
                    $localizedRel = 'assets/images/meal-plan-' . $imgIdx . '-' . $locale . '.png';
                    if (is_file(public_path($localizedRel))) {
                        return asset($localizedRel);
                    }

                    return asset('assets/images/meal-plan-' . $imgIdx . '.png');
                };

                $__localizedCategoryField = static function (mixed $value): string {
                    if (is_array($value)) {
                        $locale = app()->getLocale();

                        return (string) ($value[$locale] ?? $value['ar'] ?? $value['en'] ?? reset($value) ?: '');
                    }

                    return (string) ($value ?? '');
                };

                $__mealPlanCoverForCategory = static function (array $category) use ($__homepageMealPlanCardImage, $__localizedCategoryField): string {
                    $cover = trim((string) ($category['image_url'] ?? ''));
                    if ($cover !== '') {
                        return $cover;
                    }

                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $__localizedCategoryField($category['name'] ?? ''),
                        $__localizedCategoryField($category['description'] ?? ''),
                    ])));

                    if (preg_match('/وزن|weight\s*manag|weight\s*loss|healthy\s*weight/u', $haystack)) {
                        return $__homepageMealPlanCardImage(3);
                    }

                    if (preg_match('/طب|medical|health\s*condition/u', $haystack)) {
                        return $__homepageMealPlanCardImage(2);
                    }

                    return $__homepageMealPlanCardImage(1);
                };

                $__categoryTagline = static function (array $category) use ($__localizedCategoryField): string {
                    $name = trim($__localizedCategoryField($category['name'] ?? ''));
                    $description = trim($__localizedCategoryField($category['description'] ?? ''));

                    if ($description !== '' && mb_strtolower($description) !== mb_strtolower($name)) {
                        return $description;
                    }

                    $haystack = mb_strtolower(implode(' ', array_filter([$name, $description])));

                    if (preg_match('/وزن|weight\s*manag|weight\s*loss|healthy\s*weight/u', $haystack)) {
                        return __('category.tagline_weight');
                    }

                    if (preg_match('/طب|medical|health\s*condition/u', $haystack)) {
                        return __('category.tagline_medical');
                    }

                    if (preg_match('/نمط|حياة|lifestyle|everyday\s*wellness|صحي/u', $haystack)) {
                        return __('category.tagline_lifestyle');
                    }

                    return __('category.tagline_default');
                };

                $__categoryTheme = static function (array $category) use ($__localizedCategoryField): string {
                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $__localizedCategoryField($category['name'] ?? ''),
                        $__localizedCategoryField($category['description'] ?? ''),
                    ])));

                    if (preg_match('/وزن|weight\s*manag|weight\s*loss|healthy\s*weight/u', $haystack)) {
                        return 'weight';
                    }

                    if (preg_match('/طب|medical|health\s*condition|حالة\s*صح/u', $haystack)) {
                        return 'medical';
                    }

                    if (preg_match('/نمط|حياة|lifestyle|everyday\s*wellness|صحي/u', $haystack)) {
                        return 'lifestyle';
                    }

                    return 'weight';
                };

                $__isPlaceholderIcon = static function (string $url): bool {
                    $url = mb_strtolower($url);

                    return $url === ''
                        || str_contains($url, 'uploadable')
                        || str_contains($url, 'placeholder')
                        || str_contains($url, 'default.');
                };

                $__mealPlanIconForCategory = static function (array $category) use ($__mealPlanCoverForCategory, $__isPlaceholderIcon): string {
                    $cover = trim((string) ($category['image_url'] ?? ''));
                    $badge = $category['badge'] ?? null;
                    $badgeUrl = is_array($badge) ? trim((string) ($badge['image'] ?? '')) : '';

                    if ($cover !== '' && ! $__isPlaceholderIcon($cover)) {
                        return $__mealPlanCoverForCategory($category);
                    }

                    if ($badgeUrl !== '' && ! $__isPlaceholderIcon($badgeUrl)) {
                        return $__mealPlanCoverForCategory(['image_url' => $badgeUrl]);
                    }

                    return $__mealPlanCoverForCategory($category);
                };

                $__fallbackCategories = [
                    [
                        'id' => null,
                        'name' => __('category.fallback_lifestyle_title'),
                        'description' => __('category.tagline_lifestyle'),
                        'image_url' => $__homepageMealPlanCardImage(1),
                        'programs_count' => 0,
                    ],
                    [
                        'id' => null,
                        'name' => __('category.fallback_weight_title'),
                        'description' => __('category.tagline_weight'),
                        'image_url' => $__homepageMealPlanCardImage(3),
                        'programs_count' => 0,
                    ],
                    [
                        'id' => null,
                        'name' => __('category.fallback_medical_title'),
                        'description' => __('category.tagline_medical'),
                        'image_url' => $__homepageMealPlanCardImage(2),
                        'programs_count' => 0,
                    ],
                ];

                $__displayCategories = $mealPlanCategories->isNotEmpty()
                    ? $mealPlanCategories
                    : collect($__fallbackCategories);
            @endphp

            <div
                class="cat-carousel mb-10 md:mb-14"
                x-data="{
                    active: 0,
                    _scrollRaf: null,
                    init() {
                        this.$nextTick(() => {
                            this.syncActive();
                            this.$refs.track?.addEventListener('scroll', () => this.onTrackScroll(), { passive: true });
                            window.addEventListener('resize', () => this.syncActive(), { passive: true });
                        });
                    },
                    onTrackScroll() {
                        if (this._scrollRaf) return;
                        this._scrollRaf = requestAnimationFrame(() => {
                            this._scrollRaf = null;
                            this.syncActive();
                        });
                    },
                    syncActive() {
                        const track = this.$refs.track;
                        if (!track) return;
                        const cards = [...track.querySelectorAll('.cat-card')];
                        if (!cards.length) return;
                        const trackRect = track.getBoundingClientRect();
                        const trackCenter = trackRect.left + trackRect.width / 2;
                        let best = 0;
                        let bestDist = Infinity;
                        cards.forEach((card, i) => {
                            const rect = card.getBoundingClientRect();
                            const dist = Math.abs((rect.left + rect.width / 2) - trackCenter);
                            if (dist < bestDist) {
                                bestDist = dist;
                                best = i;
                            }
                        });
                        this.active = best;
                        const isMobile = window.matchMedia('(max-width: 639px)').matches;
                        cards.forEach((card, i) => {
                            card.classList.toggle('is-mobile-active', isMobile && i === best);
                        });
                    },
                    goTo(index) {
                        const card = this.$refs.track?.querySelectorAll('.cat-card')[index];
                        if (!card) return;
                        card.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                    }
                }"
                x-init="init()">
                <div class="cat-carousel__fade cat-carousel__fade--start" aria-hidden="true"></div>
                <div class="cat-carousel__fade cat-carousel__fade--end" aria-hidden="true"></div>

                <div class="cat-grid" data-anim-stagger x-ref="track">
                @foreach($__displayCategories as $category)
                    @php
                        $catId = (int) ($category['id'] ?? 0);
                        $catName = $__localizedCategoryField($category['name'] ?? '');
                        $catTagline = $__categoryTagline($category);
                        $catTheme = $__categoryTheme($category);
                        $iconUrl = $__mealPlanIconForCategory($category);
                        $programsCount = max(0, (int) ($category['programs_count'] ?? 0));
                        $categoryHref = $catId > 0
                            ? route('meal-plans.index', ['category' => $catId])
                            : route('meal-plans.index');
                    @endphp
                    <a href="{{ $categoryHref }}"
                       class="cat-card cat-card--{{ $catTheme }}"
                       style="--cat-i: {{ $loop->index }}"
                       data-anim="fade-up"
                       aria-label="{{ $catName }} — {{ $catTagline }}">
                        <div class="cat-card__icon-wrap">
                            <img src="{{ $iconUrl }}"
                                 class="cat-card__icon"
                                 alt=""
                                 loading="lazy"
                                 decoding="async"
                                 onerror="this.onerror=null;this.src='{{ $__homepageMealPlanCardImage(match($catTheme) { 'lifestyle' => 1, 'medical' => 2, default => 3 }) }}';" />
                        </div>
                        <h3 class="cat-card__title">
                            <span class="cat-card__title-text">{{ $catName }}</span>
                            <svg class="cat-card__scribble" viewBox="0 0 120 12" aria-hidden="true">
                                <path d="M4 8 C 20 2, 40 10, 60 6 S 100 4, 116 7" />
                            </svg>
                        </h3>
                        <p class="cat-card__tagline">{{ $catTagline }}</p>
                        <div class="cat-card__foot">
                            <div class="cat-card__stat" aria-label="{{ $programsCount > 0 ? trans_choice('category.programs_count', $programsCount, ['count' => $programsCount]) : __('category.coming_soon') }}">
                                @if($programsCount > 0)
                                    <span class="cat-card__stat-value">{{ $programsCount }}</span>
                                    <span class="cat-card__stat-unit">{{ trans_choice('category.programs_unit', $programsCount) }}</span>
                                @else
                                    <span class="cat-card__stat-soon">{{ __('category.coming_soon') }}</span>
                                @endif
                            </div>
                            <span class="cat-card__hint">
                                {{ __('Explore') }}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
                </div>

                <div class="cat-carousel__nav" aria-hidden="false">
                    <div class="cat-carousel__dots" role="tablist" aria-label="{{ __('Meal Plan') }}">
                        @foreach($__displayCategories as $category)
                            @php $dotTheme = $__categoryTheme($category); @endphp
                            <button type="button"
                                    class="cat-carousel__dot cat-carousel__dot--{{ $dotTheme }}"
                                    :class="active === {{ $loop->index }} ? 'is-active' : ''"
                                    @click="goTo({{ $loop->index }})"
                                    :aria-selected="active === {{ $loop->index }} ? 'true' : 'false'"
                                    role="tab"
                                    aria-label="{{ $__localizedCategoryField($category['name'] ?? '') }}"></button>
                        @endforeach
                    </div>
                    <p class="cat-carousel__swipe">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        {{ __('category.swipe_hint') }}
                    </p>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ route('meal-plans.index') }}" class="btn btn--primary btn--md">{{ __('Choose Your Meal Plan') }}</a>
            </div>
        </div>
    </section>

    {{-- How It Works Section --}}
    <section class="bg-gray-200 py-20">
        <div class="container">
            @php
                $resolveHowImage = function (array $candidates): string {
                    foreach ($candidates as $path) {
                        if (is_file(public_path($path))) {
                            return asset($path);
                        }
                    }

                    return asset('assets/images/plan-1.png');
                };
                $howDefaultImages = [
                    $resolveHowImage(['assets/images/how-old-1.png', 'assets/images/how-1.png']),
                    $resolveHowImage(['assets/images/how-old-2.png', 'assets/images/how-2.png']),
                    $resolveHowImage(['assets/images/how-old-3.png', 'assets/images/how-3.png']),
                ];
            @endphp
            <header class="section-header section-header--center" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ __('How It Works') }}</h4>
                <h2 class="section-header__title">{{ __('3 Easy Steps For Happy Life') }}</h2>
            </header>
            <div class="grid gap-8 lg:grid-cols-3" data-anim-stagger>
                @forelse($howItWorksSteps as $step)
                    @php
                        $stepFallback = $howDefaultImages[$loop->index] ?? $howDefaultImages[array_key_last($howDefaultImages)];
                        $stepImage = !empty($step->image_url) ? $step->image_url : $stepFallback;
                    @endphp
                    <article data-anim="fade-up" class="how-step-card">
                        <img src="{{ $stepImage }}"
                             class="mb-8 w-full rounded-lg"
                             alt="{{ $step->title() }}"
                             onerror="this.src='{{ $stepFallback }}'" />
                        <h3 class="mb-4 text-xl font-semibold md:text-2xl">{{ $step->title() }}</h3>
                        <p class="text-lg text-black/70 md:text-xl">
                            {{ $step->description() }}
                        </p>
                    </article>
                @empty
                    {{-- Fallback static content --}}
                    <article data-anim="fade-up" class="how-step-card">
                        <img src="{{ $howDefaultImages[0] }}" class="mb-8 w-full rounded-lg" alt="{{ __('Choose Your Plan') }}" />
                        <h3 class="mb-4 text-xl font-semibold md:text-2xl">{{ __('Choose Your Plan') }}</h3>
                        <p class="text-lg text-black/70 md:text-xl">
                            {{ __('Select a meal plan based on calories, lifestyle, or fitness goals.') }}
                        </p>
                    </article>
                    <article data-anim="fade-up" class="how-step-card">
                        <img src="{{ $howDefaultImages[1] }}" class="mb-8 w-full rounded-lg" alt="{{ __('Swap to Your Favorite Meals') }}" />
                        <h3 class="mb-4 text-xl font-semibold md:text-2xl">{{ __('Swap to Your Favorite Meals') }}</h3>
                        <p class="text-lg text-black/70 md:text-xl">
                            {{ __('Change meals anytime and enjoy dishes that suit your taste, mood, and lifestyle.') }}
                        </p>
                    </article>
                    <article data-anim="fade-up" class="how-step-card">
                        <img src="{{ $howDefaultImages[2] }}" class="mb-8 w-full rounded-lg" alt="{{ __('Enjoy Your Meals!') }}" />
                        <h3 class="mb-4 text-xl font-semibold md:text-2xl">{{ __('Enjoy Your Meals!') }}</h3>
                        <p class="text-lg text-black/70 md:text-xl">
                            {{ __('our meals are ready - fresh, nutritious, and made to enjoy.') }}
                        </p>
                    </article>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Why Choose Us Section --}}
    <section class="py-20">
        <div class="container">
            @php
                $whyChooseSection = \App\Models\WhyChooseSection::where('is_active', true)->first();
                $features = \App\Models\Content\Feature::active()->orderBy('order')->get();
            @endphp
            <header class="section-header section-header--center" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ $whyChooseSection?->badge_title() ?? __('Why Diet Watchers?') }}</h4>
                <h2 class="section-header__title">{{ $whyChooseSection?->title() ?? __('Choosing Diet watchers') }}</h2>
                <p class="section-header__desc">
                    {{ $whyChooseSection?->subtitle() ?? __('We simplifies healthy eating with fresh meals, expert plans, and flexible options to help you feel your best.') }}
                </p>
            </header>

            <div class="mx-auto flex max-w-7xl flex-col items-center gap-6 md:flex-row">
                <div class="relative flex-1" data-anim="fade-right">
                    <img id="why-choose-img" src="{{ $whyChooseSection?->image_url ?? asset('assets/images/why-1.png') }}"
                        class="relative z-20 mx-auto w-full max-w-[416px] transition-opacity duration-500" alt="" />
                    <div class="absolute inset-0 z-10 flex items-center justify-center">
                        <svg width="832" height="832" viewBox="0 0 832 832" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g filter="url(#filter0_f_572_6637)">
                                <circle cx="416" cy="416" r="266" fill="#FFC400" fill-opacity="0.2" />
                            </g>
                            <defs>
                                <filter id="filter0_f_572_6637" x="0" y="0" width="832" height="832"
                                    filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                    <feFlood flood-opacity="0" result="BackgroundImageFix" />
                                    <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                                    <feGaussianBlur stdDeviation="75" result="effect1_foregroundBlur_572_6637" />
                                </filter>
                            </defs>
                        </svg>
                    </div>
                </div>

                <div id="why-choose-accordion" class="hs-accordion-group mx-auto flex-1 space-y-5 md:max-w-xl" data-anim="fade-left">
                    @forelse ($features as $index => $feature)
                        <div class="hs-accordion {{ $index === 0 ? 'active' : '' }} [&.active]:border-blue border-s-[3px] border-gray-300"
                            id="why-choose-{{ $feature->id }}">
                            <button class="hs-accordion-toggle w-full py-2.5 ps-10 pe-4 text-start focus:outline-none"
                                aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="why-choose-collapse-{{ $feature->id }}">
                                <h3 class="text-xl font-bold">{{ $feature->title }}</h3>
                            </button>
                            <div id="why-choose-collapse-{{ $feature->id }}"
                                class="hs-accordion-content {{ $index === 0 ? '' : 'hidden' }} w-full overflow-hidden transition-[height] duration-300"
                                role="region" aria-labelledby="why-choose-{{ $feature->id }}">
                                <div class="py-3 ps-10 pe-4">
                                    <p class="text-black/70">
                                        {{ $feature->description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500">
                            {{ __('No features available yet.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- Instant Orders Section --}}
    <section class="bg-gray-200 py-20">
        <div class="container">
            <header class="section-header section-header--center section-header--always-visible">
                <h4 class="section-header__subtitle">{{ __('Instant Orders') }}</h4>
                <h2 class="section-header__title">{{ __('Order Individual Meals Anytime') }}</h2>
                <p class="section-header__desc">
                    {{ __('Explore chef-prepared meals and healthy options available for instant order.') }}
                </p>
            </header>

            @php
                $homeStoreMeals = count($instantMeals) > 0
                    ? $instantMeals
                    : collect(range(1, 4))->map(fn (int $i) => [
                        'id' => 0,
                        'name' => __('Meal').' '.$i,
                        'price' => 0,
                        'offer_price' => 0,
                        'tag_name' => '',
                        'image_url' => '',
                        '_placeholder' => true,
                    ])->all();
            @endphp
            <div class="products-rail" data-products-rail data-rail-min-width="240" data-rail-speed="0.12">
                <div class="products-rail__viewport">
                    <div class="products-rail__track" data-products-track>
                @foreach (['original', 'clone'] as $railSegmentType)
                    @php $isRailCloneSegment = $railSegmentType === 'clone'; @endphp
                    <div class="infinite-rail__segment"
                         data-rail-segment="{{ $railSegmentType }}"
                         @if($isRailCloneSegment) aria-hidden="true" data-rail-clone="1" @endif>
                @foreach($homeStoreMeals as $meal)
                    @php
                        $mealImage = $meal['image_url'] ?? '';
                        $mealImageTrim = trim((string) $mealImage);
                        if ($mealImageTrim === '') {
                            $mealImageUrl = asset('assets/images/meal-' . ($loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3) . '.png');
                        } elseif (str_starts_with($mealImageTrim, '//')) {
                            $mealImageUrl = 'https:'.$mealImageTrim;
                        } elseif (str_starts_with($mealImageTrim, 'http://') || str_starts_with($mealImageTrim, 'https://')) {
                            $mealImageUrl = $mealImageTrim;
                        } else {
                            $mealImageUrl = asset(ltrim($mealImageTrim, '/'));
                        }
                        $mealFallback = asset('assets/images/meal-' . ($loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3) . '.png');
                        $effectivePrice = ($meal['offer_price'] ?? 0) > 0 && ($meal['offer_price'] < $meal['price']) ? $meal['offer_price'] : $meal['price'];
                    @endphp
                    @php
                        $isPlaceholderMeal = ! empty($meal['_placeholder']);
                        // كاروسيل متحرّك أفقياً: lazy-loading يخلّي المتصفح يؤجّل صور
                        // الكروت اللي خارج الـ viewport للأبد (لأنها لا "تدخل" الشاشة
                        // بالـ scroll) فتظهر فاضية. لذا كل صور الـ rail eager.
                        if ($isRailCloneSegment) {
                            $railImgLoading = 'eager';
                            $railImgFetchPriority = 'low';
                        } else {
                            $railImgLoading = 'eager';
                            $railImgFetchPriority = $loop->iteration <= 4 ? 'high' : 'low';
                        }
                    @endphp
                    <article class="meal-card products-rail__card" data-rail-item @if($isRailCloneSegment) data-rail-clone="1" @endif>
                        <div class="meal-card__thumbnail">
                            @if($isPlaceholderMeal)
                                <a href="{{ route('meals.index') }}" @if($isRailCloneSegment) tabindex="-1" @endif>
                                    <img src="{{ $mealImageUrl }}" alt="{{ $meal['name'] }}" width="400" height="300" loading="{{ $railImgLoading }}" fetchpriority="{{ $railImgFetchPriority }}" decoding="async" />
                                </a>
                            @else
                                <a href="{{ route('store.show', $meal['id']) }}" @if($isRailCloneSegment) tabindex="-1" @endif>
                                    <img src="{{ $mealImageUrl }}" alt="{{ $meal['name'] }}" width="400" height="300" loading="{{ $railImgLoading }}" fetchpriority="{{ $railImgFetchPriority }}" decoding="async" onerror="this.src='{{ $mealFallback }}'" />
                                </a>
                            @endif
                        </div>

                        <div class="meal-card__body">
                            @if($isPlaceholderMeal)
                                <a href="{{ route('meals.index') }}" class="meal-card__title-link" @if($isRailCloneSegment) tabindex="-1" @endif>
                                    <h3 class="meal-card__title">{{ $meal['name'] }}</h3>
                                </a>
                            @else
                                <a href="{{ route('store.show', $meal['id']) }}" class="meal-card__title-link" @if($isRailCloneSegment) tabindex="-1" @endif>
                                    <h3 class="meal-card__title">{{ $meal['name'] }}</h3>
                                </a>
                            @endif

                            <div class="meal-card__lower">
                                <div class="meal-card__footer">
                                    @if(! empty($meal['tag_name']))
                                        <span class="meal-card__category">{{ $meal['tag_name'] }}</span>
                                    @endif
                                    <div class="meal-card__price-wrap">
                                        @if(($meal['offer_price'] ?? 0) > 0 && $meal['offer_price'] < $meal['price'])
                                            <span class="meal-card__price">
                                                <span class="line-through text-gray-600 text-sm"><x-sar :amount="$meal['price']" :decimals="0" /></span>
                                                <x-sar :amount="$meal['offer_price']" :decimals="0" />
                                            </span>
                                        @else
                                            <span class="meal-card__price"><x-sar :amount="$meal['price']" :decimals="0" /></span>
                                        @endif
                                    </div>
                                </div>

                                @if($isPlaceholderMeal)
                                    <a href="{{ route('meals.index') }}" class="meal-card__btn products-rail__btn btn btn--primary btn--sm" @if($isRailCloneSegment) tabindex="-1" @endif>
                                        {{ __('Choose from Market') }}
                                    </a>
                                @else
                                    <button type="button"
                                            class="meal-card__btn products-rail__btn hero-magnetic"
                                            data-add-to-cart-btn
                                            data-default-label="{{ __('Add to Cart') }}"
                                            data-success-label="{{ __('Added') }}"
                                            @if($isRailCloneSegment) tabindex="-1" @endif
                                            onclick="Livewire.dispatch('add-to-cart', { mealId: {{ $meal['id'] }}, name: '{{ addslashes($meal['name']) }}', price: {{ $effectivePrice }}, image: '{{ addslashes($mealImageUrl) }}' })">
                                        {{ __('Add to Cart') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
                    </div>
                @endforeach
                    </div>
                </div>
                <div class="products-rail__cursor products-rail__cursor--dot" aria-hidden="true"></div>
                <div class="products-rail__cursor products-rail__cursor--ring" aria-hidden="true">
                    <span>{{ __('Drag') }}</span>
                </div>
                <p class="products-rail__hint" aria-hidden="true">{{ __('checkout.duration_swipe_hint') }}</p>
            </div>

            <div class="mt-10 flex items-center justify-center md:mt-20">
                <a href="{{ route('meals.index') }}" class="btn btn--primary btn--md">{{ __('Choose from Market') }}</a>
            </div>
        </div>
    </section>

    {{-- Download App Section --}}
    <section class="py-20">
        <div class="container">
            @php
                $appDownloadSection = \App\Models\AppDownloadSection::where('is_active', true)->first();
            @endphp
            <header class="section-header section-header--center" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ $appDownloadSection?->badge_title() ?? __('Download the App') }}</h4>
                <h2 class="section-header__title">{{ $appDownloadSection?->title() ?? __('Your Meals. Your Control.') }}</h2>
                <p class="section-header__desc">
                    {{ $appDownloadSection?->subtitle() ?? __('Take full control of your meal plan with the freedom to customize meals, manage deliveries, and make changes anytime - right from your phone.') }}
                </p>
            </header>

            <div class="mb-10 flex flex-wrap items-center justify-center gap-1.5 md:mb-12 app-store-links" data-anim="fade-up" data-anim-delay="100">
                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener" class="hero-magnetic">
                    <img src="{{ asset('assets/images/play.png') }}" class="h-16" alt="{{ __('Google Play') }}" />
                </a>
                <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener" class="hero-magnetic">
                    <img src="{{ asset('assets/images/store.png') }}" class="h-16" alt="{{ __('App Store') }}" />
                </a>
            </div>

            <div class="mb-12 flex items-center justify-center gap-3 app-social-links" data-anim="fade-up" data-anim-delay="180">
                @if(!empty($socialWhatsapp) && $socialWhatsapp !== '#')
                    <a href="{{ $socialWhatsapp }}" target="_blank" rel="noopener" class="app-social-link hero-magnetic" data-platform="whatsapp" aria-label="{{ __('WhatsApp') }}">
                        <svg class="size-5"><use href="{{ asset('assets/images/icons/sprite.svg#whatsapp') }}"></use></svg>
                    </a>
                @endif
                @if(!empty($socialTwitter) && $socialTwitter !== '#')
                    <a href="{{ $socialTwitter }}" target="_blank" rel="noopener" class="app-social-link hero-magnetic" data-platform="twitter" aria-label="{{ __('Twitter') }}">
                        <img src="{{ asset('assets/images/icons/twitter.svg') }}" alt="" class="size-5 object-contain" />
                    </a>
                @endif
                @if(!empty($socialSnapchat) && $socialSnapchat !== '#')
                    <a href="{{ $socialSnapchat }}" target="_blank" rel="noopener" class="app-social-link hero-magnetic" data-platform="snapchat" aria-label="{{ __('Snapchat') }}">
                        <img src="{{ asset('assets/images/icons/snapchat.svg') }}" alt="" class="size-5 object-contain" />
                    </a>
                @endif
                @if(!empty($socialInstagram) && $socialInstagram !== '#')
                    <a href="{{ $socialInstagram }}" target="_blank" rel="noopener" class="app-social-link hero-magnetic" data-platform="instagram" aria-label="{{ __('Instagram') }}">
                        <svg class="size-5"><use href="{{ asset('assets/images/icons/sprite.svg#instagram') }}"></use></svg>
                    </a>
                @endif
            </div>

            <img src="{{ $appDownloadSection?->mobile_image_url ?? asset('assets/images/app-screens.png') }}"
                class="relative z-20 mx-auto w-full max-w-[630px]"
                alt="{{ __('App Preview') }}" data-anim="zoom-in" />
        </div>
    </section>

    {{-- Testimonials Section --}}
    <section class="bg-gray-200 py-20">
        <div class="container">
            @php
                $testimonialHeader = \App\Models\TestimonialSectionHeader::where('is_active', true)->first();
            @endphp
            <header class="section-header section-header--center testimonials-header section-header--always-visible">
                <h4 class="section-header__subtitle">{{ $testimonialHeader?->badge_title() ?? __('Feedback') }}</h4>
                <h2 class="section-header__title">{{ $testimonialHeader?->title() ?? __('What our customer say') }}</h2>
                <p class="section-header__desc">
                    {{ $testimonialHeader?->subtitle() ?? __('Real experiences from customers who have made healthy eating part of their everyday lives with Diet Watchers.') }}
                </p>
            </header>

            @php
                $homeTestimonials = $testimonials->isNotEmpty()
                    ? $testimonials
                    : collect([
                        ['content' => __('Real experiences from customers who have made healthy eating part of their everyday lives with Diet Watchers.'), 'rating' => 5, 'author_name' => __('Diet Watchers Customer'), 'author_title' => '', 'author_image_url' => asset('assets/images/Profile.png')],
                        ['content' => __('The meals are fresh, portions are perfect, and delivery is always on time.'), 'rating' => 5, 'author_name' => __('Diet Watchers Customer'), 'author_title' => '', 'author_image_url' => asset('assets/images/Profile.png')],
                        ['content' => __('I finally found a plan that fits my lifestyle without sacrificing taste.'), 'rating' => 5, 'author_name' => __('Diet Watchers Customer'), 'author_title' => '', 'author_image_url' => asset('assets/images/Profile.png')],
                    ]);
            @endphp
            <div class="testimonials-rail" data-testimonials-rail data-rail-min-width="280">
                <div class="testimonials-rail__viewport" data-testimonials-viewport>
                    <div class="testimonials-rail__track" data-testimonials-track>
                @foreach (['original', 'clone'] as $tSegmentType)
                    @php $isTCloneSegment = $tSegmentType === 'clone'; @endphp
                    <div class="infinite-rail__segment"
                         data-rail-segment="{{ $tSegmentType }}"
                         @if($isTCloneSegment) aria-hidden="true" data-rail-clone="1" @endif>
                @foreach ($homeTestimonials as $testimonial)
                    <article class="hs-carousel-slide testimonials-card-wrap testimonials-rail__item" data-testimonial-item @if($isTCloneSegment) data-rail-clone="1" @endif>
                        <div class="review-card testimonials-card">
                            <svg class="review-card__quote">
                                <use href="{{ asset('assets/images/icons/sprite.svg#quote') }}"></use>
                            </svg>

                            <p class="review-card__content">
                                {{ is_object($testimonial) ? $testimonial->content : ($testimonial['content'] ?? '') }}
                            </p>

                            <div class="review-card__rating">
                                @php $rating = (int) (is_object($testimonial) ? $testimonial->rating : ($testimonial['rating'] ?? 5)); @endphp
                                @for ($j = 0; $j < 5; $j++)
                                    <svg class="{{ $j < $rating ? '' : 'text-gray-300' }}">
                                        <use href="{{ asset('assets/images/icons/sprite.svg#star') }}"></use>
                                    </svg>
                                @endfor
                            </div>

                            <div class="review-card__author">
                                <img class="review-card__author-img" src="{{ is_object($testimonial) ? ($testimonial->author_image_url ?? asset('assets/images/Profile.png')) : ($testimonial['author_image_url'] ?? asset('assets/images/Profile.png')) }}" alt="{{ is_object($testimonial) ? $testimonial->author_name : ($testimonial['author_name'] ?? '') }}" loading="eager" decoding="async" />
                                <div>
                                    <h3 class="review-card__author-name">{{ is_object($testimonial) ? $testimonial->author_name : ($testimonial['author_name'] ?? '') }}</h3>
                                    @php $authorTitle = is_object($testimonial) ? $testimonial->author_title : ($testimonial['author_title'] ?? ''); @endphp
                                    @if($authorTitle)
                                        <p class="text-sm text-gray-500">{{ $authorTitle }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
                    </div>
                @endforeach
                    </div>
                </div>
                <p class="testimonials-rail__hint" aria-hidden="true">{{ __('checkout.duration_swipe_hint') }}</p>
            </div>
        </div>
    </section>

    {{-- Blog Section --}}
    <section class="py-20 blog-premium-section">
        <div class="container">
            <header class="section-header section-header--center blog-premium-head" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ __('Insightful') }}</h4>
                <h2 class="section-header__title">{{ __('Insights for a Healthier You') }}</h2>
                <p class="section-header__desc">
                    {{ __('Get expert nutrition and lifestyle tips for healthier daily choices.') }}
                </p>
            </header>

            <div class="mb-10 grid grid-cols-1 gap-6 md:mb-14 md:grid-cols-2 lg:grid-cols-4 blog-premium-grid" data-anim-stagger>
                @forelse($latestPosts as $post)
                    <div class="blog-card blog-premium-card" data-anim="fade-up" data-blog-card>
                        <div class="blog-card__thumbnail">
                            <a href="{{ route('blog.show', $post->translate(app()->getLocale())->slug) }}" data-blog-link>
                                @php
                                    $postImage = $post->cover_image_exists
                                        ? $post->cover_image_url
                                        : asset('assets/images/blog-1.png');
                                @endphp
                                <img src="{{ $postImage }}" alt="{{ $post->title }}" />
                            </a>
                        </div>

                        <a href="{{ route('blog.show', $post->translate(app()->getLocale())->slug) }}" class="blog-card__body" data-blog-link>
                            <time datetime="{{ $post->published_at->format('Y-m-d') }}">{{ $post->formatted_date }}</time>
                            <h3 class="blog-card__title">
                                {{ $post->title }}
                            </h3>
                        </a>
                    </div>
                @empty
                    <div class="col-span-full text-center">
                        <p class="text-gray-500">{{ __('No blog posts available yet.') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="text-center">
                <a href="{{ route('blog.index') }}" class="btn btn--primary btn--md">{{ __('View All Blogs') }}</a>
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section id="faq" class="bg-gray-200 py-20">
        <div class="container">
            @php
                $faqHeader = \App\Models\FaqSectionHeader::where('is_active', true)->first();
                $faqs = \App\Models\Faq::where('is_active', true)->orderBy('order_column')->get();
            @endphp
            <header class="section-header section-header--center" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ $faqHeader?->badge_title() ?? __('Answers') }}</h4>
                <h2 class="section-header__title">{{ $faqHeader?->title() ?? __('Frequently Asked Questions') }}</h2>
                <p class="section-header__desc">
                    {{ $faqHeader?->subtitle() ?? __('Get answers to frequently asked questions.') }}
                </p>
            </header>

            <div class="hs-accordion-group mx-auto max-w-4xl space-y-4">
                @forelse ($faqs as $index => $faq)
                    <div
                        class="hs-accordion {{ $index === 0 ? 'active' : '' }} hs-accordion-active:border-blue/10 hs-accordion-active:bg-white rounded-xl border border-transparent">
                        <button
                            class="hs-accordion-toggle inline-flex w-full items-center justify-between gap-x-3 px-5 py-4 text-start text-lg font-medium text-black focus:outline-hidden md:text-xl"
                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-controls="hs-basic-active-bordered-collapse-{{ $faq->id }}">
                            {{ $faq->question }}
                            <svg class="hs-accordion-active:hidden size-5">
                                <use href="{{ asset('assets/images/icons/sprite.svg#plus') }}"></use>
                            </svg>
                            <svg class="hs-accordion-active:block hidden size-5">
                                <use href="{{ asset('assets/images/icons/sprite.svg#minus') }}"></use>
                            </svg>
                        </button>
                        <div role="region"
                            class="hs-accordion-content {{ $index === 0 ? '' : 'hidden' }} w-full overflow-hidden transition-[height] duration-300"
                            aria-labelledby="hs-basic-active-bordered-heading-{{ $faq->id }}">
                            <div class="px-5 pb-4">
                                <div class="prose prose-gray max-w-none text-gray-600">
                                    {!! $faq->answer !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500">
                        {{ __('No FAQs available at the moment.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
/* ─── Scroll-triggered Animations ──────────────────── */
[data-anim] {
    opacity: 0;
    transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: opacity, transform;
}
[data-anim="fade-up"]   { transform: translateY(40px); }
[data-anim="fade-down"] { transform: translateY(-40px); }
[data-anim="fade-left"] { transform: translateX(60px); }
[data-anim="fade-right"]{ transform: translateX(-60px); }
[data-anim="zoom-in"]   { transform: scale(0.9); }
[data-anim="zoom-out"]  { transform: scale(1.08); }
[data-anim="flip-up"]   { transform: perspective(800px) rotateX(8deg) translateY(30px); }

[data-anim].is-visible {
    opacity: 1;
    transform: none;
}

/* Stagger children */
[data-anim-stagger] > [data-anim] { transition-delay: calc(var(--anim-i, 0) * 0.08s); }

/* ─── Hero title: content-creator refined scale + cinematic line reveal ──
 *
 * Senior-hero aesthetic (Linear / Stripe / Notion range): ~32px mobile,
 * ~60px max desktop. Confident weight, tight leading, balanced wrap —
 * the hierarchy does the work, not raw size.
 *
 * Arabic sizes down slightly and loosens leading — Arabic glyphs carry
 * more vertical mass, so 1.02 line-height clips descenders.
 */
.hero-title {
    font-size: clamp(2rem, 2.8vw + 1rem, 3.75rem);
    line-height: 1.08;
    letter-spacing: -0.02em;
    font-feature-settings: "kern", "liga", "calt", "ss01";
    text-wrap: balance;
}
[dir="rtl"] .hero-title {
    letter-spacing: 0;
    line-height: 1.22;
    font-size: clamp(1.875rem, 2.6vw + 1rem, 3.375rem);
}

/* Each line becomes a reveal unit. Using `display: block` forces proper
   line breaks so the hero never wraps mid-phrase. */
.hero-title .hero-line {
    display: block;
    opacity: 0;
    transform: translateY(22px);
    filter: blur(5px);
    animation: heroLineIn 0.75s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    will-change: opacity, transform, filter;
    overflow: visible !important;
}
.hero-title .hero-line--1 { animation-delay: 0.08s; }
.hero-title .hero-line--2 { animation-delay: 0.22s; }
.hero-title .hero-line--3 { animation-delay: 0.36s; }
@keyframes heroLineIn {
    to { opacity: 1; transform: translateY(0); filter: blur(0); }
}

/* Shared shimmer for branded phrases (blue + green). Uses background-clip:
   text so the shimmer flows through the letterforms, no overlays. */
.hero-title .hero-line__phrase--blue,
.hero-title .hero-line__phrase--green {
    display: inline-block;
    background-clip: text;
    -webkit-background-clip: text;
    background-repeat: no-repeat;
    background-size: 220% 100%;
    background-position: 100% 0;
    letter-spacing: -0.025em;
}
.hero-title .hero-line__phrase--blue {
    background-image: linear-gradient(100deg,
        currentColor 0%, currentColor 40%,
        rgba(255,255,255,.92) 50%,
        currentColor 60%, currentColor 100%);
    animation: heroShine 2.8s ease-in-out 1.4s infinite;
}
.hero-title .hero-line__phrase--green {
    background-image: linear-gradient(100deg,
        currentColor 0%, currentColor 40%,
        rgba(255,255,255,.88) 50%,
        currentColor 60%, currentColor 100%);
    animation: heroShine 2.8s ease-in-out 2.2s infinite;
}
[dir="rtl"] .hero-title .hero-line__phrase--blue,
[dir="rtl"] .hero-title .hero-line__phrase--green {
    letter-spacing: 0;
}

/* Brand cluster: vertical stack — phrase on top, smile tucked tight
   beneath it like a slogan badge. The whole cluster is an inline-flex
   column that flows inline with the rest of line 1, so text around it
   stays on the same baseline row while the icon decorates below. */
.hero-title .hero-line__brand {
    display: inline-flex;
    align-items: center;
    vertical-align: baseline;
    line-height: 1;
    gap: 0.12em;
    overflow: visible !important;
}

.hero-title .hero-line__word-with-smile {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    line-height: 1;
    gap: 0.04em;
}

/* Smile swoosh (raster): sits tight under the brand word, spans its
   width, and wipes in left-to-right as the "draw-on" reveal. The filter
   chain tints the raster to brand green (#3fb536). */
.hero-title .hero-line__smile {
    display: block;
    inline-size: 1.9em;
    block-size: 0.24em;
    object-fit: contain;
    object-position: center;
    margin-block-start: 0;
    /* Tint raster → brand green + soft green drop-shadow */
    filter:
        brightness(0) saturate(100%)
        invert(47%) sepia(89%) saturate(414%) hue-rotate(70deg)
        brightness(99%) contrast(86%);
    /* Wipe-reveal: from clipped-to-nothing to fully visible */
    clip-path: inset(0 100% 0 0);
    animation:
        heroSmileReveal 0.85s cubic-bezier(0.65, 0, 0.35, 1) 0.35s forwards;
    transform-origin: center;
    user-select: none;
    -webkit-user-drag: none;
}

@keyframes heroSmileReveal {
    from { clip-path: inset(0 100% 0 0); }
    to   { clip-path: inset(0 0 0 0); }
}

/* RTL wipe: Arabic reads right-to-left, so reveal from right to left. */
[dir="rtl"] .hero-title .hero-line__smile {
    animation-name: heroSmileRevealRtl;
}
@keyframes heroSmileRevealRtl {
    from { clip-path: inset(0 0 0 100%); }
    to   { clip-path: inset(0 0 0 0); }
}
/* Respect reduced-motion: show the finished smile immediately. */
@media (prefers-reduced-motion: reduce) {
    .hero-title .hero-line__smile {
        clip-path: none;
        animation: none;
    }
}
@keyframes heroShine {
    0%   { background-position: 100% 0; -webkit-text-fill-color: transparent; }
    45%  { background-position:   0% 0; -webkit-text-fill-color: transparent; }
    60%  { -webkit-text-fill-color: currentColor; }
    100% { -webkit-text-fill-color: currentColor; background-position: -100% 0; }
}

@media (prefers-reduced-motion: reduce) {
    .hero-title .hero-line {
        animation: none;
        opacity: 1;
        transform: none;
        filter: none;
    }
    .hero-title .hero-line__smile,
    .hero-title .hero-line__phrase--blue,
    .hero-title .hero-line__phrase--green {
        animation: none;
    }
}

/* Hero supporting elements */
.hero-section {
    position: relative;
}
.hero-section::after {
    content: '';
    position: absolute;
    inset-inline: 0;
    bottom: -1px;
    height: 5rem;
    pointer-events: none;
    background: linear-gradient(180deg, transparent, rgba(248, 251, 255, .95));
    z-index: 5;
}
.hero-cinematic {
    position: absolute;
    inset: 0;
    z-index: 12;
    pointer-events: none;
    overflow: hidden;
    border-radius: inherit;
}
.hero-cinematic__vignette {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 90% 70% at 50% 40%, transparent 35%, rgba(15, 23, 42, .12) 100%),
        linear-gradient(180deg, transparent 55%, rgba(15, 23, 42, .28) 100%);
}
.hero-cinematic__glow {
    position: absolute;
    width: clamp(220px, 34vw, 420px);
    height: clamp(220px, 34vw, 420px);
    border-radius: 50%;
    filter: blur(60px);
    opacity: .35;
    animation: heroGlowDrift 12s ease-in-out infinite;
}
.hero-cinematic__glow--blue {
    inset-inline-end: 8%;
    inset-block-start: 10%;
    background: rgba(39, 159, 249, .45);
}
.hero-cinematic__glow--green {
    inset-inline-start: 6%;
    inset-block-end: 18%;
    background: rgba(63, 181, 54, .35);
    animation-delay: -6s;
}
@keyframes heroGlowDrift {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: .3; }
    50% { transform: translate(-12px, 8px) scale(1.08); opacity: .42; }
}
.hero-shell {
    display: flex;
    flex-direction: column;
    min-height: clamp(560px, 74vh, 780px);
    padding-top: clamp(2.6rem, 5vw, 6.5rem);
}
.hero-grid {
    min-height: inherit;
    align-items: end;
}
.hero-copy {
    padding-bottom: clamp(1.5rem, 3vw, 3rem);
}
/* Smile swoosh under "محسوبة" — inline SVG, guaranteed positioning */
.hero-smile-wrap {
    position: relative;
    display: inline-block;
    padding-bottom: 0.38em;
    overflow: visible !important;
}

.hero-smile-word {
    display: inline-block;
}

/* ─── Shared green tint filter ─────────────────────── */
/* Converts the original icon color to brand green #10B981 */
.hero-smile-icon {
    filter: brightness(0) saturate(100%) invert(56%) sepia(82%)
            saturate(458%) hue-rotate(118deg) brightness(95%) contrast(88%) !important;
}

/* ─── HERO: under "محسوبة" word ─────────────────────── */
.hero-smile-icon {
    position: absolute;
    left: 50%;
    bottom: 0.04em;
    transform: translateX(-50%) scale(0.3);
    width: 118%;
    height: 0.66em;
    max-height: 44px;
    object-fit: contain;
    pointer-events: none;
    opacity: 0;
    animation:
        heroSmileAppear 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) 0.9s forwards,
        heroSmileBreathe 3.2s ease-in-out 2s infinite;
}

@keyframes heroSmileBreathe {
    0%, 100% { transform: translateX(-50%) scale(1); }
    50%      { transform: translateX(-50%) scale(1.12); }
}

@keyframes heroSmileAppear {
    0% {
        opacity: 0;
        transform: translateX(-50%) scale(0) rotate(-15deg);
    }
    50% {
        opacity: 1;
        transform: translateX(-50%) scale(1.25) rotate(5deg);
    }
    75% {
        transform: translateX(-50%) scale(0.95) rotate(-2deg);
    }
    100% {
        opacity: 1;
        transform: translateX(-50%) scale(1) rotate(0deg);
    }
}
.hero-visual {
    width: min(100%, 720px);
}
.hero-stage {
    position: relative;
    width: min(100vw - 3rem, 680px);
    aspect-ratio: 1.2 / 1;
    margin-inline: auto;
}
.hero-bg {
    opacity: .95;
    animation: heroKenBurns 22s ease-in-out infinite alternate;
    will-change: transform;
}
@keyframes heroKenBurns {
    0% { transform: scale(1) translateX(0); }
    100% { transform: scale(1.06) translateX(-1.5%); }
}
[dir="rtl"] .hero-bg {
    animation-name: heroKenBurnsRtl;
}
@keyframes heroKenBurnsRtl {
    0% { transform: scaleX(-1) scale(1) translateX(0); }
    100% { transform: scaleX(-1) scale(1.06) translateX(-1.5%); }
}
.hero-food {
    position: absolute;
    inset-inline-end: -6%;
    inset-block-start: -1%;
    width: clamp(430px, 52vw, 760px);
    max-width: none;
    z-index: 10;
}
.hero-phones {
    position: absolute;
    inset-inline-start: 2%;
    inset-block-end: -8%;
    width: clamp(270px, 36vw, 540px);
    z-index: 30;
    filter: drop-shadow(0 30px 45px rgba(0,0,0,.26));
    opacity: 0;
    transform: translateY(44px) scale(.93);
    animation: heroPhoneIn 1s cubic-bezier(.16,1,.3,1) .65s forwards,
               heroPhoneBob 6s ease-in-out 1.8s infinite;
}
@media (max-width: 1023px) {
    .hero-shell {
        min-height: auto;
        padding-top: 2.8rem;
    }
    .hero-grid {
        align-items: start;
    }
    .hero-copy {
        padding-bottom: 0;
    }
    .hero-stage {
        width: min(100%, 560px);
        aspect-ratio: 1.1 / 1;
    }
    .hero-food {
        inset-inline-end: -12%;
        inset-block-start: 0;
        width: min(132%, 640px);
    }
    .hero-phones {
        inset-inline-start: 2%;
        inset-block-end: -8%;
        width: min(78%, 380px);
    }
}
@media (max-width: 767px) {
    .hero-shell {
        padding-top: 2.2rem;
    }
    .hero-stage {
        width: min(100%, 430px);
        aspect-ratio: 1 / 1;
    }
    .hero-food {
        inset-inline-end: -14%;
        inset-block-start: 6%;
        width: min(145%, 520px);
    }
    .hero-phones {
        width: min(80%, 305px);
        inset-inline-start: 3%;
        inset-block-end: -12%;
    }
}
.hero-desc-anim {
    opacity: 1;
    transform: none;
    animation: heroDescEnter 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.9s both;
}
.hero-btn-anim {
    position: relative;
    display: inline-flex;
    opacity: 1;
    transform: translateY(0);
    animation: heroBtnEnter 0.8s cubic-bezier(0.16, 1, 0.3, 1) 1.05s both;
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
    z-index: 2;
}
@media (hover: hover) and (pointer: fine) {
    .hero-btn-anim:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(23, 125, 201, 0.32);
    }
}
.hero-btn-anim:active,
.hero-btn-anim.hero-btn-anim--ready:active {
    transform: scale(0.98) translateY(0) !important;
    filter: brightness(0.96);
    opacity: 1 !important;
    box-shadow: 0 8px 18px rgba(23, 125, 201, 0.28) !important;
}
.hero-btn-anim--ready {
    opacity: 1 !important;
    transform: translateY(0) !important;
    animation: none !important;
}
.hero-apps-anim {
    opacity: 1;
    transform: none;
    animation: heroAppsEnter 0.8s cubic-bezier(0.16, 1, 0.3, 1) 1.2s both;
}
.hero-img-anim {
    opacity: 0;
    transform: translateX(40px) scale(0.95);
    animation: heroImgIn 1s cubic-bezier(0.16, 1, 0.3, 1) 0.4s forwards;
}
[dir="rtl"] .hero-img-anim {
    transform: translateX(-40px) scale(0.95);
}

/* Magnetic CTA glow halo */
.hero-magnetic {
    transition: transform .25s cubic-bezier(.16,1,.3,1), box-shadow .3s ease;
}
.hero-magnetic::after {
    content: "";
    position: absolute;
    inset: -6px;
    border-radius: inherit;
    background: radial-gradient(circle at var(--mx, 50%) var(--my, 50%),
                rgba(59,130,246,.55), transparent 60%);
    opacity: 0;
    transition: opacity .35s ease;
    z-index: -1;
    filter: blur(14px);
}
.hero-magnetic:hover::after { opacity: 1; }

/* App badges subtle bob on hover */
.hero-app-badge { display: inline-block; transition: transform .25s cubic-bezier(.16,1,.3,1); }
.hero-app-badge:hover { transform: translateY(-3px) scale(1.03); }

/* Parallax float wrapper for the hero image */
.hero-parallax {
    transition: transform .4s cubic-bezier(.16,1,.3,1);
}

@keyframes heroPhoneIn {
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes heroPhoneBob {
    0%, 100% { translate: 0 0; }
    50%      { translate: 0 -12px; }
}

@keyframes heroSlideUp {
    to { opacity: 1; transform: none; }
}
@keyframes heroDescEnter {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes heroBtnEnter {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes heroAppsEnter {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes heroImgIn {
    to { opacity: 1; transform: none; }
}

/* Floating effect for hero image */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.hero-float {
    animation: float 4s ease-in-out infinite;
    animation-delay: 1.5s;
}

/* ─── Premium instant products rail ─────────────────── */
.products-rail {
    position: relative;
    --rail-gap: clamp(1rem, 1.6vw, 1.6rem);
    --card-width: clamp(240px, 20vw, 300px);
    /* thumb 4:3 + title/footer/button + track padding */
    --rail-card-body-h: 11.25rem;
    --rail-viewport-min-h: calc((var(--card-width) * 0.75) + var(--rail-card-body-h) + 0.8rem);
    min-height: var(--rail-viewport-min-h);
}
.products-rail__viewport {
    position: relative;
}
.products-rail.is-rail-rebuilding {
    pointer-events: none;
}
@media (scripting: none) {
    .products-rail.is-initialized .products-rail__viewport,
    .testimonials-rail.is-initialized .testimonials-rail__viewport {
        overflow-x: auto;
    }
}
.products-empty-state {
    max-width: 36rem;
    margin: 0 auto;
    padding: 3.5rem 1.5rem;
    text-align: center;
    border-radius: 1rem;
    border: 1px dashed rgba(148, 163, 184, 0.45);
    background: rgba(255, 255, 255, 0.72);
}
.products-empty-state__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: rgba(100, 116, 139, 0.85);
}
.products-empty-state__title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.products-empty-state__desc {
    color: rgba(15, 23, 42, 0.62);
}
.products-rail__viewport,
.testimonials-rail__viewport {
    overflow-x: auto;
    overflow-y: hidden;
    border-radius: 16px;
    min-height: var(--rail-viewport-min-h, 300px);
    touch-action: pan-x;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x proximity;
    scrollbar-width: none;
    /* RTL fix: نُثبّت سياق LTR على الـ viewport نفسه حتى تكون نقطة بداية
       الـ flex والـ scroll على اليسار (لا اليمين كما في RTL الافتراضي).
       بدون هذا، يبدأ الـ track متزحلقاً لليمين فتخرج الكروت خارج الشاشة
       عند تطبيق translateX السالب. النص داخل الكروت يعود لـ rtl بقواعده. */
    direction: ltr;
}
.products-rail__viewport::-webkit-scrollbar,
.testimonials-rail__viewport::-webkit-scrollbar {
    display: none;
}
.products-rail.is-initialized .products-rail__viewport,
.testimonials-rail.is-initialized .testimonials-rail__viewport {
    cursor: grab;
    user-select: none;
    scroll-snap-type: none;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-x;
}
.products-rail.is-marquee-active .products-rail__viewport,
.testimonials-rail.is-marquee-active .testimonials-rail__viewport {
    overflow: hidden;
}
.products-rail__viewport.is-dragging {
    cursor: grabbing;
}
.products-rail__track {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    gap: 0;
    width: max-content;
    padding: .4rem;
    will-change: transform;
    transform: translate3d(0, 0, 0);
    direction: ltr;
    backface-visibility: hidden;
}
/* is-rail-running / is-rail-paused: state classes only — motion is rAF-driven */
@keyframes infinite-rail-marquee {
    from { transform: translate3d(0, 0, 0); }
    to   { transform: translate3d(-50%, 0, 0); }
}
.infinite-rail__segment {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    flex-shrink: 0;
    gap: var(--rail-gap);
}
.testimonials-rail__track .infinite-rail__segment {
    gap: var(--t-gap);
}
.products-rail__hint {
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    margin: 0;
    padding: 0;
    font-size: 0.6875rem;
    font-weight: 500;
    color: #9ca3af;
    pointer-events: none;
    z-index: 1;
}
@media (min-width: 768px) {
    .products-rail__hint {
        display: none;
    }
}
.products-rail__card {
    width: var(--card-width);
    min-width: var(--card-width);
    flex: 0 0 var(--card-width);
    transform: translateY(0) scale(1);
    transition: transform .36s cubic-bezier(.16,1,.3,1), box-shadow .36s ease;
}
[dir="rtl"] .products-rail__card {
    direction: rtl;
}
[dir="ltr"] .products-rail__card {
    direction: ltr;
}
.products-rail__card .meal-card__thumbnail {
    overflow: hidden;
    border-radius: 10px;
    aspect-ratio: 4 / 3;
}
.products-rail__card .meal-card__thumbnail img {
    display: block;
    width: 100%;
    height: auto;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    transition: transform .45s cubic-bezier(.16,1,.3,1), filter .45s ease;
    transform-origin: center;
}
.products-rail__card .meal-card__btn {
    transition: transform .22s cubic-bezier(.16,1,.3,1), opacity .24s ease, box-shadow .28s ease, background-color .28s ease, color .28s ease;
}
.products-rail__card .meal-card__lower {
    transition: transform .24s cubic-bezier(.16,1,.3,1);
}
.products-rail__card:hover {
    transform: translateY(-8px) scale(1.015);
    box-shadow: 0 18px 36px rgba(0, 0, 0, .14);
}
.products-rail__card:hover .meal-card__thumbnail img {
    transform: scale(1.06);
    filter: saturate(1.05);
}
.products-rail__card:hover .meal-card__lower {
    transform: translateY(-2px);
}
.products-rail__btn {
    position: relative;
    overflow: hidden;
}
.products-rail__btn.is-pressed {
    transform: scale(0.96);
}
.products-rail__btn.is-success {
    background: #16a34a !important;
    color: #fff !important;
    border-color: #16a34a !important;
    box-shadow: 0 8px 20px rgba(22,163,74,.28);
}
.products-rail__btn.is-success::before {
    content: "✓";
    margin-inline-end: 6px;
}
.products-rail__btn::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(110deg, transparent 20%, rgba(255,255,255,.35) 46%, transparent 70%);
    transform: translateX(-120%);
}
.products-rail__card:hover .products-rail__btn::after {
    animation: railBtnSheen .9s ease;
}
.products-rail__cursor {
    position: fixed;
    left: 0;
    top: 0;
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transform: translate3d(-100px, -100px, 0);
    z-index: 70;
    transition: opacity .2s ease;
}
.products-rail.is-cursor-active.is-cursor-positioned .products-rail__cursor {
    visibility: visible;
}
.products-rail__cursor--dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #279ff9;
}
.products-rail__cursor--ring {
    width: 74px;
    height: 74px;
    border-radius: 999px;
    border: 1px solid rgba(39,159,249,.35);
    background: rgba(39,159,249,.08);
    display: grid;
    place-items: center;
    font-size: .68rem;
    font-weight: 600;
    color: #1f2937;
    letter-spacing: .03em;
    backdrop-filter: blur(4px);
}
.products-rail.is-cursor-active.is-cursor-positioned .products-rail__cursor {
    opacity: 1;
}
.cart-badge-bounce {
    animation: cartBadgeBounce .55s cubic-bezier(.2,1.2,.25,1);
}
@keyframes railBtnSheen {
    from { transform: translateX(-120%); }
    to   { transform: translateX(120%); }
}
@keyframes cartBadgeBounce {
    0% { transform: scale(1); }
    35% { transform: scale(1.25); }
    65% { transform: scale(.93); }
    100% { transform: scale(1); }
}

/* ─── App section enhancements ──────────────────────── */
.app-section-head {
    position: relative;
}
.app-section-head::after {
    content: "";
    position: absolute;
    inset-inline: 50%;
    bottom: -10px;
    width: 94px;
    height: 4px;
    border-radius: 999px;
    transform: translateX(-50%);
    background: linear-gradient(90deg, #f472b6 0%, #fb7185 40%, #279ff9 100%);
    opacity: .85;
}
.app-store-links a {
    transition: transform .3s cubic-bezier(.16,1,.3,1), filter .3s ease;
}
.app-store-links a:hover {
    transform: translateY(-4px) scale(1.03);
    filter: drop-shadow(0 10px 18px rgba(0,0,0,.18));
}
.app-social-links {
    flex-wrap: wrap;
}
.app-social-link {
    width: 42px;
    height: 42px;
    border-radius: 999px;
    border: 1px solid rgba(17,24,39,.18);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #111827;
    background: rgba(255,255,255,.55);
    backdrop-filter: blur(4px);
    transition: transform .25s cubic-bezier(.16,1,.3,1), background-color .25s ease, box-shadow .25s ease, border-color .25s ease;
}
.app-social-link:hover {
    transform: translateY(-3px) scale(1.05);
    background: #279ff9;
    color: #fff;
    border-color: #279ff9;
    box-shadow: 0 12px 20px rgba(39,159,249,.3);
}
.app-social-link[data-platform="whatsapp"]:hover {
    background: #25D366;
    border-color: #25D366;
    box-shadow: 0 12px 20px rgba(37,211,102,.35);
}
.app-social-link[data-platform="snapchat"]:hover {
    background: #FFFC00;
    color: #111827;
    border-color: #FFFC00;
    box-shadow: 0 12px 20px rgba(255,252,0,.4);
}
.app-social-link[data-platform="instagram"]:hover {
    background: #E1306C;
    border-color: #E1306C;
    box-shadow: 0 12px 20px rgba(225,48,108,.35);
}

/* ─── Testimonials premium single-row rail ─────────── */
.testimonials-rail {
    --t-gap: clamp(1rem, 1.8vw, 1.4rem);
    --t-card-w: clamp(280px, 33vw, 390px);
    --rail-card-body-h: 9.5rem;
    --rail-viewport-min-h: calc((var(--t-card-w) * 0.62) + var(--rail-card-body-h) + 1rem);
    position: relative;
    min-height: var(--rail-viewport-min-h);
}
.testimonials-rail.is-rail-rebuilding {
    pointer-events: none;
}
[dir="rtl"] .testimonials-rail__item {
    direction: rtl;
}
[dir="ltr"] .testimonials-rail__item {
    direction: ltr;
}
.testimonials-rail__viewport {
    min-height: var(--rail-viewport-min-h);
}
.testimonials-rail__hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    margin: 0.65rem 0 0;
    font-size: 0.6875rem;
    font-weight: 500;
    color: #9ca3af;
}
@media (min-width: 768px) {
    .testimonials-rail__hint {
        display: none;
    }
}
.testimonials-rail__viewport.is-dragging {
    cursor: grabbing;
}
.testimonials-rail__track {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    gap: 0;
    width: max-content;
    will-change: transform;
    transform: translate3d(0,0,0);
    padding: .5rem .25rem;
    direction: ltr;
    backface-visibility: hidden;
}
/* testimonials rail: motion rAF-driven, same as products rail */
.testimonials-rail__item {
    width: var(--t-card-w);
    min-width: var(--t-card-w);
    flex: 0 0 var(--t-card-w);
    transform-origin: 50% 100%;
    opacity: 1;
    transform: translateY(0) scale(1);
}
.testimonials-rail__item,
.products-rail__card {
    opacity: 1 !important;
    visibility: visible !important;
}
.testimonials-rail [data-rail-clone="1"],
.products-rail [data-rail-clone="1"] {
    pointer-events: none;
}
.section-header--always-visible .section-header__subtitle,
.section-header--always-visible .section-header__title,
.section-header--always-visible .section-header__desc {
    opacity: 1 !important;
    transform: none !important;
    filter: none !important;
}
.testimonials-card {
    height: 100%;
    transition: transform .48s cubic-bezier(.16,1,.3,1), box-shadow .48s cubic-bezier(.16,1,.3,1), border-color .36s ease;
    border: 1px solid rgba(148,163,184,.18);
    box-shadow: 0 14px 30px rgba(15,23,42,.08);
}
.testimonials-rail__viewport.is-dragging .testimonials-card {
    transition-duration: .12s;
}
.testimonials-rail__viewport.is-settling .testimonials-card {
    animation: testimonialSettle .52s cubic-bezier(.22,1.25,.32,1);
}
.testimonials-card-wrap:hover .testimonials-card,
.testimonials-card-wrap:focus-within .testimonials-card {
    transform: translateY(-9px) scale(1.018);
    box-shadow: 0 24px 45px rgba(15,23,42,.14);
    border-color: rgba(39,159,249,.25);
}
.testimonials-card .review-card__quote {
    transition: transform .32s cubic-bezier(.16,1,.3,1), color .3s ease;
}
.testimonials-card-wrap:hover .review-card__quote,
.testimonials-card-wrap:focus-within .review-card__quote {
    transform: translateY(-2px) scale(1.08);
    color: #f472b6;
}
.testimonials-card .review-card__author-img {
    transition: transform .3s cubic-bezier(.16,1,.3,1), box-shadow .3s ease;
}
.testimonials-card-wrap:hover .review-card__author-img,
.testimonials-card-wrap:focus-within .review-card__author-img {
    transform: scale(1.06);
    box-shadow: 0 8px 16px rgba(15,23,42,.2);
}
@keyframes testimonialCardIn {
    from { opacity: 0; transform: translateY(16px) scale(.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes testimonialSettle {
    0% { transform: translateY(-8px) scale(1.02); }
    55% { transform: translateY(2px) scale(.996); }
    100% { transform: translateY(0) scale(1); }
}

/* ─── Blog premium motion & interactions ───────────── */
.blog-premium-head.section-header[data-anim] .section-header__subtitle,
.blog-premium-head.section-header[data-anim] .section-header__title,
.blog-premium-head.section-header[data-anim] .section-header__desc {
    opacity: 0;
    filter: blur(8px);
    transform: translateY(22px);
    transition:
        opacity .75s cubic-bezier(.16,1,.3,1),
        transform .75s cubic-bezier(.16,1,.3,1),
        filter .75s cubic-bezier(.16,1,.3,1);
}
.blog-premium-head.section-header[data-anim].is-visible .section-header__subtitle {
    opacity: 1;
    filter: blur(0);
    transform: translateY(0);
    transition-delay: .04s;
}
.blog-premium-head.section-header[data-anim].is-visible .section-header__title {
    opacity: 1;
    filter: blur(0);
    transform: translateY(0);
    transition-delay: .16s;
}
.blog-premium-head.section-header[data-anim].is-visible .section-header__desc {
    opacity: 1;
    filter: blur(0);
    transform: translateY(0);
    transition-delay: .28s;
}
.blog-premium-card {
    transform: translateY(0) scale(1);
    transition:
        transform .46s cubic-bezier(.16,1,.3,1),
        box-shadow .46s cubic-bezier(.16,1,.3,1);
    box-shadow: 0 12px 30px rgba(15,23,42,.08);
}
.blog-premium-card .blog-card__thumbnail img {
    transition: transform .95s cubic-bezier(.16,1,.3,1), filter .95s ease;
    will-change: transform;
}
.blog-premium-card .blog-card__body {
    transition: background .45s ease;
}
.blog-premium-card .blog-card__title {
    transition: transform .38s cubic-bezier(.16,1,.3,1);
}
.blog-premium-card:hover,
.blog-premium-card:focus-within {
    transform: translateY(-10px) scale(1.016);
    box-shadow: 0 26px 54px rgba(15,23,42,.2);
}
.blog-premium-card:hover .blog-card__thumbnail img,
.blog-premium-card:focus-within .blog-card__thumbnail img {
    transform: scale(1.08);
    filter: saturate(1.05);
}
.blog-premium-card:hover .blog-card__body,
.blog-premium-card:focus-within .blog-card__body {
    background: linear-gradient(
      180deg,
      rgba(0, 0, 0, 0.1) 50%,
      rgba(0, 0, 0, 0.92) 100%
    );
}
.blog-premium-card:hover .blog-card__title,
.blog-premium-card:focus-within .blog-card__title {
    transform: translateY(-3px);
}
.blog-premium-card.is-pressing {
    transform: translateY(-2px) scale(.992) !important;
    transition-duration: .16s;
}

.how-step-card {
    transition: transform .35s cubic-bezier(.16,1,.3,1), filter .35s ease;
}
.how-step-card img {
    transition: transform .5s cubic-bezier(.16,1,.3,1), filter .5s ease;
}
.how-step-card:hover {
    transform: translateY(-5px);
}
.how-step-card:hover img {
    transform: scale(1.025);
    filter: saturate(1.03);
}

@media (max-width: 767px) {
    .products-rail {
        --card-width: min(78vw, 280px);
    }
    .testimonials-rail {
        --t-card-w: min(84vw, 340px);
    }
    .products-rail.is-initialized .products-rail__viewport,
    .testimonials-rail.is-initialized .testimonials-rail__viewport {
        mask-image: none;
        -webkit-mask-image: none;
    }
}
@media (prefers-reduced-motion: reduce) {
    .products-rail__track,
    .testimonials-rail__track {
        transform: none !important;
        animation: none !important;
    }
    .products-rail__viewport,
    .testimonials-rail__viewport {
        overflow-x: auto;
        scroll-snap-type: x mandatory;
    }
    .products-rail__card,
    .testimonials-rail__item {
        scroll-snap-align: center;
    }
    .hero-desc-anim,
    .hero-btn-anim,
    .hero-apps-anim {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
    .hero-bg,
    .hero-cinematic__glow,
    .hero-phones {
        animation: none !important;
    }
    .hero-btn-anim:hover,
    .hero-btn-anim:active {
        transform: none !important;
        box-shadow: none !important;
        filter: none !important;
    }
    .products-rail__card,
    .products-rail__card .meal-card__thumbnail img,
    .products-rail__btn {
        transition: none !important;
    }
    .products-rail__cursor {
        display: none !important;
    }
    .app-store-links a,
    .app-social-link,
    .testimonials-card,
    .testimonials-card .review-card__quote,
    .testimonials-card .review-card__author-img,
    .blog-premium-head.section-header[data-anim] .section-header__subtitle,
    .blog-premium-head.section-header[data-anim] .section-header__title,
    .blog-premium-head.section-header[data-anim] .section-header__desc,
    .blog-premium-card,
    .blog-premium-card .blog-card__thumbnail img,
    .blog-premium-card .blog-card__body,
    .blog-premium-card .blog-card__title,
    .how-step-card,
    .how-step-card img {
        transition: none !important;
    }
    .testimonials-rail__track {
        transform: none !important;
    }
    .hero-smile-icon {
        opacity: 1 !important;
        transform: translateX(-50%) scale(1) !important;
        animation: none !important;
    }
}

/* Section headers entrance */
.section-header[data-anim] .section-header__subtitle {
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.5s ease 0.1s;
}
.section-header[data-anim] .section-header__title {
    opacity: 0;
    transform: translateY(15px);
    transition: all 0.5s ease 0.2s;
}
.section-header[data-anim] .section-header__desc {
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.5s ease 0.35s;
}
.section-header[data-anim].is-visible .section-header__subtitle,
.section-header[data-anim].is-visible .section-header__title,
.section-header[data-anim].is-visible .section-header__desc {
    opacity: 1;
    transform: none;
}

/* Meal plan cards hover lift */
.meal-card {
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
                box-shadow 0.35s ease;
}
.meal-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(0,0,0,0.1);
}

/* Counter animation for stats */
@keyframes countPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

</style>
@endpush

@push('scripts')
    <script>
        window.addEventListener("load", () => {
            const accordionItems = document.querySelectorAll("#why-choose-accordion .hs-accordion");
            const sideImage = document.querySelector("#why-choose-img");
            let currentIndex = 0;
            let intervalId;

            const updateImage = (index) => {
                if (sideImage) {
                    sideImage.classList.add("opacity-0");
                    setTimeout(() => {
                        sideImage.src = "{{ asset('assets/images') }}/why-" + (index + 1) + ".png";
                        const handleLoad = () => {
                            sideImage.classList.remove("opacity-0");
                            sideImage.removeEventListener("load", handleLoad);
                        };
                        sideImage.addEventListener("load", handleLoad);

                        if (sideImage.complete) {
                            handleLoad();
                        }
                    }, 300);
                }
            };

            const startLoop = () => {
                if (intervalId) clearInterval(intervalId);
                intervalId = setInterval(() => {
                    currentIndex = (currentIndex + 1) % accordionItems.length;
                    updateImage(currentIndex);
                }, 5000);
            };

            accordionItems.forEach((item, index) => {
                const toggle = item.querySelector(".hs-accordion-toggle");
                if (toggle) {
                    toggle.addEventListener("click", () => {
                        currentIndex = index;
                        updateImage(index);
                        startLoop();
                    });
                }
            });

            startLoop();
        });

        /* ─── Scroll-triggered animation observer ───────── */
        (function() {
            var els = document.querySelectorAll('[data-anim]');
            if (!els.length) return;

            document.querySelectorAll('[data-anim-stagger]').forEach(function(parent) {
                var children = parent.querySelectorAll('[data-anim]');
                children.forEach(function(child, i) {
                    child.style.setProperty('--anim-i', i);
                });
            });

            function revealAnimEl(el) {
                if (el.classList.contains('is-visible')) {
                    return;
                }
                var delay = parseInt(el.getAttribute('data-anim-delay') || '0', 10);
                if (delay > 0) {
                    setTimeout(function() {
                        el.classList.add('is-visible');
                    }, delay);
                } else {
                    el.classList.add('is-visible');
                }
            }

            function isInViewport(el) {
                var rect = el.getBoundingClientRect();
                var vh = window.innerHeight || document.documentElement.clientHeight;

                return rect.top < vh * 0.92 && rect.bottom > vh * 0.08;
            }

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        revealAnimEl(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '0px 0px -5% 0px',
                threshold: 0.05
            });

            els.forEach(function(el) {
                if (isInViewport(el)) {
                    revealAnimEl(el);
                }
                observer.observe(el);
            });

            window.addEventListener('load', function() {
                els.forEach(function(el) {
                    if (! el.classList.contains('is-visible') && isInViewport(el)) {
                        revealAnimEl(el);
                    }
                });
            }, { once: true });

            setTimeout(function() {
                els.forEach(function(el) {
                    if (! el.classList.contains('is-visible')) {
                        revealAnimEl(el);
                    }
                });
            }, 2200);
        })();

        /* ─── Hero CTA: lock visible state after entrance animation ─── */
        (function() {
            document.querySelectorAll('.hero-btn-anim').forEach(function(btn) {
                function markReady() {
                    btn.classList.add('hero-btn-anim--ready');
                }
                btn.addEventListener('animationend', markReady, { once: true });
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    markReady();
                }
            });
        })();

        /* ─── Hero image parallax (desktop only) ─── */
        (function() {
            var heroImg = document.querySelector('.hero-parallax');
            if (heroImg && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                document.addEventListener('pointermove', function(e) {
                    var w = window.innerWidth;
                    var h = window.innerHeight;
                    var dx = (e.clientX / w - 0.5) * 14;
                    var dy = (e.clientY / h - 0.5) * 10;
                    heroImg.style.transform = 'translate(' + dx + 'px,' + dy + 'px)';
                });
            }
        })();

        /* ─── Shared infinite rail (store + testimonials): vanilla JS, RTL/LTR safe ─── */
        function getPageDirection() {
            return document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr';
        }

        function forceRevealRail(section) {
            if (!section) return;
            section.classList.remove('is-rail-rebuilding');
            section.classList.add('is-initialized');
            var viewport = section.querySelector('.products-rail__viewport, .testimonials-rail__viewport');
            var track = section.querySelector('[data-products-track], [data-testimonials-track]');
            if (viewport) viewport.style.overflowX = 'auto';
            if (track) track.style.transform = 'none';
        }

        function scheduleRailSafetyReveal(section, ms) {
            if (!section || section._railSafetyTimer) return;
            section._railSafetyTimer = setTimeout(function() {
                if (!section.classList.contains('is-initialized')) {
                    forceRevealRail(section);
                }
                section._railSafetyTimer = null;
            }, ms || 3000);
        }

        function clearRailSafetyReveal(section) {
            if (section && section._railSafetyTimer) {
                clearTimeout(section._railSafetyTimer);
                section._railSafetyTimer = null;
            }
        }

        function ensureRailDirObserver() {
            if (window._railDirObserverBound) return;
            window._railDirObserverBound = true;
            if (typeof MutationObserver === 'undefined') return;

            var dirObs = new MutationObserver(function(mutations) {
                var dirChanged = false;
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].attributeName === 'dir') {
                        dirChanged = true;
                        break;
                    }
                }
                if (!dirChanged) return;

                document.querySelectorAll('[data-products-rail], [data-testimonials-rail]').forEach(function(section) {
                    var savedCfg = section._railCfg;
                    if (!savedCfg) return;
                    try {
                        if (section._railApi && typeof section._railApi.destroy === 'function') {
                            section._railApi.destroy();
                        }
                        initInfiniteRail(savedCfg);
                    } catch (err) {
                        forceRevealRail(section);
                    }
                });
            });
            dirObs.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['dir'],
            });
            window._railDirObserver = dirObs;
        }

        function initInfiniteRail(cfg) {
            var section  = cfg.section;
            var viewport = cfg.viewport;
            var track    = cfg.track;
            if (!section || !viewport || !track) return null;

            if (section._railApi && typeof section._railApi.destroy === 'function') {
                section._railApi.destroy();
            }

            var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var canHoverPause  = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            var speed = parseFloat(section.getAttribute('data-rail-speed') || '') || cfg.speed || 0.12;
            if (isNaN(speed) || speed <= 0) speed = 0.12;

            var listeners   = [];
            var visObs      = null;
            var resizeTimer = null;
            // rAF state
            var rafId      = null;
            var posX       = 0;       // current X offset in px  (0 .. -segW)
            var lastTs     = null;    // last rAF timestamp
            var cachedSegW = 0;       // segment width, measured once + on resize
            var state = {
                animating:      false,
                isDragging:     false,
                isPaused:       false,
                hasBeenVisible: false,
                pointerId:      null,
                dragStartX:     0,
                dragOffsetX:    0,
                resumeTimer:    null,
            };

            function on(el, evt, fn, opts) {
                el.addEventListener(evt, fn, opts);
                listeners.push({ el: el, evt: evt, fn: fn, opts: opts });
            }

            track.style.direction = 'ltr';
            track.style.transform = 'translate3d(0,0,0)';

            /* ── Segment width ───────────────────────── */
            function getSegmentWidth() {
                var orig  = track.querySelector('[data-rail-segment="original"]');
                var clone = track.querySelector('[data-rail-segment="clone"]');
                // الأدق: المسافة بين بداية النسخة الأصلية وبداية النسخة المستنسخة
                // (تشمل الـ gap الفاصل بين الـ segments) → wrap بلا فراغ.
                if (orig && clone) {
                    var d = clone.offsetLeft - orig.offsetLeft;
                    if (d > 0) return d;
                }
                // احتياطي: العرض الفعلي للنسخة الأصلية (للحالات بلا clone)
                if (orig) {
                    var w = orig.getBoundingClientRect().width;
                    if (w > 0) return w;
                    if (orig.offsetWidth > 0) return orig.offsetWidth;
                }
                return 0;
            }

            /* ── rAF loop ────────────────────────────── */
            function tick(ts) {
                // أعد قياس عرض الـ segment طالما لم نحصل على قيمة موثوقة بعد.
                // هذا يزيل تأخّر البدء (الصور/الخطوط قد لا تكون جاهزة في أول إطار)
                // ويمنع استمرار الحركة بقيمة 0 التي تترك فراغاً في النهاية.
                if (cachedSegW <= 0) {
                    cachedSegW = getSegmentWidth();
                }

                if (lastTs !== null && cachedSegW > 0) {
                    var elapsed = Math.min(ts - lastTs, 100);
                    posX -= speed * elapsed;
                    // لفّ فوري بمجرد انتهاء النسخة الأولى — بلا أي فراغ.
                    // modulo يضمن استمرار النسخة المستنسخة في مكان النسخة الأصلية تماماً.
                    while (posX <= -cachedSegW) {
                        posX += cachedSegW;
                    }
                    track.style.transform = 'translate3d(' + posX.toFixed(3) + 'px,0,0)';
                }
                lastTs = ts;
                rafId = requestAnimationFrame(tick);
            }

            function startLoop() {
                if (rafId !== null) return;
                lastTs = null;
                rafId = requestAnimationFrame(tick);
            }

            function stopLoop() {
                if (rafId !== null) { cancelAnimationFrame(rafId); rafId = null; }
                lastTs = null;
            }

            /* ── Animation control ───────────────────── */
            function startAnim(startPx) {
                stopLoop();
                // حماية إضافية ضد بداية scroll متزحلقة في RTL:
                // نُصفّر أي إزاحة scroll أفقية على الـ viewport قبل بدء الحركة.
                if (viewport.scrollLeft !== 0) {
                    try { viewport.scrollLeft = 0; } catch (e) {}
                }
                posX = typeof startPx === 'number' ? startPx : 0;
                track.style.transform = 'translate3d(' + posX.toFixed(3) + 'px,0,0)';
                section.classList.add('is-marquee-active');
                track.classList.add('is-rail-running');
                track.classList.remove('is-rail-paused');
                state.animating = true;
                startLoop();
            }

            function stopAnim() {
                stopLoop();
                track.classList.remove('is-rail-running', 'is-rail-paused');
                section.classList.remove('is-marquee-active');
                state.animating = false;
            }

            function syncPause() {
                if (state.isPaused || state.isDragging) {
                    stopLoop();
                    track.classList.add('is-rail-paused');
                } else {
                    track.classList.remove('is-rail-paused');
                    if (state.animating && rafId === null) startLoop();
                }
            }

            /* ── Drag ────────────────────────────────── */
            function pointerDown(e) {
                if (cfg.ignoreDragSelector && e.target.closest(cfg.ignoreDragSelector)) return;
                state.dragOffsetX = posX;          // capture before stopping loop
                stopLoop();                        // pause rAF; is-marquee-active stays
                state.isDragging = true;
                state.pointerId  = e.pointerId;
                state.dragStartX = e.clientX;
                track.style.transform = 'translate3d(' + posX.toFixed(3) + 'px,0,0)';
                viewport.classList.add('is-dragging');
                try { viewport.setPointerCapture(e.pointerId); } catch (err) {}
            }

            function pointerMove(e) {
                if (!state.isDragging || e.pointerId !== state.pointerId) return;
                var x = state.dragOffsetX + (e.clientX - state.dragStartX);
                track.style.transform = 'translate3d(' + x.toFixed(3) + 'px,0,0)';
            }

            function commitDrag(finalClientX) {
                var finalX = state.dragOffsetX + (finalClientX - state.dragStartX);
                var segW   = getSegmentWidth();
                // Normalise to (-segW, 0]
                posX = segW > 0 ? -((((-finalX % segW) + segW) % segW)) : 0;
                state.isDragging = false;
                viewport.classList.remove('is-dragging');
                startAnim(posX);
            }

            function pointerUp(e) {
                if (!state.isDragging || e.pointerId !== state.pointerId) return;
                try { viewport.releasePointerCapture(e.pointerId); } catch (err) {}
                commitDrag(e.clientX);
            }

            function onLostCapture() {
                if (!state.isDragging) return;
                state.isDragging = false;
                viewport.classList.remove('is-dragging');
                startAnim(posX);
            }

            /* ── Pause / resume ──────────────────────── */
            function pauseRail(autoResumeMs) {
                state.isPaused = true;
                syncPause();
                clearTimeout(state.resumeTimer);
                if (autoResumeMs) {
                    state.resumeTimer = setTimeout(function() {
                        state.isPaused = false;
                        syncPause();
                    }, autoResumeMs);
                }
            }

            function resumeRail(delayMs) {
                clearTimeout(state.resumeTimer);
                state.resumeTimer = setTimeout(function() {
                    state.isPaused = false;
                    syncPause();
                }, delayMs || 0);
            }

            /* ── Boot ────────────────────────────────── */
            function reveal() {
                section.classList.add('is-initialized');
                section.classList.remove('is-rail-rebuilding');
                clearRailSafetyReveal(section);
            }

            function boot() {
                // أجبر كل صور الـ rail على التحميل الفوري: في كاروسيل متحرّك
                // الصور خارج الشاشة لا "تدخل" الـ viewport أبداً، فالمتصفح يؤجّلها
                // للأبد (lazy intervention) وتظهر فاضية. eager يضمن ظهورها.
                track.querySelectorAll('img').forEach(function(img) {
                    img.loading = 'eager';
                    if (img.getAttribute('decoding') !== 'async') {
                        img.setAttribute('decoding', 'async');
                    }
                });

                var cloneSeg = track.querySelector('[data-rail-segment="clone"]');
                if (cloneSeg) {
                    cloneSeg.querySelectorAll('img').forEach(function(img) { img.loading = 'eager'; });
                }

                if (prefersReduced) {
                    viewport.style.overflowX = 'auto';
                    track.style.transform = 'none';
                    reveal();
                    return;
                }

                // Two rAFs: let layout flush before measuring segment width
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        reveal();
                        cachedSegW = getSegmentWidth();
                        startAnim(0);
                    });
                });

                // إعادة قياس بعد اكتمال تحميل الصور والخطوط: قبل ذلك تكون أبعاد
                // البطاقات غير نهائية فيخرج عرض الـ segment خاطئاً (سبب الفراغ والتأخير).
                function remeasure() {
                    var w = getSegmentWidth();
                    if (w > 0) {
                        cachedSegW = w;
                        if (posX < -cachedSegW) {
                            posX = posX % cachedSegW;
                        }
                    }
                }

                track.querySelectorAll('img').forEach(function(img) {
                    if (!img.complete) {
                        img.addEventListener('load', remeasure, { once: true });
                        img.addEventListener('error', remeasure, { once: true });
                    }
                });

                if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
                    document.fonts.ready.then(remeasure);
                }
                window.addEventListener('load', remeasure, { once: true });
            }

            /* ── Events ──────────────────────────────── */
            if (!prefersReduced) {
                if (canHoverPause) {
                    on(section, 'mouseenter', function() { pauseRail(1500); });
                    on(section, 'mouseleave', function() { resumeRail(80); });
                }
                on(viewport, 'pointerdown', pointerDown);
                on(viewport, 'pointermove', pointerMove, { passive: true });
                on(viewport, 'pointerup', pointerUp);
                on(viewport, 'pointercancel', pointerUp);
                on(viewport, 'lostpointercapture', onLostCapture);
            }

            // Pause when out of view, resume when back
            if ('IntersectionObserver' in window) {
                visObs = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) state.hasBeenVisible = true;
                        if (!state.hasBeenVisible) return;
                        state.isPaused = !entry.isIntersecting;
                        syncPause();
                    });
                }, { threshold: 0, rootMargin: '200px 0px' });
                visObs.observe(section);
            }

            // On resize: invalidate cached width and re-normalise posX
            on(window, 'resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    cachedSegW = getSegmentWidth();
                    if (cachedSegW > 0 && posX < -cachedSegW) {
                        posX = posX % cachedSegW;   // wrap to (-segW, 0]
                    }
                }, 200);
            }, { passive: true });

            /* ── Destroy ─────────────────────────────── */
            function destroy() {
                stopAnim();
                clearTimeout(state.resumeTimer);
                clearTimeout(resizeTimer);
                listeners.forEach(function(b) { b.el.removeEventListener(b.evt, b.fn, b.opts); });
                listeners.length = 0;
                if (visObs) { visObs.disconnect(); visObs = null; }
                clearRailSafetyReveal(section);
                track.style.transform = '';
                viewport.classList.remove('is-dragging');
                section.classList.remove('is-marquee-active', 'is-initialized');
                section.classList.add('is-rail-rebuilding');
                section.removeAttribute('data-rail-initialized');
                section._railApi = null;
            }

            section.setAttribute('data-rail-initialized', '1');
            section._railCfg = cfg;
            scheduleRailSafetyReveal(section, 3000);
            ensureRailDirObserver();

            try {
                boot();
            } catch (err) {
                viewport.style.overflowX = 'auto';
                track.style.transform = 'none';
                forceRevealRail(section);
            }

            section._railApi = {
                destroy:   destroy,
                rebuild:   function() {
                    cachedSegW = getSegmentWidth();
                    if (state.animating && !state.isDragging && cachedSegW > 0 && posX < -cachedSegW) {
                        posX = posX % cachedSegW;
                    }
                },
                direction: getPageDirection,
            };

            return section._railApi;
        }

        function bootInfiniteRails() {
            var productsSection = document.querySelector('[data-products-rail]');
            if (productsSection && productsSection.getAttribute('data-rail-initialized') !== '1') {
                try {
                    initInfiniteRail({
                        section: productsSection,
                        viewport: productsSection.querySelector('.products-rail__viewport'),
                        track: productsSection.querySelector('[data-products-track]'),
                        itemSelector: '[data-rail-item]',
                        speed: 0.1,
                        ignoreDragSelector: '[data-add-to-cart-btn]',
                    });
                } catch (err) {
                    if (typeof console !== 'undefined' && console.error) {
                        console.error('[products-rail] boot failed', err);
                    }
                    forceRevealRail(productsSection);
                }
            }

            var testimonialsSection = document.querySelector('[data-testimonials-rail]');
            if (testimonialsSection && testimonialsSection.getAttribute('data-rail-initialized') !== '1') {
                try {
                    initInfiniteRail({
                        section: testimonialsSection,
                        viewport: testimonialsSection.querySelector('[data-testimonials-viewport]'),
                        track: testimonialsSection.querySelector('[data-testimonials-track]'),
                        itemSelector: '[data-testimonial-item]',
                        speed: 0.06,
                    });
                } catch (err) {
                    if (typeof console !== 'undefined' && console.error) {
                        console.error('[testimonials-rail] boot failed', err);
                    }
                    forceRevealRail(testimonialsSection);
                }
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootInfiniteRails);
        } else {
            bootInfiniteRails();
        }

        (function() {
            var section = document.querySelector('[data-products-rail]');
            if (!section) return;

            var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            var viewport = section.querySelector('.products-rail__viewport');
            var dot = section.querySelector('.products-rail__cursor--dot');
            var ring = section.querySelector('.products-rail__cursor--ring');
            if (viewport && dot && ring && !prefersReduced && window.matchMedia('(hover: hover)').matches) {
                var cursorX = 0;
                var cursorY = 0;
                var ringX = 0;
                var ringY = 0;
                var rafId = null;

                function animateCursor() {
                    ringX += (cursorX - ringX) * 0.14;
                    ringY += (cursorY - ringY) * 0.14;
                    dot.style.transform = 'translate3d(' + cursorX + 'px,' + cursorY + 'px,0) translate(-50%, -50%)';
                    ring.style.transform = 'translate3d(' + ringX + 'px,' + ringY + 'px,0) translate(-50%, -50%)';
                    rafId = requestAnimationFrame(animateCursor);
                }

                viewport.addEventListener('mouseenter', function() {
                    if (!section.classList.contains('is-initialized')) return;
                    section.classList.add('is-cursor-active');
                    if (!rafId) {
                        rafId = requestAnimationFrame(animateCursor);
                    }
                });

                viewport.addEventListener('mouseleave', function() {
                    section.classList.remove('is-cursor-active', 'is-cursor-positioned');
                    if (rafId) {
                        cancelAnimationFrame(rafId);
                        rafId = null;
                    }
                });

                viewport.addEventListener('mousemove', function(e) {
                    cursorX = e.clientX;
                    cursorY = e.clientY;
                    section.classList.add('is-cursor-positioned');
                }, { passive: true });
            }

            function bounceCartBadge() {
                var badge = document.querySelector('[data-cart-count], .cart-badge, #cart-count, [data-cart-badge]');
                if (!badge) return;
                badge.classList.remove('cart-badge-bounce');
                void badge.offsetWidth;
                badge.classList.add('cart-badge-bounce');
            }

            function flyToCart(fromEl) {
                if (prefersReduced) return;
                var target = document.querySelector('[data-cart-icon], .header-cart, .cart-toggle, a[href*="cart"]');
                if (!target) return;

                var start = fromEl.getBoundingClientRect();
                var end = target.getBoundingClientRect();
                if (!start.width || !end.width) return;

                var ghost = document.createElement('span');
                ghost.setAttribute('aria-hidden', 'true');
                ghost.style.position = 'fixed';
                ghost.style.left = (start.left + start.width / 2) + 'px';
                ghost.style.top = (start.top + start.height / 2) + 'px';
                ghost.style.width = '12px';
                ghost.style.height = '12px';
                ghost.style.borderRadius = '999px';
                ghost.style.background = '#279ff9';
                ghost.style.boxShadow = '0 0 0 10px rgba(39,159,249,.18)';
                ghost.style.zIndex = '120';
                ghost.style.pointerEvents = 'none';
                ghost.style.transition = 'transform .55s cubic-bezier(.16,1,.3,1), opacity .55s ease';
                document.body.appendChild(ghost);

                var dx = (end.left + end.width / 2) - (start.left + start.width / 2);
                var dy = (end.top + end.height / 2) - (start.top + start.height / 2);
                requestAnimationFrame(function() {
                    ghost.style.transform = 'translate(' + dx + 'px,' + dy + 'px) scale(.45)';
                    ghost.style.opacity = '0';
                });

                setTimeout(function() {
                    ghost.remove();
                }, 600);
            }

            section.querySelectorAll('[data-add-to-cart-btn]').forEach(function(btn) {
                var defaultLabel = btn.getAttribute('data-default-label') || btn.textContent.trim();
                var successLabel = btn.getAttribute('data-success-label') || 'Added';
                var resetTimer = null;

                btn.addEventListener('pointerdown', function() {
                    btn.classList.add('is-pressed');
                });
                ['pointerup', 'pointercancel', 'mouseleave'].forEach(function(evt) {
                    btn.addEventListener(evt, function() {
                        btn.classList.remove('is-pressed');
                    });
                });

                btn.addEventListener('click', function() {
                    btn.classList.add('is-success');
                    btn.textContent = successLabel;
                    clearTimeout(resetTimer);
                    resetTimer = setTimeout(function() {
                        btn.classList.remove('is-success');
                        btn.textContent = defaultLabel;
                    }, 1400);
                    bounceCartBadge();
                    flyToCart(btn);
                });
            });

            if (!prefersReduced && window.matchMedia('(hover: hover)').matches) {
                section.querySelectorAll('.products-rail__btn').forEach(function(btn) {
                    btn.addEventListener('pointermove', function(e) {
                        var r = btn.getBoundingClientRect();
                        var x = e.clientX - r.left;
                        var y = e.clientY - r.top;
                        btn.style.setProperty('--mx', x + 'px');
                        btn.style.setProperty('--my', y + 'px');
                        var dx = (x - r.width / 2) / r.width;
                        var dy = (y - r.height / 2) / r.height;
                        btn.style.transform = 'translate(' + (dx * 5) + 'px,' + (dy * 3) + 'px)';
                    });
                    btn.addEventListener('pointerleave', function() {
                        btn.style.transform = '';
                    });
                });
            }
        })();

        /* ─── Blog premium interactions: parallax + press feedback ─── */
        (function() {
            var cards = Array.from(document.querySelectorAll('[data-blog-card]'));
            if (!cards.length) return;

            var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var canHover = window.matchMedia('(hover: hover)').matches;

            if (!prefersReduced && canHover) {
                cards.forEach(function(card) {
                    var img = card.querySelector('.blog-card__thumbnail img');
                    if (!img) return;

                    card.addEventListener('pointermove', function(e) {
                        var rect = card.getBoundingClientRect();
                        var rx = (e.clientX - rect.left) / rect.width - 0.5;
                        var ry = (e.clientY - rect.top) / rect.height - 0.5;
                        img.style.transform = 'scale(1.08) translate(' + (rx * 7) + 'px,' + (ry * 5) + 'px)';
                    });

                    card.addEventListener('pointerleave', function() {
                        img.style.transform = '';
                    });
                });
            }

            document.querySelectorAll('[data-blog-link]').forEach(function(link) {
                link.addEventListener('pointerdown', function() {
                    var card = link.closest('[data-blog-card]');
                    if (!card) return;
                    card.classList.add('is-pressing');
                });

                ['pointerup', 'pointercancel', 'mouseleave'].forEach(function(evt) {
                    link.addEventListener(evt, function() {
                        var card = link.closest('[data-blog-card]');
                        if (!card) return;
                        setTimeout(function() { card.classList.remove('is-pressing'); }, 120);
                    });
                });
            });
        })();
    </script>
@endpush