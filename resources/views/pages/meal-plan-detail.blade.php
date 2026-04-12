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

// Program cover: API profile.image — root-relative paths must use API host, not this Laravel origin
$externalApiOrigin = rtrim(preg_replace('#/api/?$#i', '', (string) config('services.external_api.url', '')), '/');
$resolveProgramImage = function (?string $url) use ($externalApiOrigin): string {
    $url = trim((string) $url);
    if ($url === '') {
        return asset('assets/images/plan-1.png');
    }
    if (str_starts_with($url, '//')) {
        return 'https:'.$url;
    }
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        return $url;
    }
    if (str_starts_with($url, '/') && $externalApiOrigin !== '') {
        return $externalApiOrigin.$url;
    }

    return asset(ltrim($url, '/'));
};
$rawProgramImage = $plan->profile_image_url ?? $plan->image_url ?? '';
$planImageUrl = $resolveProgramImage($rawProgramImage);
$rawGallery = $plan->images ?? null;
if (! is_array($rawGallery) || empty(array_filter($rawGallery))) {
    $images = [$planImageUrl];
} else {
    $images = array_values(array_filter(array_map(
        static fn ($img) => $resolveProgramImage(is_string($img) ? $img : ''),
        $rawGallery
    )));
    if ($images === []) {
        $images = [$planImageUrl];
    }
}

// Subscription variants (Full / Morning / …) from /programs/{id} when API returns profile + plans
$subscriptionPlans = [];
if (! empty($plan->subscription_plans)) {
    $subscriptionPlans = is_array($plan->subscription_plans)
        ? $plan->subscription_plans
        : json_decode(json_encode($plan->subscription_plans), true) ?? [];
}
$hasSubscriptionPlans = count($subscriptionPlans) > 0;

// Meal types (legacy detail shape without subscription_plans)
$mealTypes = [
    ['id' => 'breakfast', 'name' => __('Breakfast')],
    ['id' => 'lunch', 'name' => __('Lunch')],
    ['id' => 'dinner', 'name' => __('Dinner')],
    ['id' => 'snack', 'name' => __('Snack')],
];

// Calorie options — prefer first subscription plan, then API list, then CMS
if ($hasSubscriptionPlans && ! empty($subscriptionPlans[0]['calories'])) {
    $calorieOptions = array_map(static function (array $c): array {
        return [
            'range' => $c['range'] ?? '',
            'label' => $c['label'] ?? (($c['amount'] ?? '').' '.__('kcal')),
            'id' => (int) ($c['id'] ?? 0),
            'macros' => $c['macros'] ?? null,
            'is_default' => (bool) ($c['is_default'] ?? false),
        ];
    }, $subscriptionPlans[0]['calories']);
} elseif (!empty($apiCalories)) {
    $calorieOptions = array_map(function ($cal) {
        $min = $cal['min_amount'] ?? 0;
        $max = $cal['max_amount'] ?? 0;
        $range = $min && $max ? "{$min}-{$max}" : ($max ?: $min);

        return [
            'range' => $range,
            'label' => $range.' '.__('kcal'),
            'id' => $cal['id'] ?? 0,
            'macros' => $cal['macros'] ?? null,
            'is_default' => (bool) ($cal['is_default'] ?? false),
        ];
    }, $apiCalories);
} elseif (!empty($plan->calorie_options)) {
    $calorieOptions = $plan->calorie_options;
} else {
    $calorieOptions = [
        ['range' => ($plan->calories_min ?? 700) . '-' . ($plan->calories_max ?? 800), 'label' => ($plan->calories_min ?? 700) . '-' . ($plan->calories_max ?? 800) . ' ' . __('kcal')],
    ];
}

// Nutritional info — build from default calorie option macros or API program data
$defaultCalorieRow = collect($calorieOptions)->firstWhere('is_default', true) ?? ($calorieOptions[0] ?? null);
$firstMacros = is_array($defaultCalorieRow) ? ($defaultCalorieRow['macros'] ?? null) : null;
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

// Default meal includes based on type (legacy UI only)
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

