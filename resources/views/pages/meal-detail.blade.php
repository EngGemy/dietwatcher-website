@extends('layouts.app')

@php
    $mealImage = $meal['image_url'] ?? '';
    $mealImageTrim = trim((string) $mealImage);
    if ($mealImageTrim === '') {
        $mealImageUrl = asset('assets/images/meal-2.png');
    } elseif (str_starts_with($mealImageTrim, '//')) {
        $mealImageUrl = 'https:'.$mealImageTrim;
    } elseif (str_starts_with($mealImageTrim, 'http://') || str_starts_with($mealImageTrim, 'https://')) {
        $mealImageUrl = $mealImageTrim;
    } else {
        $mealImageUrl = $mealImageTrim;
    }
    $mealFallback = asset('assets/images/meal-2.png');
    $effectivePrice = ($meal['offer_price'] ?? 0) > 0 && ($meal['offer_price'] < $meal['price'])
        ? (float) $meal['offer_price']
        : (float) $meal['price'];
    $hasOffer = ($meal['offer_price'] ?? 0) > 0 && $meal['offer_price'] < $meal['price'];
    $discount = $hasOffer && ($meal['price'] ?? 0) > 0
        ? round((1 - $meal['offer_price'] / $meal['price']) * 100)
        : 0;
    $category = $meal['categories'][0] ?? null;
    $categoryName = $category['name'] ?? '—';
    $tagLabel = $meal['tag_name'] ?? ($meal['tags'][0]['name'] ?? '—');
    $p = $meal['protein'] ?? null;
    $c = $meal['carbs'] ?? null;
    $f = $meal['fat'] ?? null;
    $macroVals = array_values(array_filter([$p, $c, $f], static fn ($v) => $v !== null && (float) $v > 0));
    $maxMacro = $macroVals !== [] ? max($macroVals) : 1.0;
    $pPct = $p !== null ? (int) round(min(100, ((float) $p / $maxMacro) * 100)) : 0;
    $cPct = $c !== null ? (int) round(min(100, ((float) $c / $maxMacro) * 100)) : 0;
    $fPct = $f !== null ? (int) round(min(100, ((float) $f / $maxMacro) * 100)) : 0;
    $calories = $meal['calories'] ?? null;
    $shareUrl = urlencode(url()->current());
    $ingredientRows = $meal['ingredients'] ?? [];
    $benefitsText = is_string($meal['benefits'] ?? null) ? trim($meal['benefits']) : '';
@endphp

@section('title', ($meal['name'] ?? __('Meals')) . ' | ' . config('app.name'))

@section('content')
<div
    class="bg-gray-200 pt-5 pb-28 md:pt-10"
    x-data="{
        qty: 1,
        terms: false,
        mealId: {{ (int) $meal['id'] }},
        name: @js($meal['name']),
        price: {{ $effectivePrice }},
        image: @js($mealImageUrl),
        addToCart() {
            for (let i = 0; i < this.qty; i++) {
                Livewire.dispatch('add-to-cart', { mealId: this.mealId, name: this.name, price: this.price, image: this.image });
            }
        },
        buyNow() {
            if (!this.terms) { return; }
            this.addToCart();
            window.location.href = @js(route('checkout.index'));
        }
    }"
