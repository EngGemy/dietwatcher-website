@extends('layouts.app')

@php
$locale = app()->getLocale();

// Handle name: could be JSON {"en":"..","ar":".."} or plain string
$rawName = $plan->name ?? '';
if (is_string($rawName)) {
    $decoded = json_decode($rawName, true);
    $planName = is_array($decoded) ? ($decoded[$locale] ?? $decoded['en'] ?? $rawName) : $rawName;
} elseif (is_array($rawName)) {
    $planName = $rawName[$locale] ?? $rawName['en'] ?? '';
} else {
    $planName = (string) $rawName;
}
$planName = $planName ?: 'Meal Plan';

// Handle description: same logic
$rawDesc = $plan->description ?? '';
if (is_string($rawDesc)) {
    $decoded = json_decode($rawDesc, true);
    $planDesc = is_array($decoded) ? ($decoded[$locale] ?? $decoded['en'] ?? $rawDesc) : $rawDesc;
} elseif (is_array($rawDesc)) {
    $planDesc = $rawDesc[$locale] ?? $rawDesc['en'] ?? '';
} else {
    $planDesc = (string) $rawDesc;
}

// Get image URL — API returns full URL
$planImage = $plan->image_url ?? '';
$planImageUrl = (str_starts_with($planImage, 'http')) ? $planImage : ($planImage ? asset($planImage) : asset('assets/images/plan-1.png'));
$images = $plan->images ?? [$planImageUrl];
if (empty($images[0])) {
    $images = [$planImageUrl];
}

// Meal types
$mealTypes = [
    ['id' => 'breakfast', 'name' => __('Breakfast')],
    ['id' => 'lunch', 'name' => __('Lunch')],
    ['id' => 'dinner', 'name' => __('Dinner')],
    ['id' => 'snack', 'name' => __('Snack')],
];

// Calorie options — prefer API data, fallback to program data
if (!empty($apiCalories)) {
    $calorieOptions = array_map(function ($cal) {
        $min = $cal['min_amount'] ?? 0;
        $max = $cal['max_amount'] ?? 0;
        $range = $min && $max ? "{$min}-{$max}" : ($max ?: $min);
        return ['range' => $range, 'label' => $range . ' ' . __('kcal'), 'id' => $cal['id'] ?? 0, 'macros' => $cal['macros'] ?? null];
    }, $apiCalories);
} elseif (!empty($plan->calorie_options)) {
    $calorieOptions = $plan->calorie_options;
} else {
    $calorieOptions = [
        ['range' => ($plan->calories_min ?? 700) . '-' . ($plan->calories_max ?? 800), 'label' => ($plan->calories_min ?? 700) . '-' . ($plan->calories_max ?? 800) . ' ' . __('kcal')],
    ];
}

// Nutritional info — build from first calorie option macros or API program data
$firstMacros = $calorieOptions[0]['macros'] ?? null;
if ($firstMacros) {
    $totalMacros = ($firstMacros['protein'] ?? 0) + ($firstMacros['carbs'] ?? 0) + ($firstMacros['fats'] ?? $firstMacros['fat'] ?? 0);
    $nutrition = [
        'carbs' => [
            'amount' => ($firstMacros['carbs'] ?? 0) . 'g',
            'percent' => $totalMacros > 0 ? round(($firstMacros['carbs'] ?? 0) / $totalMacros * 100) : 33,
            'color' => 'bg-green',
        ],
        'protein' => [
            'amount' => ($firstMacros['protein'] ?? 0) . 'g',
            'percent' => $totalMacros > 0 ? round(($firstMacros['protein'] ?? 0) / $totalMacros * 100) : 33,
            'color' => 'bg-yellow',
        ],
        'fat' => [
            'amount' => ($firstMacros['fats'] ?? $firstMacros['fat'] ?? 0) . 'g',
            'percent' => $totalMacros > 0 ? round(($firstMacros['fats'] ?? $firstMacros['fat'] ?? 0) / $totalMacros * 100) : 33,
            'color' => 'bg-red',
        ],
    ];
} else {
    $nutrition = $plan->nutrition ?? [
        'carbs' => ['amount' => '—', 'percent' => 33, 'color' => 'bg-green'],
        'protein' => ['amount' => '—', 'percent' => 33, 'color' => 'bg-yellow'],
        'fat' => ['amount' => '—', 'percent' => 33, 'color' => 'bg-red'],
    ];
}

