@extends('layouts.app')

@php
$locale = app()->getLocale();

// Get first cart item for breadcrumb
$firstItem = collect($cart)->first();
$planName = $firstItem['name'] ?? __('Order');
$cartCount = collect($cart)->sum('quantity');
$hasPlanItems = collect($cart)->contains(fn($item) => !empty($item['options']['duration_days']));
$firstPlanForPrice = $hasPlanItems ? collect($cart)->first(fn($item) => !empty($item['options']['duration_days'])) : null;
$planDurationDays = (int) ($firstPlanForPrice['options']['duration_days'] ?? 28);
$planLinePrice = (float) ($firstPlanForPrice['price'] ?? 0);
$planPricePerDay = $planDurationDays > 0 ? $planLinePrice / $planDurationDays : 0;

$sessionVerifiedPhone = session('phone_verified');
$oldPhone = old('phone', '');
$phoneVerifiedFromSession = $sessionVerifiedPhone && $oldPhone !== ''
    && str_replace(' ', '', (string) $sessionVerifiedPhone) === str_replace(' ', '', (string) $oldPhone);
@endphp

@section('title', __('Checkout') . ' | ' . $siteName)
@section('description', __('Complete your order to start your healthy journey'))

@section('content')
<section class="checkout-page bg-gray-200 pt-10 pb-32 min-h-[60vh]">
    <div class="container max-w-[1420px]">
        {{-- Breadcrumb --}}
        <ol class="breadcrumb">
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('home') }}">{{ __('Home') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </li>
            @if($hasPlanItems)
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('meal-plans.index') }}">{{ __('Meal Plans') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </li>
            @else
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('meals.index') }}">{{ __('Meals') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </li>
            @endif
            <li class="breadcrumb__item breadcrumb__item--active" aria-current="page">
                {{ __('Checkout') }}
            </li>
        </ol>

        <form action="{{ route('checkout.store') }}" method="POST" class="checkout-page__form"
              x-ref="checkoutForm"
              x-data="checkoutPage()"
              @address-selected.window="handleAddressFromMap($event)"
              @map-address-draft.window="handleMapAddressDraft($event)"
              @submit.prevent="submitForm($event)">
            @csrf

            {{-- Desktop: 50/50 two-column layout (matches Figma / static checkout) --}}
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start lg:gap-x-10">
                {{-- Left: form steps --}}
                <div class="order-1 min-w-0">
                    {{-- Select Options --}}
                    <div class="rounded-md border border-gray-200 bg-white p-5">
                        <h3 class="mb-6 text-2xl font-semibold md:text-2xl">{{ __('Select Options') }}</h3>

                        <div class="space-y-4 md:space-y-6">
                            {{-- Start Date --}}
                            <div>
                                <p class="mb-3 text-lg md:text-xl">{{ __('Start Date') }}</p>
                                <div class="date-picker-wrap" id="date_picker_wrap">
                                    <input
                                        type="text"
                                        name="start_date"
                                        id="start_date_input"
                                        readonly
                                        placeholder="{{ __('Select day') }}"
                                        class="date-picker-input @error('start_date') date-picker-input--error @enderror"
                                        value="{{ old('start_date', date('Y-m-d', strtotime('+1 day'))) }}"
                                    />
                                    <div class="date-picker-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75" />
                                        </svg>
                                    </div>
                                    <div class="date-picker-label" id="date_display">
                                        @php
                                            $defaultDate = old('start_date', date('Y-m-d', strtotime('+1 day')));
                                            $dateObj = \Carbon\Carbon::parse($defaultDate);
                                        @endphp
                                        <span class="date-picker-label__day">{{ $dateObj->format('d') }}</span>
                                        <span class="date-picker-label__month">{{ $dateObj->translatedFormat('M Y') }}</span>
                                    </div>
                                </div>
                                @error('start_date')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Duration: subscription = selectable cards (server + client fetch + cart fallback); meals = weekly/monthly radios --}}
                            @if($hasPlanItems)
                                <input type="hidden" name="duration" value="once" />
                                <div>
                                    <p class="mb-3 text-lg md:text-xl">{{ __('Duration') }}</p>
                                    <p x-show="durationsLoading" x-cloak class="mb-3 text-sm text-gray-500">{{ __('Loading...') }}</p>
                                    <div x-show="! durationsLoading && planDurations.length" x-cloak class="duration-pills">
                                        <template x-for="(d, idx) in planDurations" :key="'pd-' + idx + '-' + (d.id ?? 'x')">
                                            <div class="duration-pills__item">
                                                <div x-show="Number(d.id) > 0">
                                                    <input
                                                        type="radio"
                                                        name="plan_duration_id"
                                                        class="duration-pills__input"
                                                        :id="'plan-dur-' + d.id"
                                                        :value="String(d.id)"
                                                        x-model="selectedPlanDurationId"
                                                    />
                                                    <label class="duration-pills__face" :for="'plan-dur-' + d.id">
                                                        <span class="duration-pills__offer-badge" x-show="durationPlanHasOffer(d)" x-cloak>{{ __('Offer') }}</span>
                                                        <span class="duration-pills__title" x-text="durationCardTitle(d)"></span>
                                                        <span class="duration-pills__strike" x-show="durationPlanHasOffer(d)" x-text="'{{ __('SAR') }} ' + durationPlanListTotalStr(d)"></span>
                                                        <span class="duration-pills__total-line" x-text="'{{ __('SAR') }} ' + durationPlanEffectiveTotalStr(d)"></span>
                                                        <span class="duration-pills__avg" x-show="durationPlanAvgLine(d)" x-text="durationPlanAvgLine(d)"></span>
                                                    </label>
                                                </div>
                                                <div x-show="Number(d.id) <= 0" class="duration-pills__face duration-pills__face--static">
                                                    <span class="duration-pills__offer-badge" x-show="durationPlanHasOffer(d)" x-cloak>{{ __('Offer') }}</span>
                                                    <span class="duration-pills__title" x-text="durationCardTitle(d)"></span>
                                                    <span class="duration-pills__strike" x-show="durationPlanHasOffer(d)" x-text="'{{ __('SAR') }} ' + durationPlanListTotalStr(d)"></span>
                                                    <span class="duration-pills__total-line" x-text="'{{ __('SAR') }} ' + durationPlanEffectiveTotalStr(d)"></span>
                                                    <span class="duration-pills__avg" x-show="durationPlanAvgLine(d)" x-text="durationPlanAvgLine(d)"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <p x-show="! durationsLoading && ! planDurations.length" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                        {{ __('Could not load duration options. Please return to the meal plan and try again.') }}
                                    </p>
                                    @error('plan_duration_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <div>
                                    <p class="mb-3 text-lg md:text-xl">{{ __('Duration') }}</p>
                                    <div class="choice-group">
                                        <div class="choice-group__item">
                                            <input type="radio" name="duration" id="weekly" class="choice-group__input"
                                                   value="weekly" {{ old('duration') === 'weekly' ? 'checked' : '' }}
                                                   x-model="duration">
                                            <label for="weekly" class="choice-group__label">
                                                <div class="choice-group__content">
                                                    <span class="choice-group__title">{{ __('Weekly') }}</span>
                                                </div>
                                                <span class="choice-group__icon"></span>
                                            </label>
                                        </div>
                                        <div class="choice-group__item">
                                            <input type="radio" name="duration" id="monthly" class="choice-group__input"
                                                   value="monthly" {{ old('duration', 'monthly') === 'monthly' ? 'checked' : '' }}
                                                   x-model="duration">
                                            <label for="monthly" class="choice-group__label">
                                                <div class="choice-group__content">
                                                    <span class="choice-group__title">{{ __('Monthly') }}</span>
                                                </div>
                                                <span class="choice-group__icon"></span>
                                            </label>
                                        </div>
                                        <div class="choice-group__item">
                                            <input type="radio" name="duration" id="3months" class="choice-group__input"
                                                   value="3months" {{ old('duration') === '3months' ? 'checked' : '' }}
                                                   x-model="duration">
                                            <label for="3months" class="choice-group__label">
                                                <div class="choice-group__content">
                                                    <span class="choice-group__title">{{ __('3 Months') }}</span>
                                                </div>
                                                <span class="choice-group__icon"></span>
                                            </label>
                                        </div>
                                    </div>
                                    @error('duration')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            {{-- Coupon Code --}}
                            <div>
                                <p class="mb-3 text-lg md:text-xl">
                                    {{ __('Coupon Code') }}
                                    <span class="text-gray-600">({{ __('optional') }})</span>
                                </p>
                                <div class="form-input-action">
                                    <input type="text" name="coupon" class="form-control bg-blue/5"
                                           placeholder="{{ __('Promo code') }}" value="{{ old('coupon') }}"
                                           x-model="couponCode" :disabled="couponApplied" />
                                    <template x-if="!couponApplied">
                                        <button type="button" class="form-input-action__btn"
                                                @click="applyCoupon()" :disabled="couponLoading || !couponCode.trim()">
                                            <span x-show="!couponLoading">{{ __('Apply') }}</span>
                                            <span x-show="couponLoading" x-cloak>...</span>
                                        </button>
                                    </template>
                                    <template x-if="couponApplied">
                                        <button type="button" class="form-input-action__btn !bg-red-500 !text-white"
                                                @click="removeCoupon()">
                                            {{ __('Remove') }}
                                        </button>
                                    </template>
                                </div>
                                <p x-show="couponMessage" x-cloak
                                   :class="couponApplied ? 'text-green-600' : 'text-red-500'"
                                   class="text-sm mt-1" x-text="couponMessage"></p>
                            </div>
                        </div>
                    </div>

                    {{-- User Information --}}
                    <div class="mt-6 rounded-md border border-gray-200 bg-white p-5" x-ref="checkoutUserCard">
                        <h3 class="mb-6 text-2xl font-semibold md:text-2xl">{{ __('User Information') }}</h3>

                        <div class="space-y-4">
                            {{-- Name field: hidden initially, shown after OTP verification --}}
                            <div x-show="phoneVerified && showNameField" x-cloak
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0">
                                <input type="text" name="name" class="form-control @error('name') border-red-500 @enderror"
                                       placeholder="{{ __('Add your name') }}" x-model="customerName" required />
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Hidden name input when field is not shown (existing user with name already set) --}}
                            <template x-if="phoneVerified && !showNameField && customerName">
                                <input type="hidden" name="name" :value="customerName" />
                            </template>

                            <div>
                                <div class="form-input-action">
                                    <input type="tel" name="phone" class="form-control @error('phone') border-red-500 @enderror"
                                           placeholder="{{ __('Add your phone number') }}" value="{{ old('phone') }}" required dir="ltr"
                                           x-model="phone" :readonly="phoneVerified"
                                           :class="phoneVerified ? 'bg-gray-50 cursor-default' : ''" />
                                    <template x-if="!phoneVerified">
                                        <button type="button" class="form-input-action__btn"
                                                @click="openOtpModal()" :disabled="otpLoading || !phone.trim()">
                                            <span x-show="!otpLoading">{{ __('Verify') }}</span>
                                            <span x-show="otpLoading" x-cloak>...</span>
                                        </button>
                                    </template>
                                    <template x-if="phoneVerified">
                                        <span class="form-input-action__btn !bg-green-500 !text-white !cursor-default">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        </span>
                                    </template>
                                </div>
                                <p x-show="phoneVerified" x-cloak class="text-green-600 text-sm mt-1">{{ __('Phone verified') }}</p>
                                @error('phone')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-show="phoneVerified && savedAddresses.length > 0" x-cloak class="rounded-lg border border-blue-100 bg-blue-50/80 p-4">
                                <p class="mb-2 text-sm font-semibold text-gray-900">{{ __('checkout.saved_addresses_title') }}</p>
                                <p class="mb-3 text-xs text-gray-600">{{ __('checkout.saved_addresses_hint') }}</p>
                                <ul class="max-h-64 space-y-2 overflow-y-auto">
                                    <template x-for="addr in savedAddresses" :key="addr.id">
                                        <li>
                                            <button type="button"
                                                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-left text-sm text-gray-800 transition hover:border-blue-400 hover:bg-blue-50/50"
                                                    @click="applySavedAddress(addr)">
                                                <span class="line-clamp-2" x-text="addr.description || addr.title || ''"></span>
                                                <span class="mt-1 block text-xs text-gray-500" x-text="savedAddressDistrict(addr)"></span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                        </div>
                    </div>

                    {{-- Delivery address: map (home) or branch (pickup) — toggles live in Select Options --}}
                    <div class="mt-6 rounded-md border border-gray-200 bg-white p-5">
                        <h3 class="mb-6 text-2xl font-semibold md:text-2xl">{{ __('Delivery Address') }}</h3>

                        {{-- Delivery preference (under heading — matches reference layout) --}}
                        <div class="mb-6">
                            <p class="mb-3 text-lg md:text-xl">{{ __('Delivery Preference') }}</p>
                            <div class="choice-group choice-group--two">
                                <div class="choice-group__item">
                                    <input type="radio" name="delivery_type" id="home" class="choice-group__input"
                                           value="home" {{ old('delivery_type', 'home') === 'home' ? 'checked' : '' }}
                                           x-model="deliveryType">
                                    <label for="home" class="choice-group__label">
                                        <div class="choice-group__content">
                                            <span class="choice-group__title">{{ __('Home Delivery') }}</span>
                                        </div>
                                        <span class="choice-group__icon"></span>
                                    </label>
                                </div>
                                <div class="choice-group__item">
                                    <input type="radio" name="delivery_type" id="pickup" class="choice-group__input"
                                           value="pickup" {{ old('delivery_type') === 'pickup' ? 'checked' : '' }}
                                           x-model="deliveryType">
                                    <label for="pickup" class="choice-group__label">
                                        <div class="choice-group__content">
                                            <span class="choice-group__title">{{ __('Pickup from Branch') }}</span>
                                        </div>
                                        <span class="choice-group__icon"></span>
                                    </label>
                                </div>
                            </div>
                            @error('delivery_type')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Pickup: choose branch → search list → confirmed --}}
                        <div x-show="deliveryType === 'pickup'" x-transition class="space-y-4">
                            <input type="hidden" name="branch_id" :value="selectedBranchId" :disabled="deliveryType === 'home'" />

                            <p x-show="branchesLoading" class="text-sm text-gray-500">{{ __('Loading branches...') }}</p>

                            <div x-show="!branchesLoading && pickupPhase === 'cta'" x-transition>
                                <button type="button" class="btn btn--primary btn--md w-full py-4 text-base font-semibold" @click="openBranchPicker()">
                                    {{ __('Choose Branch') }}
                                </button>
                            </div>

                            <div x-show="!branchesLoading && pickupPhase === 'list'" x-cloak x-transition class="space-y-3">
                                <input type="search" class="form-control w-full" x-model="branchSearch"
                                       placeholder="{{ __('Search branches') }}" autocomplete="off" />
                                <ul class="checkout-branch-list max-h-80 space-y-2 overflow-y-auto pe-1">
                                    <template x-for="branch in filterBranches()" :key="branch.id">
                                        <li>
                                            <button type="button" class="checkout-branch-list__item" @click="selectBranch(branch.id)">
                                                <span class="checkout-branch-list__name" x-text="branchLabel(branch)"></span>
                                                <span class="checkout-branch-list__addr" x-show="branch.address" x-text="branch.address"></span>
                                                <span class="checkout-branch-list__phone" x-show="branch.phone" dir="ltr" x-text="branch.phone"></span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                                <p x-show="!branchesLoading && filterBranches().length === 0" class="text-sm text-gray-500">{{ __('No branches match your search.') }}</p>
                            </div>

                            <div x-show="!branchesLoading && pickupPhase === 'done' && selectedBranchId" x-cloak x-transition>
                                <div class="checkout-branch-selected">
                                    <div class="checkout-branch-selected__head">
                                        <p class="font-semibold text-gray-900" x-text="branchLabel(selectedBranchObj())"></p>
                                        <button type="button" class="text-sm font-bold text-blue-600 hover:underline" @click="editBranchSelection()">{{ __('Edit') }}</button>
                                    </div>
                                    <template x-if="selectedBranchObj()">
                                        <div>
                                            <p class="mt-1 text-sm text-gray-600" x-show="selectedBranchObj().address" x-text="selectedBranchObj().address"></p>
                                            <p class="mt-1 text-sm text-gray-600" x-show="selectedBranchObj().phone" dir="ltr" x-text="selectedBranchObj().phone"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            @error('branch_id')
                                <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Home: city + inline map + address — no x-transition (can leave map invisible); x-show keeps block in DOM --}}
                        <div x-show="deliveryType === 'home'" class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('City') }}</label>
                                    <select name="zone_id" class="form-control @error('zone_id') border-red-500 @enderror"
                                            x-model="selectedZoneId" @change="onZoneChange()"
                                            :disabled="deliveryType === 'pickup'"
                                            :required="deliveryType === 'home'">
                                        <option value="">{{ __('Select city') }}</option>
                                        @foreach($zones as $zone)
                                            @if($zone['is_active'] ?? true)
                                            <option value="{{ $zone['id'] }}" {{ old('zone_id') == $zone['id'] ? 'selected' : '' }}>
                                                {{ is_array($zone['name']) ? ($zone['name'][app()->getLocale()] ?? $zone['name']['en'] ?? '') : $zone['name'] }}
                                            </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('zone_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-3">
                                    <p class="text-sm text-gray-600">{{ __('checkout.map_address_hint') }}</p>
                                    <div class="checkout-map-embed relative z-[1] min-h-[360px] w-full overflow-hidden rounded-xl border border-gray-200 bg-white">
                                        <x-google-map-picker
                                            field-prefix="delivery"
                                            variant="inline"
                                            :placeholder="__('Search for an address')"
                                        />
                                    </div>
                                    @unless(config('services.google_maps.key'))
                                        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                            {{ __('Add GOOGLE_MAPS_API_KEY to your .env file to load the live map.') }}
                                        </p>
                                    @endunless

                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Address') }}</label>
                                        <textarea name="street" rows="3"
                                                  class="form-control @error('street') border-red-500 @enderror"
                                                  placeholder="{{ __('Street, district, details…') }}"
                                                  x-model="addressStreet"
                                                  :required="deliveryType === 'home'"
                                                  :disabled="deliveryType === 'pickup'"></textarea>
                                        @error('street')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3" x-show="deliveryType === 'home'" x-cloak>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Building') }}</label>
                                            <input type="text" class="form-control" autocomplete="section-shipping address-line2"
                                                   placeholder="{{ __('Building no.') }}"
                                                   x-model="deliveryBuilding"
                                                   @input.debounce.300ms="composeBuildingNotes()"
                                                   :disabled="deliveryType === 'pickup'" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Floor') }}</label>
                                            <input type="text" class="form-control" inputmode="numeric" autocomplete="off"
                                                   placeholder="{{ __('Floor no.') }}"
                                                   x-model="deliveryFloor"
                                                   @input.debounce.300ms="composeBuildingNotes()"
                                                   :disabled="deliveryType === 'pickup'" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Door') }}</label>
                                            <input type="text" class="form-control" inputmode="numeric" autocomplete="off"
                                                   placeholder="{{ __('Door no.') }}"
                                                   x-model="deliveryDoor"
                                                   @input.debounce.300ms="composeBuildingNotes()"
                                                   :disabled="deliveryType === 'pickup'" />
                                        </div>
                                    </div>

                                    <input type="hidden" name="building" :value="buildingNotes" :disabled="deliveryType === 'pickup'" />
                                </div>
                        </div>
                    </div>

                    {{-- Payment: Moyasar (fields visible, pay button disabled until phone verified) --}}
                    <div class="mt-6 rounded-md border border-gray-200 bg-white p-5"
                         :class="{ 'checkout-pay-locked': !phoneVerified }"
                    >
                        <h3 class="mb-2 text-2xl font-semibold md:text-2xl">{{ __('Payment') }}</h3>
                        <p class="mb-4 text-sm text-gray-600">{{ __('payment.pay_with_moyasar') }}</p>

                        <div class="mb-3 flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3">
                            <svg x-show="phoneVerified" xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <svg x-show="!phoneVerified" x-cloak xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ __('payment.secure_checkout') }}</p>
                                <p class="text-xs text-gray-500">{{ __('payment.secure_note') }}</p>
                            </div>
                        </div>

                        <div x-show="moyasarError" x-cloak class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" x-text="moyasarError"></div>

                        <div class="relative min-h-[160px] rounded-xl border border-gray-200 bg-gray-50 p-4">
                            {{-- Info banner when phone not verified --}}
                            <div
                                x-show="!phoneVerified"
                                x-cloak
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100 max-h-20"
                                x-transition:leave-end="opacity-0 max-h-0"
                                class="mb-3 flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                <span>{{ __('payment.verify_phone_to_pay') }}</span>
                            </div>

                            <div id="moyasar-form-checkout" class="relative z-[1] min-h-[120px] w-full"></div>
                        </div>
                    </div>
                </div>

                {{-- Right: Plan summary (sticky on desktop — matches Figma) --}}
                <div class="order-2 min-w-0 space-y-6">
                    <div class="rounded-md border border-gray-200 bg-white p-5 lg:sticky lg:top-24 lg:z-10">
                        <h3 class="mb-6 text-2xl font-semibold md:text-2xl">{{ __('Plan Summary') }}</h3>

                        <div class="space-y-4">
                            {{-- Cart Items --}}
                            @foreach($cart as $key => $item)
                                @php
                                    $itemImg = $item['image'] ?? '';
                                    $itemImgUrl = str_starts_with($itemImg, 'http') ? $itemImg : ($itemImg ? asset($itemImg) : asset('assets/images/plan-1.png'));
                                @endphp
                                <div class="flex items-center gap-4 rounded-md bg-gray-200 p-4">
                                    <img src="{{ $itemImgUrl }}" alt="{{ $item['name'] }}" class="h-16 w-16 rounded-md object-cover" onerror="this.src='{{ asset('assets/images/plan-1.png') }}'" />
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-lg font-bold text-gray-900 truncate">{{ $item['name'] }}</h4>
                                        <p class="text-sm text-gray-600">
                                            {{ __('Qty') }}: {{ $item['quantity'] }}
                                            @if(!empty($item['options']['mealType']))
                                                <span class="mx-1">&bull;</span> {{ __(ucfirst($item['options']['mealType'])) }}
                                            @endif
                                            @if(!empty($item['options']['calories']))
                                                <span class="mx-1">&bull;</span> {{ $item['options']['calories'] }} {{ __('Kcal') }}
                                            @endif
                                        </p>
                                        @if($hasPlanItems)
                                            <p class="mt-1 text-sm text-gray-600" x-show="!durationsLoading && planDurationSummaryLabel()" x-text="planDurationSummaryLabel()"></p>
                                        @endif
                                    </div>
                                    @if($hasPlanItems)
                                        <span class="font-bold text-gray-900 whitespace-nowrap" x-text="'SAR ' + subtotalInclVat().toFixed(2)"></span>
                                    @else
                                        <span class="font-bold text-gray-900 whitespace-nowrap">SAR {{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Payment Summary --}}
                            <div class="border-y border-gray-300 py-4">
                                <div>
                                    <p class="mb-3 text-lg md:text-xl">{{ __('Payment Summary') }}</p>

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">{{ $hasPlanItems ? __('Plan Price') : __('Items Total') }} <span class="text-xs">({{ __('Incl. VAT') }})</span></span>
                                            <span class="font-bold text-gray-900">SAR <span x-text="subtotalInclVat().toFixed(2)"></span></span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm" x-show="isPlanCheckout && planSelectedAvgPerDayAmount()" x-cloak>
                                            <span class="text-gray-600">{{ __('Avg. per day') }} <span class="text-xs text-gray-400">({{ __('Incl. VAT') }})</span></span>
                                            <span class="font-semibold text-gray-800" x-text="planSelectedAvgPerDayAmount()"></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">{{ __('Delivery fees') }}</span>
                                            <span class="font-bold text-gray-900">SAR <span x-text="deliveryFee().toFixed(2)"></span></span>
                                        </div>
                                        <div class="flex items-center justify-between" x-show="discount > 0" x-cloak>
                                            <span class="text-green-600">{{ __('Discount') }}</span>
                                            <span class="font-bold text-green-600">- SAR <span x-text="discount.toFixed(2)"></span></span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm text-gray-400">
                                            <span>{{ __('VAT included') }} ({{ (int)(\App\Models\Settings\Setting::getValue('vat_rate', 15)) }}%)</span>
                                            <span>SAR <span x-text="vatAmount().toFixed(2)"></span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Total --}}
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-semibold md:text-xl">{{ __('Total') }} <span class="text-xs font-normal text-gray-500">({{ __('Incl. VAT') }})</span></span>
                                <span class="text-lg font-semibold text-green-600 md:text-xl">SAR <span x-text="total().toFixed(2)"></span></span>
                            </div>

                            {{-- Proceed to Payment Button --}}
                            <div class="pt-2">
                                <button type="submit" class="btn btn--primary btn--md w-full">
                                    {{ __('payment.proceed') }} — SAR <span x-text="total().toFixed(2)"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── OTP Verification Modal (teleported to body) ──── --}}
            <template x-teleport="body">
                <div x-show="otpModalOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="otp-overlay"
                     style="display: none;"
                     @keydown.escape.window="if(otpModalOpen && !otpLoading) otpModalOpen = false">

                    {{-- Backdrop --}}
                    <div class="otp-overlay__backdrop" @click="if(!otpLoading) otpModalOpen = false"></div>

                    {{-- Modal Card --}}
                    <div class="otp-modal"
                         x-show="otpModalOpen"
                         x-transition:enter="transition ease-out duration-300 delay-75"
                         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                         @click.stop>

                        {{-- Close button --}}
                        <button type="button" @click="otpModalOpen = false" :disabled="otpLoading"
                                class="otp-modal__close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>

                        {{-- Icon --}}
                        <div class="otp-modal__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                        </div>

                        {{-- Title --}}
                        <h3 class="otp-modal__title">{{ __('Verify Phone Number') }}</h3>

                        {{-- Subtitle with phone --}}
                        <p class="otp-modal__subtitle">
                            {{ __('We sent a verification code to') }}
                        </p>
                        <p class="otp-modal__phone" dir="ltr" x-text="phone"></p>

                        {{-- 4 OTP Digit Inputs --}}
                        <div class="otp-modal__digits" dir="ltr">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                   id="otp-input-0" class="otp-digit" placeholder="&middot;"
                                   x-model="otpDigits[0]"
                                   @input="handleOtpInput($event, 0)"
                                   @keydown.backspace="handleOtpBackspace($event, 0)"
                                   @paste="handleOtpPaste($event)"
                                   :disabled="otpLoading"
                                   :class="{ 'otp-digit--filled': otpDigits[0] }" />
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                   id="otp-input-1" class="otp-digit" placeholder="&middot;"
                                   x-model="otpDigits[1]"
                                   @input="handleOtpInput($event, 1)"
                                   @keydown.backspace="handleOtpBackspace($event, 1)"
                                   @paste="handleOtpPaste($event)"
                                   :disabled="otpLoading"
                                   :class="{ 'otp-digit--filled': otpDigits[1] }" />
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                   id="otp-input-2" class="otp-digit" placeholder="&middot;"
                                   x-model="otpDigits[2]"
                                   @input="handleOtpInput($event, 2)"
                                   @keydown.backspace="handleOtpBackspace($event, 2)"
                                   @paste="handleOtpPaste($event)"
                                   :disabled="otpLoading"
                                   :class="{ 'otp-digit--filled': otpDigits[2] }" />
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                   id="otp-input-3" class="otp-digit" placeholder="&middot;"
                                   x-model="otpDigits[3]"
                                   @input="handleOtpInput($event, 3)"
                                   @keydown.backspace="handleOtpBackspace($event, 3)"
                                   @paste="handleOtpPaste($event)"
                                   :disabled="otpLoading"
                                   :class="{ 'otp-digit--filled': otpDigits[3] }" />
                        </div>

                        {{-- Message --}}
                        <div x-show="otpMessage" x-cloak class="otp-modal__message-wrap">
                            <p :class="otpMessageType === 'success' ? 'otp-modal__message--success' : 'otp-modal__message--error'"
                               class="otp-modal__message" x-text="otpMessage"></p>
                        </div>

                        {{-- Verify Button --}}
                        <button type="button" class="otp-modal__btn"
                                @click="verifyOtp()"
                                :disabled="otpLoading || otpDigits.join('').length < 4">
                            <span x-show="!otpLoading">{{ __('Verify') }}</span>
                            <span x-show="otpLoading" x-cloak style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                                <svg style="width:18px;height:18px;" class="animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                {{ __('Verifying...') }}
                            </span>
                        </button>

                        {{-- Resend --}}
                        <p class="otp-modal__resend">
                            {{ __("Didn't receive the code?") }}
                            <button type="button" class="otp-modal__resend-btn"
                                    @click="sendOtp()"
                                    :disabled="otpLoading || otpCooldown > 0">
                                <span x-text="otpCooldown > 0 ? '{{ __('Resend in') }} ' + otpCooldown + 's' : '{{ __('Resend') }}'"></span>
                            </button>
                        </p>
                    </div>
                </div>
            </template>

        </form>
    </div>