>
    <section class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('home') }}">{{ __('Home') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </li>
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('store.index') }}">{{ __('Market') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </li>
            <li class="breadcrumb__item breadcrumb__item--active" aria-current="page">
                {{ Str::limit($meal['name'], 80) }}
            </li>
        </ol>

        <div class="mb-10 grid gap-10 md:mb-16 md:grid-cols-2">
            <div class="w-full">
                <div class="h-[500px] md:h-[780px]">
                    <img
                        src="{{ $mealImageUrl }}"
                        class="size-full rounded-xl object-cover md:rounded-2xl"
                        alt="{{ $meal['name'] }}"
                        loading="eager"
                        onerror="this.src='{{ $mealFallback }}'"
                    />
                </div>
            </div>

            <div class="w-full">
                <div class="space-y-4">
                    <h1 class="mb-3 text-2xl font-bold md:text-3xl">{{ $meal['name'] }}</h1>
                    @if(!empty($meal['description']))
                        <p class="text-lg text-black/70 md:text-xl">
                            {{ Str::limit(strip_tags($meal['description']), 400) }}
                        </p>
                    @endif

                    <div class="flex flex-wrap items-baseline gap-3">
                        <p class="text-green text-2xl font-semibold md:text-3xl">
                            {{ __('SAR') }} {{ number_format($effectivePrice, 2) }}
                        </p>
                        @if($hasOffer)
                            <span class="text-lg text-black/40 line-through">{{ __('SAR') }} {{ number_format($meal['price'], 2) }}</span>
                            <span class="rounded bg-[#ff707a] px-2 py-0.5 text-xs font-bold text-white">-{{ $discount }}%</span>
                        @endif
                    </div>
                </div>

                <div class="my-4 h-px bg-black/20 md:my-6"></div>

                @if($calories !== null || $p !== null || $c !== null || $f !== null)
                    <div class="mb-5 space-y-3 md:mb-10 md:space-y-5">
                        <p class="text-lg md:text-2xl">{{ __('Nutritional info') }}</p>

                        @if($calories !== null)
                            <div class="flex items-center gap-3 rounded-md bg-amber-100/80 p-3 pe-5 text-lg md:text-xl">
                                <svg class="size-8 shrink-0 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3 11.452 8.994 8.994 0 0 1-3 1.331v-4.8z"/>
                                </svg>
                                <p>{{ __('Calories') }}</p>
                                <p class="ms-auto font-bold">{{ rtrim(rtrim(number_format((float) $calories, 2, '.', ''), '0'), '.') }}kcal</p>
                            </div>
                        @endif

                        @if($p !== null || $c !== null || $f !== null)
                            <div class="flex flex-col gap-6 md:flex-row">
                                @if($p !== null)
                                    <div class="flex-1">
                                        <div class="mb-2 flex items-center justify-between">
                                            <p class="font-semibold">{{ __('Protein') }}</p>
                                            <p>{{ rtrim(rtrim(number_format((float) $p, 2, '.', ''), '0'), '.') }}g</p>
                                        </div>
                                        <div class="flex h-1 w-full overflow-hidden rounded-full bg-zinc-200" role="progressbar" aria-valuenow="{{ $pPct }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="bg-green flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap transition duration-500" style="width: {{ $pPct }}%"></div>
                                        </div>
                                    </div>
                                @endif
                                @if($c !== null)
                                    <div class="flex-1">
                                        <div class="mb-2 flex items-center justify-between">
                                            <p class="font-semibold">{{ __('Carbs') }}</p>
                                            <p>{{ rtrim(rtrim(number_format((float) $c, 2, '.', ''), '0'), '.') }}g</p>
                                        </div>
                                        <div class="flex h-1 w-full overflow-hidden rounded-full bg-zinc-200" role="progressbar" aria-valuenow="{{ $cPct }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="bg-yellow flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap transition duration-500" style="width: {{ $cPct }}%"></div>
                                        </div>
                                    </div>
                                @endif
                                @if($f !== null)
                                    <div class="flex-1">
                                        <div class="mb-2 flex items-center justify-between">
                                            <p class="font-semibold">{{ __('Fat') }}</p>
                                            <p>{{ rtrim(rtrim(number_format((float) $f, 2, '.', ''), '0'), '.') }}g</p>
                                        </div>
                                        <div class="flex h-1 w-full overflow-hidden rounded-full bg-zinc-200" role="progressbar" aria-valuenow="{{ $fPct }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="bg-red flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap transition duration-500" style="width: {{ $fPct }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <div class="space-y-4 md:space-y-6">
                    <div class="flex flex-col gap-5 md:flex-row">
                        <div class="flex flex-1 gap-5">
                            <div class="inline-flex border border-gray-300 bg-white">
                                <div class="flex h-full items-center divide-x divide-gray-300 rtl:divide-x-reverse">
                                    <button type="button" tabindex="-1" @click="qty = Math.max(1, qty - 1)" aria-label="{{ __('Decrease') }}" class="inline-flex size-11 h-full shrink-0 items-center justify-center bg-white font-medium hover:bg-gray-50 focus:bg-gray-50 focus:outline-hidden">
                                        <svg class="size-6 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                                    </button>
                                    <input type="number" min="1" x-model.number="qty" aria-label="{{ __('Quantity') }}" class="size-11 h-full w-14 shrink-0 border-0 bg-transparent text-center text-lg font-semibold text-black focus:ring-0 md:text-xl [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                    <button type="button" tabindex="-1" @click="qty++" aria-label="{{ __('Increase') }}" class="inline-flex size-11 h-full shrink-0 items-center justify-center bg-white font-medium hover:bg-gray-50 focus:bg-gray-50 focus:outline-hidden">
                                        <svg class="size-6 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn--primary btn--md flex-1 uppercase" @click="addToCart()">
                                {{ __('Add to Cart') }}
                            </button>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="market-terms" class="checkbox-input" x-model="terms" />
                        <label for="market-terms" class="checkbox-label">
                            {{ __('market.agree_terms_prefix') }}
                            <a href="{{ route('terms') }}" class="font-semibold text-[#279ff9] underline hover:no-underline">{{ __('Terms & Conditions') }}</a>
                        </label>
                    </div>

                    <button type="button" class="btn btn--outline btn--md w-full uppercase" @click="buyNow()" :class="terms ? '' : 'opacity-50 cursor-not-allowed'">
                        {{ __('market.buy_now') }}
                    </button>

                    <div class="grid gap-3.5 md:grid-cols-2">
                        <div class="flex items-center justify-between bg-white px-5 py-3.5">
                            <p class="text-lg">{{ __('market.product_code') }}</p>
                            <p class="text-lg font-bold">{{ $meal['id'] }}</p>
                        </div>
                        <div class="flex items-center justify-between bg-white px-5 py-3.5">
                            <p class="text-lg">{{ __('market.category_label') }}</p>
                            <p class="text-lg font-bold">{{ $categoryName }}</p>
                        </div>
                        <div class="flex items-center justify-between bg-white px-5 py-3.5">
                            <p class="text-lg">{{ __('market.tag_label') }}</p>
                            <p class="text-lg font-bold">{{ $tagLabel }}</p>
                        </div>
                        <div class="flex items-center justify-between bg-white px-5 py-3.5 md:col-span-2">
                            <p class="text-lg">{{ __('market.sharing') }}</p>
                            <div class="flex items-center gap-2">
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="social-link text-[#1877f2]" aria-label="Facebook">FB</a>
                                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="X">X</a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="LinkedIn">in</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hs-accordion-group mt-10 space-y-5 md:mt-16">
            <div class="hs-accordion active rounded-md border border-gray-300/25 bg-white" id="hs-meal-desc-{{ $meal['id'] }}">
                <button type="button" class="hs-accordion-toggle flex w-full items-center justify-between px-5 py-4 text-start text-2xl font-bold text-gray-800 transition-colors focus:outline-hidden md:p-6" aria-controls="hs-meal-desc-content-{{ $meal['id'] }}">
                    {{ __('Description') }}
                    <svg class="hs-accordion-active:rotate-180 size-5 shrink-0 text-gray-500 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div id="hs-meal-desc-content-{{ $meal['id'] }}" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-meal-desc-{{ $meal['id'] }}">
                    <div class="p-5 pt-0 md:p-6 md:pt-0">
                        <div class="space-y-4 text-black/80">
                            @if(!empty($meal['description']))
                                <div class="max-w-none text-black/80 leading-relaxed">{!! nl2br(e(strip_tags($meal['description']))) !!}</div>
                            @else
                                <p>—</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="hs-accordion rounded-md border border-gray-300/25 bg-white" id="hs-meal-ing-{{ $meal['id'] }}">
                <button type="button" class="hs-accordion-toggle flex w-full items-center justify-between px-5 py-4 text-start text-2xl font-bold text-gray-800 transition-colors focus:outline-hidden md:p-6" aria-controls="hs-meal-ing-content-{{ $meal['id'] }}">
                    {{ __('Ingredients') }}
                    <svg class="hs-accordion-active:rotate-180 size-5 shrink-0 text-gray-500 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div id="hs-meal-ing-content-{{ $meal['id'] }}" class="hs-accordion-content hidden w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-meal-ing-{{ $meal['id'] }}">
                    <div class="p-5 pt-0 md:p-6 md:pt-0">
                        @if(!empty($ingredientRows))
                            <ul class="list-disc space-y-2 ps-5 text-black/80">
                                @foreach($ingredientRows as $ing)
                                    <li>{{ is_array($ing) ? ($ing['name'] ?? '') : (string) $ing }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-black/60">—</p>
                        @endif
                    </div>
                </div>
            </div>

            @if($benefitsText !== '')
                <div class="hs-accordion rounded-md border border-gray-300/25 bg-white" id="hs-meal-ben-{{ $meal['id'] }}">
                    <button type="button" class="hs-accordion-toggle flex w-full items-center justify-between px-5 py-4 text-start text-2xl font-bold text-gray-800 transition-colors focus:outline-hidden md:p-6" aria-controls="hs-meal-ben-content-{{ $meal['id'] }}">
                        {{ __('Benefits') }}
                        <svg class="hs-accordion-active:rotate-180 size-5 shrink-0 text-gray-500 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div id="hs-meal-ben-content-{{ $meal['id'] }}" class="hs-accordion-content hidden w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-meal-ben-{{ $meal['id'] }}">
                        <div class="p-5 pt-0 md:p-6 md:pt-0">
                            <p class="text-black/80">{!! nl2br(e($benefitsText)) !!}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if(count($relatedMeals) > 0)
        <section class="container mt-10 md:mt-24">
            <header>
                <h2 class="section-header__title">{{ __('market.related_products') }}</h2>
            </header>

            <div class="mt-10 grid gap-8 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($relatedMeals as $rel)
                    @php
                        $rImg = $rel['image_url'] ?? '';
                        $rImgUrl = str_starts_with((string) $rImg, 'http') ? $rImg : ($rImg ? asset(ltrim($rImg, '/')) : asset('assets/images/meal-1.png'));
                        $rFallback = asset('assets/images/meal-'.(($loop->iteration % 3) + 1).'.png');
                        $rPrice = ($rel['offer_price'] ?? 0) > 0 && ($rel['offer_price'] < $rel['price']) ? $rel['offer_price'] : $rel['price'];
                    @endphp
                    <div class="meal-card">
                        <div class="meal-card__thumbnail">
                            <a href="{{ route('store.show', $rel['id']) }}">
                                <img src="{{ $rImgUrl }}" alt="{{ $rel['name'] }}" loading="lazy" onerror="this.src='{{ $rFallback }}'" />
                            </a>
                        </div>
                        <div class="meal-card__body">
                            <a href="{{ route('store.show', $rel['id']) }}" class="meal-card__title-link">
                                <h3 class="meal-card__title">{{ $rel['name'] }}</h3>
                            </a>
                            <div class="meal-card__lower">
                                <div class="meal-card__footer">
                                    @if(!empty($rel['tag_name']))
                                        <span class="meal-card__category">{{ $rel['tag_name'] }}</span>
                                    @endif
                                    <div class="meal-card__price-wrap">
                                        <span class="meal-card__price">{{ __('SAR') }} {{ number_format((float) $rPrice, 0) }}</span>
                                    </div>
                                </div>
                                <button type="button" class="meal-card__btn"
                                    onclick="Livewire.dispatch('add-to-cart', { mealId: {{ (int) $rel['id'] }}, name: @js($rel['name']), price: {{ (float) $rPrice }}, image: @js($rImgUrl) })">
                                    {{ __('Add to Cart') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

@push('styles')
<style>
    .text-green { color: #3fb536; }
    .bg-green { background-color: #3fb536; }
    .bg-yellow { background-color: #eab308; }
    .bg-red { background-color: #ef4444; }
    .social-link { font-size: 0.75rem; font-weight: 700; text-decoration: underline; }
</style>
@endpush
