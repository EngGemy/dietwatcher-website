<div>
    {{-- Page Header --}}
    <header class="section-header max-w-3xl">
        <h2 class="section-header__title">{{ $pageTitle }}</h2>
        <p class="section-header__desc">{{ $pageDescription }}</p>
    </header>

    {{-- Filters Row --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

        {{-- Category Tags --}}
        <div class="tag-list m-0">
            <button
                type="button"
                wire:click="filterByCategory(null)"
                class="tag"
                aria-pressed="{{ $selectedCategory === null ? 'true' : 'false' }}">
                {{ __('All') }}
            </button>

            @foreach($categories as $category)
                @php
                    $catName = is_array($category['name'] ?? null)
                        ? ($category['name'][app()->getLocale()] ?? $category['name']['en'] ?? '')
                        : ($category['name'] ?? '');

                    // Map to sprite icon by scanning category name for keywords
                    $lower = strtolower($catName);
                    $spriteId = match(true) {
                        str_contains($lower, 'protein')                    => 'high-protein',
                        str_contains($lower, 'vegetarian') ||
                          str_contains($lower, 'vegan')                    => 'vegetarian',
                        str_contains($lower, 'weight') ||
                          str_contains($lower, 'loss')                     => 'weight-loss',
                        str_contains($lower, 'dry')                        => 'drying-plan',
                        str_contains($lower, 'bulk') ||
                          str_contains($lower, 'muscle') ||
                          str_contains($lower, 'gain')                     => 'bulking-plan',
                        default                                             => 'balanced',
                    };
                @endphp
                @if($catName)
                    <button
                        type="button"
                        wire:click="filterByCategory({{ (int)$category['id'] }})"
                        class="tag"
                        aria-pressed="{{ $selectedCategory === (int)$category['id'] ? 'true' : 'false' }}">
                        <svg>
                            <use href="{{ asset('assets/images/icons/sprite.svg#' . $spriteId) }}"></use>
                        </svg>
                        {{ $catName }}
                    </button>
                @endif
            @endforeach
        </div>

        {{-- Types of Meal — custom Alpine dropdown --}}
        <div class="relative flex-shrink-0" x-data="{ open: false, label: '{{ __('Types of Meal') }}' }" @click.outside="open = false">
            <button
                type="button"
                @click="open = !open"
                class="flex items-center gap-x-2 cursor-pointer border-b border-gray-300 ps-1 pe-2 py-2.5 text-sm text-gray-700 min-w-[160px] focus:outline-none whitespace-nowrap"
            >
                <span x-text="label" class="flex-1 text-start"></span>
                <svg width="16" height="16" style="width:16px;height:16px;flex-shrink:0;color:#6b7280" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </button>
            <div
                x-show="open"
                x-transition
                class="absolute end-0 top-full z-50 mt-1 w-44 rounded-md border border-gray-200 bg-white p-1 shadow-md"
                style="display:none"
            >
                @foreach(['' => __('All Types'), 'breakfast' => __('Breakfast'), 'lunch' => __('Lunch'), 'snack' => __('Snack'), 'dinner' => __('Dinner')] as $val => $lbl)
                    <button
                        type="button"
                        wire:click="filterByMealType('{{ $val }}')"
                        @click="label = '{{ addslashes($val === '' ? __('Types of Meal') : $lbl) }}'; open = false"
                        class="flex w-full items-center justify-between rounded-md px-4 py-2 text-sm hover:bg-gray-100 {{ $selectedMealType === $val ? 'font-semibold text-blue-600' : 'text-gray-800' }}"
                    >
                        {{ $lbl }}
                        @if($selectedMealType === $val && $val !== '')
                            <svg width="14" height="14" style="width:14px;height:14px;flex-shrink:0;color:#279ff9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Plans Grid --}}
    <div class="mt-14 grid gap-8 lg:grid-cols-3 xl:grid-cols-4 meal-plans-grid">
        @forelse($plans as $plan)
            @php
                $planImg     = $plan['image_url'] ?? '';
                $planImgUrl  = str_starts_with($planImg, 'http') ? $planImg : ($planImg ? asset($planImg) : asset('assets/images/plan-1.png'));
                $planFallback = asset('assets/images/plan-' . ($loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3) . '.png');
                $catLabel = is_array($plan['category']['name'] ?? null)
                    ? ($plan['category']['name'][app()->getLocale()] ?? $plan['category']['name']['en'] ?? '')
                    : ($plan['category']['name'] ?? '');

                // Compute "lowest valid price" across available pricing options.
                $durationPrices = collect($plan['subscription_plans'] ?? [])
                    ->flatMap(fn ($sp) => $sp['durations'] ?? [])
                    ->map(function ($d) {
                        $price = (float) ($d['price'] ?? 0);
                        $offer = (float) ($d['offer_price'] ?? 0);
                        $effective = (float) ($d['effective_price'] ?? 0);
                        if ($effective > 0) {
                            return $effective;
                        }
                        if ($offer > 0 && ($price <= 0 || $offer < $price)) {
                            return $offer;
                        }

                        return $price;
                    })
                    ->filter(fn ($p) => is_numeric($p) && (float) $p > 0)
                    ->values();

                $directPrices = collect([
                    (float) ($plan['min_price'] ?? 0),
                    (float) ($plan['offer_price'] ?? 0),
                    (float) ($plan['price'] ?? 0),
                    (float) ($plan['weekly_price'] ?? 0),
                ])->filter(fn ($p) => is_numeric($p) && (float) $p > 0)->values();

                $allValidPrices = $durationPrices->merge($directPrices);
                $minPlanPrice = $allValidPrices->isNotEmpty() ? (float) $allValidPrices->min() : null;
                $startsFromLabel = app()->getLocale() === 'ar' ? 'يبدأ من' : __('Starting From');
            @endphp
            <div class="plan-card plan-card--premium group flex h-full flex-col" wire:key="plan-{{ $plan['id'] }}">
                <div class="plan-card__thumbnail plan-card__thumbnail--premium">
                    <a href="{{ route('meal-plans.show', $plan['id']) }}">
                        <img src="{{ $planImgUrl }}" alt="{{ $plan['name'] }}" loading="lazy"
                             onerror="this.src='{{ $planFallback }}'" />
                    </a>

                    <div class="plan-card__badges-wrapper">
                        @if(!empty($plan['calories_per_day']))
                            <span class="plan-card__badge">{{ number_format($plan['calories_per_day']) }} {{ __('kcal/day') }}</span>
                        @endif
                    </div>
                </div>

                <div class="plan-card__body plan-card__body--premium flex min-h-0 flex-1 flex-col">
                    <a href="{{ route('meal-plans.show', $plan['id']) }}">
                        <h3 class="plan-card__title">{{ $plan['name'] }}</h3>
                    </a>
                    <div class="plan-card__meta">
                        @if(!empty($plan['duration_days']))
                            <p>{{ $plan['duration_days'] }} {{ __('Days') }}</p>
                            &bull;
                        @endif
                        <p>{{ __('Starting From') }} <time>{{ now()->addHours(48)->format('d M Y') }}</time></p>
                    </div>

                    <div class="plan-card__footer plan-card__footer--premium">
                        @if($catLabel)
                            <span class="plan-card__category">{{ $catLabel }}</span>
                        @endif
                        <div class="plan-card__price-cta">
                            @if($minPlanPrice)
                                <span class="plan-card__price">{{ $startsFromLabel }} {{ number_format($minPlanPrice, 0) }} {{ app()->getLocale() === 'ar' ? 'ر.س' : __('SAR') }}</span>
                            @else
                                <span class="plan-card__price">{{ __('Price unavailable') }}</span>
                            @endif
                            <a href="{{ route('meal-plans.show', $plan['id']) }}" class="plan-card__cta">
                                {{ __('Select') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-16">
                <svg width="80" height="80" style="width:80px;height:80px;margin:0 auto 1rem;color:#d1d5db" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="text-xl font-semibold text-black mb-2">{{ __('No meal plans found') }}</h3>
                <p class="text-black/60">{{ __('Try adjusting your filters.') }}</p>
            </div>
        @endforelse
    </div>

    <style>
        .meal-plans-grid > .plan-card--premium {
            opacity: 0;
            transform: translateY(18px);
            animation: planCardIn .55s cubic-bezier(.22,1,.36,1) forwards;
            animation-delay: calc(var(--card-i, 0) * 70ms);
        }
        .plan-card--premium {
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s ease, border-color .25s ease;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
            border-color: rgba(148, 163, 184, .45);
        }
        .plan-card--premium:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
            border-color: rgba(39, 159, 249, .35);
        }
        .plan-card__thumbnail--premium {
            overflow: hidden;
            border-radius: .6rem;
        }
        .plan-card__thumbnail--premium img {
            transition: transform .45s cubic-bezier(.22,1,.36,1);
        }
        .plan-card--premium:hover .plan-card__thumbnail--premium img {
            transform: scale(1.03);
        }
        .plan-card__body--premium {
            gap: .15rem;
        }
        .plan-card__footer--premium {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: .6rem;
            border-top: 1px solid rgba(100, 116, 139, .2);
            padding-top: .65rem;
        }
        .plan-card__price-cta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            width: 100%;
        }
        .plan-card__price {
            white-space: nowrap;
            font-size: 1rem;
            font-weight: 700;
        }
        .plan-card__cta {
            display: inline-flex;
            width: auto;
            min-width: 90px;
            align-items: center;
            justify-content: center;
            border-radius: .65rem;
            padding: .45rem .9rem;
            background: #279ff9;
            color: #fff;
            font-weight: 600;
            transition: background-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }
        .plan-card__cta:hover {
            background: #1789e4;
            box-shadow: 0 8px 18px rgba(39, 159, 249, 0.24);
            transform: translateY(-1px);
        }
        @keyframes planCardIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 640px) {
            .plan-card__price-cta {
                gap: .5rem;
            }
            .plan-card__cta {
                min-width: 82px;
                padding: .42rem .8rem;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .meal-plans-grid > .plan-card--premium {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
            .plan-card--premium,
            .plan-card__thumbnail--premium img,
            .plan-card__cta {
                transition: none !important;
            }
        }
    </style>

    <script>
        (function () {
            var cards = document.querySelectorAll('.meal-plans-grid > .plan-card--premium');
            cards.forEach(function (card, idx) {
                card.style.setProperty('--card-i', idx);
            });
        })();
    </script>
</div>