// Default meal includes based on type
$mealIncludes = [
    'breakfast' => [
        __('Fresh breakfast dish (protein + carbs)'),
        __('Healthy side (fruit or grains)'),
        __('Low-calorie sauce or spread'),
    ],
    'lunch' => [
        __('Main protein dish'),
        __('Side salad or vegetables'),
        __('Healthy carbohydrate portion'),
    ],
    'dinner' => [
        __('Lean protein main course'),
        __('Steamed vegetables'),
        __('Light carbohydrate serving'),
    ],
    'snack' => [
        __('Healthy snack portion'),
        __('Protein-rich option'),
        __('Fresh fruit or nuts'),
    ],
];

// Calculate start date (next day)
$startDate = now()->addDay()->format('Y-m-d');
$startDateDisplay = now()->addDay()->format('D d M');

// Prices from API are already VAT-inclusive (like mobile app)
$planPrice = $plan->price ?? 2200;
$offerPrice = $plan->offer_price ?? 0;
$vatRate = (float) \App\Models\Settings\Setting::getValue('vat_rate', 15) / 100;
// Price is already inclusive of VAT — extract VAT for display
$planPriceInclVat = $planPrice;
$vatInPrice = round($planPrice - ($planPrice / (1 + $vatRate)), 2);
$deliveryFee = 0; // delivery included in plan subscription
$totalPrice = $planPriceInclVat;
@endphp

@section('title', $planName . ' | ' . $siteName)
@section('description', Str::limit(strip_tags($planDesc), 160))

