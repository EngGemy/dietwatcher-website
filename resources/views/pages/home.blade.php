@extends('layouts.app')

@section('title', __('Diet Watchers'))

@section('content')
    {{-- Hero Section --}}
    <section>
        <div class="relative container overflow-hidden rounded-md bg-gray-200 pt-12 md:pt-28">
            <div class="relative z-20 mx-auto grid w-full max-w-[1500px] gap-10 lg:grid-cols-2 lg:gap-0">
                <div class="md:pb-28">
                    <h1 class="hero-title-anim mb-4 text-4xl font-bold md:mb-7 lg:text-6xl/tight">
                        <span class="text-green">{{ __('Healthy') }}</span> {{ __('Meals Delivered Daily. Designed for') }}
                        <br class="hidden lg:block" />{{ __('Your') }}
                        <span class="text-blue">{{ __('Goals.') }}</span>
                    </h1>
                    <p class="hero-desc-anim mb-5 max-w-xl text-lg text-black/80 md:mb-12 lg:text-2xl">
                        {{ __('Chef-made, calorie-smart meals delivered in Saudi Arabia. Plans online, managed via our app.') }}
                    </p>

                    <a href="https://app.diet-watchers.sa/meal-plans" class="hero-btn-anim btn btn--primary mb-8 text-lg">
                        {{ __('Choose Meal Plans') }}
                    </a>

                    <div class="hero-apps-anim">
                        <p class="mb-2 text-lg">{{ __('Download app') }}</p>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener">
                                <img src="{{ asset('assets/images/play.png') }}" alt="{{ __('Google Play') }}" />
                            </a>
                            <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener">
                                <img src="{{ asset('assets/images/store.png') }}" alt="{{ __('App Store') }}" />
                            </a>
                        </div>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-[320px] self-end md:w-fit md:max-w-none">
                    <img src="{{ asset('assets/images/app-screens.png') }}"
                        class="hero-img-anim hero-float relative z-20 mx-auto w-full max-w-[280px] select-none md:max-w-[420px] lg:max-w-[520px]" alt="{{ __('App Preview') }}" />
                </div>
            </div>

            <img src="{{ asset('assets/images/hero-bg.png') }}"
                class="absolute inset-y-0 start-0 z-0 h-full w-full object-cover object-right opacity-60 select-none md:opacity-100 md:object-contain rtl:-scale-x-100"
                alt="" />
        </div>
    </section>

    {{-- Meal Plans Section --}}
    <section class="py-20">
        <div class="container">
            <header class="section-header section-header--center" data-anim="fade-up">
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
            <header class="section-header section-header--center" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ __('How It Works') }}</h4>
                <h2 class="section-header__title">{{ __('3 Easy Steps For Happy Life') }}</h2>
            </header>
            <div class="grid gap-8 lg:grid-cols-3" data-anim-stagger>
                @forelse($howItWorksSteps as $step)
                    <div data-anim="fade-up">
                        <img src="{{ $step->image_url }}" class="mb-8 rounded-lg w-full h-64 object-cover" alt="{{ $step->title() }}" />
                        <h3 class="mb-4 text-xl font-semibold md:text-2xl">{{ $step->title() }}</h3>
                        <p class="text-lg text-black/70 md:text-xl">
                            {{ $step->description() }}
                        </p>
                    </div>
                @empty
                    {{-- Fallback static content --}}
                    <div>
                        <img src="{{ asset('assets/images/how-old-1.png') }}" class="mb-8 rounded-lg" alt="" />
                        <h3 class="mb-4 text-xl font-semibold md:text-2xl">{{ __('Choose Your Plan') }}</h3>
                        <p class="text-lg text-black/70 md:text-xl">
                            {{ __('Select a meal plan based on calories, lifestyle, or fitness goals.') }}
                        </p>
                    </div>
                    <div>
                        <img src="{{ asset('assets/images/how-old-2.png') }}" class="mb-8 rounded-lg" alt="" />
                        <h3 class="mb-4 text-xl font-semibold md:text-2xl">{{ __('Swap to Your Favorite Meals') }}</h3>
                        <p class="text-lg text-black/70 md:text-xl">
                            {{ __('Change meals anytime and enjoy dishes that suit your taste, mood, and lifestyle.') }}
                        </p>
                    </div>
                    <div>
                        <img src="{{ asset('assets/images/how-old-3.png') }}" class="mb-8 rounded-lg" alt="" />
                        <h3 class="mb-4 text-xl font-semibold md:text-2xl">{{ __('Enjoy Your Meals!') }}</h3>
                        <p class="text-lg text-black/70 md:text-xl">
                            {{ __('our meals are ready - fresh, nutritious, and made to enjoy.') }}
                        </p>
                    </div>
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

            <div class="grid items-stretch gap-8 md:grid-cols-2 md:gap-12 lg:grid-cols-4" data-anim-stagger>
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
                    <div class="meal-card" data-anim="fade-up">
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
                                        class="meal-card__btn"
                                        onclick="Livewire.dispatch('add-to-cart', { mealId: {{ $meal['id'] }}, name: '{{ addslashes($meal['name']) }}', price: {{ $effectivePrice }}, image: '{{ addslashes($mealImageUrl) }}' })">
                                    {{ __('Add to Cart') }}
                                </button>
                            </div>
                        </div>
                    </div>
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

            <div class="mb-10 flex flex-wrap items-center justify-center gap-1.5 md:mb-20" data-anim="fade-up" data-anim-delay="100">
                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener">
                    <img src="{{ asset('assets/images/play.png') }}" class="h-16" alt="{{ __('Google Play') }}" />
                </a>
                <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener">
                    <img src="{{ asset('assets/images/store.png') }}" class="h-16" alt="{{ __('App Store') }}" />
                </a>
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
            <header class="section-header section-header--center" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ $testimonialHeader?->badge_title() ?? __('Feedback') }}</h4>
                <h2 class="section-header__title">{{ $testimonialHeader?->title() ?? __('What our customer say') }}</h2>
                <p class="section-header__desc">
                    {{ $testimonialHeader?->subtitle() ?? __('Real experiences from customers who have made healthy eating part of their everyday lives with Diet Watchers.') }}
                </p>
            </header>

            <div class="grid gap-6 md:grid-cols-3 md:gap-10" data-anim-stagger>
                @forelse ($testimonials as $testimonial)
                    <div class="hs-carousel-slide" data-anim="fade-up">
                        <div class="review-card">
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
                    </div>
                @empty
                    <div class="col-span-full text-center text-gray-500">
                        {{ __('No testimonials available yet.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Blog Section --}}
    <section class="py-20">
        <div class="container">
            <header class="section-header section-header--center" data-anim="fade-up">
                <h4 class="section-header__subtitle">{{ __('Insightful') }}</h4>
                <h2 class="section-header__title">{{ __('Insights for a Healthier You') }}</h2>
                <p class="section-header__desc">
                    {{ __('Get expert nutrition and lifestyle tips for healthier daily choices.') }}
                </p>
            </header>

            <div class="mb-10 grid grid-cols-1 gap-6 md:mb-14 md:grid-cols-2 lg:grid-cols-4">
                @forelse($latestPosts as $post)
                    <div class="blog-card">
                        <div class="blog-card__thumbnail">
                            <a href="{{ route('blog.show', $post->translate(app()->getLocale())->slug) }}">
                                @php
                                    $postImage = $post->cover_image_exists 
                                        ? $post->cover_image_url 
                                        : asset('assets/images/blog-1.png');
                                @endphp
                                <img src="{{ $postImage }}" alt="{{ $post->title }}" />
                            </a>
                        </div>

                        <a href="{{ route('blog.show', $post->translate(app()->getLocale())->slug) }}" class="blog-card__body">
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

/* Hero-specific entrance */
.hero-title-anim {
    opacity: 0;
    transform: translateY(30px);
    animation: heroSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s forwards;
}
.hero-desc-anim {
    opacity: 0;
    transform: translateY(20px);
    animation: heroSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.45s forwards;
}
.hero-btn-anim {
    opacity: 0;
    transform: translateY(20px);
    animation: heroSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.6s forwards;
}
.hero-apps-anim {
    opacity: 0;
    transform: translateY(20px);
    animation: heroSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.75s forwards;
}
.hero-img-anim {
    opacity: 0;
    transform: translateX(40px) scale(0.95);
    animation: heroImgIn 1s cubic-bezier(0.16, 1, 0.3, 1) 0.4s forwards;
}
[dir="rtl"] .hero-img-anim {
    transform: translateX(-40px) scale(0.95);
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
    </script>
@endpush