$firstCalRange = '';
foreach ($calorieOptions as $co) {
    if (! empty($co['is_default'])) {
        $firstCalRange = (string) ($co['range'] ?? '');
        break;
    }
}
if ($firstCalRange === '' && isset($calorieOptions[0]['range'])) {
    $firstCalRange = (string) $calorieOptions[0]['range'];
}

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
                @if($hasSubscriptionPlans)
                    <div class="mb-5 w-full overflow-hidden rounded-md md:mb-6">
                        <img src="{{ $planImageUrl }}"
                             x-bind:src="heroImage"
                             class="h-[400px] size-full object-cover md:h-[600px]"
                             alt="{{ $planName }}"
                             referrerpolicy="no-referrer"
                             decoding="async"
                             x-on:error="onPlanHeroImageError($event)">
                    </div>
                @else
                    <div data-hs-carousel='{ "loadingClasses": "opacity-0", "isInfinite": true }' class="relative">
                        <div class="hs-carousel relative w-full">
                            <div class="mb-5 w-full overflow-hidden rounded-md md:mb-6">
                                <div class="hs-carousel-body flex h-[400px] flex-nowrap overflow-hidden opacity-0 transition-transform duration-700 md:h-[600px]">
                                    @foreach($images as $index => $image)
                                        <div class="hs-carousel-slide h-full">
                                            <img src="{{ $image }}"
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
                                                <img src="{{ $image }}"
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
                @endif
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

                {{-- Plan variant (API menus) or legacy meal type --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    @if($hasSubscriptionPlans)
                        <p class="mb-3 text-lg md:text-xl">{{ __('Choose your plan') }}</p>

                        <div class="mb-6 flex flex-wrap gap-3">
                            @foreach($subscriptionPlans as $sp)
                                @php $spId = (int) ($sp['id'] ?? 0); @endphp
                                <div class="choice-group__item">
                                    <input type="radio"
                                           name="subscription-plan"
                                           id="subplan-{{ $spId }}"
                                           class="choice-group__input"
                                           value="{{ $spId }}"
                                           x-model.number="selectedSubscriptionPlanId"
                                           {{ $loop->first ? 'checked' : '' }}>
                                    <label for="subplan-{{ $spId }}" class="choice-group__label justify-center max-w-full text-center">
                                        <span class="choice-group__icon"></span>
                                        <span class="text-start">{{ $sp['name'] ?? '' }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="rounded-md bg-gray-200 p-5">
                            <p class="mb-2 text-lg font-semibold">{{ __("What's included") }}</p>
                            <ul class="list-disc space-y-1.5 ps-6">
                                <template x-for="line in activeMenusDisplay" :key="line">
                                    <li x-text="line"></li>
                                </template>
                            </ul>
                        </div>
                    @else
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
                    @endif
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
                        <template x-for="(opt, index) in calories" :key="opt.id || opt.range || index">
                            <div class="selection-group__item">
                                <input type="radio"
                                       name="calories"
                                       :id="'cal-opt-' + index"
                                       class="selection-group__input"
                                       :value="opt.range"
                                       x-model="selectedCalories">
                                <label :for="'cal-opt-' + index" class="selection-group__label" x-text="opt.label"></label>
                            </div>
                        </template>
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

                    <div class="duration-pills">
                        <template x-for="(dur, index) in durations" :key="dur.id">
                            <div class="duration-pills__item">
                                <input type="radio"
                                       name="duration"
                                       :id="'dur-' + index"
                                       class="duration-pills__input"
                                       :value="dur.id"
                                       x-model="selectedDurationId"
                                       @change="onDurationChange(dur)">
                                <label :for="'dur-' + index" class="duration-pills__face">
                                    <span class="duration-pills__offer-badge" x-show="durationRowHasOffer(dur)" x-cloak>{{ __('Offer') }}</span>
                                    <span class="duration-pills__title" x-text="dur.label || (dur.days + ' {{ __('Days') }}')"></span>
                                    <span class="duration-pills__strike" x-show="durationRowHasOffer(dur)" x-text="'{{ __('SAR') }} ' + durationRowListStr(dur)"></span>
                                    <span class="duration-pills__total-line" x-text="'{{ __('SAR') }} ' + durationRowEffectiveStr(dur)"></span>
                                    <span class="duration-pills__avg" x-show="durationRowAvgLine(dur)" x-text="durationRowAvgLine(dur)"></span>
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
                        <div class="flex items-center justify-between text-sm text-gray-600" x-show="selectedDurationDays > 0" x-cloak>
                            <p>{{ __('Avg. per day') }} <span class="text-xs text-gray-400">({{ __('Incl. VAT') }})</span></p>
                            <p class="font-semibold text-gray-800" x-text="avgPerDayAmount()"></p>
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
        selectedSubscriptionPlanId: null,
        hasSubscriptionPlans: {{ $hasSubscriptionPlans ? 'true' : 'false' }},
        subscriptionPlans: @json($subscriptionPlans),
        externalMediaOrigin: @json($externalApiOrigin),
        defaultProgramImage: @json($planImageUrl),
        heroImage: @json($planImageUrl),
        heroImageErrorStage: 0,
        activeMenusDisplay: @json($hasSubscriptionPlans ? ($subscriptionPlans[0]['menus_display'] ?? []) : []),
        selectedCalories: @json($firstCalRange),
        selectedDurationId: '',
        selectedDurationDays: 0,
        planPrice: {{ $planPrice }},
        vatRate: {{ $vatRate }},
        durations: [],
        calories: @json($calorieOptions),
        displayPrice: {{ $planPriceInclVat }},
        originalPrice: {{ $offerPrice > 0 ? $planPrice : 0 }},
        vatAmount: {{ $vatInPrice }},

        currentNutrition: {
            carbs: '{{ $nutrition['carbs']['amount'] ?? '—' }}',
            carbsPercent: {{ $nutrition['carbs']['percent'] ?? 33 }},
            protein: '{{ $nutrition['protein']['amount'] ?? '—' }}',
            proteinPercent: {{ $nutrition['protein']['percent'] ?? 33 }},
            fat: '{{ $nutrition['fat']['amount'] ?? '—' }}',
            fatPercent: {{ $nutrition['fat']['percent'] ?? 33 }},
        },

        normalizeImageUrl(s) {
            if (!s || typeof s !== 'string') return '';
            const u = s.trim();
            if (u.startsWith('//')) return 'https:' + u;
            if (/^https?:\/\//i.test(u)) return u;
            if (u.startsWith('/') && this.externalMediaOrigin) {
                return this.externalMediaOrigin + u;
            }
            if (this.externalMediaOrigin && /^(storage|uploads?)\//i.test(u)) {
                return this.externalMediaOrigin + '/' + u;
            }
            return '';
        },

        onPlanHeroImageError() {
            const fallback = '{{ asset('assets/images/plan-1.png') }}';
            if (this.heroImageErrorStage === 0) {
                this.heroImageErrorStage = 1;
                if (this.heroImage !== this.defaultProgramImage) {
                    this.heroImage = this.defaultProgramImage;
                } else {
                    this.heroImage = fallback;
                }
                return;
            }
            if (this.heroImageErrorStage === 1) {
                this.heroImageErrorStage = 2;
                this.heroImage = fallback;
            }
        },

        mapDurationRow(d) {
            const p = parseFloat(d.price) || 0;
            const o = parseFloat(d.offer_price) || 0;
            const eff = o > 0 && o < p ? o : p;
            return {
                ...d,
                price_incl_vat: eff,
                list_price: p,
                effective_total: eff,
                has_offer: o > 0 && o < p,
            };
        },

        durationRowHasOffer(dur) {
            if (!dur) return false;
            if (dur.has_offer) return true;
            const p = parseFloat(dur.price) || 0;
            const o = parseFloat(dur.offer_price) || 0;
            return o > 0 && o < p;
        },

        durationRowListStr(dur) {
            const lp = parseFloat(dur.list_price);
            const raw = !Number.isNaN(lp) && lp > 0 ? lp : parseFloat(dur.price) || 0;
            const n = Math.round(raw * 100) / 100;
            return Number.isInteger(n) ? String(n) : n.toFixed(2);
        },

        durationRowEffectiveStr(dur) {
            const p = parseFloat(dur.price) || 0;
            const o = parseFloat(dur.offer_price) || 0;
            const eff = o > 0 && o < p ? o : p;
            const n = Math.round(eff * 100) / 100;
            return Number.isInteger(n) ? String(n) : n.toFixed(2);
        },

        durationRowAvgLine(dur) {
            const days = parseInt(dur.days, 10) || 0;
            const p = parseFloat(dur.price) || 0;
            const o = parseFloat(dur.offer_price) || 0;
            const eff = o > 0 && o < p ? o : p;
            if (days <= 0 || eff <= 0) return '';
            const avg = Math.round((eff / days) * 100) / 100;
            const ns = Number.isInteger(avg) ? String(avg) : avg.toFixed(2);
            return '{{ __('SAR') }} ' + ns + ' · {{ __('per day') }}';
        },

        avgPerDayAmount() {
            const days = Number(this.selectedDurationDays) || 0;
            if (days <= 0) return '—';
            const v = Number(this.displayPrice) / days;
            const avg = Math.round(v * 100) / 100;
            return '{{ __('SAR') }} ' + (Number.isInteger(avg) ? String(avg) : avg.toFixed(2));
        },

        applySubscriptionPlan(plan) {
            if (!plan) return;
            this.heroImageErrorStage = 0;
            const variantUrl = this.normalizeImageUrl(plan.image_url || '');
            this.heroImage = variantUrl || this.defaultProgramImage;
            this.activeMenusDisplay = Array.isArray(plan.menus_display) ? plan.menus_display : [];

            this.durations = (plan.durations || []).map((d) => this.mapDurationRow(d));

            this.calories = (plan.calories || []).map(c => ({
                range: c.range,
                label: c.label || (c.amount ? c.amount + ' {{ __('kcal') }}' : ''),
                id: c.id || 0,
                is_default: !!c.is_default,
                macros: c.macros || null,
            }));

            const defaultCal = this.calories.find(c => c.is_default) || this.calories[0];
            if (defaultCal) {
                this.selectedCalories = defaultCal.range;
                this.updateNutrition(defaultCal);
            }

            const defaultDur = this.durations.find(d => d.is_default) || this.durations[0];
            if (defaultDur) {
                this.selectedDurationId = defaultDur.id;
                this.onDurationChange(defaultDur);
            } else {
                this.selectedDurationId = '';
            }
        },

        async init() {
            if (this.hasSubscriptionPlans && this.subscriptionPlans.length) {
                this.selectedSubscriptionPlanId = Number(this.subscriptionPlans[0].id);
                this.applySubscriptionPlan(this.subscriptionPlans[0]);
                this.$watch('selectedSubscriptionPlanId', (id) => {
                    const plan = this.subscriptionPlans.find(p => Number(p.id) === Number(id));
                    if (plan) this.applySubscriptionPlan(plan);
                });
            } else {
                try {
                    const durRes = await fetch('{{ route('api.plan.durations', $plan->id) }}');
                    const durData = await durRes.json();
                    if (durData.length > 0) {
                        this.durations = durData.map((d) => this.mapDurationRow(d));
                        const defaultDur = this.durations.find(d => d.is_default) || this.durations[0];
                        if (defaultDur) {
                            this.selectedDurationId = defaultDur.id;
                            this.onDurationChange(defaultDur);
                        }
                    }
                } catch (e) {
                    console.warn('Could not fetch plan durations:', e);
                }

                try {
                    const calRes = await fetch('{{ route('api.plan.calories', $plan->id) }}');
                    const calData = await calRes.json();
                    if (calData.length > 0) {
                        this.calories = calData.map(c => ({
                            range: (c.min_amount || 0) + '-' + (c.max_amount || 0),
                            label: (c.min_amount || 0) + '-' + (c.max_amount || 0) + ' {{ __('kcal') }}',
                            id: c.id || 0,
                            is_default: !!c.is_default,
                            macros: c.macros || null,
                        }));
                        const defaultCal = this.calories.find(c => c.is_default) || this.calories[0];
                        if (defaultCal) {
                            this.selectedCalories = defaultCal.range;
                            this.updateNutrition(defaultCal);
                        }
                    }
                } catch (e) {
                    console.warn('Could not fetch plan calories:', e);
                }
            }

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
            if (!dur) return;
            this.selectedDurationDays = parseInt(dur.days, 10) || 0;
            const price = dur.price || 0;
            const offer = dur.offer_price || 0;
            if (offer > 0 && offer < price) {
                this.displayPrice = offer;
                this.originalPrice = price;
            } else {
                this.displayPrice = price;
                this.originalPrice = 0;
            }
            const incl = this.displayPrice;
            this.vatAmount = Math.round((incl - (incl / (1 + this.vatRate))) * 100) / 100;
        },

        subscribeNow() {
            let url = '{{ route('checkout.index') }}?plan_id={{ $plan->id }}' +
                '&calories=' + encodeURIComponent(this.selectedCalories);
            if (this.selectedDurationId) {
                url += '&duration_id=' + encodeURIComponent(this.selectedDurationId);
                const dur = this.durations.find(d => String(d.id) === String(this.selectedDurationId));
                if (dur && dur.days != null) {
                    url += '&duration_days=' + encodeURIComponent(String(dur.days));
                }
            }
            if (this.hasSubscriptionPlans && this.selectedSubscriptionPlanId) {
                url += '&subscription_plan_id=' + encodeURIComponent(this.selectedSubscriptionPlanId);
            } else {
                url += '&meal_type=' + encodeURIComponent(this.selectedMeal);
            }
            url += '&plan_total=' + encodeURIComponent(String(this.displayPrice));
            window.location.href = url;
        },
    };
}
</script>
@endpush