@section('content')
<section class="bg-gray-200 pt-10 pb-28">
    <div class="container">
        {{-- Breadcrumb --}}
        <ol class="breadcrumb">
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('home') }}">{{ __('Home') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </li>
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('meal-plans.index') }}">{{ __('Meal Plans') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </li>
            <li class="breadcrumb__item breadcrumb__item--active" aria-current="page">
                {{ $planName }}
            </li>
        </ol>

        <div class="mb-10 grid gap-10 md:mb-16 md:grid-cols-2" x-data="planDetail()" x-init="init()">
            {{-- Image Gallery --}}
            <div class="w-full min-w-0">
                <div data-hs-carousel='{ "loadingClasses": "opacity-0", "isInfinite": true }' class="relative">
                    <div class="hs-carousel relative w-full">
                        <div class="mb-5 w-full overflow-hidden rounded-md md:mb-6">
                            <div class="hs-carousel-body flex h-[400px] flex-nowrap overflow-hidden opacity-0 transition-transform duration-700 md:h-[600px]">
                                @foreach($images as $index => $image)
                                    <div class="hs-carousel-slide h-full">
                                        <img src="{{ $image && str_starts_with($image, 'http') ? $image : ($image ? asset($image) : asset('assets/images/meal-' . ($index + 1) . '.png')) }}" 
                                             class="size-full object-cover" 
                                             alt="{{ $planName }} - {{ $index + 1 }}"
                                             onerror="this.src='{{ asset('assets/images/meal-' . (($index % 3) + 1) . '.png') }}'">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if(count($images) > 1)
                            <div class="hs-carousel-pagination mt-0! w-full overflow-x-auto">
                                <div class="flex flex-row items-center gap-4">
                                    @foreach($images as $index => $image)
                                        <div class="hs-carousel-pagination-item hs-carousel-active:border-primary size-20 shrink-0 cursor-pointer overflow-hidden rounded-md border-2 border-transparent md:size-28">
                                            <img src="{{ $image && str_starts_with($image, 'http') ? $image : ($image ? asset($image) : asset('assets/images/meal-' . ($index + 1) . '.png')) }}" 
                                                 class="size-full object-contain object-center" 
                                                 alt=""
                                                 onerror="this.src='{{ asset('assets/images/meal-' . (($index % 3) + 1) . '.png') }}'">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Plan Details & Options --}}
            <div class="w-full space-y-5">
                {{-- Plan Title --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <h2 class="mb-3 text-2xl font-bold md:text-3xl">{{ $planName }}</h2>
                    <p class="text-lg text-black/70 md:text-xl">
                        {{ $planDesc ?: __('Nutritionist-designed meal plans for safe, sustainable weight loss.') }}
                    </p>
                </div>

                {{-- Meal Type Selection --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <p class="mb-3 text-lg md:text-xl">{{ __('Choose your meal type') }}</p>

                    <div class="mb-6 flex flex-wrap gap-3">
                        @foreach($mealTypes as $type)
                            <div class="choice-group__item">
                                <input type="radio" 
                                       name="meal-type" 
                                       id="meal-{{ $type['id'] }}" 
                                       class="choice-group__input"
                                       value="{{ $type['id'] }}"
                                       x-model="selectedMeal"
                                       {{ $loop->first ? 'checked' : '' }}>
                                <label for="meal-{{ $type['id'] }}" class="choice-group__label justify-center">
                                    <span class="choice-group__icon"></span>
                                    {{ $type['name'] }}
                                </label>
                            </div>
                        @endforeach
                    </div>

                    {{-- Dynamic Includes based on meal type --}}
                    <div class="rounded-md bg-gray-200 p-5">
                        <template x-for="(items, type) in {{ json_encode($mealIncludes) }}" :key="type">
                            <div x-show="selectedMeal === type" x-transition>
                                <p class="mb-2 text-lg font-semibold" x-text="selectedMeal.charAt(0).toUpperCase() + selectedMeal.slice(1) + ' {{ __('Includes') }}'"></p>
                                <ul class="list-disc space-y-1.5 ps-6">
                                    <template x-for="item in items" :key="item">
                                        <li x-text="item"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Start Date --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <div class="flex items-center gap-4">
                        <svg class="size-8">
                            <use href="{{ asset('assets/images/icons/sprite.svg#calendar') }}"></use>
                        </svg>
                        <p class="text-lg">
                            {{ __('Start your plan as soon as') }}
                            <time datetime="{{ $startDate }}" class="font-semibold">{{ $startDateDisplay }}</time>
                        </p>
                    </div>
                </div>

                {{-- Calories Selection --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <p class="mb-3 text-lg md:text-xl">{{ __('Choose calories') }}</p>

                    <div class="selection-group">
                        @foreach($calorieOptions as $option)
                            <div class="selection-group__item">
                                <input type="radio" 
                                       name="calories" 
                                       id="cal-{{ $loop->index }}" 
                                       class="selection-group__input"
                                       value="{{ $option['range'] }}"
                                       x-model="selectedCalories"
                                       {{ $loop->first ? 'checked' : '' }}>
                                <label for="cal-{{ $loop->index }}" class="selection-group__label">
                                    {{ $option['label'] }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Nutritional Info (dynamic based on selected calorie) --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <p class="mb-3 text-lg md:text-xl">{{ __('Nutritional info') }}</p>

                    <div class="flex flex-col gap-6 md:flex-row">
                        <div class="flex-1">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="font-semibold">{{ __('Carbs') }}</p>
                                <p x-text="currentNutrition.carbs"></p>
                            </div>
                            <div class="flex h-1 w-full overflow-hidden rounded-full bg-zinc-200" role="progressbar">
                                <div class="bg-green flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap transition duration-500" :style="'width:' + currentNutrition.carbsPercent + '%'"></div>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="font-semibold">{{ __('Protein') }}</p>
                                <p x-text="currentNutrition.protein"></p>
                            </div>
                            <div class="flex h-1 w-full overflow-hidden rounded-full bg-zinc-200" role="progressbar">
                                <div class="bg-yellow flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap transition duration-500" :style="'width:' + currentNutrition.proteinPercent + '%'"></div>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="font-semibold">{{ __('Fat') }}</p>
                                <p x-text="currentNutrition.fat"></p>
                            </div>
                            <div class="flex h-1 w-full overflow-hidden rounded-full bg-zinc-200" role="progressbar">
                                <div class="bg-red flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap transition duration-500" :style="'width:' + currentNutrition.fatPercent + '%'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Duration Selection --}}
                <div class="rounded-md border border-gray-200 bg-white p-5" x-show="durations.length > 0" x-cloak>
                    <p class="mb-3 text-lg md:text-xl">{{ __('Choose Duration') }}</p>

                    <div class="selection-group">
                        <template x-for="(dur, index) in durations" :key="dur.id">
                            <div class="selection-group__item">
                                <input type="radio"
                                       name="duration"
                                       :id="'dur-' + index"
                                       class="selection-group__input"
                                       :value="dur.id"
                                       x-model="selectedDurationId"
                                       @change="onDurationChange(dur)">
                                <label :for="'dur-' + index" class="selection-group__label">
                                    <span x-text="dur.label || (dur.days + ' {{ __('Days') }}')"></span>
                                    <span class="text-xs text-gray-500 block" x-text="'SAR ' + dur.price_incl_vat.toFixed(0)"></span>
                                </label>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Payment Summary --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <p class="mb-3 text-lg md:text-xl">{{ __('Payment Summary') }}</p>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600">{{ __('Plan Price') }} <span class="text-xs">({{ __('Incl. VAT') }})</span></p>
                            <p>SAR <span x-text="displayPrice.toLocaleString()"></span></p>
                        </div>
                        <template x-if="originalPrice > 0 && originalPrice !== displayPrice">
                            <div class="flex items-center justify-between">
                                <p class="text-gray-400 line-through text-sm">{{ __('Original Price') }}</p>
                                <p class="text-gray-400 line-through text-sm">SAR <span x-text="originalPrice.toLocaleString()"></span></p>
                            </div>
                        </template>
                        <div class="flex items-center justify-between text-sm text-gray-400">
                            <p>{{ __('VAT included') }} ({{ (int)(\App\Models\Settings\Setting::getValue('vat_rate', 15)) }}%)</p>
                            <p>SAR <span x-text="vatAmount.toFixed(2)"></span></p>
                        </div>

                        <div class="my-3 h-px bg-gray-300"></div>

                        <div class="flex items-center justify-between">
                            <p class="text-xl font-semibold">{{ __('Total') }}</p>
                            <p class="text-green text-xl">SAR <span x-text="displayPrice.toLocaleString()"></span></p>
                        </div>

                        <button type="button"
                                class="btn btn--primary btn--lg mt-3 w-full"
                                @click="subscribeNow()">
                            {{ __('Subscribe Now') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Accordions --}}
        <div class="hs-accordion-group accordion-group mt-10 space-y-5 md:mt-16">
            {{-- Description --}}
            <div class="hs-accordion active rounded-md border border-gray-200 bg-white" id="hs-description">
                <button class="hs-accordion-toggle flex w-full items-center justify-between px-5 py-4 text-start text-2xl font-bold text-gray-800 transition-colors focus:outline-hidden md:p-6" aria-controls="hs-description-content">
                    {{ __('Description') }}
                    <svg class="hs-accordion-active:rotate-180 size-5 text-gray-500 transition-transform duration-300">
                        <use href="{{ asset('assets/images/icons/sprite.svg#chevron-down') }}"></use>
                    </svg>
                </button>
                <div id="hs-description-content" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-description">
                    <div class="p-5 pt-0! md:p-6">
                        <div class="prose prose-lg max-w-none">
                            @if($planDesc)
                                {!! nl2br(e($planDesc)) !!}
                            @else
                                <p>{{ __('A calorie-controlled meal plan designed by nutritionists to support safe, sustainable weight loss. Enjoy balanced, portioned meals delivered daily to help you stay consistent and reach your goals with ease.') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ingredients --}}
            <div class="hs-accordion rounded-md border border-gray-200 bg-white" id="hs-ingredients">
                <button class="hs-accordion-toggle flex w-full items-center justify-between px-5 py-4 text-start text-2xl font-bold text-gray-800 transition-colors focus:outline-hidden md:p-6" aria-controls="hs-ingredients-content">
                    {{ __('Ingredients') }}
                    <svg class="hs-accordion-active:rotate-180 size-5 text-gray-500 transition-transform duration-300">
                        <use href="{{ asset('assets/images/icons/sprite.svg#chevron-down') }}"></use>
                    </svg>
                </button>
                <div id="hs-ingredients-content" class="hs-accordion-content hidden w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-ingredients">
                    <div class="p-5 pt-0! md:p-6">
                        <p>{{ $plan->ingredients ?? __('Fresh vegetables, whole grains, lean proteins, and natural seasonings.') }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    /* Breadcrumb styles */
    .breadcrumb {
        @apply flex flex-wrap items-center gap-1 text-sm text-gray-600 mb-6;
    }
    .breadcrumb__item {
        @apply flex items-center;
    }
    .breadcrumb__link {
        @apply hover:text-blue transition-colors;
    }
    .breadcrumb__separator {
        @apply mx-1 size-4;
    }
    .breadcrumb__item--active {
        @apply text-gray-900 font-medium;
    }

    /* Choice group (radio buttons as cards) */
    .choice-group__item {
        @apply relative;
    }
    .choice-group__input {
        @apply absolute opacity-0 w-0 h-0;
    }
    .choice-group__label {
        @apply flex items-center gap-2 px-4 py-2.5 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-gray-300;
    }
    .choice-group__input:checked + .choice-group__label {
        @apply border-blue bg-blue-50 text-blue;
    }
    .choice-group__icon {
        @apply size-4 rounded-full border-2 border-gray-300 flex items-center justify-center;
    }
    .choice-group__input:checked + .choice-group__label .choice-group__icon {
        @apply border-blue bg-blue;
    }
    .choice-group__icon::after {
        content: '';
        @apply size-2 rounded-full bg-white opacity-0;
    }
    .choice-group__input:checked + .choice-group__label .choice-group__icon::after {
        @apply opacity-100;
    }

    /* Selection group (calorie options) */
    .selection-group {
        @apply flex flex-wrap gap-3;
    }
    .selection-group__item {
        @apply relative;
    }
    .selection-group__input {
        @apply absolute opacity-0 w-0 h-0;
    }
    .selection-group__label {
        @apply flex items-center justify-center px-6 py-3 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-gray-300 font-medium min-w-[100px];
    }
    .selection-group__input:checked + .selection-group__label {
        @apply border-blue bg-blue-50 text-blue;
    }

    /* Progress bar colors */
    .bg-green {
        background-color: #22c55e;
    }
    .bg-yellow {
        background-color: #eab308;
    }
    .bg-red {
        background-color: #ef4444;
    }
    .text-green {
        color: #22c55e;
    }
</style>
@endpush

@push('scripts')
<script>
function planDetail() {
    return {
        selectedMeal: 'breakfast',
        selectedCalories: '{{ $calorieOptions[0]['range'] ?? '' }}',
        selectedDurationId: '',
        planPrice: {{ $planPrice }},
        vatRate: {{ $vatRate }},
        durations: [],
        calories: @json($calorieOptions),
        displayPrice: {{ $planPriceInclVat }},
        originalPrice: {{ $offerPrice > 0 ? $planPrice : 0 }},
        vatAmount: {{ $vatInPrice }},

        // Nutritional info (reactive)
        currentNutrition: {
            carbs: '{{ $nutrition['carbs']['amount'] ?? '—' }}',
            carbsPercent: {{ $nutrition['carbs']['percent'] ?? 33 }},
            protein: '{{ $nutrition['protein']['amount'] ?? '—' }}',
            proteinPercent: {{ $nutrition['protein']['percent'] ?? 33 }},
            fat: '{{ $nutrition['fat']['amount'] ?? '—' }}',
            fatPercent: {{ $nutrition['fat']['percent'] ?? 33 }},
        },

        async init() {
            // Fetch durations from API
            try {
                const durRes = await fetch('{{ route('api.plan.durations', $plan->id) }}');
                const durData = await durRes.json();
                if (durData.length > 0) {
                    // Prices from API are already VAT-inclusive
                    this.durations = durData.map(d => ({
                        ...d,
                        price_incl_vat: d.price
                    }));
                    // Select default duration
                    const defaultDur = this.durations.find(d => d.is_default) || this.durations[0];
                    if (defaultDur) {
                        this.selectedDurationId = defaultDur.id;
                        this.onDurationChange(defaultDur);
                    }
                }
            } catch (e) {
                console.warn('Could not fetch plan durations:', e);
            }

            // Fetch calories from API
            try {
                const calRes = await fetch('{{ route('api.plan.calories', $plan->id) }}');
                const calData = await calRes.json();
                if (calData.length > 0) {
                    this.calories = calData.map(c => ({
                        range: (c.min_amount || 0) + '-' + (c.max_amount || 0),
                        label: (c.min_amount || 0) + '-' + (c.max_amount || 0) + ' {{ __('kcal') }}',
                        id: c.id || 0,
                        macros: c.macros || null,
                    }));
                    const defaultCal = calData.find(c => c.is_default) || calData[0];
                    if (defaultCal) {
                        this.selectedCalories = (defaultCal.min_amount || 0) + '-' + (defaultCal.max_amount || 0);
                        this.updateNutrition(this.calories[0]);
                    }
                }
            } catch (e) {
                console.warn('Could not fetch plan calories:', e);
            }

            // Watch calorie selection to update nutritional info
            this.$watch('selectedCalories', (val) => {
                const cal = this.calories.find(c => c.range === val);
                if (cal) this.updateNutrition(cal);
            });
        },

        updateNutrition(cal) {
            if (cal && cal.macros) {
                const m = cal.macros;
                const protein = m.protein || 0;
                const carbs = m.carbs || 0;
                const fat = m.fats || m.fat || 0;
                const total = protein + carbs + fat;
                this.currentNutrition = {
                    carbs: carbs + 'g',
                    carbsPercent: total > 0 ? Math.round(carbs / total * 100) : 33,
                    protein: protein + 'g',
                    proteinPercent: total > 0 ? Math.round(protein / total * 100) : 33,
                    fat: fat + 'g',
                    fatPercent: total > 0 ? Math.round(fat / total * 100) : 33,
                };
            }
        },

        onDurationChange(dur) {
            // Price from API is already VAT-inclusive
            const priceInclVat = dur.price;
            this.displayPrice = priceInclVat;
            // Extract VAT from inclusive price for display
            this.vatAmount = Math.round((priceInclVat - (priceInclVat / (1 + this.vatRate))) * 100) / 100;
        },

        subscribeNow() {
            let url = '{{ route('checkout.index') }}?plan_id={{ $plan->id }}' +
                '&meal_type=' + this.selectedMeal +
                '&calories=' + this.selectedCalories;
            if (this.selectedDurationId) {
                url += '&duration_id=' + this.selectedDurationId;
            }
            window.location.href = url;
        }
    };
}
</script>
@endpush