</section>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/moyasar-payment-form/dist/moyasar.css" />
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* ─── Smooth Page Animations ───────────────────── */
    .checkout-page .rounded-md {
        animation: checkout-fade-up 0.5s ease both;
    }
    .checkout-page .rounded-md:nth-child(1) { animation-delay: 0s; }
    .checkout-page .rounded-md:nth-child(2) { animation-delay: 0.1s; }
    .checkout-page .rounded-md:nth-child(3) { animation-delay: 0.2s; }
    .checkout-page .order-2 { animation: checkout-fade-up 0.5s ease 0.15s both; }

    @keyframes checkout-fade-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Smooth transitions for interactive elements */
    .choice-group__label,
    .duration-pills__face,
    .form-control,
    .form-input-action__btn {
        transition: all 0.25s ease !important;
    }

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

    /* ─── Date Picker Input ─────────────────────────── */
    .date-picker-wrap {
        position: relative;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        background: linear-gradient(135deg, #f0f7ff 0%, #f8fbff 100%);
        border: 2px solid #d4e8fc;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.25s ease;
    }
    .date-picker-wrap:hover {
        border-color: #279ff9;
        box-shadow: 0 4px 16px rgba(39,159,249,0.12);
    }
    .date-picker-wrap:focus-within {
        border-color: #279ff9;
        box-shadow: 0 0 0 4px rgba(39,159,249,0.1), 0 4px 16px rgba(39,159,249,0.12);
    }
    .date-picker-input {
        position: absolute;
        inset: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        z-index: 2;
    }
    .date-picker-input--error + .date-picker-icon { color: #ef4444; }
    .date-picker-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: #279ff9;
        color: #fff;
        flex-shrink: 0;
    }
    .date-picker-icon svg {
        width: 24px;
        height: 24px;
    }
    .date-picker-label {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .date-picker-label__day {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1a1a2e;
        line-height: 1;
    }
    .date-picker-label__month {
        font-size: 0.85rem;
        font-weight: 600;
        color: #666;
        line-height: 1;
    }

    /* ─── Flatpickr Theme Override ──────────────────── */
    .flatpickr-calendar {
        border-radius: 16px !important;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.04) !important;
        font-family: 'Instrument Sans', 'Almarai', ui-sans-serif, system-ui, sans-serif !important;
        border: none !important;
        overflow: hidden;
        width: 320px !important;
        padding: 0 !important;
    }
    .flatpickr-calendar.arrowTop::before,
    .flatpickr-calendar.arrowTop::after,
    .flatpickr-calendar.arrowBottom::before,
    .flatpickr-calendar.arrowBottom::after { display: none !important; }

    .flatpickr-months {
        background: linear-gradient(135deg, #279ff9 0%, #1a7ed4 100%) !important;
        padding: 12px 8px 8px !important;
        border-radius: 16px 16px 0 0 !important;
    }
    .flatpickr-months .flatpickr-month { height: 40px !important; }
    .flatpickr-current-month {
        font-size: 1.05rem !important;
        font-weight: 700 !important;
        color: #fff !important;
        padding-top: 4px !important;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months {
        background: transparent !important;
        color: #fff !important;
        font-weight: 700 !important;
        appearance: none !important;
        -webkit-appearance: none !important;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months option {
        background: #fff !important;
        color: #333 !important;
    }
    .flatpickr-current-month input.cur-year {
        color: #fff !important;
        font-weight: 700 !important;
    }
    .flatpickr-months .flatpickr-prev-month,
    .flatpickr-months .flatpickr-next-month {
        color: #fff !important;
        fill: #fff !important;
        padding: 8px 12px !important;
        top: 8px !important;
    }
    .flatpickr-months .flatpickr-prev-month:hover,
    .flatpickr-months .flatpickr-next-month:hover {
        background: rgba(255,255,255,0.15) !important;
        border-radius: 8px !important;
    }
    .flatpickr-months .flatpickr-prev-month svg,
    .flatpickr-months .flatpickr-next-month svg {
        fill: #fff !important;
        width: 14px !important;
        height: 14px !important;
    }
    .flatpickr-weekdays {
        background: transparent !important;
        padding: 8px 12px 0 !important;
    }
    span.flatpickr-weekday {
        color: #999 !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }
    .flatpickr-innerContainer { padding: 4px 8px 8px !important; }
    .flatpickr-days { width: 100% !important; }
    .dayContainer {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        padding: 4px !important;
    }
    .flatpickr-day {
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        color: #333 !important;
        height: 40px !important;
        line-height: 40px !important;
        max-width: 40px !important;
        margin: 1px !important;
        transition: all 0.15s ease !important;
    }
    .flatpickr-day:hover {
        background: #e8f4ff !important;
        border-color: #e8f4ff !important;
        color: #279ff9 !important;
    }
    .flatpickr-day.today {
        border: 2px solid #279ff9 !important;
        background: transparent !important;
        color: #279ff9 !important;
        font-weight: 800 !important;
    }
    .flatpickr-day.today:hover {
        background: #e8f4ff !important;
    }
    .flatpickr-day.selected,
    .flatpickr-day.selected:hover {
        background: #279ff9 !important;
        border-color: #279ff9 !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(39,159,249,0.35) !important;
    }
    .flatpickr-day.flatpickr-disabled,
    .flatpickr-day.flatpickr-disabled:hover {
        color: #ddd !important;
        background: transparent !important;
        cursor: not-allowed !important;
        text-decoration: line-through !important;
    }
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay {
        color: #ccc !important;
    }

    /* Only hide cloaked nodes inside checkout — avoids stuck hidden UI if Alpine loads late */
    .checkout-page [x-cloak] { display: none !important; }

    /* ─── OTP Modal ─────────────────────────────────── */
    .otp-overlay {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .otp-overlay__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    .otp-modal {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 420px;
        background: #fff;
        border-radius: 24px;
        padding: 40px 32px 32px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        text-align: center;
    }
    .otp-modal__close {
        position: absolute;
        top: 16px;
        {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}: 16px;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f1f5f9;
        color: #94a3b8;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    .otp-modal__close:hover {
        background: #e2e8f0;
        color: #64748b;
    }
    .otp-modal__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        color: #279ff9;
        margin: 0 auto 20px;
    }
    .otp-modal__title {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
    }
    .otp-modal__subtitle {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 4px;
    }
    .otp-modal__phone {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: 1px;
        margin-bottom: 28px;
    }

    /* Digit inputs */
    .otp-modal__digits {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-bottom: 24px;
    }
    .otp-digit {
        width: 64px;
        height: 68px;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        background: #f8fafc;
        text-align: center;
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        outline: none;
        transition: all 0.2s ease;
        caret-color: transparent;
        -moz-appearance: textfield;
    }
    .otp-digit::placeholder {
        color: #cbd5e1;
        font-size: 32px;
    }
    .otp-digit::-webkit-outer-spin-button,
    .otp-digit::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .otp-digit:focus {
        border-color: #279ff9;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(39, 159, 249, 0.12), 0 4px 12px rgba(39, 159, 249, 0.08);
        transform: translateY(-2px);
    }
    .otp-digit--filled {
        border-color: #279ff9;
        background: #eff6ff;
    }
    .otp-digit:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Message */
    .otp-modal__message-wrap { margin-bottom: 20px; }
    .otp-modal__message {
        font-size: 13px;
        padding: 10px 16px;
        border-radius: 12px;
        border: 1px solid;
    }
    .otp-modal__message--success {
        background: #f0fdf4;
        color: #16a34a;
        border-color: #bbf7d0;
    }
    .otp-modal__message--error {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
    }

    /* Button */
    .otp-modal__btn {
        width: 100%;
        height: 52px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, #279ff9 0%, #1a8ae0 100%);
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        margin-bottom: 16px;
    }
    .otp-modal__btn:hover:not(:disabled) {
        background: linear-gradient(135deg, #1a8ae0 0%, #1578c5 100%);
        box-shadow: 0 4px 16px rgba(39, 159, 249, 0.3);
        transform: translateY(-1px);
    }
    .otp-modal__btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Resend */
    .otp-modal__resend {
        font-size: 14px;
        color: #64748b;
    }
    .otp-modal__resend-btn {
        background: none;
        border: none;
        color: #279ff9;
        font-weight: 700;
        cursor: pointer;
        font-size: 14px;
        padding: 0;
    }
    .otp-modal__resend-btn:hover:not(:disabled) {
        text-decoration: underline;
    }
    .otp-modal__resend-btn:disabled {
        color: #94a3b8;
        cursor: not-allowed;
    }

    @media (max-width: 420px) {
        .otp-modal {
            padding: 32px 20px 24px;
            border-radius: 20px;
        }
        .otp-digit {
            width: 56px;
            height: 60px;
            font-size: 24px;
            border-radius: 12px;
        }
        .otp-modal__digits { gap: 8px; }
    }

    /* Pickup branch list (checkout) */
    .checkout-branch-list { list-style: none; margin: 0; padding: 0; }
    .checkout-branch-list__item {
        display: block; width: 100%; text-align: start;
        border: 1px solid #e5e7eb; border-radius: 12px; padding: 0.85rem 1rem;
        background: #fff; cursor: pointer; transition: border-color .15s, box-shadow .15s;
    }
    .checkout-branch-list__item:hover {
        border-color: #279ff9; box-shadow: 0 2px 8px rgba(39,159,249,.12);
    }
    .checkout-branch-list__name { display: block; font-weight: 700; color: #111827; }
    .checkout-branch-list__addr { display: block; font-size: 0.8rem; color: #6b7280; margin-top: 0.2rem; }
    .checkout-branch-list__phone { display: block; font-size: 0.8rem; color: #6b7280; margin-top: 0.15rem; }
    .checkout-branch-selected {
        border: 2px solid #bfdbfe; border-radius: 12px; background: #eff6ff; padding: 1rem 1.1rem;
    }
    .checkout-branch-selected__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }

    #moyasar-form-checkout .mysr-form {
        font-family: inherit !important;
    }
    #moyasar-form-checkout .mysr-form button[type="submit"],
    #moyasar-form-checkout .mysr-form .mysr-form-button {
        background: #279ff9 !important;
        border-radius: 10px !important;
        transition: opacity 0.3s, filter 0.3s;
    }
    /* Disable pay button when phone not verified */
    .checkout-pay-locked #moyasar-form-checkout .mysr-form button[type="submit"],
    .checkout-pay-locked #moyasar-form-checkout .mysr-form .mysr-form-button {
        pointer-events: none !important;
        opacity: 0.5 !important;
        filter: grayscale(0.3) !important;
        cursor: not-allowed !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/moyasar-payment-form/dist/moyasar.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@if($locale === 'ar')
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
@endif
<script>
    function checkoutPage() {
        return {
            // Reactive state
            baseSubtotal: {{ $baseSubtotal }},
            isPlanCheckout: @json($hasPlanItems),
            duration: @json($hasPlanItems ? 'once' : old('duration', 'monthly')),
            selectedPlanDurationId: @json((string) ($preferredPlanDurationId ?? '')),
            planDurationPrices: @json($planDurationPrices ?? []),
            deliveryType: '{{ old('delivery_type', 'home') }}',
            vatRate: {{ $vatRate }},
            deliveryFeeAmount: {{ $deliveryFeeAmount }},
            discount: 0,
            addressStreet: @json(old('street', '')),
            buildingNotes: @json(old('building', '')),
            customerName: @json(old('name', '')),
            showNameField: false,
            isContinueUser: false,
            savedAddresses: [],
            deviceId: (function () {
                try {
                    const k = 'dw_checkout_device_id';
                    let v = localStorage.getItem(k);
                    if (! v && typeof crypto !== 'undefined' && crypto.randomUUID) {
                        v = 'web-' + crypto.randomUUID();
                        localStorage.setItem(k, v);
                    }

                    return v || 'web-checkout-device';
                } catch (e) {
                    return 'web-checkout-device';
                }
            })(),
            deliveryBuilding: '',
            deliveryFloor: '',
            deliveryDoor: '',
            addressConfirmedForSync: false,
            _syncExtTimer: null,

            // Zone state
            selectedZoneId: '{{ old('zone_id', '') }}',
            zones: @json($zones),

            checkoutProgramId: {{ (int) ($checkoutProgramId ?? 0) }},
            /** Matches cart line duration_days — used when API duration_id differs from list ids */
            cartDurationDaysHint: {{ (int) ($planDurationDays ?? 0) }},
            cartDurationFallback: @json($cartDurationFallback ?? null),
            durationsLoading: @json($hasPlanItems),
            // Plan durations (filled from server, client fetch, or cart fallback)
            planDurations: @json($planDurations ?? []),

            // Branch pickup state
            selectedBranchId: '{{ old('branch_id', '') }}',
            branches: [],
            branchesLoading: true,
            pickupPhase: @json(old('branch_id') && old('delivery_type') === 'pickup' ? 'done' : 'cta'),
            branchSearch: '',

            // Duration multiplier map from backend
            durationMultipliers: @json($durationMultipliers),

            // Phone / OTP state
            phone: '{{ old('phone', '') }}',
            phoneVerified: @json($phoneVerifiedFromSession ?? false),
            otpModalOpen: false,
            otpSent: false,
            otpDigits: ['', '', '', ''],
            otpLoading: false,
            otpMessage: '',
            otpMessageType: '',
            otpCooldown: 0,

            // Coupon state
            couponCode: '{{ old('coupon', '') }}',
            couponApplied: false,
            couponLoading: false,
            couponMessage: '',

            moyasarError: '',
            _moyasarTimer: null,

            // ─── PRICES FROM API ARE VAT-INCLUSIVE (like mobile app) ───
            // The baseSubtotal already includes VAT. We extract VAT for display only.

            durationCardTitle(d) {
                if (! d) {
                    return '';
                }
                let label = d.label;
                if (typeof label === 'object' && label !== null && ! Array.isArray(label)) {
                    label = label['{{ $locale }}'] || label['en'] || '';
                }
                if (label) {
                    return String(label);
                }
                if (d.days) {
                    return String(d.days) + ' {{ __('days') }}';
                }

                return '';
            },

            /** Total SAR when available (matches meal-plan duration chips); else price / day */
            durationPillPriceLine(d) {
                if (! d) {
                    return '';
                }
                const total = parseFloat(d.effective_price) || 0;
                if (total > 0) {
                    const n = Math.round(total * 100) / 100;

                    return 'SAR ' + (Number.isInteger(n) ? String(n) : n.toFixed(2));
                }
                const ppd = parseFloat(d.price_per_day) || 0;
                if (ppd > 0) {
                    return 'SAR ' + ppd.toFixed(2) + ' / {{ __('day') }}';
                }

                return '';
            },

            durationPlanHasOffer(d) {
                if (! d) {
                    return false;
                }
                if (d.has_offer === true) {
                    return true;
                }
                const p = parseFloat(d.price) || 0;
                const o = parseFloat(d.offer_price) || 0;

                return o > 0 && o < p;
            },

            durationPlanListTotalStr(d) {
                const lp = parseFloat(d.list_price);
                const raw = ! Number.isNaN(lp) && lp > 0 ? lp : parseFloat(d.price) || 0;
                const n = Math.round(raw * 100) / 100;

                return Number.isInteger(n) ? String(n) : n.toFixed(2);
            },

            durationPlanEffectiveTotal(d) {
                const eff = parseFloat(d.effective_price);
                if (! Number.isNaN(eff) && eff > 0) {
                    return eff;
                }
                const p = parseFloat(d.price) || 0;
                const o = parseFloat(d.offer_price) || 0;

                return o > 0 && o < p ? o : p;
            },

            durationPlanEffectiveTotalStr(d) {
                const n = Math.round(this.durationPlanEffectiveTotal(d) * 100) / 100;

                return Number.isInteger(n) ? String(n) : n.toFixed(2);
            },

            durationPlanAvgLine(d) {
                const days = parseInt(d.days, 10) || 0;
                const e = this.durationPlanEffectiveTotal(d);
                if (days <= 0 || e <= 0) {
                    return '';
                }
                const avg = Math.round((e / days) * 100) / 100;
                const ns = Number.isInteger(avg) ? String(avg) : avg.toFixed(2);

                return '{{ __('SAR') }} ' + ns + ' · {{ __('per day') }}';
            },

            planSelectedAvgPerDayAmount() {
                if (! this.isPlanCheckout) {
                    return '';
                }
                const id = this.selectedPlanDurationId;
                const row = (this.planDurations || []).find((r) => String(r.id) === String(id));
                if (! row) {
                    return '';
                }
                const days = parseInt(row.days, 10) || 0;
                const e = this.durationPlanEffectiveTotal(row);
                if (days <= 0 || e <= 0) {
                    return '';
                }
                const avg = Math.round((e / days) * 100) / 100;

                return '{{ __('SAR') }} ' + (Number.isInteger(avg) ? String(avg) : avg.toFixed(2));
            },

            normalizeDurationRow(row) {
                const p = parseFloat(row.price) || 0;
                const o = parseFloat(row.offer_price) || 0;
                const eff = parseFloat(row.effective_price);
                const effective = ! Number.isNaN(eff) && eff > 0
                    ? eff
                    : (o > 0 && o < p ? o : p);
                const days = parseInt(row.days, 10) || 0;
                const ppd = days > 0 ? Math.round((effective / days) * 100) / 100 : (parseFloat(row.price_per_day) || 0);
                const hasOffer = o > 0 && o < p;

                return { ...row, effective_price: effective, price_per_day: ppd, list_price: p, has_offer: hasOffer };
            },

            async hydratePlanDurations() {
                let list = Array.isArray(this.planDurations) ? [...this.planDurations] : [];
                list = list.map((row) => this.normalizeDurationRow(row));
                if (list.length === 0 && this.checkoutProgramId) {
                    try {
                        const res = await fetch('{{ url('/api/plan') }}/' + this.checkoutProgramId + '/durations');
                        const data = await res.json();
                        const raw = Array.isArray(data) ? data : [];
                        list = raw.map((row) => this.normalizeDurationRow(row));
                    } catch (e) {}
                }
                if (list.length === 0 && this.cartDurationFallback) {
                    list = [this.normalizeDurationRow(this.cartDurationFallback)];
                }
                this.planDurations = list;
                this.planDurationPrices = {};
                list.forEach((row) => {
                    const id = String(row.id);
                    const eff = parseFloat(row.effective_price) || 0;
                    this.planDurationPrices[id] = eff;
                });
                const idOk = (s) => s && list.some((r) => String(r.id) === String(s));
                let sel = @json((string) old('plan_duration_id', $preferredPlanDurationId ?? ''));
                if (! idOk(sel)) {
                    let pick = this.cartDurationDaysHint > 0
                        ? list.find((r) => parseInt(r.days, 10) === this.cartDurationDaysHint)
                        : null;
                    if (! pick) {
                        pick = list.find((r) => r.is_default && Number(r.id) > 0) || list.find((r) => Number(r.id) > 0);
                    }
                    sel = pick ? String(pick.id) : (list[0] ? String(list[0].id) : '');
                }
                this.selectedPlanDurationId = sel;
                if (sel !== '' && this.planDurationPrices[sel] != null) {
                    this.baseSubtotal = Math.round(this.planDurationPrices[sel] * 100) / 100;
                }
                this.durationsLoading = false;
            },

            planDurationSummaryLabel() {
                const id = this.selectedPlanDurationId;
                const row = (this.planDurations || []).find((d) => String(d.id) === String(id));
                if (! row) {
                    return '';
                }
                let label = row.label;
                if (typeof label === 'object' && label !== null && ! Array.isArray(label)) {
                    label = label['{{ $locale }}'] || label['en'] || '';
                }
                const labelStr = String(label || '').trim();
                const daysNum = parseInt(row.days, 10) || 0;
                if (labelStr && daysNum > 0 && labelStr.includes(String(daysNum))) {
                    return labelStr;
                }
                if (! labelStr && daysNum > 0) {
                    return `${daysNum} {{ __('days') }}`;
                }
                if (labelStr && daysNum > 0) {
                    return labelStr + ` · ${daysNum} {{ __('days') }}`;
                }

                return labelStr;
            },

            composeBuildingNotes() {
                const p = [];
                const b = (this.deliveryBuilding || '').trim();
                const f = (this.deliveryFloor || '').trim();
                const d = (this.deliveryDoor || '').trim();
                if (b) {
                    p.push('{{ __("Building") }}: ' + b);
                }
                if (f) {
                    p.push('{{ __("Floor") }}: ' + f);
                }
                if (d) {
                    p.push('{{ __("Door") }}: ' + d);
                }
                this.buildingNotes = p.join(', ');
                if (this.addressConfirmedForSync) {
                    clearTimeout(this._syncExtTimer);
                    this._syncExtTimer = setTimeout(() => this.syncExternalAddress(), 1200);
                }
            },

            async syncExternalAddress() {
                if (this.deliveryType !== 'home') {
                    return;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return;
                }
                const fd = new FormData(form);
                try {
                    await fetch('{{ route('checkout.sync-address') }}', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                    });
                } catch (e) {}
            },

            handleAddressFromMap(event) {
                const d = event.detail || {};
                if (d.description) {
                    this.addressStreet = d.description;
                }
                this.deliveryBuilding = d.building_num != null && d.building_num !== '' ? String(d.building_num) : '';
                this.deliveryFloor = d.floor != null && d.floor !== '' ? String(d.floor) : '';
                this.deliveryDoor = d.door != null && d.door !== '' ? String(d.door) : '';
                if (d.building_notes) {
                    this.buildingNotes = d.building_notes;
                } else {
                    this.composeBuildingNotes();
                }
                this.addressConfirmedForSync = true;
                this.$nextTick(() => this.syncExternalAddress());
            },

            handleMapAddressDraft(event) {
                const d = event.detail || {};
                if (d.description) {
                    this.addressStreet = d.description;
                }
            },

            savedAddressDistrict(addr) {
                if (! addr || ! addr.district) {
                    return '';
                }
                const d = addr.district;
                if (typeof d.name === 'string') {
                    return d.name;
                }
                if (d.name && typeof d.name === 'object') {
                    return d.name['{{ $locale }}'] || d.name['en'] || '';
                }

                return '';
            },

            applySavedAddress(addr) {
                if (! addr || this.deliveryType !== 'home') {
                    return;
                }
                const districtId = addr.district?.id ?? addr.district_id;
                let pickup = 'hand_it_to_me';
                const pt = addr.pickupType;
                if (pt && typeof pt === 'object') {
                    const id = String(pt.id ?? '').toLowerCase();
                    const tx = String(pt.text ?? '').toLowerCase();
                    if (id.includes('leave') || tx.includes('leave') || tx.includes('door')) {
                        pickup = 'leave_at_door';
                    }
                }
                window.dispatchEvent(new CustomEvent('gmp-external-address-apply', {
                    detail: {
                        latitude: addr.latitude,
                        longitude: addr.longitude,
                        description: addr.description || '',
                        district_id: districtId,
                        type: addr.type || 'residential',
                        title: addr.title || '',
                        pickup_type: pickup,
                    },
                }));
                if (addr.description) {
                    this.addressStreet = addr.description;
                }
            },

            async refreshCustomerFromServer() {
                try {
                    const res = await fetch('{{ route('checkout.customer-state') }}', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const d = await res.json().catch(() => ({}));
                    if (! d.success) {
                        return;
                    }
                    this.savedAddresses = Array.isArray(d.addresses) ? d.addresses : [];
                    if (d.profile && d.profile.name && ! (this.customerName || '').trim()) {
                        this.customerName = String(d.profile.name);
                    }
                    // If already verified (page reload), determine name field visibility
                    if (this.phoneVerified) {
                        const hasName = !!(d.profile && d.profile.name);
                        const isCont = !!(d.is_continue);
                        this.isContinueUser = isCont;
                        this.showNameField = !(isCont && hasName);
                        // Auto-apply first saved address if existing user with addresses
                        if (isCont && this.savedAddresses.length > 0 && !this.addressStreet) {
                            this.$nextTick(() => this.applySavedAddress(this.savedAddresses[0]));
                        }
                    }
                } catch (e) {}
            },

            branchLabel(branch) {
                if (!branch) return '';
                if (typeof branch.name === 'object' && branch.name !== null) {
                    return branch.name['{{ app()->getLocale() }}'] || branch.name['en'] || '';
                }
                return branch.name || '';
            },

            filterBranches() {
                const q = (this.branchSearch || '').trim().toLowerCase();
                if (!q) return this.branches;
                return this.branches.filter((b) => {
                    const name = this.branchLabel(b).toLowerCase();
                    const addr = (b.address || '').toLowerCase();
                    const phone = (b.phone || '').toLowerCase();
                    return name.includes(q) || addr.includes(q) || phone.includes(q);
                });
            },

            selectedBranchObj() {
                if (!this.selectedBranchId) return null;
                return this.branches.find((b) => String(b.id) === String(this.selectedBranchId)) || null;
            },

            openBranchPicker() {
                this.pickupPhase = 'list';
                this.branchSearch = '';
            },

            selectBranch(id) {
                this.selectedBranchId = String(id);
                this.pickupPhase = 'done';
            },

            editBranchSelection() {
                this.pickupPhase = 'list';
                this.branchSearch = '';
            },

            syncPickupPhase() {
                if (this.deliveryType !== 'pickup') return;
                if (this.selectedBranchId) {
                    this.pickupPhase = 'done';
                } else {
                    this.pickupPhase = 'cta';
                }
            },

            // Computed: subscription line total is fixed; meals use duration multiplier
            subtotal() {
                if (this.isPlanCheckout) {
                    return Math.round(this.baseSubtotal * 100) / 100;
                }
                const multiplier = this.durationMultipliers[this.duration] || 1;
                return Math.round(this.baseSubtotal * multiplier * 100) / 100;
            },

            // Computed: subtotal including VAT (same as subtotal — price already includes VAT)
            subtotalInclVat() {
                return this.subtotal();
            },

            // Computed: delivery fee based on zone selection
            deliveryFee() {
                if (this.deliveryType !== 'home') return 0;
                if (this.selectedZoneId && this.zones.length > 0) {
                    const zone = this.zones.find(z => String(z.id) === String(this.selectedZoneId));
                    if (zone) {
                        const hasPlan = {{ collect($cart)->contains(fn($item) => !empty($item['options']['duration_days'])) ? 'true' : 'false' }};
                        return hasPlan
                            ? parseFloat(zone.subscription_delivery_price || 0)
                            : parseFloat(zone.order_delivery_price || 0);
                    }
                }
                return this.deliveryFeeAmount;
            },

            // Zone change handler
            onZoneChange() {
                // Recalculate when zone changes
            },

            // Computed: VAT extracted from VAT-inclusive price (for display only)
            // Formula: VAT = inclPrice - (inclPrice / (1 + vatRate))
            vatAmount() {
                const inclTotal = this.subtotal() + this.deliveryFee() - this.discount;
                return Math.round((inclTotal - (inclTotal / (1 + this.vatRate))) * 100) / 100;
            },

            // Computed: grand total (price already includes VAT, just add delivery and subtract discount)
            total() {
                return Math.round((this.subtotal() + this.deliveryFee() - this.discount) * 100) / 100;
            },

            // AJAX coupon validation
            async applyCoupon() {
                if (!this.couponCode.trim()) return;

                this.couponLoading = true;
                this.couponMessage = '';

                try {
                    const response = await fetch('{{ route('checkout.apply-coupon') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            code: this.couponCode.trim(),
                            subtotal: this.subtotal(),
                            identifier: this.phone || '',
                        }),
                    });

                    const data = await response.json();
                    this.couponMessage = data.message;

                    if (data.valid) {
                        this.discount = data.discount;
                        this.couponApplied = true;
                    } else {
                        this.discount = 0;
                        this.couponApplied = false;
                    }
                } catch (error) {
                    this.couponMessage = '{{ __('An error occurred. Please try again.') }}';
                    this.discount = 0;
                    this.couponApplied = false;
                }

                this.couponLoading = false;
            },

            // Remove applied coupon
            removeCoupon() {
                this.discount = 0;
                this.couponApplied = false;
                this.couponCode = '';
                this.couponMessage = '';
            },

            // Re-validate coupon when duration changes (subtotal changes)
            async revalidateCoupon() {
                if (this.couponApplied && this.couponCode.trim()) {
                    await this.applyCoupon();
                }
            },

            // Open OTP modal and send code
            async openOtpModal() {
                if (!this.phone.trim()) return;
                this.otpMessage = '';
                this.otpDigits = ['', '', '', ''];
                this.otpModalOpen = true;
                if (!this.otpSent) {
                    await this.sendOtp();
                } else {
                    this.$nextTick(() => document.getElementById('otp-input-0')?.focus());
                }
            },

            // Send OTP
            async sendOtp() {
                if (!this.phone.trim()) return;

                this.otpLoading = true;
                this.otpMessage = '';
                this.otpDigits = ['', '', '', ''];

                try {
                    const response = await fetch('{{ route('otp.send') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ phone: this.phone.trim() }),
                    });

                    const data = await response.json();
                    this.otpMessage = data.otp
                        ? data.message + ' (Code: ' + data.otp + ')'
                        : data.message;

                    if (data.success) {
                        this.otpSent = true;
                        this.otpMessageType = 'success';
                        this.startCooldown();
                        this.$nextTick(() => document.getElementById('otp-input-0')?.focus());
                    } else {
                        this.otpMessageType = 'error';
                    }
                } catch (error) {
                    this.otpMessage = '{{ __('An error occurred. Please try again.') }}';
                    this.otpMessageType = 'error';
                }

                this.otpLoading = false;
            },

            // Verify OTP
            async verifyOtp() {
                const code = this.otpDigits.join('');
                if (code.length < 4) return;

                this.otpLoading = true;
                this.otpMessage = '';

                try {
                    const response = await fetch('{{ route('otp.verify') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            phone: this.phone.trim(),
                            otp: code,
                            device_id: this.deviceId,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.phoneVerified = true;
                        this.otpMessageType = 'success';
                        this.otpMessage = data.message;
                        this.savedAddresses = Array.isArray(data.addresses) ? data.addresses : [];
                        this.isContinueUser = !!data.is_continue;

                        if (data.profile && data.profile.name) {
                            this.customerName = String(data.profile.name);
                        }

                        // Name field: show for new users (need to enter name), hide for existing (already has name)
                        if (data.is_continue && data.profile && data.profile.name) {
                            this.showNameField = false;
                        } else {
                            this.showNameField = true;
                        }

                        // Auto-apply first saved address for existing users
                        if (data.is_continue && this.savedAddresses.length > 0) {
                            this.$nextTick(() => {
                                this.applySavedAddress(this.savedAddresses[0]);
                                this.$refs.checkoutUserCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            });
                        } else if (data.is_continue) {
                            this.$nextTick(() => this.$refs.checkoutUserCard?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
                        }

                        setTimeout(() => { this.otpModalOpen = false; }, 800);
                        this.$nextTick(() => this.scheduleMoyasarRefresh());
                    } else {
                        this.otpMessageType = 'error';
                        this.otpMessage = data.message;
                        this.otpDigits = ['', '', '', ''];
                        this.$nextTick(() => document.getElementById('otp-input-0')?.focus());
                    }
                } catch (error) {
                    this.otpMessage = '{{ __('An error occurred. Please try again.') }}';
                    this.otpMessageType = 'error';
                }

                this.otpLoading = false;
            },

            // Handle single digit input → auto-focus next
            handleOtpInput(event, index) {
                const val = event.target.value.replace(/\D/g, '');
                const digit = val.charAt(0) || '';
                // Force new array reference for Alpine reactivity
                const newDigits = [...this.otpDigits];
                newDigits[index] = digit;
                this.otpDigits = newDigits;
                event.target.value = digit;

                if (digit && index < 3) {
                    this.$nextTick(() => document.getElementById('otp-input-' + (index + 1))?.focus());
                }
                // Auto-submit when all 4 filled
                if (this.otpDigits.join('').length === 4) {
                    this.$nextTick(() => this.verifyOtp());
                }
            },

            // Handle backspace → go to previous input
            handleOtpBackspace(event, index) {
                if (!this.otpDigits[index] && index > 0) {
                    this.$nextTick(() => document.getElementById('otp-input-' + (index - 1))?.focus());
                }
            },

            // Handle paste → fill all digits
            handleOtpPaste(event) {
                event.preventDefault();
                const paste = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').substring(0, 4);
                const newDigits = ['', '', '', ''];
                for (let i = 0; i < 4; i++) {
                    newDigits[i] = paste.charAt(i) || '';
                }
                this.otpDigits = newDigits;
                const lastIndex = Math.min(paste.length, 4) - 1;
                if (lastIndex >= 0) {
                    this.$nextTick(() => document.getElementById('otp-input-' + lastIndex)?.focus());
                }
                if (paste.length === 4) {
                    this.$nextTick(() => this.verifyOtp());
                }
            },

            // Cooldown timer for resend
            startCooldown() {
                this.otpCooldown = 60;
                const timer = setInterval(() => {
                    this.otpCooldown--;
                    if (this.otpCooldown <= 0) clearInterval(timer);
                }, 1000);
            },

            // Form submission — require phone verification
            submitForm(event) {
                if (!this.phoneVerified) {
                    // Open OTP modal so user can verify
                    this.openOtpModal();
                    return;
                }
                event.target.submit();
            },

            scheduleMoyasarRefresh() {
                clearTimeout(this._moyasarTimer);
                this._moyasarTimer = setTimeout(() => {
                    if (this.phoneVerified) {
                        this.bootstrapMoyasar();
                    } else {
                        this.bootstrapMoyasarPreview();
                    }
                }, 450);
            },

            async bootstrapMoyasarPreview() {
                if (this.phoneVerified) {
                    return;
                }
                const hasSdk = await this.waitForMoyasar();
                if (! hasSdk) {
                    this.moyasarError = '{{ __('payment.moyasar_load_failed') }}';

                    return;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return;
                }
                this.moyasarError = '';
                const fd = new FormData(form);
                fd.append('preview_only', '1');
                try {
                    const res = await fetch('{{ route('checkout.moyasar-session') }}', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (this.phoneVerified) {
                        return;
                    }
                    if (! res.ok || ! data.success) {
                        this.moyasarError = data.message || '{{ __('payment.fill_delivery_first') }}';
                        const el = document.getElementById('moyasar-form-checkout');
                        if (el) {
                            el.innerHTML = '';
                        }

                        return;
                    }
                    this.initMoyasarWidget(data);
                } catch (e) {
                    this.moyasarError = '{{ __('An error occurred. Please try again.') }}';
                }
            },

            waitForMoyasar(maxMs = 8000) {
                return new Promise((resolve) => {
                    if (typeof Moyasar !== 'undefined') {
                        resolve(true);

                        return;
                    }
                    const start = Date.now();
                    const tick = () => {
                        if (typeof Moyasar !== 'undefined') {
                            resolve(true);

                            return;
                        }
                        if (Date.now() - start >= maxMs) {
                            resolve(false);

                            return;
                        }
                        setTimeout(tick, 100);
                    };
                    tick();
                });
            },

            async bootstrapMoyasar() {
                if (! this.phoneVerified) {
                    return;
                }
                const hasSdk = await this.waitForMoyasar();
                if (! hasSdk) {
                    this.moyasarError = '{{ __('payment.moyasar_load_failed') }}';

                    return;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return;
                }
                this.moyasarError = '';
                const fd = new FormData(form);
                try {
                    const res = await fetch('{{ route('checkout.moyasar-session') }}', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (! this.phoneVerified) {
                        return;
                    }
                    if (! res.ok || ! data.success) {
                        this.moyasarError = data.message || '{{ __('payment.fill_delivery_first') }}';
                        const el = document.getElementById('moyasar-form-checkout');
                        if (el) {
                            el.innerHTML = '';
                        }

                        return;
                    }
                    this.initMoyasarWidget(data);
                } catch (e) {
                    this.moyasarError = '{{ __('An error occurred. Please try again.') }}';
                }
            },

            initMoyasarWidget(data) {
                const el = document.getElementById('moyasar-form-checkout');
                if (! el || typeof Moyasar === 'undefined') {
                    return;
                }
                el.innerHTML = '';
                let cb = (data.callback_url || '').trim();
                if (data.order_number) {
                    const sep = cb.includes('?') ? '&' : '?';
                    cb = cb + sep + 'order=' + encodeURIComponent(data.order_number);
                }
                Moyasar.init({
                    element: '#moyasar-form-checkout',
                    amount: data.amount_halalas,
                    currency: data.currency || 'SAR',
                    description: data.description || '',
                    publishable_api_key: data.publishable_key,
                    callback_url: cb,
                    methods: ['creditcard', 'applepay', 'stcpay'],
                    supported_networks: ['visa', 'mastercard', 'mada'],
                    apple_pay: {
                        country: 'SA',
                        label: 'Diet Watchers',
                        validate_merchant_url: 'https://api.moyasar.com/v1/applepay/initiate',
                    },
                    language: '{{ $locale }}',
                });
            },

            // Watch for duration changes to re-validate coupon
            async init() {
                if (this.isPlanCheckout) {
                    await this.hydratePlanDurations();
                } else {
                    this.durationsLoading = false;
                }
                this.$watch('selectedPlanDurationId', (id) => {
                    if (! this.isPlanCheckout || id === undefined || id === null) {
                        return;
                    }
                    const p = this.planDurationPrices[String(id)];
                    if (p != null) {
                        this.baseSubtotal = Math.round(p * 100) / 100;
                        this.revalidateCoupon();
                    }
                    this.scheduleMoyasarRefresh();
                });
                this.$watch('duration', () => this.revalidateCoupon());
                this.$watch('selectedZoneId', () => this.scheduleMoyasarRefresh());
                this.$watch('deliveryType', (v) => {
                    if (v === 'pickup') {
                        this.syncPickupPhase();
                    }
                    if (v === 'home') {
                        setTimeout(() => window.dispatchEvent(new CustomEvent('checkout-home-map-refresh')), 300);
                    }
                    this.scheduleMoyasarRefresh();
                });
                this.$watch('couponApplied', () => this.scheduleMoyasarRefresh());
                if (this.deliveryType === 'home') {
                    setTimeout(() => window.dispatchEvent(new CustomEvent('checkout-home-map-refresh')), 500);
                }

                fetch('{{ route('api.branches') }}')
                    .then(r => r.json())
                    .then(data => {
                        this.branches = data;
                        this.branchesLoading = false;
                        this.syncPickupPhase();
                    })
                    .catch(() => { this.branches = []; this.branchesLoading = false; });

                if (this.phoneVerified) {
                    await this.refreshCustomerFromServer();
                }
                this.scheduleMoyasarRefresh();
            }
        }
    }

    window.checkoutPage = checkoutPage;

    document.addEventListener('DOMContentLoaded', function() {
        const locale = '{{ $locale }}';
        const months = locale === 'ar'
            ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
            : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        function updateDisplay(dateStr) {
            const d = new Date(dateStr);
            const dayEl = document.querySelector('.date-picker-label__day');
            const monthEl = document.querySelector('.date-picker-label__month');
            if (dayEl && monthEl) {
                dayEl.textContent = String(d.getDate()).padStart(2, '0');
                monthEl.textContent = months[d.getMonth()] + ' ' + d.getFullYear();
            }
        }

        flatpickr('#start_date_input', {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            defaultDate: '{{ old('start_date', date('Y-m-d', strtotime('+1 day'))) }}',
            disableMobile: true,
            @if($locale === 'ar')
            locale: 'ar',
            @endif
            onChange: function(selectedDates, dateStr) {
                updateDisplay(dateStr);
            },
        });

        // Click anywhere on the wrapper to open the picker
        document.getElementById('date_picker_wrap')?.addEventListener('click', function() {
            document.getElementById('start_date_input')?._flatpickr?.open();
        });
    });
</script>
@endpush