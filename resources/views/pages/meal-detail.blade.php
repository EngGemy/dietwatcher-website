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
        $mealImageUrl = asset(ltrim($mealImageTrim, '/'));
    }
    $mealFallback = asset('assets/images/meal-2.png');
    $effectivePrice = ($meal['offer_price'] ?? 0) > 0 && ($meal['offer_price'] < $meal['price'])
        ? (float) $meal['offer_price']
        : (float) $meal['price'];
    $hasOffer = ($meal['offer_price'] ?? 0) > 0 && $meal['offer_price'] < $meal['price'];
    $discount = $hasOffer && ($meal['price'] ?? 0) > 0
        ? round((1 - $meal['offer_price'] / $meal['price']) * 100)
        : 0;
    $categoryName = $meal['category_name'] ?? $meal['group_name'] ?? '—';
    $highlights = $meal['highlights'] ?? $meal['categories'] ?? [];
    $productNumber = $meal['code'] ?? ('#'.$meal['id']);
    $rating = (float) ($meal['rate'] ?? 0);
    $p = $meal['protein'] ?? null;
    $c = $meal['carbs'] ?? null;
    $f = $meal['fat'] ?? null;
    $calories = $meal['calories'] ?? null;
    $shareUrl = urlencode(url()->current());
    $ingredientRows = $meal['ingredients'] ?? [];
    $allergies = $meal['allergies'] ?? [];
    $benefitsText = is_string($meal['benefits'] ?? null) ? trim($meal['benefits']) : '';
    $mealListRoute = request()->routeIs('meals.show') ? 'meals.index' : 'store.index';
    $mealTagsForLinks = [];
    foreach ($meal['tags'] ?? [] as $t) {
        if (! is_array($t)) {
            continue;
        }
        $tid = (int) ($t['id'] ?? $t['value'] ?? 0);
        $tname = trim((string) ($t['display_name'] ?? $t['name'] ?? ''));
        if ($tid > 0 && $tname !== '') {
            $mealTagsForLinks[] = ['id' => $tid, 'name' => $tname, 'icon' => $t['icon'] ?? ''];
        }
    }
    $formatMacro = static fn (?float $v): string => $v === null ? '—' : rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
@endphp

@section('title', ($meal['name'] ?? __('Meals')) . ' | ' . config('app.name'))

@section('content')
<div
    class="meal-detail bg-gray-200 pt-5 pb-28 md:pt-10"
    x-data="{
        qty: 1,
        terms: true,
        added: false,
        ready: false,
        mealId: {{ (int) $meal['id'] }},
        name: @js($meal['name']),
        price: {{ $effectivePrice }},
        image: @js($mealImageUrl),
        init() {
            requestAnimationFrame(() => {
                this.ready = true;
                this.$el.classList.add('is-ready');
            });
        },
        showAdded() {
            this.added = true;
            clearTimeout(this._addedTimer);
            this._addedTimer = setTimeout(() => { this.added = false; }, 2200);
        },
        addToCart() {
            for (let i = 0; i < this.qty; i++) {
                Livewire.dispatch('add-to-cart', { mealId: this.mealId, name: this.name, price: this.price, image: this.image });
            }
            this.showAdded();
        },
        buyNow() {
            this.addToCart();
            window.location.href = @js(route('checkout.index'));
        }
    }"
    x-init="init()"
