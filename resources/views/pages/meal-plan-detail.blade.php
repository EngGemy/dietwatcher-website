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

// Calorie options from API data
$calorieOptions = !empty($plan->calorie_options) ? $plan->calorie_options : [
    ['range' => ($plan->calories_min ?? 700) . '-' . ($plan->calories_max ?? 800), 'label' => ($plan->calories_min ?? 700) . '-' . ($plan->calories_max ?? 800)],
];

// Nutritional info
$nutrition = $plan->nutrition ?? [
    'carbs' => ['amount' => '20g', 'percent' => 50, 'color' => 'bg-green'],
    'protein' => ['amount' => '20g', 'percent' => 50, 'color' => 'bg-yellow'],
    'fat' => ['amount' => '20g', 'percent' => 50, 'color' => 'bg-red'],
];

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

// Calculate prices from API
$planPrice = $plan->price ?? 2200;
$offerPrice = $plan->offer_price ?? 0;
$deliveryFee = 25;
$totalPrice = $planPrice + $deliveryFee;
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

        <div class="mb-10 grid gap-10 md:mb-16 md:grid-cols-2" x-data="{ 
            selectedMeal: 'breakfast',
            selectedCalories: '{{ $calorieOptions[0]['range'] ?? '700-800' }}',
            planPrice: {{ $planPrice }},
            deliveryFee: {{ $deliveryFee }},
            get total() { return this.planPrice + this.deliveryFee; }
        }">
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

                {{-- Nutritional Info --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <p class="mb-3 text-lg md:text-xl">{{ __('Nutritional info') }}</p>

                    <div class="flex flex-col gap-6 md:flex-row">
                        @foreach($nutrition as $key => $info)
                            <div class="flex-1">
                                <div class="mb-2 flex items-center justify-between">
                                    <p class="font-semibold">{{ __(ucfirst($key)) }}</p>
                                    <p>{{ $info['amount'] }}</p>
                                </div>
                                <div class="flex h-1 w-full overflow-hidden rounded-full bg-zinc-200" role="progressbar" aria-valuenow="{{ $info['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="{{ $info['color'] }} flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap transition duration-500" style="width: {{ $info['percent'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Payment Summary --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <p class="mb-3 text-lg md:text-xl">{{ __('Payment Summary') }}</p>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600">{{ __('Plan Price') }}</p>
                            <p>SAR <span x-text="planPrice.toLocaleString()"></span></p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-gray-600">{{ __('Delivery fees') }}</p>
                            <p>SAR <span x-text="deliveryFee"></span></p>
                        </div>

                        <div class="my-3 h-px bg-gray-300"></div>

                        <div class="flex items-center justify-between">
                            <p class="text-xl font-semibold">{{ __('Sub total') }}</p>
                            <p class="text-green text-xl">({{ __('Incl. Vat') }}) SAR <span x-text="total.toLocaleString()"></span></p>
                        </div>

                        <button type="button"
                                class="btn btn--primary btn--lg mt-3 w-full"
                                @click="window.location.href = '{{ route('checkout.index') }}?plan_id={{ $plan->id }}&meal_type=' + selectedMeal + '&calories=' + selectedCalories">
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

            {{-- Benefits --}}
            <div class="hs-accordion rounded-md border border-gray-200 bg-white" id="hs-benefits">
                <button class="hs-accordion-toggle flex w-full items-center justify-between px-5 py-4 text-start text-2xl font-bold text-gray-800 transition-colors focus:outline-hidden md:p-6" aria-controls="hs-benefits-content">
                    {{ __('Benefits') }}
                    <svg class="hs-accordion-active:rotate-180 size-5 text-gray-500 transition-transform duration-300">
                        <use href="{{ asset('assets/images/icons/sprite.svg#chevron-down') }}"></use>
                    </svg>
                </button>
                <div id="hs-benefits-content" class="hs-accordion-content hidden w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-benefits">
                    <div class="p-5 pt-0! md:p-6">
                        <p>{{ $plan->benefits ?? __('Improved energy levels, sustainable weight management, and better digestion.') }}</p>
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
