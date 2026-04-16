@extends('layouts.app')

@section('title', __('Diet Watchers'))

@section('content')
    {{-- Hero Section --}}
    <section>
        <div
            class="hero-shell relative container overflow-hidden rounded-md bg-gray-200">
            <div class="hero-grid relative z-20 mx-auto grid w-full max-w-[1500px] gap-10 lg:grid-cols-2 lg:gap-0">
                <div class="hero-copy md:pb-28">
                    <h1 class="hero-title mb-4 text-4xl font-bold md:mb-7 lg:text-6xl/tight">
                        <span class="hero-word hero-word--green text-green">{{ __('Healthy') }}</span>
                        <span class="hero-word">{{ __('Meals') }}</span>
                        <span class="hero-word">{{ __('Delivered') }}</span>
                        <span class="hero-word">{{ __('Daily.') }}</span>
                        <span class="hero-word">{{ __('Designed') }}</span>
                        <span class="hero-word">{{ __('for') }}</span>
                        <br class="hidden lg:block" />
                        <span class="hero-word">{{ __('Your') }}</span>
                        <span class="hero-word hero-word--blue text-blue">{{ __('Goals.') }}</span>
                    </h1>
                    <p class="hero-desc-anim mb-5 max-w-xl text-lg text-black/80 md:mb-12 lg:text-2xl">
                        {{ __('Chef-made, calorie-smart meals delivered in Saudi Arabia. Plans online, managed via our app.') }}
                    </p>

                    <a href="https://app.diet-watchers.sa/meal-plans" class="hero-btn-anim hero-magnetic btn btn--primary mb-8 text-lg">
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
        </div>
    </section>

    {{-- Meal Plans Section --}}
    <section class="py-20">
        <div class="container">
            <header class="section-header section-header--center app-section-head" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ __('Meal Plan') }}</h4>
                <h2 class="section-header__title">{{ __('Meal Plans for Every Lifestyle') }}</h2>
                <p class="section-header__desc">
                    {{ __('Expert-designed plans with transparent calories and flexible pricing.') }}
                </p>
            </header>

            <div class="mb-10 grid grid-cols-1 gap-6 md:mb-14 md:grid-cols-2 lg:grid-cols-3" data-anim-stagger>
                @forelse($mealPlanCategories as $category)
                    @php
                        $catDesc = $category['description'] ?? '';
                        $imgIdx  = $loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3;
                        $cardImg = asset('assets/images/meal-plan-' . $imgIdx . '.png');
                    @endphp
                    <a href="{{ route('meal-plans.index', ['category' => $category['id']]) }}"
                       class="rounded-xl border border-gray-300 p-3 block transition hover:shadow-md hover:border-blue/40" data-anim="fade-up">
                        <img src="{{ $cardImg }}" class="mb-4 w-full rounded-lg" alt="" />
                        <p class="px-2 text-center text-lg text-black/70 md:text-xl">
                            {{ $catDesc ?: '' }}
                        </p>
                    </a>
                @empty
                    {{-- Fallback static content when no categories from external DB --}}
                    <a href="{{ route('meal-plans.index') }}"
                       class="rounded-xl border border-gray-300 p-3 block transition hover:shadow-md hover:border-blue/40">
                        <img src="{{ asset('assets/images/meal-plan-1.png') }}" class="mb-4 w-full rounded-lg" alt="" />
                        <p class="px-2 text-center text-lg text-black/70 md:text-xl">
                            {{ __('Provides balanced, portion-controlled meals to support healthy weight goals.') }}
                        </p>
                    </a>
                    <a href="{{ route('meal-plans.index') }}"
                       class="rounded-xl border border-gray-300 p-3 block transition hover:shadow-md hover:border-blue/40">
                        <img src="{{ asset('assets/images/meal-plan-2.png') }}" class="mb-4 w-full rounded-lg" alt="" />
                        <p class="px-2 text-center text-lg text-black/70 md:text-xl">
                            {{ __('Supports everyday health and manage medical conditions through nutrition.') }}
                        </p>
                    </a>
                    <a href="{{ route('meal-plans.index') }}"
                       class="rounded-xl border border-gray-300 p-3 block transition hover:shadow-md hover:border-blue/40">
                        <img src="{{ asset('assets/images/meal-plan-3.png') }}" class="mb-4 w-full rounded-lg" alt="" />
                        <p class="px-2 text-center text-lg text-black/70 md:text-xl">
                            {{ __('Focuses on balanced, nutritious eating for everyday wellness.') }}
                        </p>
                    </a>
                @endforelse
            </div>

            <div class="text-center">
                <a href="https://app.diet-watchers.sa/meal-plans" class="btn btn--primary btn--md">{{ __('Choose Your Meal Plan') }}</a>
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
            <header class="section-header">
                <h4 class="section-header__subtitle">{{ __('Instant Orders') }}</h4>
                <h2 class="section-header__title">{{ __('Order Individual Meals Anytime') }}</h2>
                <p class="section-header__desc">
                    {{ __('Explore chef-prepared meals and healthy options available for instant order.') }}
                </p>
            </header>

            <div class="products-rail" data-products-rail data-anim="fade-up">
                <div class="products-rail__viewport">
                    <div class="products-rail__track" data-products-track>
                @forelse($instantMeals as $meal)
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
                    <article class="meal-card products-rail__card" data-rail-item>
                        <div class="meal-card__thumbnail">
                            <a href="{{ route('store.show', $meal['id']) }}">
                                <img src="{{ $mealImageUrl }}" alt="{{ $meal['name'] }}" onerror="this.src='{{ $mealFallback }}'" />
                            </a>
                        </div>

                        <div class="meal-card__body">
                            <a href="{{ route('store.show', $meal['id']) }}" class="meal-card__title-link">
                                <h3 class="meal-card__title">{{ $meal['name'] }}</h3>
                            </a>

                            <div class="meal-card__lower">
                                <div class="meal-card__footer">
                                    @if(! empty($meal['tag_name']))
                                        <span class="meal-card__category">{{ $meal['tag_name'] }}</span>
                                    @endif
                                    <div class="meal-card__price-wrap">
                                        @if(($meal['offer_price'] ?? 0) > 0 && $meal['offer_price'] < $meal['price'])
                                            <span class="meal-card__price">
                                                <span class="line-through text-gray-600 text-sm">{{ __('SAR') }} {{ number_format($meal['price'], 0) }}</span>
                                                {{ __('SAR') }} {{ number_format($meal['offer_price'], 0) }}
                                            </span>
                                        @else
                                            <span class="meal-card__price">{{ __('SAR') }} {{ number_format($meal['price'], 0) }}</span>
                                        @endif
                                    </div>
                                </div>

                                <button type="button"
                                        class="meal-card__btn products-rail__btn hero-magnetic"
                                        data-add-to-cart-btn
                                        data-default-label="{{ __('Add to Cart') }}"
                                        data-success-label="{{ __('Added') }}"
                                        onclick="Livewire.dispatch('add-to-cart', { mealId: {{ $meal['id'] }}, name: '{{ addslashes($meal['name']) }}', price: {{ $effectivePrice }}, image: '{{ addslashes($mealImageUrl) }}' })">
                                    {{ __('Add to Cart') }}
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full text-center py-16">
                        <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-black mb-2">{{ __('No meals available') }}</h3>
                        <p class="text-black/60">{{ __('Check back soon for new meals.') }}</p>
                    </div>
                @endforelse
                    </div>
                </div>
                <div class="products-rail__cursor products-rail__cursor--dot" aria-hidden="true"></div>
                <div class="products-rail__cursor products-rail__cursor--ring" aria-hidden="true">
                    <span>{{ __('Drag') }}</span>
                </div>
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
                @if(!empty($socialInstagram) && $socialInstagram !== '#')
                    <a href="{{ $socialInstagram }}" target="_blank" rel="noopener" class="app-social-link hero-magnetic" aria-label="{{ __('Instagram') }}">
                        <svg class="size-5"><use href="{{ asset('assets/images/icons/sprite.svg#instagram') }}"></use></svg>
                    </a>
                @endif
                @if(!empty($socialFacebook) && $socialFacebook !== '#')
                    <a href="{{ $socialFacebook }}" target="_blank" rel="noopener" class="app-social-link hero-magnetic" aria-label="{{ __('Facebook') }}">
                        <img src="{{ asset('assets/images/icons/facebook.svg') }}" alt="" class="size-5 object-contain" />
                    </a>
                @endif
                @if(!empty($socialTwitter) && $socialTwitter !== '#')
                    <a href="{{ $socialTwitter }}" target="_blank" rel="noopener" class="app-social-link hero-magnetic" aria-label="{{ __('Twitter') }}">
                        <img src="{{ asset('assets/images/icons/twitter.svg') }}" alt="" class="size-5 object-contain" />
                    </a>
                @endif
                @if(!empty($socialLinkedIn) && $socialLinkedIn !== '#')
                    <a href="{{ $socialLinkedIn }}" target="_blank" rel="noopener" class="app-social-link hero-magnetic" aria-label="{{ __('LinkedIn') }}">
                        <img src="{{ asset('assets/images/icons/linkedint.svg') }}" alt="" class="size-5 object-contain" />
                    </a>
                @endif
                @if(!empty($socialYouTube) && $socialYouTube !== '#')
                    <a href="{{ $socialYouTube }}" target="_blank" rel="noopener" class="app-social-link hero-magnetic" aria-label="{{ __('YouTube') }}">
                        <svg class="size-5"><use href="{{ asset('assets/images/icons/sprite.svg#telegram') }}"></use></svg>
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
            <header class="section-header section-header--center testimonials-header" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ $testimonialHeader?->badge_title() ?? __('Feedback') }}</h4>
                <h2 class="section-header__title">{{ $testimonialHeader?->title() ?? __('What our customer say') }}</h2>
                <p class="section-header__desc">
                    {{ $testimonialHeader?->subtitle() ?? __('Real experiences from customers who have made healthy eating part of their everyday lives with Diet Watchers.') }}
                </p>
            </header>

            <div class="testimonials-rail" data-testimonials-rail data-anim="fade-up">
                <div class="testimonials-rail__viewport" data-testimonials-viewport>
                    <div class="testimonials-rail__track" data-testimonials-track>
                @forelse ($testimonials as $testimonial)
                    <article class="hs-carousel-slide testimonials-card-wrap testimonials-rail__item" data-testimonial-item>
                        <div class="review-card testimonials-card">
                            <svg class="review-card__quote">
                                <use href="{{ asset('assets/images/icons/sprite.svg#quote') }}"></use>
                            </svg>

                            <p class="review-card__content">
                                {{ $testimonial->content }}
                            </p>

                            <div class="review-card__rating">
                                @for ($j = 0; $j < 5; $j++)
                                    <svg class="{{ $j < $testimonial->rating ? '' : 'text-gray-300' }}">
                                        <use href="{{ asset('assets/images/icons/sprite.svg#star') }}"></use>
                                    </svg>
                                @endfor
                            </div>

                            <div class="review-card__author">
                                <img class="review-card__author-img" src="{{ $testimonial->author_image_url ?? asset('assets/images/Profile.png') }}" alt="{{ $testimonial->author_name }}" />
                                <div>
                                    <h3 class="review-card__author-name">{{ $testimonial->author_name }}</h3>
                                    @if($testimonial->author_title)
                                        <p class="text-sm text-gray-500">{{ $testimonial->author_title }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="text-center text-gray-500">
                        {{ __('No testimonials available yet.') }}
                    </div>
                @endforelse
                    </div>
                </div>
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

/* ─── Hero: word-by-word reveal ─── */
.hero-title .hero-word {
    display: inline-block;
    opacity: 0;
    transform: translateY(28px) skewY(4deg);
    filter: blur(6px);
    animation: heroWordIn 0.75s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.hero-title .hero-word:nth-child(1)  { animation-delay: 0.10s; }
.hero-title .hero-word:nth-child(2)  { animation-delay: 0.18s; }
.hero-title .hero-word:nth-child(3)  { animation-delay: 0.26s; }
.hero-title .hero-word:nth-child(4)  { animation-delay: 0.34s; }
.hero-title .hero-word:nth-child(5)  { animation-delay: 0.42s; }
.hero-title .hero-word:nth-child(6)  { animation-delay: 0.50s; }
.hero-title .hero-word:nth-child(8)  { animation-delay: 0.58s; }
.hero-title .hero-word:nth-child(9)  { animation-delay: 0.66s; }
@keyframes heroWordIn {
    to { opacity: 1; transform: translateY(0) skewY(0); filter: blur(0); }
}

/* Shimmer on highlight words after they settle */
.hero-title .hero-word--green,
.hero-title .hero-word--blue {
    background-clip: text;
    -webkit-background-clip: text;
    background-repeat: no-repeat;
    background-size: 220% 100%;
    background-position: 100% 0;
}
.hero-title .hero-word--green {
    background-image: linear-gradient(100deg,
        currentColor 0%, currentColor 40%,
        rgba(255,255,255,.85) 50%,
        currentColor 60%, currentColor 100%);
    animation: heroWordIn 0.75s cubic-bezier(0.16, 1, 0.3, 1) forwards,
               heroShine 2.6s ease-in-out 1.2s infinite;
}
.hero-title .hero-word--blue {
    background-image: linear-gradient(100deg,
        currentColor 0%, currentColor 40%,
        rgba(255,255,255,.9) 50%,
        currentColor 60%, currentColor 100%);
    animation: heroWordIn 0.75s cubic-bezier(0.16, 1, 0.3, 1) forwards,
               heroShine 2.6s ease-in-out 1.6s infinite;
}
@keyframes heroShine {
    0%   { background-position: 100% 0; -webkit-text-fill-color: transparent; }
    45%  { background-position:   0% 0; -webkit-text-fill-color: transparent; }
    60%  { -webkit-text-fill-color: currentColor; }
    100% { -webkit-text-fill-color: currentColor; background-position: -100% 0; }
}

/* Hero supporting elements */
.hero-shell {
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
    opacity: 0;
    transform: translateY(20px);
    animation: heroSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.9s forwards;
}
.hero-btn-anim {
    opacity: 0;
    transform: translateY(20px);
    animation: heroSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 1.05s forwards;
    position: relative;
}
.hero-apps-anim {
    opacity: 0;
    transform: translateY(20px);
    animation: heroSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 1.2s forwards;
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
}
.products-rail__viewport {
    overflow: hidden;
    border-radius: 16px;
    mask-image: linear-gradient(to right, transparent, #000 4%, #000 96%, transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, #000 4%, #000 96%, transparent);
}
.products-rail__track {
    display: flex;
    align-items: stretch;
    gap: var(--rail-gap);
    width: max-content;
    padding: .4rem;
    animation: productsRailScroll var(--rail-duration, 34s) linear infinite;
    will-change: transform;
}
.products-rail.is-paused .products-rail__track {
    animation-play-state: paused;
}
.products-rail__card {
    width: var(--card-width);
    min-width: var(--card-width);
    transform: translateY(0) scale(1);
    transition: transform .36s cubic-bezier(.16,1,.3,1), box-shadow .36s ease;
}
.products-rail__card .meal-card__thumbnail {
    overflow: hidden;
    border-radius: 10px;
}
.products-rail__card .meal-card__thumbnail img {
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
    transform: translate3d(-50%, -50%, 0);
    z-index: 70;
    transition: opacity .2s ease;
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
.products-rail.is-cursor-active .products-rail__cursor {
    opacity: 1;
}
.cart-badge-bounce {
    animation: cartBadgeBounce .55s cubic-bezier(.2,1.2,.25,1);
}
@keyframes productsRailScroll {
    from { transform: translate3d(0, 0, 0); }
    to   { transform: translate3d(calc(-1 * var(--loop-distance, 50%)), 0, 0); }
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

/* ─── Testimonials premium single-row rail ─────────── */
.testimonials-rail {
    --t-gap: clamp(1rem, 1.8vw, 1.4rem);
    --t-card-w: clamp(280px, 33vw, 390px);
    position: relative;
}
.testimonials-rail__viewport {
    overflow: hidden;
    border-radius: 16px;
    touch-action: pan-y;
    cursor: grab;
    user-select: none;
    mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
}
.testimonials-rail__viewport.is-dragging {
    cursor: grabbing;
}
.testimonials-rail__track {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    gap: var(--t-gap);
    width: max-content;
    will-change: transform;
    transform: translate3d(0,0,0);
    padding: .5rem .25rem;
}
.testimonials-rail__item {
    width: var(--t-card-w);
    min-width: var(--t-card-w);
    flex: 0 0 var(--t-card-w);
    transform-origin: 50% 100%;
}
.testimonials-card {
    height: 100%;
    transition: transform .48s cubic-bezier(.16,1,.3,1), box-shadow .48s cubic-bezier(.16,1,.3,1), border-color .36s ease;
    border: 1px solid rgba(148,163,184,.18);
    box-shadow: 0 14px 30px rgba(15,23,42,.08);
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
    .products-rail__viewport {
        mask-image: none;
        -webkit-mask-image: none;
    }
    .testimonials-rail {
        --t-card-w: min(84vw, 340px);
    }
    .testimonials-rail__viewport {
        mask-image: none;
        -webkit-mask-image: none;
    }
}
@media (prefers-reduced-motion: reduce) {
    .products-rail__track {
        animation: none !important;
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

            // Auto-assign stagger index to children
            document.querySelectorAll('[data-anim-stagger]').forEach(function(parent) {
                var children = parent.querySelectorAll('[data-anim]');
                children.forEach(function(child, i) {
                    child.style.setProperty('--anim-i', i);
                });
            });

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var delay = parseInt(entry.target.getAttribute('data-anim-delay') || '0', 10);
                        if (delay > 0) {
                            setTimeout(function() {
                                entry.target.classList.add('is-visible');
                            }, delay);
                        } else {
                            entry.target.classList.add('is-visible');
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '0px 0px -60px 0px',
                threshold: 0.1
            });

            els.forEach(function(el) { observer.observe(el); });
        })();

        /* ─── Hero magnetic CTA + image parallax ─── */
        (function() {
            var cta = document.querySelector('.hero-magnetic');
            if (cta && window.matchMedia('(hover: hover)').matches) {
                cta.addEventListener('pointermove', function(e) {
                    var r = cta.getBoundingClientRect();
                    var x = e.clientX - r.left, y = e.clientY - r.top;
                    cta.style.setProperty('--mx', x + 'px');
                    cta.style.setProperty('--my', y + 'px');
                    var dx = (x - r.width / 2) / r.width;
                    var dy = (y - r.height / 2) / r.height;
                    cta.style.transform = 'translate(' + (dx * 8) + 'px,' + (dy * 6) + 'px)';
                });
                cta.addEventListener('pointerleave', function() {
                    cta.style.transform = '';
                });
            }

            var heroImg = document.querySelector('.hero-parallax');
            if (heroImg && window.matchMedia('(hover: hover)').matches) {
                document.addEventListener('pointermove', function(e) {
                    var w = window.innerWidth, h = window.innerHeight;
                    var dx = (e.clientX / w - 0.5) * 14;
                    var dy = (e.clientY / h - 0.5) * 10;
                    heroImg.style.transform = 'translate(' + dx + 'px,' + dy + 'px)';
                });
            }
        })();

        /* ─── Premium products rail: infinite scroll + interactions ─── */
        (function() {
            var section = document.querySelector('[data-products-rail]');
            if (!section) return;

            var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var viewport = section.querySelector('.products-rail__viewport');
            var track = section.querySelector('[data-products-track]');
            if (!viewport || !track) return;

            var baseItems = Array.from(track.querySelectorAll('[data-rail-item]'));
            if (!baseItems.length) return;

            function setupLoop() {
                Array.from(track.querySelectorAll('[data-rail-clone="1"]')).forEach(function(node) {
                    node.remove();
                });

                var needsClone = track.scrollWidth <= viewport.clientWidth * 1.65;
                if (!needsClone) {
                    needsClone = true;
                }

                if (needsClone) {
                    baseItems.forEach(function(item) {
                        var clone = item.cloneNode(true);
                        clone.setAttribute('data-rail-clone', '1');
                        clone.setAttribute('aria-hidden', 'true');
                        clone.querySelectorAll('a, button, input, select, textarea').forEach(function(el) {
                            el.setAttribute('tabindex', '-1');
                        });
                        track.appendChild(clone);
                    });
                }

                var originalWidth = baseItems.reduce(function(sum, item) {
                    return sum + item.getBoundingClientRect().width;
                }, 0);
                var computedStyle = getComputedStyle(track);
                var gap = parseFloat(computedStyle.columnGap || computedStyle.gap || '0') || 0;
                var distance = originalWidth + (Math.max(baseItems.length - 1, 0) * gap);
                track.style.setProperty('--loop-distance', distance + 'px');

                var speedPxPerSecond = 110;
                var durationSeconds = Math.max(18, Math.round(distance / speedPxPerSecond));
                track.style.setProperty('--rail-duration', durationSeconds + 's');
            }

            setupLoop();

            var resizeTimer = null;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(setupLoop, 200);
            }, { passive: true });

            if (!prefersReduced) {
                section.addEventListener('mouseenter', function() {
                    section.classList.add('is-paused');
                });
                section.addEventListener('mouseleave', function() {
                    section.classList.remove('is-paused');
                });
                section.addEventListener('focusin', function() {
                    section.classList.add('is-paused');
                });
                section.addEventListener('focusout', function(e) {
                    if (!section.contains(e.relatedTarget)) {
                        section.classList.remove('is-paused');
                    }
                });
            } else {
                section.classList.add('is-paused');
            }

            var dot = section.querySelector('.products-rail__cursor--dot');
            var ring = section.querySelector('.products-rail__cursor--ring');
            if (dot && ring && !prefersReduced && window.matchMedia('(hover: hover)').matches) {
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

                section.addEventListener('mouseenter', function() {
                    section.classList.add('is-cursor-active');
                    if (!rafId) {
                        rafId = requestAnimationFrame(animateCursor);
                    }
                });

                section.addEventListener('mouseleave', function() {
                    section.classList.remove('is-cursor-active');
                    if (rafId) {
                        cancelAnimationFrame(rafId);
                        rafId = null;
                    }
                });

                section.addEventListener('mousemove', function(e) {
                    cursorX = e.clientX;
                    cursorY = e.clientY;
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

        /* ─── Testimonials single-row infinite rail (rAF + drag) ─── */
        (function() {
            var section = document.querySelector('[data-testimonials-rail]');
            if (!section) return;

            var viewport = section.querySelector('[data-testimonials-viewport]');
            var track = section.querySelector('[data-testimonials-track]');
            if (!viewport || !track) return;

            var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var baseItems = Array.from(track.querySelectorAll('[data-testimonial-item]'));
            if (!baseItems.length) return;

            var state = {
                x: 0,
                loopWidth: 0,
                speed: 0.05,
                isPaused: false,
                isDragging: false,
                dragStartX: 0,
                dragStartOffset: 0,
                pointerId: null,
                resumeTimer: null,
                rafId: null,
                lastTs: 0,
            };

            function normalizeX() {
                if (!state.loopWidth) return;
                while (state.x <= -state.loopWidth) state.x += state.loopWidth;
                while (state.x > 0) state.x -= state.loopWidth;
            }

            function render() {
                track.style.transform = 'translate3d(' + state.x + 'px,0,0)';
            }

            function buildRail() {
                Array.from(track.querySelectorAll('[data-testimonial-clone="1"]')).forEach(function(node) {
                    node.remove();
                });

                baseItems.forEach(function(item) {
                    var clone = item.cloneNode(true);
                    clone.setAttribute('data-testimonial-clone', '1');
                    clone.setAttribute('aria-hidden', 'true');
                    clone.querySelectorAll('a, button, input, select, textarea').forEach(function(el) {
                        el.setAttribute('tabindex', '-1');
                    });
                    track.appendChild(clone);
                });

                var gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || '0') || 0;
                var total = 0;
                baseItems.forEach(function(item, idx) {
                    total += item.getBoundingClientRect().width;
                    if (idx < baseItems.length - 1) total += gap;
                });
                state.loopWidth = Math.max(1, total);
                state.x = 0;
                render();
            }

            function tick(ts) {
                if (!state.lastTs) state.lastTs = ts;
                var dt = ts - state.lastTs;
                state.lastTs = ts;

                if (!prefersReduced && !state.isPaused && !state.isDragging) {
                    state.x -= state.speed * dt;
                    normalizeX();
                    render();
                }
                state.rafId = requestAnimationFrame(tick);
            }

            function pauseRail() {
                state.isPaused = true;
                clearTimeout(state.resumeTimer);
            }

            function resumeRail(delay) {
                clearTimeout(state.resumeTimer);
                state.resumeTimer = setTimeout(function() {
                    state.isPaused = false;
                }, delay || 0);
            }

            function pointerDown(e) {
                if (prefersReduced) return;
                state.isDragging = true;
                state.pointerId = e.pointerId;
                state.dragStartX = e.clientX;
                state.dragStartOffset = state.x;
                pauseRail();
                viewport.classList.add('is-dragging');
                viewport.setPointerCapture(e.pointerId);
            }

            function pointerMove(e) {
                if (!state.isDragging || e.pointerId !== state.pointerId) return;
                state.x = state.dragStartOffset + (e.clientX - state.dragStartX);
                normalizeX();
                render();
            }

            function pointerUp(e) {
                if (!state.isDragging || e.pointerId !== state.pointerId) return;
                state.isDragging = false;
                viewport.classList.remove('is-dragging');
                try { viewport.releasePointerCapture(e.pointerId); } catch (err) {}
                resumeRail(700);
            }

            buildRail();

            if (!prefersReduced) {
                section.addEventListener('mouseenter', pauseRail);
                section.addEventListener('mouseleave', function() { resumeRail(260); });
                section.addEventListener('focusin', pauseRail);
                section.addEventListener('focusout', function(e) {
                    if (!section.contains(e.relatedTarget)) resumeRail(240);
                });

                viewport.addEventListener('pointerdown', pointerDown);
                viewport.addEventListener('pointermove', pointerMove, { passive: true });
                viewport.addEventListener('pointerup', pointerUp);
                viewport.addEventListener('pointercancel', pointerUp);
                viewport.addEventListener('lostpointercapture', function() {
                    state.isDragging = false;
                    viewport.classList.remove('is-dragging');
                    resumeRail(500);
                });
            } else {
                viewport.style.overflowX = 'auto';
                track.style.transform = 'none';
            }

            var resizeTimer = null;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(buildRail, 200);
            }, { passive: true });

            state.rafId = requestAnimationFrame(tick);
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
