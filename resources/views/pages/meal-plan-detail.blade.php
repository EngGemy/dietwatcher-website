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

// Nutritional info — build from default subscription plan macros, calorie macros, or API program data
$buildNutritionFromMacros = static function (?array $macros): ?array {
    if (! is_array($macros)) {
        return null;
    }

    $read = static function (array $macros, string ...$keys): array {
        foreach ($keys as $key) {
            $node = $macros[$key] ?? null;
            if (is_array($node)) {
                return [
                    'min' => (float) ($node['min'] ?? 0),
                    'max' => (float) ($node['max'] ?? 0),
                ];
            }
        }

        return ['min' => 0.0, 'max' => 0.0];
    };

    $proteins = $read($macros, 'proteins', 'protein');
    $carbs = $read($macros, 'carbs', 'carb', 'carbohydrates');
    $fats = $read($macros, 'fats', 'fat');

    $fmt = static function (float $min, float $max): string {
        if ($min <= 0 && $max <= 0) {
            return '—';
        }
        if ($min > 0 && $max > 0 && abs($min - $max) > 0.01) {
            $sa = fmod($min, 1.0) === 0.0 ? (string) (int) $min : rtrim(rtrim(number_format($min, 1, '.', ''), '0'), '.');
            $sb = fmod($max, 1.0) === 0.0 ? (string) (int) $max : rtrim(rtrim(number_format($max, 1, '.', ''), '0'), '.');

            return $sa.'-'.$sb.'g';
        }
        $v = max($min, $max);
        $sv = fmod($v, 1.0) === 0.0 ? (string) (int) $v : rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');

        return $sv.'g';
    };

    $proteinMid = ($proteins['min'] + $proteins['max']) / 2;
    $carbsMid = ($carbs['min'] + $carbs['max']) / 2;
    $fatMid = ($fats['min'] + $fats['max']) / 2;
    $total = $proteinMid + $carbsMid + $fatMid;

    if ($total <= 0) {
        return null;
    }

    return [
        'carbs' => [
            'amount' => $fmt($carbs['min'], $carbs['max']),
            'percent' => (int) round(($carbsMid / $total) * 100),
            'color' => 'bg-green',
        ],
        'protein' => [
            'amount' => $fmt($proteins['min'], $proteins['max']),
            'percent' => (int) round(($proteinMid / $total) * 100),
            'color' => 'bg-yellow',
        ],
        'fat' => [
            'amount' => $fmt($fats['min'], $fats['max']),
            'percent' => (int) round(($fatMid / $total) * 100),
            'color' => 'bg-red',
        ],
    ];
};

$firstPlanMacros = $hasSubscriptionPlans ? ($subscriptionPlans[0]['macros'] ?? null) : null;
$nutritionFromPlan = $buildNutritionFromMacros(is_array($firstPlanMacros) ? $firstPlanMacros : null);

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
if ($nutritionFromPlan) {
    $nutrition = $nutritionFromPlan;
} elseif (is_array($firstMacros)) {
    $nutrition = $buildNutritionFromMacros($firstMacros);
}

