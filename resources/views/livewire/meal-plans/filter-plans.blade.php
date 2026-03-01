<div>
    <style>
        .tag--active {
            background-color: #279ff9 !important;
            color: white !important;
        }
    </style>

    {{-- Page Header --}}
    <header class="section-header max-w-3xl">
        <h2 class="section-header__title">{{ $pageTitle }}</h2>
        <p class="section-header__desc">{{ $pageDescription }}</p>
    </header>

    {{-- Category Filters (only shown when categories exist in API) --}}
    @if(!empty($categories))
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="tag-list m-0">
                <button
                    type="button"
                    wire:click="filterByCategory(null)"
                    class="tag {{ $selectedCategory === null ? 'tag--active' : '' }}"
                    aria-pressed="{{ $selectedCategory === null ? 'true' : 'false' }}">
                    {{ __('All') }}
                </button>
                @foreach($categories as $category)
                    @php
                        $catName = is_array($category['name'] ?? null)
                            ? ($category['name'][app()->getLocale()] ?? $category['name']['en'] ?? '')
                            : ($category['name'] ?? '');
                    @endphp
                    <button
                        type="button"
                        wire:click="filterByCategory({{ (int)$category['id'] }})"
                        class="tag {{ $selectedCategory === (int)$category['id'] ? 'tag--active' : '' }}"
                        aria-pressed="{{ $selectedCategory === (int)$category['id'] ? 'true' : 'false' }}">
                        {{ $catName }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Plans Grid --}}
    <div class="mt-14 grid gap-8 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($plans as $plan)
            <div class="plan-card" wire:key="plan-{{ $plan['id'] }}">
                <div class="plan-card__thumbnail">
                    <a href="{{ route('meal-plans.show', $plan['id']) }}">
                        @php
                            $planImg = $plan['image_url'] ?? '';
                            $planImgUrl = str_starts_with($planImg, 'http') ? $planImg : ($planImg ? asset($planImg) : asset('assets/images/plan-1.png'));
                            $planFallback = asset('assets/images/plan-' . ($loop->iteration % 3 === 0 ? 3 : $loop->iteration % 3) . '.png');
                        @endphp
                        <img src="{{ $planImgUrl }}" alt="{{ $plan['name'] }}" loading="lazy" onerror="this.src='{{ $planFallback }}'" />
                    </a>

                    <div class="plan-card__badges-wrapper">
                        @if(!empty($plan['calories_per_day']))
                            <span class="plan-card__badge">{{ number_format($plan['calories_per_day']) }} {{ __('kcal/day') }}</span>
                        @endif

                        <a href="{{ route('meal-plans.show', $plan['id']) }}" class="plan-card__select">
                            {{ __('Select') }}
                            <svg>
                                <use href="{{ asset('assets/images/icons/sprite.svg#bag') }}"></use>
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
                        <p>{{ __('Starting From') }} <time datetime="">{{ now()->format('d M Y') }}</time></p>
                    </div>

                    <div class="plan-card__footer">
                        <span class="plan-card__category">{{ $plan['category']['name'] ?? '' }}</span>
                        <span class="plan-card__price">{{ __('SAR') }} {{ number_format($plan['price'], 0) }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-16">
                <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="text-xl font-semibold text-black mb-2">{{ __('No meal plans found') }}</h3>
                <p class="text-black/60">{{ __('Try adjusting your filters.') }}</p>
            </div>
        @endforelse
    </div>
</div>
