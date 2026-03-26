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
                class="tag {{ $selectedCategory === null ? 'tag--active' : '' }}"
                aria-pressed="{{ $selectedCategory === null ? 'true' : 'false' }}">
                {{ __('All') }}
            </button>

            @foreach($categories as $category)
                @php
                    $catName = is_array($category['name'] ?? null)
                        ? ($category['name'][app()->getLocale()] ?? $category['name']['en'] ?? '')
                        : ($category['name'] ?? '');

                    // Map category name to sprite icon ID
                    $slug = strtolower(preg_replace('/[\s_]+/', '-', $catName));
                    $iconMap = [
                        'high-protein'   => 'high-protein',
                        'vegetarian'     => 'vegetarian',
                        'balanced'       => 'balanced',
                        'weight-loss'    => 'weight-loss',
                        'weight-management' => 'weight-loss',
                        'drying-plan'    => 'drying-plan',
                        'drying'         => 'drying-plan',
                        'bulking-plan'   => 'bulking-plan',
                        'bulking'        => 'bulking-plan',
                        'medical'        => 'balanced',
                        'lifestyle'      => 'balanced',
                    ];
                    // Try exact slug, then check if any key is contained in the slug
                    $iconId = $iconMap[$slug] ?? null;
                    if (!$iconId) {
                        foreach ($iconMap as $key => $val) {
                            if (str_contains($slug, $key)) { $iconId = $val; break; }
                        }
                    }
                @endphp
                <button
                    type="button"
                    wire:click="filterByCategory({{ (int)$category['id'] }})"
                    class="tag {{ $selectedCategory === (int)$category['id'] ? 'tag--active' : '' }}"
                    aria-pressed="{{ $selectedCategory === (int)$category['id'] ? 'true' : 'false' }}">
                    @if($iconId)
                        <svg style="width:16px;height:16px;flex-shrink:0">
                            <use href="{{ asset('assets/images/icons/sprite.svg#' . $iconId) }}"></use>
                        </svg>
                    @endif
                    {{ $catName }}
                </button>
            @endforeach
        </div>

        {{-- Types of Meal dropdown --}}
        <div class="relative" style="flex-shrink:0">
            <select
                wire:model.live="selectedMealType"
                class="relative ps-4 py-2.5 pe-8 flex gap-x-2 text-nowrap w-full min-w-[180px] cursor-pointer border-b border-gray-300 text-start bg-transparent outline-none appearance-none text-sm text-gray-700"
            >
                <option value="">{{ __('Types of Meal') }}</option>
                <option value="breakfast">{{ __('Breakfast') }}</option>
                <option value="lunch">{{ __('Lunch') }}</option>
                <option value="snack">{{ __('Snack') }}</option>
                <option value="dinner">{{ __('Dinner') }}</option>
            </select>
            <div class="pointer-events-none absolute end-2.5 top-1/2 -translate-y-1/2">
                <svg width="16" height="16" style="width:16px;height:16px" class="text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
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
                            <svg width="16" height="16" style="width:16px;height:16px">
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