if (! isset($nutrition) || ! is_array($nutrition)) {
    $legacyProtein = 0.0;
    $legacyCarbs = 0.0;
    $legacyFat = 0.0;

    if (is_array($firstMacros)) {
        $legacyProtein = is_numeric($firstMacros['protein'] ?? null) ? (float) $firstMacros['protein'] : 0.0;
        $legacyCarbs = is_numeric($firstMacros['carbs'] ?? null) ? (float) $firstMacros['carbs'] : 0.0;
        $legacyFat = is_numeric($firstMacros['fats'] ?? $firstMacros['fat'] ?? null)
            ? (float) ($firstMacros['fats'] ?? $firstMacros['fat'])
            : 0.0;
    }

    $totalMacros = $legacyProtein + $legacyCarbs + $legacyFat;

    if ($totalMacros > 0) {
        $nutrition = [
            'carbs' => [
                'amount' => $legacyCarbs.'g',
                'percent' => (int) round(($legacyCarbs / $totalMacros) * 100),
                'color' => 'bg-green',
            ],
            'protein' => [
                'amount' => $legacyProtein.'g',
                'percent' => (int) round(($legacyProtein / $totalMacros) * 100),
                'color' => 'bg-yellow',
            ],
            'fat' => [
                'amount' => $legacyFat.'g',
                'percent' => (int) round(($legacyFat / $totalMacros) * 100),
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
}

// If calorie-row macros are missing, fallback to profile-level macros.
if (($nutrition['carbs']['amount'] ?? '—') === '—' && ($nutrition['protein']['amount'] ?? '—') === '—' && ($nutrition['fat']['amount'] ?? '—') === '—') {
    $profileProtein = (float) ($plan->protein ?? 0);
    $profileCarbs = (float) ($plan->carbs ?? 0);
    $profileFat = (float) ($plan->fats ?? $plan->fat ?? 0);
    $profileTotal = $profileProtein + $profileCarbs + $profileFat;

    if ($profileTotal > 0) {
        $nutrition = [
            'carbs' => [
                'amount' => rtrim(rtrim(number_format($profileCarbs, 2, '.', ''), '0'), '.').'g',
                'percent' => (int) round(($profileCarbs / $profileTotal) * 100),
                'color' => 'bg-green',
            ],
            'protein' => [
                'amount' => rtrim(rtrim(number_format($profileProtein, 2, '.', ''), '0'), '.').'g',
                'percent' => (int) round(($profileProtein / $profileTotal) * 100),
                'color' => 'bg-yellow',
            ],
            'fat' => [
                'amount' => rtrim(rtrim(number_format($profileFat, 2, '.', ''), '0'), '.').'g',
                'percent' => (int) round(($profileFat / $profileTotal) * 100),
                'color' => 'bg-red',
            ],
        ];
    }
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

// Calculate earliest allowed start date (48 hours from now)
$startAt = now()->addHours(48);
$startDate = $startAt->format('Y-m-d');
$startDateDisplay = $startAt->locale($locale)->translatedFormat('l d F');

$planDescPlain = trim(strip_tags($planDesc));
$planDescExcerpt = $planDescPlain !== '' ? Str::limit($planDescPlain, 180) : '';

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
<div class="meal-detail plan-detail bg-gray-200 pt-5 pb-28 md:pt-10" x-data="planDetail()" x-init="init()">
    <section class="container">
        <ol class="breadcrumb mb-6 md:mb-8">
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

        <div class="meal-detail__hero mb-10 md:mb-16">
            {{-- Hero media --}}
            <div class="meal-detail__media md-reveal md-reveal--image">
                @if($hasSubscriptionPlans)
                    <img src="{{ $planImageUrl }}"
                         x-bind:src="heroImage"
                         class="meal-detail__media-img"
                         alt="{{ $planName }}"
                         referrerpolicy="no-referrer"
                         decoding="async"
                         loading="eager"
                         x-on:error="onPlanHeroImageError($event)">
                @else
                    <div data-hs-carousel='{ "loadingClasses": "opacity-0", "isInfinite": true }' class="relative size-full">
                        <div class="hs-carousel relative size-full">
                            <div class="hs-carousel-body flex size-full flex-nowrap overflow-hidden opacity-0 transition-transform duration-700">
                                @foreach($images as $index => $image)
                                    <div class="hs-carousel-slide h-full min-w-full">
                                        <img src="{{ $image }}"
                                             class="size-full object-cover"
                                             alt="{{ $planName }} - {{ $index + 1 }}"
                                             onerror="this.src='{{ asset('assets/images/meal-' . (($index % 3) + 1) . '.png') }}'">
                                    </div>
                                @endforeach
                            </div>
                            @if(count($images) > 1)
                                <div class="hs-carousel-pagination absolute inset-x-0 bottom-3 z-10 flex justify-center gap-2 px-3">
                                    @foreach($images as $index => $image)
                                        <div class="hs-carousel-pagination-item size-2 shrink-0 cursor-pointer rounded-full bg-white/50 transition-all hs-carousel-active:w-6 hs-carousel-active:bg-white"></div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Config panel --}}
            <div class="meal-detail__panel">
                <h1 class="meal-detail__title md-reveal" style="--md-i:0">{{ $planName }}</h1>

                @if($planDescExcerpt !== '')
                    <p class="meal-detail__excerpt md-reveal" style="--md-i:1">{{ $planDescExcerpt }}</p>
                    <a href="#plan-description" class="plan-detail__read-more md-reveal md:hidden" style="--md-i:1">{{ __('Read More') }}</a>
                @else
                    <p class="meal-detail__excerpt md-reveal" style="--md-i:1">{{ __('Nutritionist-designed meal plans for safe, sustainable weight loss.') }}</p>
                @endif

                <div class="meal-detail__price-row md-reveal" style="--md-i:2">
                    <p class="meal-detail__price"><x-sar /> <span x-text="displayPrice.toLocaleString()"></span></p>
                    <template x-if="originalPrice > 0 && originalPrice !== displayPrice">
                        <span class="meal-detail__price-old"><x-sar /> <span x-text="originalPrice.toLocaleString()"></span></span>
                    </template>
                    <span class="meal-detail__per-serving" x-show="selectedDurationDays > 0" x-cloak x-text="avgPerDayAmount() + ' · {{ __('per day') }}'"></span>
                </div>

                <div class="plan-detail__start-chip md-reveal" style="--md-i:3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span>
                        {{ __('Start your plan as soon as') }}
                        <time datetime="{{ $startDate }}">{{ $startDateDisplay }}</time>
                    </span>
                </div>

                {{-- Plan variant --}}
                <div class="plan-detail__block md-reveal" style="--md-i:4">
                    @if($hasSubscriptionPlans)
                        <p class="plan-detail__block-head">{{ __('Choose your plan') }}</p>
                        <div class="choice-group__row">
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
                                    <label for="subplan-{{ $spId }}" class="choice-group__label max-w-full text-center">
                                        <span class="choice-group__icon"></span>
                                        <span>{{ $sp['name'] ?? '' }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="plan-detail__includes">
                            <p class="plan-detail__includes-title">{{ __("What's included") }}</p>
                            <ul>
                                <template x-for="line in activeMenusDisplay" :key="line">
                                    <li x-text="line"></li>
                                </template>
                            </ul>
                        </div>
                    @else
                        <p class="plan-detail__block-head">{{ __('Choose your meal type') }}</p>
                        <div class="choice-group__row">
                            @foreach($mealTypes as $type)
                                <div class="choice-group__item">
                                    <input type="radio"
                                           name="meal-type"
                                           id="meal-{{ $type['id'] }}"
                                           class="choice-group__input"
                                           value="{{ $type['id'] }}"
                                           x-model="selectedMeal"
                                           {{ $loop->first ? 'checked' : '' }}>
                                    <label for="meal-{{ $type['id'] }}" class="choice-group__label">
                                        <span class="choice-group__icon"></span>
                                        {{ $type['name'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="plan-detail__includes">
                            <template x-for="(items, type) in {{ json_encode($mealIncludes) }}" :key="type">
                                <div x-show="selectedMeal === type" x-transition>
                                    <p class="plan-detail__includes-title" x-text="selectedMeal.charAt(0).toUpperCase() + selectedMeal.slice(1) + ' {{ __('Includes') }}'"></p>
                                    <ul>
                                        <template x-for="item in items" :key="item">
                                            <li x-text="item"></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    @endif
                </div>

                {{-- Calories --}}
                <div class="plan-detail__block md-reveal" style="--md-i:5">
                    <p class="plan-detail__block-head">{{ __('Choose calories') }}</p>
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

                {{-- Nutrition --}}
                <div class="plan-detail__block md-reveal" style="--md-i:6">
                    <div class="plan-detail__nutrition-head">
                        <p class="plan-detail__block-head !mb-0">{{ __('Nutritional info') }}</p>
                        <span>{{ __('Min - Max') }}</span>
                    </div>
                    <div class="meal-detail__nutrition" aria-label="{{ __('Nutritional info') }}">
                        <div class="meal-detail__stat meal-detail__stat--carbs">
                            <span class="meal-detail__stat-value" x-text="nutritionDisplayShort(currentNutrition.carbs)"></span>
                            <span class="meal-detail__stat-label">{{ __('Carbs') }}</span>
                            <span class="plan-detail__stat-pct" x-text="currentNutrition.carbsPercent + '%'"></span>
                            <div class="plan-detail__macro-bar plan-detail__macro-bar--carbs" role="progressbar" :aria-valuenow="currentNutrition.carbsPercent">
                                <span :style="'width:' + currentNutrition.carbsPercent + '%'"></span>
                            </div>
                        </div>
                        <div class="meal-detail__stat meal-detail__stat--protein">
                            <span class="meal-detail__stat-value" x-text="nutritionDisplayShort(currentNutrition.protein)"></span>
                            <span class="meal-detail__stat-label">{{ __('Protein') }}</span>
                            <span class="plan-detail__stat-pct" x-text="currentNutrition.proteinPercent + '%'"></span>
                            <div class="plan-detail__macro-bar plan-detail__macro-bar--protein" role="progressbar" :aria-valuenow="currentNutrition.proteinPercent">
                                <span :style="'width:' + currentNutrition.proteinPercent + '%'"></span>
                            </div>
                        </div>
                        <div class="meal-detail__stat meal-detail__stat--fat">
                            <span class="meal-detail__stat-value" x-text="nutritionDisplayShort(currentNutrition.fat)"></span>
                            <span class="meal-detail__stat-label">{{ __('Fat') }}</span>
                            <span class="plan-detail__stat-pct" x-text="currentNutrition.fatPercent + '%'"></span>
                            <div class="plan-detail__macro-bar plan-detail__macro-bar--fat" role="progressbar" :aria-valuenow="currentNutrition.fatPercent">
                                <span :style="'width:' + currentNutrition.fatPercent + '%'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Duration --}}
                <div class="plan-detail__block md-reveal" style="--md-i:7" x-show="durations.length > 0" x-cloak>
                    <p class="plan-detail__block-head">{{ __('Choose Duration') }}</p>
                    <x-duration-carousel>
                        <template x-for="(dur, index) in durations" :key="dur.id">
                            <div class="duration-pills__item">
                                <input type="radio"
                                       name="duration"
                                       :id="'dur-' + index"
                                       class="duration-pills__input"
                                       :value="dur.id"
                                       x-model="selectedDurationId"
                                       @change="onDurationChange(dur); scrollDurationToSelected()">
                                <label :for="'dur-' + index" class="duration-pills__face">
                                    <span class="duration-pills__offer-badge" x-show="durationRowHasOffer(dur)" x-cloak>{{ __('Offer') }}</span>
                                    <span class="duration-pills__title" x-text="dur.label || (dur.days + ' {{ __('Days') }}')"></span>
                                    <span class="duration-pills__strike" x-show="durationRowHasOffer(dur)" x-text="'\u20C1 ' + durationRowListStr(dur)"></span>
                                    <span class="duration-pills__total-line" x-text="'\u20C1 ' + durationRowEffectiveStr(dur)"></span>
                                    <span class="duration-pills__avg" x-show="durationRowAvgLine(dur)" x-text="durationRowAvgLine(dur)"></span>
                                </label>
                            </div>
                        </template>
                    </x-duration-carousel>
                </div>

                {{-- Payment summary (desktop) --}}
                <div class="meal-detail__purchase hidden md:block md-reveal" style="--md-i:8">
                    <p class="plan-detail__block-head mb-3">{{ __('Payment Summary') }}</p>
                    <div class="plan-detail__summary-row">
                        <p>{{ __('Plan Price') }} <span class="text-xs">({{ __('Incl. VAT') }})</span></p>
                        <p><x-sar /> <span x-text="displayPrice.toLocaleString()"></span></p>
                    </div>
                    <div class="plan-detail__summary-row text-sm" x-show="selectedDurationDays > 0" x-cloak>
                        <p>{{ __('Avg. per day') }} <span class="text-xs text-gray-400">({{ __('Incl. VAT') }})</span></p>
                        <p class="font-semibold text-gray-800" x-text="avgPerDayAmount()"></p>
                    </div>
                    <template x-if="originalPrice > 0 && originalPrice !== displayPrice">
                        <div class="plan-detail__summary-row text-sm">
                            <p class="text-gray-400 line-through">{{ __('Original Price') }}</p>
                            <p class="text-gray-400 line-through"><x-sar /> <span x-text="originalPrice.toLocaleString()"></span></p>
                        </div>
                    </template>
                    <div class="plan-detail__summary-row text-sm text-gray-400">
                        <p>{{ __('VAT included') }} ({{ (int)(\App\Models\Settings\Setting::getValue('vat_rate', 15)) }}%)</p>
                        <p><x-sar /> <span x-text="vatAmount.toFixed(2)"></span></p>
                    </div>
                    <div class="plan-detail__summary-divider"></div>
                    <div class="plan-detail__summary-row plan-detail__summary-row--total">
                        <p>{{ __('Total') }}</p>
                        <p class="plan-detail__price-total"><x-sar /> <span x-text="displayPrice.toLocaleString()"></span></p>
                    </div>
                    <button type="button" class="btn btn--primary btn--lg mt-4 w-full" @click="subscribeNow()">
                        {{ __('Subscribe Now') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Description & ingredients --}}
        <div class="meal-detail__sections md-reveal" style="--md-i:9">
            <div class="meal-detail__section" id="plan-description">
                <h2 class="meal-detail__section-head">{{ __('Description') }}</h2>
                <div class="meal-detail__section-body">
                    @if($planDescPlain !== '')
                        {!! nl2br(e($planDescPlain)) !!}
                    @else
                        <p>{{ __('A calorie-controlled meal plan designed by nutritionists to support safe, sustainable weight loss. Enjoy balanced, portioned meals delivered daily to help you stay consistent and reach your goals with ease.') }}</p>
                    @endif
                </div>
            </div>

            <div class="meal-detail__section">
                <h2 class="meal-detail__section-head">{{ __('Ingredients') }}</h2>
                <div class="meal-detail__section-body">
                    <p>{{ $plan->ingredients ?? __('Fresh vegetables, whole grains, lean proteins, and natural seasonings.') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Mobile sticky bar --}}
    <div class="plan-detail__mobile-bar md:hidden">
        <div class="plan-detail__mobile-meta">
            <span class="plan-detail__mobile-price"><x-sar /> <span x-text="displayPrice.toLocaleString()"></span></span>
            <span class="plan-detail__mobile-per-day" x-show="selectedDurationDays > 0" x-cloak x-text="avgPerDayAmount()"></span>
        </div>
        <button type="button" class="btn btn--primary btn--md" @click="subscribeNow()">
            {{ __('Subscribe Now') }}
        </button>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/styles/meal-detail.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/styles/meal-plan-detail.css') }}" />
<style>
    .breadcrumb {
        @apply flex flex-wrap items-center gap-1 text-sm text-gray-600;
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
        durationScrollAtStart: true,
        durationScrollAtEnd: false,
        _durationScrollRaf: null,
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
            return '\u20C1 ' + ns + ' · {{ __('per day') }}';
        },

        avgPerDayAmount() {
            const days = Number(this.selectedDurationDays) || 0;
            if (days <= 0) return '—';
            const v = Number(this.displayPrice) / days;
            const avg = Math.round(v * 100) / 100;
            return '\u20C1 ' + (Number.isInteger(avg) ? String(avg) : avg.toFixed(2));
        },

        durationViewportScrollLeft(vp) {
            const max = Math.max(0, vp.scrollWidth - vp.clientWidth);
            if (max <= 1) {
                return 0;
            }
            const isRtl = getComputedStyle(vp).direction === 'rtl'
                || document.documentElement.dir === 'rtl';
            if (! isRtl) {
                return vp.scrollLeft;
            }
            if (vp.scrollLeft < 0) {
                return Math.abs(vp.scrollLeft);
            }
            if (vp.scrollLeft > 0) {
                return max - vp.scrollLeft;
            }

            return 0;
        },

        onDurationViewportScroll() {
            if (this._durationScrollRaf) {
                return;
            }
            this._durationScrollRaf = requestAnimationFrame(() => {
                this._durationScrollRaf = null;
                this.refreshDurationScrollState();
            });
        },

        refreshDurationScrollState() {
            const vp = this.$refs.durationViewport;
            if (! vp) {
                this.durationScrollAtStart = true;
                this.durationScrollAtEnd = true;

                return;
            }
            const max = Math.max(0, vp.scrollWidth - vp.clientWidth);
            if (max <= 1) {
                this.durationScrollAtStart = true;
                this.durationScrollAtEnd = true;

                return;
            }
            const pos = this.durationViewportScrollLeft(vp);
            this.durationScrollAtStart = pos <= 8;
            this.durationScrollAtEnd = pos >= max - 8;
        },

        scrollDurationBy(dir) {
            const vp = this.$refs.durationViewport;
            if (! vp) {
                return;
            }
            const step = Math.max(vp.clientWidth * 0.78, 200);
            const isRtl = document.documentElement.dir === 'rtl';
            const delta = (isRtl ? -dir : dir) * step;
            vp.scrollBy({ left: delta, behavior: 'smooth' });
        },

        scrollDurationToSelected() {
            this.$nextTick(() => {
                const vp = this.$refs.durationViewport;
                if (! vp) {
                    return;
                }
                const checked = vp.querySelector('.duration-pills__input:checked');
                const slide = checked?.closest('.duration-pills__item');
                if (slide) {
                    slide.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
                setTimeout(() => this.refreshDurationScrollState(), 360);
            });
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
                macros: c.macros || plan.macros || null,
            }));

            const defaultCal = this.calories.find(c => c.is_default) || this.calories[0];
            if (defaultCal) {
                this.selectedCalories = defaultCal.range;
            }

            const defaultDur = this.durations.find(d => d.is_default) || this.durations[0];
            if (defaultDur) {
                this.selectedDurationId = defaultDur.id;
                this.onDurationChange(defaultDur);
            } else {
                this.selectedDurationId = '';
            }

            this.applyNutritionForCurrentSelection();

            this.$nextTick(() => {
                this.refreshDurationScrollState();
                this.scrollDurationToSelected();
            });
        },

        planMacrosHaveValues(macros) {
            if (!macros || typeof macros !== 'object') {
                return false;
            }
            const hasNode = (...keys) => {
                for (const key of keys) {
                    const node = macros[key];
                    if (!node || typeof node !== 'object') {
                        continue;
                    }
                    const min = Number(node.min) || 0;
                    const max = Number(node.max) || 0;
                    if (min > 0 || max > 0) {
                        return true;
                    }
                }

                return false;
            };

            return hasNode('proteins', 'protein') || hasNode('carbs', 'carb', 'carbohydrates') || hasNode('fats', 'fat');
        },

        setNutritionFromPlanMacros(macros) {
            const read = (...keys) => {
                for (const key of keys) {
                    const node = macros?.[key];
                    if (node && typeof node === 'object') {
                        return {
                            min: Number(node.min) || 0,
                            max: Number(node.max) || 0,
                        };
                    }
                }

                return { min: 0, max: 0 };
            };

            const proteins = read('proteins', 'protein');
            const carbs = read('carbs', 'carb', 'carbohydrates');
            const fats = read('fats', 'fat');
            this.setNutritionRanges(proteins.min, proteins.max, carbs.min, carbs.max, fats.min, fats.max);
        },

        applyNutritionForCurrentSelection() {
            const plan = this.hasSubscriptionPlans
                ? this.subscriptionPlans.find((p) => Number(p.id) === Number(this.selectedSubscriptionPlanId))
                : null;

            if (plan?.macros && this.planMacrosHaveValues(plan.macros)) {
                this.setNutritionFromPlanMacros(plan.macros);
                return;
            }

            const cal = this.calories.find((c) => c.range === this.selectedCalories)
                || this.calories.find((c) => c.is_default)
                || this.calories[0];

            if (cal?.macros && this.planMacrosHaveValues(cal.macros)) {
                this.setNutritionFromPlanMacros(cal.macros);
                return;
            }

            if (cal) {
                this.updateNutrition(cal);
            }

            this.applyEstimatedNutritionFromRange(this.selectedCalories);
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

            this.$watch('selectedCalories', () => {
                this.applyNutritionForCurrentSelection();
            });

            if (!this.hasSubscriptionPlans) {
                await this.hydrateNutritionFromProgramMeals();
                if (!this.hasNumericNutrition()) {
                    this.applyEstimatedNutritionFromRange(this.selectedCalories);
                }
                if (!this.hasRangeDisplay()) {
                    this.applyEstimatedNutritionFromRange(this.selectedCalories);
                }
            }

            this.$watch('durations', () => {
                this.$nextTick(() => {
                    this.refreshDurationScrollState();
                    this.scrollDurationToSelected();
                });
            });
            this.$watch('selectedDurationId', () => {
                this.scrollDurationToSelected();
            });
            if (typeof window !== 'undefined') {
                this._durationResizeHandler = () => this.refreshDurationScrollState();
                window.addEventListener('resize', this._durationResizeHandler, { passive: true });
            }
            this.$nextTick(() => {
                this.refreshDurationScrollState();
                this.scrollDurationToSelected();
            });
            setTimeout(() => this.refreshDurationScrollState(), 150);
            setTimeout(() => this.refreshDurationScrollState(), 600);
            requestAnimationFrame(() => {
                this.$el.classList.add('is-ready');
            });
        },

        updateNutrition(cal) {
            if (cal && cal.macros) {
                const m = cal.macros;
                const parseNum = (v) => {
                    if (v == null) return 0;
                    if (typeof v === 'number') return Number.isFinite(v) ? v : 0;
                    if (typeof v === 'string') {
                        const nums = v.match(/[\d.]+/g) || [];
                        if (nums.length >= 2) {
                            const a = parseFloat(nums[0]) || 0;
                            const b = parseFloat(nums[1]) || 0;
                            return a > 0 && b > 0 ? (a + b) / 2 : Math.max(a, b);
                        }
                        return nums.length ? (parseFloat(nums[0]) || 0) : 0;
                    }
                    if (typeof v === 'object') {
                        const min = parseNum(v.min ?? v.min_amount ?? v.low ?? v.from ?? null);
                        const max = parseNum(v.max ?? v.max_amount ?? v.high ?? v.to ?? null);
                        if (min > 0 && max > 0) {
                            return (min + max) / 2;
                        }
                        return parseNum(v.amount ?? v.value ?? v.total ?? v.g ?? null);
                    }
                    return 0;
                };

                const readRange = (src, valueKeys, minKeys, maxKeys) => {
                    const readFirst = (keys) => {
                        for (const k of keys) {
                            if (src && Object.prototype.hasOwnProperty.call(src, k)) {
                                const val = parseNum(src[k]);
                                if (val > 0) return val;
                            }
                        }
                        return 0;
                    };

                    const direct = readFirst(valueKeys);
                    const min = readFirst(minKeys);
                    const max = readFirst(maxKeys);

                    return { direct, min, max };
                };

                const nutrientRange = (valueKeys, minKeys, maxKeys) => {
                    const root = readRange(m, valueKeys, minKeys, maxKeys);
                    let min = root.min;
                    let max = root.max;
                    let direct = root.direct;

                    // Some API payloads send macros per meal type:
                    // macros.breakfast / macros.lunch / macros.dinner / macros.snack
                    // In that case we aggregate min/max across meal types.
                    if (min <= 0 && max <= 0 && direct <= 0) {
                        const mealTypeKeys = ['breakfast', 'lunch', 'dinner', 'snack'];
                        let aggMin = 0;
                        let aggMax = 0;
                        let aggDirect = 0;
                        let foundAny = false;

                        mealTypeKeys.forEach((mt) => {
                            const node = m?.[mt];
                            if (!node || typeof node !== 'object') return;
                            const r = readRange(node, valueKeys, minKeys, maxKeys);
                            if (r.min > 0 && r.max > 0) {
                                aggMin += r.min;
                                aggMax += r.max;
                                foundAny = true;
                                return;
                            }
                            if (r.direct > 0) {
                                aggDirect += r.direct;
                                foundAny = true;
                            }
                        });

                        if (foundAny) {
                            min = aggMin;
                            max = aggMax;
                            direct = aggDirect;
                        }
                    }

                    if (min > 0 && max > 0) {
                        const displayMin = Number.isInteger(min) ? String(min) : min.toFixed(1);
                        const displayMax = Number.isInteger(max) ? String(max) : max.toFixed(1);
                        return {
                            amount: displayMin + '-' + displayMax + 'g',
                            numeric: (min + max) / 2,
                        };
                    }

                    const numeric = direct;
                    if (numeric <= 0) {
                        return {
                            amount: '—',
                            numeric: 0,
                        };
                    }
                    const display = Number.isInteger(numeric) ? String(numeric) : numeric.toFixed(1);
                    return {
                        amount: display + 'g',
                        numeric,
                    };
                };

                const proteinRange = nutrientRange(
                    ['protein', 'proteins', 'protein_g'],
                    ['protein_min', 'proteins_min', 'protein_g_min'],
                    ['protein_max', 'proteins_max', 'protein_g_max']
                );
                const carbsRange = nutrientRange(
                    ['carbs', 'carb', 'carbohydrates', 'carbs_g'],
                    ['carbs_min', 'carb_min', 'carbohydrates_min', 'carbs_g_min'],
                    ['carbs_max', 'carb_max', 'carbohydrates_max', 'carbs_g_max']
                );
                const fatRange = nutrientRange(
                    ['fats', 'fat', 'fat_g', 'fats_g'],
                    ['fats_min', 'fat_min', 'fat_g_min', 'fats_g_min'],
                    ['fats_max', 'fat_max', 'fat_g_max', 'fats_g_max']
                );

                const total = proteinRange.numeric + carbsRange.numeric + fatRange.numeric;
                this.currentNutrition = {
                    carbs: carbsRange.amount,
                    carbsPercent: total > 0 ? Math.round(carbsRange.numeric / total * 100) : 33,
                    protein: proteinRange.amount,
                    proteinPercent: total > 0 ? Math.round(proteinRange.numeric / total * 100) : 33,
                    fat: fatRange.amount,
                    fatPercent: total > 0 ? Math.round(fatRange.numeric / total * 100) : 33,
                };
            }
        },

        hasNumericNutrition() {
            const parse = (s) => {
                const match = String(s ?? '').match(/[\d.]+/);
                return match ? parseFloat(match[0]) : 0;
            };
            const p = parse(this.currentNutrition.protein);
            const c = parse(this.currentNutrition.carbs);
            const f = parse(this.currentNutrition.fat);
            return (p + c + f) > 0;
        },

        hasRangeDisplay() {
            const check = (v) => String(v || '').includes('-');
            return check(this.currentNutrition.carbs) || check(this.currentNutrition.protein) || check(this.currentNutrition.fat);
        },

        hasRangeValue(value) {
            return String(value || '').includes('-');
        },

        nutritionRangeParts(value) {
            const text = String(value || '').trim();
            if (text === '' || text === '—') {
                return { min: '—', max: '—' };
            }
            const unit = text.includes('g') ? 'g' : '';
            const nums = text.match(/[\d.]+/g) || [];
            if (nums.length >= 2) {
                return { min: nums[0] + unit, max: nums[1] + unit };
            }
            if (nums.length === 1) {
                return { min: nums[0] + unit, max: nums[0] + unit };
            }
            return { min: text, max: text };
        },

        nutritionRangeMin(value) {
            return this.nutritionRangeParts(value).min;
        },

        nutritionRangeMax(value) {
            return this.nutritionRangeParts(value).max;
        },

        nutritionDisplayShort(value) {
            const text = String(value || '').trim();
            if (text === '' || text === '—') {
                return '—';
            }
            const parts = this.nutritionRangeParts(value);
            if (parts.min !== '—' && parts.max !== '—' && parts.min !== parts.max) {
                return parts.min + '–' + parts.max;
            }

            return text;
        },

        parseCaloriesRange(range) {
            const str = String(range || '').trim();
            if (!str) return 0;
            const nums = str.match(/[\d.]+/g) || [];
            if (nums.length === 0) return 0;
            if (nums.length === 1) return parseFloat(nums[0]) || 0;
            const min = parseFloat(nums[0]) || 0;
            const max = parseFloat(nums[1]) || 0;
            if (min > 0 && max > 0) return (min + max) / 2;
            return Math.max(min, max);
        },

        parseCaloriesBounds(range) {
            const str = String(range || '').trim();
            if (!str) return { min: 0, max: 0 };
            const nums = str.match(/[\d.]+/g) || [];
            if (nums.length === 0) return { min: 0, max: 0 };
            if (nums.length === 1) {
                const v = parseFloat(nums[0]) || 0;
                return { min: v, max: v };
            }
            const min = parseFloat(nums[0]) || 0;
            const max = parseFloat(nums[1]) || 0;
            if (min > 0 && max > 0) return { min, max };
            const v = Math.max(min, max);
            return { min: v, max: v };
        },

        setNutritionRanges(proteinMin, proteinMax, carbsMin, carbsMax, fatMin, fatMax) {
            const fmt = (min, max) => {
                const a = Number(min) || 0;
                const b = Number(max) || 0;
                if (a <= 0 && b <= 0) return '—';
                if (a > 0 && b > 0 && Math.abs(a - b) > 0.01) {
                    const sa = Number.isInteger(a) ? String(a) : a.toFixed(1);
                    const sb = Number.isInteger(b) ? String(b) : b.toFixed(1);
                    return sa + '-' + sb + 'g';
                }
                const v = Math.max(a, b);
                const sv = Number.isInteger(v) ? String(v) : v.toFixed(1);
                return sv + 'g';
            };

            const proteinMid = ((Number(proteinMin) || 0) + (Number(proteinMax) || 0)) / 2;
            const carbsMid = ((Number(carbsMin) || 0) + (Number(carbsMax) || 0)) / 2;
            const fatMid = ((Number(fatMin) || 0) + (Number(fatMax) || 0)) / 2;
            const total = proteinMid + carbsMid + fatMid;

            this.currentNutrition = {
                carbs: fmt(carbsMin, carbsMax),
                carbsPercent: total > 0 ? Math.round((carbsMid / total) * 100) : 33,
                protein: fmt(proteinMin, proteinMax),
                proteinPercent: total > 0 ? Math.round((proteinMid / total) * 100) : 33,
                fat: fmt(fatMin, fatMax),
                fatPercent: total > 0 ? Math.round((fatMid / total) * 100) : 33,
            };
        },

        applyRawNutrition(protein, carbs, fat) {
            const p = Number(protein) || 0;
            const c = Number(carbs) || 0;
            const f = Number(fat) || 0;
            const total = p + c + f;
            this.currentNutrition = {
                carbs: c + 'g',
                carbsPercent: total > 0 ? Math.round((c / total) * 100) : 33,
                protein: p + 'g',
                proteinPercent: total > 0 ? Math.round((p / total) * 100) : 33,
                fat: f + 'g',
                fatPercent: total > 0 ? Math.round((f / total) * 100) : 33,
            };
        },

        applyEstimatedNutritionFromRange(range) {
            const bounds = this.parseCaloriesBounds(range);
            if (bounds.min <= 0 && bounds.max <= 0) {
                return;
            }

            // Fallback when API doesn't provide explicit min/max macros.
            const proteinMin = Math.round(((bounds.min * 0.30) / 4) * 10) / 10;
            const proteinMax = Math.round(((bounds.max * 0.30) / 4) * 10) / 10;
            const carbsMin = Math.round(((bounds.min * 0.40) / 4) * 10) / 10;
            const carbsMax = Math.round(((bounds.max * 0.40) / 4) * 10) / 10;
            const fatMin = Math.round(((bounds.min * 0.30) / 9) * 10) / 10;
            const fatMax = Math.round(((bounds.max * 0.30) / 9) * 10) / 10;

            this.setNutritionRanges(proteinMin, proteinMax, carbsMin, carbsMax, fatMin, fatMax);
        },

        async hydrateNutritionFromProgramMeals() {
            // Final fallback: derive macros from program meals endpoint when calories
            // endpoint does not include macros payload.
            if (this.hasNumericNutrition()) {
                return;
            }
            try {
                const res = await fetch('{{ route('api.plan.meals', $plan->id) }}');
                const rows = await res.json();
                const list = Array.isArray(rows) ? rows : [];
                const n = (v) => {
                    if (v == null) return 0;
                    if (typeof v === 'number') return Number.isFinite(v) ? v : 0;
                    if (typeof v === 'string') {
                        const match = v.match(/[\d.]+/);
                        return match ? parseFloat(match[0]) : 0;
                    }
                    if (typeof v === 'object') {
                        return n(v.amount ?? v.value ?? v.total ?? null);
                    }
                    return 0;
                };

                const withMacros = list.find((row) => {
                    const protein = n(row?.protein ?? row?.proteins ?? row?.nutrition?.protein ?? null);
                    const carbs = n(row?.carbs ?? row?.carbohydrates ?? row?.nutrition?.carbs ?? null);
                    const fat = n(row?.fats ?? row?.fat ?? row?.nutrition?.fats ?? row?.nutrition?.fat ?? null);
                    return (protein + carbs + fat) > 0;
                });

                if (withMacros) {
                    const protein = n(withMacros?.protein ?? withMacros?.proteins ?? withMacros?.nutrition?.protein ?? null);
                    const carbs = n(withMacros?.carbs ?? withMacros?.carbohydrates ?? withMacros?.nutrition?.carbs ?? null);
                    const fat = n(withMacros?.fats ?? withMacros?.fat ?? withMacros?.nutrition?.fats ?? withMacros?.nutrition?.fat ?? null);
                    if ((protein + carbs + fat) > 0) {
                        this.applyRawNutrition(protein, carbs, fat);
                    }
                }
            } catch (e) {}
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
                const cal = this.calories.find(c => c.range === this.selectedCalories);
                if (cal && cal.id) {
                    url += '&calorie_id=' + encodeURIComponent(String(cal.id));
                }
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