>
    <section class="container">
        <ol class="breadcrumb mb-6 md:mb-8">
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

        <div class="meal-detail__hero">
            <div class="meal-detail__media md-reveal md-reveal--image">
                <img
                    class="meal-detail__media-img"
                    src="{{ $mealImageUrl }}"
                    alt="{{ $meal['name'] }}"
                    loading="eager"
                    decoding="async"
                    onerror="this.src='{{ $mealFallback }}'"
                />
                <div class="meal-detail__media-badges">
                    @if($hasOffer)
                        <span class="meal-detail__badge meal-detail__badge--offer">-{{ $discount }}%</span>
                    @endif
                    @if(! empty($meal['group_name']))
                        <span class="meal-detail__badge">{{ $meal['group_name'] }}</span>
                    @endif
                </div>
            </div>

            <div class="meal-detail__panel">
                <div class="meal-detail__eyebrow md-reveal" style="--md-i:0">
                    @if(! empty($meal['group_name']))
                        <span class="meal-detail__group">{{ $meal['group_name'] }}</span>
                    @endif
                    @if($rating > 0)
                        <span class="meal-detail__rating" aria-label="{{ __('market.rating_label') }}: {{ $rating }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>
                            {{ number_format($rating, 1) }}
                        </span>
                    @endif
                    @if($mealTagsForLinks !== [])
                        @foreach($mealTagsForLinks as $t)
                            <a href="{{ route($mealListRoute, ['tag' => $t['id']]) }}" class="meal-detail__tag meal-detail__tag--inline">
                                @if(! empty($t['icon']))
                                    <img src="{{ $t['icon'] }}" alt="" width="16" height="16" loading="lazy" />
                                @endif
                                {{ $t['name'] }}
                            </a>
                        @endforeach
                    @endif
                </div>

                <h1 class="meal-detail__title md-reveal" style="--md-i:1">{{ $meal['name'] }}</h1>

                @if(! empty($meal['description']))
                    <p class="meal-detail__excerpt md-reveal" style="--md-i:2">{{ Str::limit(strip_tags($meal['description']), 220) }}</p>
                @endif

                <div class="meal-detail__price-row md-reveal" style="--md-i:3">
                    <p class="meal-detail__price"><x-sar :amount="$effectivePrice" /></p>
                    @if($hasOffer)
                        <span class="meal-detail__price-old"><x-sar :amount="$meal['price']" /></span>
                    @endif
                    <span class="meal-detail__per-serving">{{ __('market.per_serving') }}</span>
                </div>

                @if($calories !== null || $p !== null || $c !== null || $f !== null)
                    <div class="meal-detail__nutrition md-reveal" style="--md-i:4" aria-label="{{ __('Nutritional info') }}">
                        @if($calories !== null)
                            <div class="meal-detail__stat meal-detail__stat--calories" data-stat="{{ (float) $calories }}">
                                <span class="meal-detail__stat-value" data-stat-value>{{ $formatMacro((float) $calories) }}</span>
                                <span class="meal-detail__stat-label">{{ __('Calories') }}</span>
                            </div>
                        @endif
                        @if($p !== null)
                            <div class="meal-detail__stat meal-detail__stat--protein" data-stat="{{ (float) $p }}" data-stat-suffix="g">
                                <span class="meal-detail__stat-value" data-stat-value>{{ $formatMacro((float) $p) }}g</span>
                                <span class="meal-detail__stat-label">{{ __('Protein') }}</span>
                            </div>
                        @endif
                        @if($c !== null)
                            <div class="meal-detail__stat meal-detail__stat--carbs" data-stat="{{ (float) $c }}" data-stat-suffix="g">
                                <span class="meal-detail__stat-value" data-stat-value>{{ $formatMacro((float) $c) }}g</span>
                                <span class="meal-detail__stat-label">{{ __('Carbs') }}</span>
                            </div>
                        @endif
                        @if($f !== null)
                            <div class="meal-detail__stat meal-detail__stat--fat" data-stat="{{ (float) $f }}" data-stat-suffix="g">
                                <span class="meal-detail__stat-value" data-stat-value>{{ $formatMacro((float) $f) }}g</span>
                                <span class="meal-detail__stat-label">{{ __('Fat') }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                @if(count($highlights) > 0)
                    <div class="md-reveal" style="--md-i:5">
                        <p class="mb-2 text-sm font-semibold text-slate-600">{{ __('market.highlights') }}</p>
                        <div class="meal-detail__highlights">
                            @foreach($highlights as $highlight)
                                <span class="meal-detail__highlight">
                                    @if(! empty($highlight['icon']))
                                        <img src="{{ $highlight['icon'] }}" alt="" width="20" height="20" loading="lazy" />
                                    @endif
                                    {{ $highlight['name'] ?? '' }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="meal-detail__purchase hidden md:block md-reveal" style="--md-i:6">
                    <div class="meal-detail__qty-row mb-4">
                        <div class="meal-detail__qty">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="{{ __('Decrease') }}">
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                            </button>
                            <input type="number" min="1" x-model.number="qty" aria-label="{{ __('Quantity') }}" />
                            <button type="button" @click="qty++" aria-label="{{ __('Increase') }}">
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            </button>
                        </div>
                        <button type="button" class="btn btn--primary btn--md meal-detail__cart-btn" @click="addToCart()" :class="added ? 'is-success' : ''">
                            <span x-show="!added">{{ __('Add to Cart') }}</span>
                            <span x-show="added" x-cloak>{{ __('Added') }}</span>
                        </button>
                    </div>

                    <div class="checkbox-group mb-4">
                        <input type="checkbox" id="market-terms" class="checkbox-input" x-model="terms" checked />
                        <label for="market-terms" class="checkbox-label">
                            {{ __('market.agree_terms_prefix') }}
                            <a href="{{ route('terms') }}" class="font-semibold text-[#279ff9] underline hover:no-underline">{{ __('Terms & Conditions') }}</a>
                        </label>
                    </div>

                    <button type="button" class="btn btn--outline btn--md w-full meal-detail__buy-now" @click="buyNow()">
                        {{ __('market.buy_now') }}
                    </button>
                </div>

                <div class="meal-detail__meta md-reveal" style="--md-i:7">
                    <div class="meal-detail__meta-item">
                        <span>{{ __('market.product_number') }}</span>
                        <span>{{ $productNumber }}</span>
                    </div>
                    <div class="meal-detail__meta-item">
                        <span>{{ __('market.category_label') }}</span>
                        <span>{{ $categoryName }}</span>
                    </div>
                    <div class="meal-detail__meta-item md:col-span-2">
                        <span>{{ __('market.sharing') }}</span>
                        <div class="flex items-center gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="Facebook">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 9H16V6h-2.5C10.9 6 9 7.9 9 10.5V12H7v3h2v6h3v-6h2.2l.8-3H12v-1.5c0-.8.7-1.5 1.5-1.5Z"/></svg>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="X">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h4.4l4.2 6 5-6H21l-7.5 8.8L21.8 21h-4.4l-4.8-6.8L7.1 21H3l7.9-9.2L3 3Z"/></svg>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="social-link" aria-label="LinkedIn">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 8.8A1.8 1.8 0 1 0 6.5 5a1.8 1.8 0 0 0 0 3.8ZM5 10h3v9H5v-9Zm5 0h2.9v1.3h.1c.4-.8 1.4-1.6 3-1.6 3.2 0 3.8 2.1 3.8 4.8V19h-3v-3.9c0-.9 0-2.2-1.3-2.2s-1.5 1-1.5 2.1V19h-3v-9Z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="meal-detail__sections md-reveal" style="--md-i:8">
            <div class="meal-detail__section">
                <h2 class="meal-detail__section-head">{{ __('Description') }}</h2>
                <div class="meal-detail__section-body">
                    @if(! empty($meal['description']))
                        {!! nl2br(e(strip_tags($meal['description']))) !!}
                    @else
                        <p>—</p>
                    @endif
                </div>
            </div>

            @if(count($ingredientRows) > 0)
                <div class="meal-detail__section">
                    <h2 class="meal-detail__section-head">{{ __('Ingredients') }}</h2>
                    <div class="meal-detail__section-body">
                        <div class="meal-detail__ingredients">
                            @foreach($ingredientRows as $ing)
                                <span class="meal-detail__ingredient">
                                    @if(is_array($ing) && ! empty($ing['icon']))
                                        <img src="{{ $ing['icon'] }}" alt="" width="18" height="18" loading="lazy" />
                                    @endif
                                    {{ is_array($ing) ? ($ing['name'] ?? '') : (string) $ing }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="meal-detail__section">
                <h2 class="meal-detail__section-head">{{ __('market.allergens') }}</h2>
                <div class="meal-detail__section-body">
                    @if(count($allergies) > 0)
                        <div class="meal-detail__allergies">
                            @foreach($allergies as $allergy)
                                <span class="meal-detail__allergy">
                                    @if(! empty($allergy['icon']))
                                        <img src="{{ $allergy['icon'] }}" alt="" width="18" height="18" loading="lazy" />
                                    @endif
                                    {{ $allergy['name'] ?? '' }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p>{{ __('market.allergen_free') }}</p>
                    @endif
                </div>
            </div>

            @if($benefitsText !== '')
                <div class="meal-detail__section">
                    <h2 class="meal-detail__section-head">{{ __('Benefits') }}</h2>
                    <div class="meal-detail__section-body">
                        {!! nl2br(e($benefitsText)) !!}
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if(count($relatedMeals) > 0)
        <section class="container meal-detail__related">
            <header class="mb-6">
                <h2 class="section-header__title text-2xl font-bold">{{ __('market.related_products') }}</h2>
            </header>
            <div class="meal-detail__related-track">
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
                                    @if(! empty($rel['tag_name']))
                                        <span class="meal-card__category">{{ $rel['tag_name'] }}</span>
                                    @endif
                                    <div class="meal-card__price-wrap">
                                        <span class="meal-card__price"><x-sar :amount="(float) $rPrice" :decimals="0" /></span>
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

    <div class="meal-detail__mobile-bar md:hidden">
        <span class="meal-detail__mobile-price"><x-sar :amount="$effectivePrice" /></span>
        <button type="button" class="btn btn--primary btn--md meal-detail__cart-btn" @click="addToCart()" :class="added ? 'is-success' : ''">
            <span x-show="!added">{{ __('Add to Cart') }}</span>
            <span x-show="added" x-cloak>✓ {{ __('Added') }}</span>
        </button>
    </div>

    <div class="meal-detail__added-toast hidden md:flex" :class="added ? 'is-visible' : ''" aria-live="polite">
        <svg class="meal-detail__added-toast-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z" fill="currentColor"/></svg>
        <span>{{ __('market.added_to_cart') }}</span>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/styles/meal-detail.css') }}" />
@endpush

@push('scripts')
<script>
(function() {
    var root = document.querySelector('.meal-detail');
    if (!root) return;

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced) {
        root.classList.add('is-ready');
        return;
    }

    root.querySelectorAll('[data-stat]').forEach(function(card, idx) {
        var target = parseFloat(card.getAttribute('data-stat') || '0');
        var suffix = card.getAttribute('data-stat-suffix') || '';
        var out = card.querySelector('[data-stat-value]');
        if (!out || !target) return;

        var start = 0;
        var dur = 900;
        var delay = 420 + idx * 70;
        var t0 = null;

        function fmt(v) {
            var s = (Math.round(v * 10) / 10).toString().replace(/\.0$/, '');
            return s + suffix;
        }

        function tick(ts) {
            if (!t0) t0 = ts;
            var p = Math.min(1, (ts - t0 - delay) / dur);
            if (p <= 0) {
                requestAnimationFrame(tick);
                return;
            }
            var eased = 1 - Math.pow(1 - p, 3);
            out.textContent = fmt(start + (target - start) * eased);
            if (p < 1) requestAnimationFrame(tick);
        }

        requestAnimationFrame(tick);
    });

    var related = root.querySelector('.meal-detail__related-track');
    if (related && 'IntersectionObserver' in window) {
        var cards = related.querySelectorAll('.meal-card');
        cards.forEach(function(card, i) {
            card.style.setProperty('--md-i', i);
            card.classList.add('md-reveal');
        });
        var obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-inview');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
        cards.forEach(function(card) { obs.observe(card); });
    }
})();
</script>
@endpush
