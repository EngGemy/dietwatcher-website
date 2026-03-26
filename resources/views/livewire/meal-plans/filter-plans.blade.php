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
