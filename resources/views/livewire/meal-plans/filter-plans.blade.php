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
                    $catImg  = $category['image_url'] ?? '';
                    $catImgUrl = $catImg ? (str_starts_with($catImg, 'http') ? $catImg : asset($catImg)) : null;

                    // Map API category name to sprite icon as fallback
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $catName));
                    $spriteMap = [
                        'weight' => 'weight-loss', 'loss' => 'weight-loss',
                        'protein' => 'high-protein', 'high-protein' => 'high-protein',
                        'vegetarian' => 'vegetarian', 'vegan' => 'vegetarian',
                        'balanced' => 'balanced', 'lifestyle' => 'balanced', 'life' => 'balanced',
                        'dry' => 'drying-plan', 'drying' => 'drying-plan',
                        'bulk' => 'bulking-plan', 'bulking' => 'bulking-plan',
                        'medical' => 'balanced', 'condition' => 'balanced',
                        'muscle' => 'high-protein', 'gain' => 'high-protein',
                    ];
                    $spriteId = null;
                    foreach ($spriteMap as $key => $val) {
                        if (str_contains($slug, $key)) { $spriteId = $val; break; }
                    }
                @endphp
                @if($catName)
                    <button
                        type="button"
                        wire:click="filterByCategory({{ (int)$category['id'] }})"
                        class="tag"
                        aria-pressed="{{ $selectedCategory === (int)$category['id'] ? 'true' : 'false' }}">
                        @if($catImgUrl)
                            <img src="{{ $catImgUrl }}" alt="" width="20" height="20" style="width:20px;height:20px;border-radius:50%;object-fit:cover;flex-shrink:0" />
                        @elseif($spriteId)
                            <svg>
                                <use href="{{ asset('assets/images/icons/sprite.svg#' . $spriteId) }}"></use>
                            </svg>
                        @endif
                        {{ $catName }}
                    </button>
                @endif
            @endforeach
        </div>

        {{-- Types of Meal — native select, no double arrow --}}
        <div class="relative flex-shrink-0">
            <select
                wire:model.live="selectedMealType"
                style="appearance:none;-webkit-appearance:none;background-image:none;background:transparent;border:none;border-bottom:1px solid #d1d5db;padding:.625rem 2rem .625rem .25rem;font-size:.875rem;color:#374151;min-width:160px;cursor:pointer;outline:none"
            >
                <option value="">{{ __('Types of Meal') }}</option>
                <option value="breakfast">{{ __('Breakfast') }}</option>
                <option value="lunch">{{ __('Lunch') }}</option>
                <option value="snack">{{ __('Snack') }}</option>
                <option value="dinner">{{ __('Dinner') }}</option>
            </select>
            <div class="pointer-events-none absolute end-0 top-1/2 -translate-y-1/2">
                <svg class="size-4 shrink-0 text-gray-500">
                    <use href="{{ asset('assets/images/icons/sprite.svg#arrow-sm-down') }}"></use>
                </svg>
            </div>
        </div>
    </div>

    {{-- Plans Grid --}}
    <div class="mt-14 grid gap-8 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($plans as $plan)
            @php
                $planImg     = $plan['image_url'] ?? '';
                $planImgUrl  = str_starts_with($planImg, 'http') ? $planImg : ($planImg ? asset($planImg) : asset('assets/images/plan-1.png'));
                $planFallback = asset('assets/images/plan-' . ($loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3) . '.png');
                $catLabel = is_array($plan['category']['name'] ?? null)
                    ? ($plan['category']['name'][app()->getLocale()] ?? $plan['category']['name']['en'] ?? '')
                    : ($plan['category']['name'] ?? '');
            @endphp
            <div class="plan-card" wire:key="plan-{{ $plan['id'] }}">
                <div class="plan-card__thumbnail">
                    <a href="{{ route('meal-plans.show', $plan['id']) }}">
                        <img src="{{ $planImgUrl }}" alt="{{ $plan['name'] }}" loading="lazy"
                             onerror="this.src='{{ $planFallback }}'" />
                    </a>

                    <div class="plan-card__badges-wrapper">
                        @if(!empty($plan['calories_per_day']))
                            <span class="plan-card__badge">{{ number_format($plan['calories_per_day']) }} {{ __('kcal/day') }}</span>
                        @endif

                        <a href="{{ route('meal-plans.show', $plan['id']) }}" class="plan-card__select">
                            {{ __('Select') }}
                            <svg>
                                <use href="{{ asset('assets/images/icons/sprite.svg#check') }}"></use>
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="plan-card__body">
                    <a href="{{ route('meal-plans.show', $plan['id']) }}">
                        <h3 class="plan-card__title">{{ $plan['name'] }}</h3>
                    </a>
                    <div class="plan-card__meta">
                        @if(!empty($plan['duration_days']))
                            <p>{{ $plan['duration_days'] }} {{ __('Days') }}</p>
                            &bull;
                        @endif
                        <p>{{ __('Starting From') }} <time>{{ now()->format('d M Y') }}</time></p>
                    </div>

                    <div class="plan-card__footer">
                        @if($catLabel)
                            <span class="plan-card__category">{{ $catLabel }}</span>
                        @endif
                        <span class="plan-card__price">{{ __('SAR') }} {{ number_format($plan['price'], 0) }}</span>
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
</div>
