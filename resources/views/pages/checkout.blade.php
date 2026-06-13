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
$initialPhone = $oldPhone !== '' ? $oldPhone : (string) ($sessionVerifiedPhone ?? '');
$phoneVerifiedFromSession = \App\Support\CustomerSession::isLoggedIn();
$initialPhoneLocal = \App\Support\SaudiPhone::localDigitsForInput($initialPhone);
$initialAddressPhoneLocal = \App\Support\SaudiPhone::localDigitsForInput(old('address_phone', ''));
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
              x-init="init()"
              @address-selected.window="handleAddressFromMap($event)"
              @map-address-draft.window="handleMapAddressDraft($event)"
              @checkout-coverage-changed.window="handleCoverageChanged($event)"
              @submit.prevent="submitForm($event)">
            @csrf
            @if($hasPlanItems)
                <input type="hidden" name="with_weekend" value="{{ $withWeekend ?? '0' }}" />
            @endif

            {{-- Desktop: 50/50 two-column layout (matches Figma / static checkout) --}}
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start lg:gap-x-10">
                {{-- Left: form steps --}}
                <div class="order-1 min-w-0">
                    {{-- Select Options --}}
                    <div class="rounded-md border border-gray-200 bg-white p-5">
                        <h3 class="mb-6 text-2xl font-semibold md:text-2xl">{{ __('Select Options') }}</h3>

                        <div class="space-y-4 md:space-y-6">
                            {{-- Start Date --}}
                            @if($hasPlanItems)
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
                                            value="{{ $defaultStartDate }}"
                                        />
                                        <div class="date-picker-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75" />
                                            </svg>
                                        </div>
                                        <div class="date-picker-label" id="date_display">
                                            @if(! empty($defaultStartDate))
                                                @php
                                                    $dateObj = \Carbon\Carbon::parse($defaultStartDate);
                                                @endphp
                                                <span class="date-picker-label__day">{{ $dateObj->format('d') }}</span>
                                                <span class="date-picker-label__month">{{ $dateObj->translatedFormat('M Y') }}</span>
                                            @else
                                                <span class="date-picker-label__day">--</span>
                                                <span class="date-picker-label__month">{{ __('Verify phone to set date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @error('start_date')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    <p x-show="startDateNotice" x-cloak class="text-amber-800 text-sm mt-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2" x-text="startDateNotice"></p>
                                </div>
                            @endif

                            {{-- Duration: subscription = selectable cards (server + client fetch + cart fallback); meals = weekly/monthly radios --}}
                            @if($hasPlanItems)
                                <input type="hidden" name="duration" value="once" />
                                <div>
                                    <p class="mb-3 text-lg md:text-xl">{{ __('Choose Duration') }}</p>
                                    <p x-show="durationsLoading" @if(empty($planDurations) && empty($cartDurationFallback)) x-cloak @endif class="mb-3 text-sm text-gray-500">{{ __('Loading...') }}</p>
                                    <div x-show="! durationsLoading && planDurations.length" @if(empty($planDurations) && empty($cartDurationFallback)) x-cloak @endif>
                                        <x-duration-carousel>
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
                                                            @change="scrollDurationToSelected()"
                                                        />
                                                        <label class="duration-pills__face" :for="'plan-dur-' + d.id">
                                                            <span class="duration-pills__offer-badge" x-show="durationPlanHasOffer(d)" x-cloak>{{ __('Offer') }}</span>
                                                            <span class="duration-pills__title" x-text="durationCardTitle(d)"></span>
                                                            <span class="duration-pills__strike" x-show="durationPlanHasOffer(d)" x-text="durationStrikeLine(d)"></span>
                                                            <span class="duration-pills__total-line" x-text="durationTotalLine(d)"></span>
                                                            <span class="duration-pills__avg" x-show="durationPlanAvgLine(d)" x-text="durationPlanAvgLine(d)"></span>
                                                        </label>
                                                    </div>
                                                    <div x-show="Number(d.id) <= 0" class="duration-pills__face duration-pills__face--static">
                                                        <span class="duration-pills__offer-badge" x-show="durationPlanHasOffer(d)" x-cloak>{{ __('Offer') }}</span>
                                                        <span class="duration-pills__title" x-text="durationCardTitle(d)"></span>
                                                        <span class="duration-pills__strike" x-show="durationPlanHasOffer(d)" x-text="durationStrikeLine(d)"></span>
                                                        <span class="duration-pills__total-line" x-text="durationTotalLine(d)"></span>
                                                        <span class="duration-pills__avg" x-show="durationPlanAvgLine(d)" x-text="durationPlanAvgLine(d)"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </x-duration-carousel>
                                    </div>
                                    <p x-show="! durationsLoading && ! planDurations.length" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                        {{ __('Could not load duration options. Please return to the meal plan and try again.') }}
                                    </p>
                                    @error('plan_duration_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <input type="hidden" name="duration" value="once" />
                            @endif

                            {{-- Coupon Code (external API — subscriptions; store meals pending API support) --}}
                            <div>
                                <p class="mb-3 text-lg md:text-xl">
                                    {{ __('Coupon Code') }}
                                    <span class="text-gray-600">({{ __('optional') }})</span>
                                </p>
                                <div class="form-input-action">
                                    <input type="text" name="coupon" class="form-control bg-blue/5"
                                           placeholder="{{ __('Promo code') }}" value="{{ old('coupon') }}"
                                           x-model="couponCode" :disabled="couponApplied" />
                                    <input type="hidden" name="promocode_name" :value="couponCode || ''" />
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
                                <div
                                    x-show="couponMessage"
                                    x-cloak
                                    class="checkout-coupon-feedback mt-2"
                                    :class="couponApplied ? 'checkout-coupon-feedback--success' : 'checkout-coupon-feedback--error'"
                                    role="status"
                                    aria-live="polite"
                                >
                                    <svg x-show="couponApplied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <svg x-show="!couponApplied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12V12.75Z" />
                                    </svg>
                                    <span x-text="couponMessage"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- User Information --}}
                    <div class="mt-6 rounded-md border border-gray-200 bg-white p-5" x-ref="checkoutUserCard" data-checkout-phone-section>
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
                                <div class="form-input-action checkout-phone-row flex w-full flex-row flex-nowrap items-stretch gap-2" dir="ltr">
                                    <input type="hidden" name="phone" id="checkout_phone_e164" x-bind:value="fullPhone966()" autocomplete="off" />
                                    <div class="checkout-phone-input-group flex min-w-0 flex-1 items-stretch rounded-md border border-gray-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 @error('phone') !border-red-500 @enderror"
                                         :class="phoneVerified ? 'bg-gray-50' : ''">
                                        <span class="flex select-none items-center border-e border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-600" dir="ltr">+966</span>
                                        <input type="tel" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-gray-900 outline-none focus:ring-0"
                                               autocomplete="tel" inputmode="numeric" maxlength="9" placeholder="5XXXXXXXX" required dir="ltr"
                                               x-model="phoneLocal"
                                               @input="phoneLocal = (typeof window.dwSaudiPhoneDigits === 'function' ? window.dwSaudiPhoneDigits($event.target.value || '') : ($event.target.value || '').replace(/\D/g, '').slice(0, 9))"
                                               :readonly="phoneVerified"
                                               :class="phoneVerified ? 'cursor-default bg-transparent' : ''" />
                                    </div>
                                    <template x-if="!phoneVerified">
                                        <button type="button" class="form-input-action__btn"
                                                @click="openOtpModal()" :disabled="otpLoading || !fullPhone966()">
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

                        </div>
                    </div>

                    {{-- Step gate: verify phone before delivery options (shown instead of the map/form) --}}
                    <div
                        x-show="!phoneVerified"
                        x-cloak
                        class="checkout-verify-gate mt-6"
                        role="status"
                    >
                        <div class="checkout-verify-gate__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                        </div>
                        <div class="checkout-verify-gate__body">
                            <p class="checkout-verify-gate__step">{{ __('checkout.step_verify_phone') }}</p>
                            <p class="checkout-verify-gate__title">{{ __('checkout.verify_phone_before_address') }}</p>
                            <button
                                type="button"
                                class="btn btn--primary btn--sm mt-3"
                                @click="promptPhoneVerificationForDelivery()"
                            >
                                {{ __('checkout.verify_phone_first_btn') }}
                            </button>
                        </div>
                    </div>

                    {{-- Delivery address: map (home) or branch (pickup) — only after phone verified --}}
                    <div
                        x-show="phoneVerified"
                        x-cloak
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="mt-6 rounded-md border border-gray-200 bg-white p-5"
                        x-ref="deliveryCard"
                    >
                        <input type="hidden" name="selected_address_id" :value="selectedAddressId || ''" :disabled="deliveryType !== 'home'" />
                        <input type="hidden" name="region_duration_id" :value="selectedRegionDurationId || ''" :disabled="deliveryType !== 'home'" />
                        <h3 class="mb-6 text-2xl font-semibold md:text-2xl">{{ __('Delivery Address') }}</h3>

                        {{-- Delivery preference (under heading ? matches reference layout) --}}
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

                        <div x-show="phoneVerified && deliveryType === 'home' && savedAddresses.length > 0" x-cloak class="mb-6 rounded-lg border border-blue-100 bg-blue-50/80 p-4">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900">{{ __('checkout.saved_addresses_title') }}</p>
                                <button type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border border-blue-500 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-500 hover:text-white"
                                        @click="startAddingAddress()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    <span x-text="newAddressToggleLabel()"></span>
                                </button>
                            </div>
                            <p class="mb-3 text-xs text-gray-600">{{ __('checkout.saved_addresses_hint') }}</p>
                            <p x-show="savedAddresses.length > 0 && deliverableSavedAddresses().length === 0" x-cloak class="mb-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                {{ __('checkout.address_not_in_delivery_zone') }}
                            </p>
                            <ul class="max-h-64 space-y-2 overflow-y-auto" x-show="!addingNewAddress && deliverableSavedAddresses().length > 0">
                                <template x-for="addr in deliverableSavedAddresses()" :key="addr.id">
                                    <li>
                                        <div class="w-full rounded-lg border px-3 py-2.5 text-sm text-gray-800 transition hover:border-blue-400 hover:bg-blue-50/50"
                                             :class="String(addr.id) === String(selectedAddressId) ? 'border-blue-500 bg-white shadow-sm ring-1 ring-blue-200' : 'border-gray-200 bg-white'">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <span class="line-clamp-2 block text-left" x-text="addr.description || addr.title || ''"></span>
                                                    <span class="mt-1 block text-xs text-gray-500 text-left" x-text="savedAddressDistrict(addr)"></span>
                                                    <div class="mt-2 space-y-2" x-show="addressDeliveryTimes(addr).length > 0" x-cloak>
                                                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-700">
                                                            <span class="font-medium text-gray-800">{{ __('checkout.delivery_time') }}:</span>
                                                            <span x-text="deliveryTimeLabelForAddress(addr)"></span>
                                                            <button type="button"
                                                                    class="rounded border border-blue-300 px-2 py-0.5 text-[11px] font-semibold text-blue-700 transition hover:bg-blue-50"
                                                                    x-show="String(addr.id) !== String(editingDeliveryTimeAddressId)"
                                                                    @click.stop="startEditingDeliveryTime(addr)">
                                                                {{ __('checkout.edit_delivery_time') }}
                                                            </button>
                                                        </div>
                                                        <div x-show="String(addr.id) === String(editingDeliveryTimeAddressId) || String(addr.id) === String(selectedAddressId)" x-cloak>
                                                            <select class="form-control form-control--sm w-full text-xs"
                                                                    :value="selectedRegionDurationId"
                                                                    @change="onSavedAddressDeliveryTimeChange(addr, $event.target.value)">
                                                                <option value="">{{ __('checkout.select_delivery_time') }}</option>
                                                                <template x-for="slot in addressDeliveryTimes(addr)" :key="slot.id">
                                                                    <option :value="String(slot.id)" x-text="slot.label || slot.time || slot.durationText || slot.id"></option>
                                                                </template>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <p x-show="addressDeliveryTimes(addr).length === 0 && String(addr.id) === String(selectedAddressId)" x-cloak class="mt-2 text-xs text-amber-800">
                                                        {{ __('checkout.no_delivery_time_slots') }}
                                                    </p>
                                                </div>
                                                <div class="flex shrink-0 flex-col gap-1.5 sm:flex-row">
                                                <button type="button"
                                                        class="rounded-md border border-blue-500 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-500 hover:text-white"
                                                        @click="selectSavedAddress(addr)">
                                                    {{ __('Select') }}
                                                </button>
                                                <button type="button"
                                                        class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50"
                                                        :disabled="deletingAddressId === String(addr.id)"
                                                        @click="deleteSavedAddress(addr)">
                                                    <span x-show="deletingAddressId !== String(addr.id)">{{ __('Delete') }}</span>
                                                    <span x-show="deletingAddressId === String(addr.id)" x-cloak>...</span>
                                                </button>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                            <p x-show="addingNewAddress" x-cloak class="text-xs text-blue-700">
                                {{ __('Fill the map and address fields below, then tap "Save address".') }}
                            </p>
                        </div>

                        {{-- Pickup: choose branch ? search list ? confirmed --}}
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

                        {{-- Home: city + inline map + address ? no x-transition (can leave map invisible); x-show keeps block in DOM --}}
                        @php
                            $riyadhZone = collect($zones)->first(function ($zone) {
                                $name = $zone['name'] ?? '';
                                if (is_array($name)) {
                                    $name = ($name[app()->getLocale()] ?? $name['ar'] ?? $name['en'] ?? '');
                                }
                                $n = mb_strtolower((string) $name);
                                return str_contains($n, 'riyadh') || str_contains($n, 'الرياض');
                            });
                        @endphp
                        <div x-show="deliveryType === 'home' && (savedAddresses.length === 0 || addingNewAddress)" class="space-y-4" x-init="if (!selectedZoneId) selectedZoneId = '{{ (string) (($riyadhZone['id'] ?? '') ?: '') }}'">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('City') }}</label>
                                    <select name="zone_id" class="form-control @error('zone_id') border-red-500 @enderror"
                                            x-model="selectedZoneId" @change="onZoneChange()"
                                            :disabled="deliveryType === 'pickup' || @json((bool) $riyadhZone)"
                                            :required="deliveryType === 'home'">
                                        @if($riyadhZone)
                                            <option value="{{ $riyadhZone['id'] }}">
                                                {{ is_array($riyadhZone['name']) ? ($riyadhZone['name'][app()->getLocale()] ?? $riyadhZone['name']['en'] ?? '') : $riyadhZone['name'] }}
                                            </option>
                                        @else
                                            <option value="">{{ __('Select city') }}</option>
                                            @foreach($zones as $zone)
                                                @if($zone['is_active'] ?? true)
                                                <option value="{{ $zone['id'] }}" {{ old('zone_id') == $zone['id'] ? 'selected' : '' }}>
                                                    {{ is_array($zone['name']) ? ($zone['name'][app()->getLocale()] ?? $zone['name']['en'] ?? '') : $zone['name'] }}
                                                </option>
                                                @endif
                                            @endforeach
                                        @endif
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
                                    <p x-show="deliveryType === 'home' && coverageMessage" x-cloak
                                       class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"
                                       x-text="coverageMessage"></p>
                                    @unless(config('services.google_maps.key'))
                                        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                            {{ __('Add GOOGLE_MAPS_API_KEY to your .env file to load the live map.') }}
                                        </p>
                                    @endunless

                                    {{-- Hidden by request: area/address/duplicate phone fields.
                                         Values are still submitted to keep backend validation intact. --}}
                                    <div class="hidden">
                                        <textarea name="street" rows="3"
                                                  x-model="addressStreet"
                                                  :required="deliveryType === 'home'"
                                                  :disabled="deliveryType === 'pickup'"></textarea>
                                        <input type="hidden" name="address_phone" x-bind:value="fullPhone966() || addressPhone966()" autocomplete="off" />
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

                                    <div x-show="deliveryType === 'home' && (loadingDistrictTimes || districtDeliveryTimes.length > 0 || (inlineMapDistrictId && !loadingDistrictTimes))" x-cloak class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                        <label class="mb-1 block text-sm font-medium text-gray-800">{{ __('checkout.delivery_time') }}</label>
                                        <p class="mb-2 text-xs text-gray-600">{{ __('checkout.delivery_time_hint') }}</p>
                                        <p x-show="loadingDistrictTimes" class="text-xs text-gray-500">{{ __('Loading...') }}</p>
                                        <select x-show="!loadingDistrictTimes && districtDeliveryTimes.length > 0"
                                                class="form-control w-full"
                                                x-model="selectedRegionDurationId">
                                            <option value="">{{ __('checkout.select_delivery_time') }}</option>
                                            <template x-for="slot in districtDeliveryTimes" :key="slot.id">
                                                <option :value="String(slot.id)" x-text="slot.label || slot.time || slot.durationText || slot.id"></option>
                                            </template>
                                        </select>
                                        <p x-show="!loadingDistrictTimes && districtDeliveryTimes.length === 0 && inlineMapDistrictId" class="text-xs text-amber-800">
                                            {{ __('checkout.no_delivery_time_slots') }}
                                        </p>
                                    </div>

                                    <div x-show="deliveryType === 'home'" x-cloak class="space-y-2 pt-1">
                                        <button
                                            type="button"
                                            class="btn btn--primary btn--md w-full"
                                            @click="saveDeliveryAddress()"
                                            :disabled="savingNewAddress || !inlineAddressCanSave()"
                                        >
                                            <span x-show="!savingNewAddress">{{ __('Save address') }}</span>
                                            <span x-show="savingNewAddress" x-cloak>{{ __('Saving...') }}</span>
                                        </button>
                                        <button
                                            x-show="addingNewAddress"
                                            x-cloak
                                            type="button"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                                            @click="addingNewAddress = false"
                                        >
                                            {{ __('Cancel') }}
                                        </button>
                                    </div>
                                    <p x-show="newAddressError" x-cloak class="text-sm text-red-600" x-text="newAddressError"></p>
                                </div>
                        </div>

                        {{-- Next-step hint: shown here (not in payment) until checkout is complete --}}
                        <div
                            x-show="phoneVerified && !canProceedToPayment() && checkoutSetupHint()"
                            x-cloak
                            class="checkout-setup-hint mt-6"
                            role="status"
                            aria-live="polite"
                        >
                            <div class="checkout-setup-hint__icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                            </div>
                            <div class="checkout-setup-hint__body">
                                <p class="checkout-setup-hint__title">{{ __('checkout.complete_before_payment_title') }}</p>
                                <p class="checkout-setup-hint__text" x-text="checkoutSetupHint()"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Payment: Moyasar — only after phone verified and all checkout steps complete --}}
                    <div
                        x-show="showPaymentSection()"
                        x-cloak
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="mt-6 rounded-md border border-gray-200 bg-white p-5"
                        x-ref="paymentCard"
                    >
                        <h3 class="mb-2 text-2xl font-semibold md:text-2xl">{{ __('Payment') }}</h3>
                        <p class="mb-4 text-sm text-gray-600">{{ __('payment.pay_with_moyasar') }}</p>

                        <div class="mb-3 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50/80 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ __('payment.secure_checkout') }}</p>
                                <p class="text-xs text-gray-500">{{ __('payment.secure_note') }}</p>
                            </div>
                        </div>

                        <div x-show="moyasarError" x-cloak class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" x-text="moyasarError"></div>
                        <div
                            x-show="syncAddressErrorDisplay()"
                            x-cloak
                            class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"
                            x-text="syncAddressErrorDisplay()"
                        ></div>

                        <div class="relative rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <div id="moyasar-form-checkout" class="relative z-[1] min-h-[120px] w-full"></div>
                        </div>
                    </div>
                </div>

                {{-- Right: Plan summary (sticky on desktop ? matches Figma) --}}
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
                                        <span class="font-bold text-gray-900 whitespace-nowrap inline-flex items-baseline gap-1" dir="ltr">
                                            <span x-text="money(subtotalInclVat())">{{ number_format((float) $baseSubtotal, 2) }}</span>
                                            <span class="sar-symbol" aria-label="{{ __('currency.symbol_label') }}">&#x20C1;</span>
                                        </span>
                                    @else
                                        <span class="font-bold text-gray-900 whitespace-nowrap"><x-sar :amount="$item['price'] * $item['quantity']" /></span>
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
                                            <span class="font-bold text-gray-900 inline-flex items-baseline gap-1" dir="ltr">
                                                <span x-text="money(subtotalInclVat())">{{ number_format((float) $baseSubtotal, 2) }}</span>
                                                <span class="sar-symbol" aria-label="{{ __('currency.symbol_label') }}">&#x20C1;</span>
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm" x-show="isPlanCheckout && planSelectedAvgPerDayAmount()" x-cloak>
                                            <span class="text-gray-600">{{ __('Avg. per day') }} <span class="text-xs text-gray-400">({{ __('Incl. VAT') }})</span></span>
                                            <span class="font-semibold text-gray-800" x-text="planSelectedAvgPerDayAmount()"></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">{{ __('Delivery fees') }}</span>
                                            <span class="font-bold text-gray-900 inline-flex items-baseline gap-1" dir="ltr">
                                                <span x-text="money(deliveryFee())">{{ number_format((float) $deliveryFeeAmount, 2) }}</span>
                                                <span class="sar-symbol" aria-label="{{ __('currency.symbol_label') }}">&#x20C1;</span>
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between" x-show="discount > 0" x-cloak>
                                            <span class="text-green-600">{{ __('Discount') }}</span>
                                            <span class="font-bold text-green-600 inline-flex items-baseline gap-1" dir="ltr">
                                                <span>-<span x-text="money(discount)">0.00</span></span>
                                                <span class="sar-symbol" aria-label="{{ __('currency.symbol_label') }}">&#x20C1;</span>
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm text-gray-400">
                                            <span>{{ __('VAT included') }} ({{ (int)(\App\Models\Settings\Setting::getValue('vat_rate', 15)) }}%)</span>
                                            <span class="inline-flex items-baseline gap-1" dir="ltr">
                                                <span x-text="money(vatAmount())">0.00</span>
                                                <span class="sar-symbol" aria-label="{{ __('currency.symbol_label') }}">&#x20C1;</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Total --}}
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-semibold md:text-xl">{{ __('Total') }} <span class="text-xs font-normal text-gray-500">({{ __('Incl. VAT') }})</span></span>
                                <span class="text-lg font-semibold text-green-600 md:text-xl inline-flex items-baseline gap-1" dir="ltr">
                                    <span x-text="money(total())">{{ number_format((float) ($baseSubtotal + $deliveryFeeAmount), 2) }}</span>
                                    <span class="sar-symbol" aria-label="{{ __('currency.symbol_label') }}">&#x20C1;</span>
                                </span>
                            </div>

                            {{-- Proceed to Payment Button --}}
                            <div class="pt-2">
                                <button type="submit" class="btn btn--primary btn--md w-full"
                                        :disabled="!phoneVerified || !canProceedToPayment()">
                                    {{ __('payment.proceed') }} —
                                    <span class="inline-flex items-baseline gap-1" dir="ltr">
                                        <span x-text="money(total())">{{ number_format((float) ($baseSubtotal + $deliveryFeeAmount), 2) }}</span>
                                        <span class="sar-symbol" aria-label="{{ __('currency.symbol_label') }}">&#x20C1;</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('partials.checkout-auth-modal')

            @if(!config('services.external_api.use_new_auth_flow', false))
            {{-- -- OTP Verification Modal (teleported to body) ---- --}}
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
                        <p class="otp-modal__phone" dir="ltr" x-text="displayPhone()"></p>

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
                                <span x-text="otpResendLabel()"></span>
                            </button>
                        </p>
                    </div>
                </div>
            </template>
            @endif

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
    /* --- Smooth Page Animations --------------------- */
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

    /* Phone +966 group: global .form-input-action__btn is absolute ? avoid overlap with composite field */
    .checkout-page .checkout-phone-row.form-input-action {
        position: relative;
        align-items: stretch;
    }
    .checkout-page .checkout-phone-row .form-input-action__btn,
    .checkout-page .checkout-phone-row > span.form-input-action__btn {
        position: relative !important;
        inset: auto !important;
        flex-shrink: 0;
        align-self: stretch;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        min-width: 4.5rem;
        border-radius: 0.375rem;
        border: 1px solid rgb(209 213 219);
        background: #fff;
    }
    .checkout-page .checkout-phone-row .checkout-phone-input-group {
        min-width: 0;
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

    /* --- Date Picker Input --------------------------- */
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

    /* --- Flatpickr Theme Override -------------------- */
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

    /* Only hide cloaked nodes inside checkout ? avoids stuck hidden UI if Alpine loads late */
    .checkout-page [x-cloak] { display: none !important; }

    /* --- OTP Modal ----------------------------------- */
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

    .checkout-verify-gate {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.15rem 1.25rem;
        border-radius: 14px;
        border: 2px dashed rgba(39, 159, 249, 0.35);
        background: linear-gradient(135deg, #f0f9ff 0%, #eff6ff 100%);
        box-shadow: 0 4px 18px rgba(39, 159, 249, 0.08);
    }
    .checkout-verify-gate__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        flex-shrink: 0;
        border-radius: 999px;
        background: #fff;
        color: #279ff9;
        border: 1px solid rgba(39, 159, 249, 0.2);
        box-shadow: 0 2px 8px rgba(39, 159, 249, 0.12);
    }
    .checkout-verify-gate__icon svg {
        width: 1.35rem;
        height: 1.35rem;
    }
    .checkout-verify-gate__step {
        margin: 0 0 0.25rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #279ff9;
    }
    .checkout-verify-gate__title {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 600;
        line-height: 1.55;
        color: #1e3a5f;
    }

    .checkout-coupon-feedback {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 0.65rem 0.85rem;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.45;
    }
    .checkout-coupon-feedback--success {
        border: 1px solid rgba(34, 197, 94, 0.35);
        background: #f0fdf4;
        color: #166534;
    }
    .checkout-coupon-feedback--error {
        border: 1px solid rgba(239, 68, 68, 0.3);
        background: #fef2f2;
        color: #991b1b;
    }

    .checkout-setup-hint {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        padding: 1rem 1.1rem;
        border-radius: 12px;
        border: 1px solid rgba(245, 158, 11, 0.35);
        background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
        box-shadow: 0 1px 2px rgba(180, 83, 9, 0.06);
    }
    .checkout-setup-hint__icon {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 9999px;
        background: rgba(251, 191, 36, 0.25);
        color: #b45309;
    }
    .checkout-setup-hint__icon svg {
        width: 1.25rem;
        height: 1.25rem;
    }
    .checkout-setup-hint__title {
        margin: 0 0 0.25rem;
        font-size: 0.875rem;
        font-weight: 700;
        line-height: 1.4;
        color: #78350f;
    }
    .checkout-setup-hint__text {
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.55;
        color: #92400e;
    }

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/moyasar-payment-form/dist/moyasar.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@if($locale === 'ar')
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
@endif
<script>
    (function () {
        window.dwSaudiPhoneDigits = function (input) {
            var d = String(input || '').replace(/\D/g, '');
            if (d.indexOf('966') === 0) {
                d = d.slice(3);
            }
            if (d.charAt(0) === '0') {
                d = d.slice(1);
            }
            return d.slice(-9);
        };
        window.dwSaudiPhone966 = function (input) {
            var nine = window.dwSaudiPhoneDigits(input);
            if (nine.length !== 9 || nine.charAt(0) !== '5') {
                return '';
            }
            return '966' + nine;
        };
        window.dwSaudiDisplayPhone = function (input) {
            var f = window.dwSaudiPhone966(input);
            return f ? ('+' + f) : '';
        };
    })();

    function checkoutPage() {
        return {
            // Reactive state
            baseSubtotal: @json((float) $baseSubtotal),
            isPlanCheckout: @json($hasPlanItems),
            duration: @json($hasPlanItems ? 'once' : old('duration', 'monthly')),
            selectedPlanDurationId: @json((string) ($preferredPlanDurationId ?? '')),
            planDurationPrices: @json($planDurationPrices ?? []),
            deliveryType: @json(old('delivery_type', 'home')),
            selectedPlanId: @json((int) (collect($cart)->first()['id'] ?? 0)),
            selectedSubscriptionPlanId: @json((int) data_get(collect($cart)->first(), 'options.subscription_plan_id', 0)),
            selectedPlanCaloryId: @json((int) data_get(collect($cart)->first(), 'options.calorie_id', 0)),
            hasCartItems: @json(!empty($cart)),
            startDate: @json($defaultStartDate),
            minStartDate: @json($minStartDate),
            scheduleReady: @json($scheduleReady ?? false),
            startDateNotice: '',
            startDateTouched: false,
            coverageOk: @json(old('delivery_type', 'home') === 'pickup' ? true : null),
            coverageMessage: '',
            inlineMapLat: '',
            inlineMapLng: '',
            inlineMapDistrictId: '',
            _minDatePollTimer: null,
            _skipStartDateTouch: false,
            _moyasarFingerprint: '',
            _moyasarRequestId: 0,
            _moyasarSessionFailed: false,
            _moyasarStartDateRetry: false,
            _paymentBootstrapInFlight: false,
            vatRate: @json((float) $vatRate),
            deliveryFeeAmount: @json((float) $deliveryFeeAmount),
            discount: 0,
            addressStreet: @json(old('street', '')),
            buildingNotes: @json(old('building', '')),
            customerName: @json(old('name', '')),
            showNameField: false,
            isContinueUser: false,
            savedAddresses: [],
            selectedAddressId: null,
            addingNewAddress: false,
            savingNewAddress: false,
            newAddressError: '',
            deletingAddressId: null,
            districtDeliveryTimes: [],
            loadingDistrictTimes: false,
            selectedRegionDurationId: '',
            editingDeliveryTimeAddressId: null,
            _districtTimesRequestId: 0,
            sarSymbol: '\u20C1',
            uiLabels: {
                cancel: @json(__('Cancel')),
                addNewAddress: @json(__('Add new address')),
                resendIn: @json(__('Resend in')),
                resend: @json(__('Resend')),
            },
            addressPhoneLocal: @json($initialAddressPhoneLocal),
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
            _customerStateGeneration: 0,

            // Zone state
            selectedZoneId: @json(old('zone_id', '')),
            zones: @json($zones),

            checkoutProgramId: @json((int) ($checkoutProgramId ?? 0)),
            /** Matches cart line duration_days ? used when API duration_id differs from list ids */
            cartDurationDaysHint: @json((int) ($planDurationDays ?? 0)),
            cartDurationFallback: @json($cartDurationFallback ?? null),
            durationsLoading: @json($hasPlanItems && empty($planDurations) && empty($cartDurationFallback)),
            // Plan durations (filled from server, client fetch, or cart fallback)
            planDurations: @json($planDurations ?? []),
            durationScrollAtStart: true,
            durationScrollAtEnd: false,
            _durationScrollRaf: null,

            // Branch pickup state
            selectedBranchId: @json(old('branch_id', '')),
            branches: [],
            branchesLoading: true,
            pickupPhase: @json(old('branch_id') && old('delivery_type') === 'pickup' ? 'done' : 'cta'),
            branchSearch: '',

            // Duration multiplier map from backend
            durationMultipliers: @json($durationMultipliers),

            // Phone / OTP state (local = 9 digits after +966)
            phoneLocal: @json($initialPhoneLocal),
            phoneVerified: @json($phoneVerifiedFromSession ?? false),
            otpModalOpen: false,
            otpSent: false,
            otpDigits: ['', '', '', ''],
            otpLoading: false,
            otpMessage: '',
            otpMessageType: '',
            otpCooldown: 0,

            // Coupon state
            couponCode: @json(old('coupon', '')),
            couponApplied: false,
            couponLoading: false,
            couponMessage: '',

            moyasarError: '',
            /** Set when POST /checkout/sync-address fails (silent sync or user-visible). */
            syncAddressError: '',
            _moyasarTimer: null,

            getCsrfToken() {
                const fromMeta = document.querySelector('meta[name="csrf-token"]')?.content;
                if (fromMeta) {
                    return fromMeta;
                }
                const fromForm = this.$refs.checkoutForm?.querySelector('input[name="_token"]')?.value;
                if (fromForm) {
                    return fromForm;
                }
                return '{{ csrf_token() }}';
            },

            syncAddressErrorDisplay() {
                const msg = String(this.syncAddressError || '').trim();
                if (! msg) {
                    return '';
                }
                const areaMsg = @json(__('checkout.area_not_served'));
                const blockerMsg = @json(__('checkout.confirm_saved_address_before_payment'));
                if (msg === areaMsg || msg === blockerMsg) {
                    return '';
                }

                return msg;
            },

            promptPhoneVerificationForDelivery() {
                if (this.phoneVerified) {
                    return;
                }
                const phoneSection = this.$refs.checkoutForm?.querySelector('[data-checkout-phone-section]');
                if (phoneSection) {
                    phoneSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                if (this.fullPhone966()) {
                    this.openOtpModal();
                }
            },

            guardPhoneVerified() {
                if (this.phoneVerified) {
                    return true;
                }
                this.promptPhoneVerificationForDelivery();

                return false;
            },

            getXsrfTokenFromCookie() {
                const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
                return m ? decodeURIComponent(m[1]) : '';
            },

            buildCsrfHeaders() {
                const csrf = this.getCsrfToken();
                const xsrf = this.getXsrfTokenFromCookie();
                const headers = {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                };
                if (xsrf) {
                    headers['X-XSRF-TOKEN'] = xsrf;
                }
                return { headers, csrf };
            },

            fullPhone966() {
                return typeof window.dwSaudiPhone966 === 'function' ? window.dwSaudiPhone966(this.phoneLocal) : '';
            },

            displayPhone() {
                const f = this.fullPhone966();
                return f ? ('+' + f) : '';
            },

            addressPhone966() {
                return typeof window.dwSaudiPhone966 === 'function' ? window.dwSaudiPhone966(this.addressPhoneLocal) : '';
            },

            // --- PRICES FROM API ARE VAT-INCLUSIVE (like mobile app) ---
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
                    return String(d.days) + ' ' + @json(__('days'));
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

                    return '\u20C1 ' + (Number.isInteger(n) ? String(n) : n.toFixed(2));
                }
                const ppd = parseFloat(d.price_per_day) || 0;
                if (ppd > 0) {
                    return '\u20C1 ' + ppd.toFixed(2) + ' / ' + @json(__('day'));
                }

                return '';
            },

            money(value) {
                const n = Number(value);
                if (! Number.isFinite(n)) {
                    return '0.00';
                }

                return n.toFixed(2);
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

            durationStrikeLine(d) {
                return this.sarSymbol + ' ' + this.durationPlanListTotalStr(d);
            },

            durationTotalLine(d) {
                return this.sarSymbol + ' ' + this.durationPlanEffectiveTotalStr(d);
            },

            newAddressToggleLabel() {
                return this.addingNewAddress ? this.uiLabels.cancel : this.uiLabels.addNewAddress;
            },

            otpResendLabel() {
                return this.otpCooldown > 0
                    ? this.uiLabels.resendIn + ' ' + this.otpCooldown + 's'
                    : this.uiLabels.resend;
            },

            durationPlanAvgLine(d) {
                const days = parseInt(d.days, 10) || 0;
                const e = this.durationPlanEffectiveTotal(d);
                if (days <= 0 || e <= 0) {
                    return '';
                }
                const avg = Math.round((e / days) * 100) / 100;
                const ns = Number.isInteger(avg) ? String(avg) : avg.toFixed(2);

                return @json(__('SAR')) + ' ' + ns + ' - ' + @json(__('per day'));
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

                return @json(__('SAR')) + ' ' + (Number.isInteger(avg) ? String(avg) : avg.toFixed(2));
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
                try {
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
                    this.applyDurationMinimumStartDate();
                    this.$nextTick(() => {
                        this.refreshDurationScrollState();
                        this.scrollDurationToSelected();
                    });
                } finally {
                    this.durationsLoading = false;
                }
            },

            durationViewportScrollLeft(vp) {
                const isRtl = document.documentElement.dir === 'rtl';

                return isRtl ? Math.abs(vp.scrollLeft) : vp.scrollLeft;
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
                const pos = this.durationViewportScrollLeft(vp);
                this.durationScrollAtStart = pos <= 8;
                this.durationScrollAtEnd = max <= 8 || pos >= max - 8;
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
                    return `${daysNum} ${@json(__('days'))}`;
                }
                if (labelStr && daysNum > 0) {
                    return labelStr + ` - ${daysNum} ${@json(__('days'))}`;
                }

                return labelStr;
            },

            composeBuildingNotes() {
                const p = [];
                const b = (this.deliveryBuilding || '').trim();
                const f = (this.deliveryFloor || '').trim();
                const d = (this.deliveryDoor || '').trim();
                if (b) {
                    p.push(@json(__('Building')) + ': ' + b);
                }
                if (f) {
                    p.push(@json(__('Floor')) + ': ' + f);
                }
                if (d) {
                    p.push(@json(__('Door')) + ': ' + d);
                }
                this.buildingNotes = p.join(', ');
            },

            resolveZoneFromDistrictId(districtId) {
                if (! districtId || ! Array.isArray(this.zones)) {
                    return '';
                }
                const match = this.zones.find((z) => {
                    const districtList = z.districts || z.district_ids || [];

                    return districtList.some((d) => {
                        const id = typeof d === 'object' ? (d.id ?? d.district_id) : d;

                        return String(id) === String(districtId);
                    });
                });

                return match ? String(match.id) : '';
            },

            buildSyncAddressFormData() {
                const form = this.$refs.checkoutForm;
                const payload = new FormData();
                if (! form) {
                    return payload;
                }
                const fd = new FormData(form);
                ['delivery_lat', 'delivery_lng', 'delivery_district_id', 'delivery_description',
                    'delivery_kind', 'delivery_title', 'delivery_pickup_type', 'building', 'zone_id', 'street']
                    .forEach((key) => {
                        if (fd.has(key)) {
                            payload.append(key, fd.get(key));
                        }
                    });
                if (payload.has('delivery_kind')) {
                    payload.append('delivery_type', payload.get('delivery_kind'));
                    payload.delete('delivery_kind');
                } else {
                    payload.append('delivery_type', 'home');
                }
                const phoneForAddress = this.addressPhone966() || this.fullPhone966();
                if (phoneForAddress) {
                    payload.set('phone', phoneForAddress);
                }
                if (this.selectedRegionDurationId) {
                    payload.set('region_duration_id', String(this.selectedRegionDurationId));
                }

                return payload;
            },

            async syncExternalAddress() {
                if (this.deliveryType !== 'home') {
                    return;
                }
                if (this.selectedAddressId) {
                    this.syncAddressError = '';

                    return;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return;
                }
                if (! this.fullPhone966()) {
                    this.syncAddressError = @json(__('checkout.address_sync_needs_phone'));

                    return;
                }
                const payload = this.buildSyncAddressFormData();
                if (! payload.get('delivery_lat') || ! payload.get('delivery_lng')) {
                    return;
                }
                if (! payload.get('delivery_district_id')) {
                    return;
                }
                try {
                    const res = await fetch('{{ route('checkout.sync-address') }}', {
                        method: 'POST',
                        body: payload,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    const ok = res.ok && (data.success === true || data.skipped === true);
                    if (ok) {
                        this.syncAddressError = '';
                        if (Array.isArray(data.addresses)) {
                            this.applyCheckoutAddresses(data.addresses);
                        }
                        if (data.data && data.data.id) {
                            this.selectedAddressId = String(data.data.id);
                            const existingIdx = this.savedAddresses.findIndex(a => String(a.id) === String(data.data.id));
                            if (existingIdx >= 0) {
                                this.savedAddresses.splice(existingIdx, 1, data.data);
                            } else {
                                this.savedAddresses = [data.data, ...this.savedAddresses];
                            }
                            this.applySavedAddress(data.data);
                        } else if (! Array.isArray(data.addresses)) {
                            await this.refreshCustomerFromServer();
                        }
                        if (this.selectedAddressId) {
                            this.resetPaymentSession();
                            this.queueMoyasarBootstrap();
                        }
                    } else {
                        const errs = data && data.errors && typeof data.errors === 'object'
                            ? Object.values(data.errors).flat().filter(Boolean)
                            : [];
                        this.syncAddressError = errs[0] || data.message || @json(__('checkout.address_sync_failed'));
                    }
                } catch (e) {
                    this.syncAddressError = @json(__('checkout.address_sync_failed'));
                }
            },

            handleAddressFromMap(event) {
                const d = event.detail || {};
                this.syncAddressError = '';
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
                const fromSaved = (d.id != null && String(d.id).trim() !== '') || !!this.selectedAddressId;
                if (! fromSaved) {
                    this.addressConfirmedForSync = false;
                }
            },

            handleMapAddressDraft(event) {
                const d = event.detail || {};
                if (d.description) {
                    this.addressStreet = d.description;
                }
            },

            handleCoverageChanged(event) {
                const d = event.detail || {};
                if (d.ok) {
                    this.coverageOk = true;
                    this.coverageMessage = '';
                } else if (d.userInitiated) {
                    this.coverageOk = false;
                    this.coverageMessage = @json(__('checkout.area_not_served'));
                }
                this.inlineMapLat = d.lat != null && d.lat !== '' ? String(d.lat) : '';
                this.inlineMapLng = d.lng != null && d.lng !== '' ? String(d.lng) : '';
                this.inlineMapDistrictId = d.districtId ? String(d.districtId) : '';
                if (d.districtId) {
                    const zoneId = this.resolveZoneFromDistrictId(d.districtId);
                    if (zoneId) {
                        this.selectedZoneId = zoneId;
                        const form = this.$refs.checkoutForm;
                        const zoneEl = form?.querySelector('select[name="zone_id"]');
                        if (zoneEl) {
                            zoneEl.value = zoneId;
                        }
                    }
                }
                if (! d.ok) {
                    this.addressConfirmedForSync = false;
                    this.selectedAddressId = null;
                    this.districtDeliveryTimes = [];
                    this.selectedRegionDurationId = '';
                } else if (d.districtId) {
                    this.loadDistrictDeliveryTimes(String(d.districtId));
                }
            },

            regionDurationLabel(row) {
                if (! row || typeof row !== 'object') {
                    return '';
                }
                const time = String(row.time || '').trim();
                const durationText = String(row.durationText || row.duration_text || '').trim();
                if (time && durationText) {
                    return durationText + ' — ' + time;
                }

                return time || durationText || String(row.duration || row.id || '');
            },

            parseAddressDurations(addr) {
                const raw = addr?.district?.durations;
                if (! Array.isArray(raw) || raw.length === 0) {
                    return [];
                }

                return raw.map((row) => ({
                    id: Number(row.id || 0),
                    label: row.label || this.regionDurationLabel(row),
                    time: row.time || '',
                    durationText: row.durationText || row.duration_text || '',
                })).filter((slot) => slot.id > 0);
            },

            addressDeliveryTimes(addr) {
                const fromAddress = this.parseAddressDurations(addr);
                if (fromAddress.length > 0) {
                    return fromAddress;
                }
                if (addr && String(addr.id) === String(this.selectedAddressId) && this.districtDeliveryTimes.length > 0) {
                    return this.districtDeliveryTimes;
                }

                return [];
            },

            deliveryTimeLabelForAddress(addr) {
                const slots = this.addressDeliveryTimes(addr);
                const selected = slots.find((slot) => String(slot.id) === String(this.selectedRegionDurationId));

                return selected?.label || selected?.time || selected?.durationText || @json(__('checkout.select_delivery_time'));
            },

            syncSelectedRegionDurationFromAddress(addr) {
                const slots = this.addressDeliveryTimes(addr);
                if (slots.length === 1) {
                    this.selectedRegionDurationId = String(slots[0].id);

                    return;
                }
                if (this.selectedRegionDurationId && slots.some((slot) => String(slot.id) === String(this.selectedRegionDurationId))) {
                    return;
                }
                this.selectedRegionDurationId = slots.length > 0 ? String(slots[0].id) : '';
            },

            async loadDistrictDeliveryTimes(districtId, addressId = null) {
                const normalizedDistrictId = String(districtId || '').trim();
                if (! normalizedDistrictId) {
                    this.districtDeliveryTimes = [];
                    this.selectedRegionDurationId = '';

                    return;
                }
                const requestId = ++this._districtTimesRequestId;
                this.loadingDistrictTimes = true;
                try {
                    let url = '{{ route('checkout.district-durations') }}?district_id=' + encodeURIComponent(normalizedDistrictId);
                    if (addressId) {
                        url += '&address_id=' + encodeURIComponent(String(addressId));
                    }
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (requestId !== this._districtTimesRequestId) {
                        return;
                    }
                    this.districtDeliveryTimes = Array.isArray(data.durations) ? data.durations : [];
                    if (this.districtDeliveryTimes.length === 1) {
                        this.selectedRegionDurationId = String(this.districtDeliveryTimes[0].id);
                    } else if (! this.districtDeliveryTimes.some((slot) => String(slot.id) === String(this.selectedRegionDurationId))) {
                        this.selectedRegionDurationId = this.districtDeliveryTimes.length > 0
                            ? String(this.districtDeliveryTimes[0].id)
                            : '';
                    }
                } catch (e) {
                    if (requestId === this._districtTimesRequestId) {
                        this.districtDeliveryTimes = [];
                    }
                } finally {
                    if (requestId === this._districtTimesRequestId) {
                        this.loadingDistrictTimes = false;
                    }
                }
            },

            startEditingDeliveryTime(addr) {
                if (! addr || ! addr.id) {
                    return;
                }
                this.editingDeliveryTimeAddressId = String(addr.id);
                this.syncSelectedRegionDurationFromAddress(addr);
            },

            async onSavedAddressDeliveryTimeChange(addr, value) {
                this.selectedRegionDurationId = String(value || '').trim();
                if (! addr || ! addr.id || ! this.selectedRegionDurationId) {
                    return;
                }
                this.editingDeliveryTimeAddressId = String(addr.id);
                this.resetPaymentSession();
                const activated = await this.activateCheckoutAddress(addr.id, addr);
                if (activated) {
                    this.queueMoyasarBootstrap();
                }
            },

            deliveryTimeReady() {
                if (this.deliveryType !== 'home' || ! this.isPlanCheckout) {
                    return true;
                }
                const addr = this.savedAddresses.find((row) => String(row.id) === String(this.selectedAddressId));
                const slots = addr ? this.addressDeliveryTimes(addr) : this.districtDeliveryTimes;
                if (slots.length === 0) {
                    return true;
                }

                return String(this.selectedRegionDurationId || '').trim() !== '';
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

            addressIsDeliverable(addr) {
                if (! addr) {
                    return false;
                }
                if (addr.is_deliverable === false) {
                    return false;
                }
                if (addr.is_deliverable === true) {
                    return true;
                }
                const durations = addr?.district?.durations;

                return Array.isArray(durations)
                    && durations.length > 0
                    && !!(durations[0]?.id || durations[0]);
            },

            deliverableSavedAddresses() {
                return (this.savedAddresses || []).filter((addr) => addr && addr.id != null);
            },

            applyCheckoutAddresses(addresses) {
                this.savedAddresses = Array.isArray(addresses) ? addresses : [];
            },

            startAddingAddress() {
                if (! this.guardPhoneVerified()) {
                    return;
                }
                this.addingNewAddress = !this.addingNewAddress;
                this.newAddressError = '';
                if (this.addingNewAddress) {
                    this.selectedAddressId = null;
                    this.addressStreet = '';
                    this.deliveryBuilding = '';
                    this.deliveryFloor = '';
                    this.deliveryDoor = '';
                    this.buildingNotes = '';
                    this.districtDeliveryTimes = [];
                    this.selectedRegionDurationId = '';
                    this.editingDeliveryTimeAddressId = null;
                    this.deliveryType = 'home';
                    if (! (this.addressPhoneLocal || '').trim()) {
                        this.addressPhoneLocal = this.phoneLocal || '';
                    }
                    setTimeout(() => window.dispatchEvent(new CustomEvent('checkout-home-map-refresh')), 200);
                }
            },

            async saveDeliveryAddress() {
                if (! this.guardPhoneVerified()) {
                    return;
                }
                this.newAddressError = '';
                this.syncAddressError = '';
                if (! this.fullPhone966()) {
                    this.syncAddressError = @json(__('checkout.address_sync_needs_phone'));

                    return;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return;
                }
                const lat = String(this.inlineMapLat || (form.querySelector('input[name="delivery_lat"]')?.value ?? '')).trim();
                const lng = String(this.inlineMapLng || (form.querySelector('input[name="delivery_lng"]')?.value ?? '')).trim();
                const district = String(this.inlineMapDistrictId || (form.querySelector('input[name="delivery_district_id"]')?.value ?? '')).trim();
                if (! lat || ! lng) {
                    this.syncAddressError = @json(__('Please set a location on the map first.'));

                    return;
                }
                if (! district) {
                    this.coverageOk = false;
                    this.coverageMessage = @json(__('checkout.area_not_served'));

                    return;
                }
                const zoneFromDistrict = this.resolveZoneFromDistrictId(district);
                if (zoneFromDistrict) {
                    this.selectedZoneId = zoneFromDistrict;
                    const zoneEl = form.querySelector('select[name="zone_id"]');
                    if (zoneEl) {
                        zoneEl.value = zoneFromDistrict;
                    }
                }
                this.savingNewAddress = true;
                try {
                    const payload = this.buildSyncAddressFormData();
                    payload.set('delivery_lat', lat);
                    payload.set('delivery_lng', lng);
                    payload.set('delivery_district_id', district);
                    if (this.selectedZoneId) {
                        payload.set('zone_id', String(this.selectedZoneId));
                    }
                    if (this.selectedRegionDurationId) {
                        payload.set('region_duration_id', String(this.selectedRegionDurationId));
                    }
                    const res = await fetch('{{ route('checkout.sync-address') }}', {
                        method: 'POST',
                        body: payload,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (! res.ok || ! data.success) {
                        const errs = data && data.errors && typeof data.errors === 'object'
                            ? Object.values(data.errors).flat().filter(Boolean)
                            : [];
                        this.newAddressError = errs[0] || data.message || @json(__('address.save_failed'));
                        this.syncAddressError = this.newAddressError;

                        return;
                    }
                    this.syncAddressError = '';
                    this._moyasarSessionFailed = false;
                    this._moyasarFingerprint = '';
                    if (Array.isArray(data.addresses)) {
                        this.applyCheckoutAddresses(data.addresses);
                    }
                    if (data.data && data.data.id) {
                        const existingIdx = this.savedAddresses.findIndex(a => String(a.id) === String(data.data.id));
                        if (existingIdx >= 0) {
                            this.savedAddresses.splice(existingIdx, 1, data.data);
                        } else {
                            this.savedAddresses = [data.data, ...this.savedAddresses];
                        }
                        this.applySavedAddress(data.data);
                        const districtId = data.data?.district?.id ?? data.data?.district_id ?? this.inlineMapDistrictId;
                        await this.loadDistrictDeliveryTimes(districtId, data.data.id);
                        this.syncSelectedRegionDurationFromAddress(data.data);
                        if (this.selectedRegionDurationId && data.data?.id) {
                            await this.activateCheckoutAddress(data.data.id, data.data);
                        }
                    } else if (! Array.isArray(data.addresses)) {
                        await this.refreshCustomerFromServer();
                    }
                    this.addingNewAddress = false;
                    this.addressConfirmedForSync = true;
                    this.coverageOk = true;
                    this.coverageMessage = '';
                } catch (e) {
                    this.newAddressError = @json(__('An error occurred. Please try again.'));
                    this.syncAddressError = this.newAddressError;
                } finally {
                    this.savingNewAddress = false;
                }
            },

            async saveNewAddress() {
                await this.saveDeliveryAddress();
            },

            applySavedAddress(addr) {
                if (! addr || this.deliveryType !== 'home') {
                    return;
                }
                this.syncAddressError = '';
                this.coverageOk = true;
                this.coverageMessage = '';
                this.selectedAddressId = addr.id ?? null;
                this.addingNewAddress = false;
                this.newAddressError = '';
                this.addressConfirmedForSync = true;
                this.moyasarError = '';
                const districtId = addr.district?.id ?? addr.district_id;
                const inlineTimes = this.parseAddressDurations(addr);
                if (inlineTimes.length > 0) {
                    this.districtDeliveryTimes = inlineTimes;
                    this.syncSelectedRegionDurationFromAddress(addr);
                } else if (districtId) {
                    this.inlineMapDistrictId = String(districtId);
                    this.loadDistrictDeliveryTimes(districtId, addr.id).then(() => {
                        this.syncSelectedRegionDurationFromAddress(addr);
                    });
                } else {
                    this.districtDeliveryTimes = [];
                    this.selectedRegionDurationId = '';
                }
                if (districtId) {
                    this.inlineMapDistrictId = String(districtId);
                }
                // Resolve zone/city id across every known field shape the external
                // API might return, then fall back to matching the district against
                // the locally known zones list. Without a valid zone the server
                // can't compute the correct delivery fee and the Moyasar session
                // fails silently ? so we *must* have one populated.
                let cityId = addr.city?.id
                    ?? addr.city_id
                    ?? addr.zone_id
                    ?? addr.zone?.id
                    ?? addr.district?.city_id
                    ?? addr.district?.zone_id
                    ?? addr.district?.city?.id
                    ?? addr.district?.zone?.id
                    ?? '';
                if (! cityId && districtId && Array.isArray(this.zones)) {
                    const match = this.zones.find((z) => {
                        const districtList = z.districts || z.district_ids || [];
                        return districtList.some((d) => {
                            const id = typeof d === 'object' ? (d.id ?? d.district_id) : d;
                            return String(id) === String(districtId);
                        });
                    });
                    if (match) {
                        cityId = match.id;
                    }
                }
                // Last-resort fallback: infer zone from address text/title
                // when API does not return city/zone IDs in saved addresses.
                if (! cityId && Array.isArray(this.zones) && this.zones.length > 0) {
                    const text = String(
                        addr.description
                        || addr.line1
                        || addr.title
                        || addr.address
                        || ''
                    ).toLowerCase();
                    if (text) {
                        const matchByName = this.zones.find((z) => {
                            let zoneName = z?.name ?? '';
                            if (zoneName && typeof zoneName === 'object') {
                                zoneName = zoneName['{{ app()->getLocale() }}'] || zoneName.en || Object.values(zoneName)[0] || '';
                            }
                            zoneName = String(zoneName || '').toLowerCase();
                            return zoneName && text.includes(zoneName);
                        });
                        if (matchByName) {
                            cityId = matchByName.id;
                        }
                    }
                }
                if (cityId) {
                    this.selectedZoneId = String(cityId);
                }
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
                        id: addr.id ?? null,
                        latitude: addr.latitude,
                        longitude: addr.longitude,
                        description: addr.description || '',
                        district_id: districtId,
                        type: addr.type || 'residential',
                        title: addr.title || '',
                        pickup_type: pickup,
                    },
                }));
                window.dispatchEvent(new CustomEvent('address-selected', {
                    detail: {
                        id: addr.id ?? null,
                        latitude: addr.latitude,
                        longitude: addr.longitude,
                        city_id: cityId || null,
                        line1: addr.line1 || addr.description || '',
                        description: addr.description || '',
                        district_id: districtId || null,
                        building_num: addr.building_num ?? this.deliveryBuilding,
                        floor: addr.floor ?? this.deliveryFloor,
                        door: addr.door ?? this.deliveryDoor,
                    },
                }));
                if (addr.description) {
                    this.addressStreet = addr.description;
                }
                this.$nextTick(() => {
                    const form = this.$refs.checkoutForm;
                    if (! form) {
                        return;
                    }
                    const setHidden = (name, value) => {
                        if (value == null || value === '') {
                            return;
                        }
                        const el = form.querySelector(`input[name="${name}"]`);
                        if (el) {
                            el.value = String(value);
                        }
                    };
                    setHidden('delivery_lat', addr.latitude);
                    setHidden('delivery_lng', addr.longitude);
                    setHidden('delivery_district_id', districtId);
                    setHidden('delivery_description', addr.description || addr.line1 || '');
                    if (cityId) {
                        const zoneEl = form.querySelector('select[name="zone_id"]');
                        if (zoneEl) {
                            zoneEl.value = String(cityId);
                        }
                    }
                });
            },

            resetPaymentSession(clearError = true) {
                this._moyasarSessionFailed = false;
                this._moyasarFingerprint = '';
                ++this._moyasarRequestId;
                clearTimeout(this._moyasarTimer);
                if (clearError) {
                    this.moyasarError = '';
                }
            },

            clearMoyasarWidget() {
                const el = document.getElementById('moyasar-form-checkout');
                if (el) {
                    el.innerHTML = '';
                }
            },

            queueMoyasarBootstrap() {
                if (! this.phoneVerified || ! this.canProceedToPayment()) {
                    this.clearMoyasarWidget();

                    return;
                }
                clearTimeout(this._moyasarTimer);
                this._moyasarTimer = setTimeout(() => this.runPaymentBootstrap(), 450);
            },

            async runPaymentBootstrap() {
                if (this._paymentBootstrapInFlight || this._moyasarSessionFailed) {
                    return;
                }
                if (! this.phoneVerified || ! this.canProceedToPayment()) {
                    this.clearMoyasarWidget();

                    return;
                }
                this._paymentBootstrapInFlight = true;
                try {
                    if (this.isPlanCheckout) {
                        await this.refreshMinStartDateFromApi();
                    }
                    if (! this.canProceedToPayment() || this._moyasarSessionFailed) {
                        this.clearMoyasarWidget();

                        return;
                    }
                    await this.bootstrapMoyasar();
                } finally {
                    this._paymentBootstrapInFlight = false;
                }
            },

            addressIsCheckoutReady(addr) {
                if (! addr) {
                    return false;
                }
                const active = addr.is_active === true || addr.isActive === true;
                const days = Array.isArray(addr.days) ? addr.days : [];

                return active && days.length > 0;
            },

            async activateCheckoutAddress(addressId, addr = null) {
                if (! addressId) {
                    return false;
                }
                try {
                    const params = { address_id: String(addressId) };
                    if (this.selectedRegionDurationId) {
                        params.region_duration_id = String(this.selectedRegionDurationId);
                    }
                    const res = await fetch('{{ route('checkout.select-address') }}', {
                        method: 'POST',
                        body: new URLSearchParams(params),
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        if (data.data && data.data.id) {
                            const idx = this.savedAddresses.findIndex((a) => String(a.id) === String(data.data.id));
                            if (idx >= 0) {
                                this.savedAddresses.splice(idx, 1, data.data);
                            }
                        }
                        if (Array.isArray(data.addresses)) {
                            this.applyCheckoutAddresses(data.addresses);
                        }
                        this.syncAddressError = '';

                        return true;
                    }
                    this.syncAddressError = String(data.message || '').trim();

                    return false;
                } catch (e) {
                    this.syncAddressError = @json(__('checkout.address_sync_failed'));

                    return false;
                }
            },

            async autoSelectSavedAddressIfNeeded() {
                if (this.deliveryType !== 'home' || this.selectedAddressId) {
                    return false;
                }
                const saved = this.deliverableSavedAddresses();
                if (saved.length === 0) {
                    return false;
                }
                const preferred = saved.find((a) => this.addressIsCheckoutReady(a));
                if (! preferred) {
                    return false;
                }
                await this.selectSavedAddress(preferred);

                return true;
            },

            async selectSavedAddress(addr) {
                if (! this.guardPhoneVerified()) {
                    return;
                }
                if (! addr || this.deliveryType !== 'home') {
                    return;
                }
                this.resetPaymentSession();
                this.applySavedAddress(addr);
                const districtId = addr.district?.id ?? addr.district_id;
                await this.loadDistrictDeliveryTimes(districtId, addr.id);
                this.syncSelectedRegionDurationFromAddress(addr);
                this.editingDeliveryTimeAddressId = null;
                const activated = await this.activateCheckoutAddress(addr.id, addr);
                if (! activated) {
                    this.clearMoyasarWidget();

                    return;
                }
                this.queueMoyasarBootstrap();
                this.$nextTick(() => {
                    this.$refs.paymentCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },

            async deleteSavedAddress(addr) {
                if (! addr || ! addr.id) {
                    return;
                }

                const confirmResult = typeof Swal !== 'undefined'
                    ? await Swal.fire({
                        title: @json(__('checkout.delete_saved_address_confirm')),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: @json(__('Delete')),
                        cancelButtonText: @json(__('Cancel')),
                        reverseButtons: true,
                        focusCancel: true,
                    })
                    : { isConfirmed: window.confirm(@json(__('checkout.delete_saved_address_confirm'))) };

                if (! confirmResult.isConfirmed) {
                    return;
                }

                const addressId = String(addr.id);
                const previousAddresses = [...this.savedAddresses];
                this.savedAddresses = this.savedAddresses.filter(a => String(a.id) !== addressId);
                this.deletingAddressId = addressId;
                this.syncAddressError = '';

                if (String(this.selectedAddressId) === addressId) {
                    this.selectedAddressId = null;
                    this.addressConfirmedForSync = false;
                    this.resetPaymentSession(true);
                    this.clearMoyasarWidget();
                }

                try {
                    const deleteUrl = '{{ url('/checkout/addresses') }}/' + encodeURIComponent(addressId);
                    const res = await fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (! res.ok || ! data.success) {
                        this.savedAddresses = previousAddresses;
                        const message = data.message || @json(__('address.delete_failed'));
                        this.syncAddressError = message;
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: message, confirmButtonText: 'OK' });
                        }

                        return;
                    }

                    this._customerStateGeneration++;
                    if (Array.isArray(data.addresses)) {
                        this.applyCheckoutAddresses(data.addresses);
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: data.message || @json(__('address.deleted')),
                            timer: 1800,
                            showConfirmButton: false,
                        });
                    }
                } catch (e) {
                    this.savedAddresses = previousAddresses;
                    this.syncAddressError = @json(__('address.delete_failed'));
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: @json(__('address.delete_failed')), confirmButtonText: 'OK' });
                    }
                } finally {
                    this.deletingAddressId = null;
                }
            },

            async refreshCustomerFromServer() {
                const generation = ++this._customerStateGeneration;
                try {
                    const res = await fetch('{{ route('checkout.customer-state') }}', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const d = await res.json().catch(() => ({}));
                    if (generation !== this._customerStateGeneration) {
                        return;
                    }
                    if (! d.success) {
                        return;
                    }
                    this.applyCheckoutAddresses(d.addresses || []);
                    if (d.profile && d.profile.name && ! (this.customerName || '').trim()) {
                        this.customerName = String(d.profile.name);
                    }
                    // If already verified (page reload), determine name field visibility
                    if (this.phoneVerified) {
                        const hasName = !!(d.profile && d.profile.name);
                        const isNewUser = !!(d.is_continue);
                        this.isContinueUser = isNewUser;
                        this.showNameField = isNewUser || !hasName;
                        // Keep selection manual: user confirms address with "اختيار العنوان" button.
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
                if (! this.guardPhoneVerified()) {
                    return;
                }
                this.pickupPhase = 'list';
                this.branchSearch = '';
            },

            selectBranch(id) {
                if (! this.guardPhoneVerified()) {
                    return;
                }
                this.resetPaymentSession(true);
                this.syncAddressError = '';
                this.selectedBranchId = String(id);
                this.pickupPhase = 'done';
                this.queueMoyasarBootstrap();
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

            selectedDurationValue() {
                if (this.isPlanCheckout) {
                    return this.selectedPlanDurationId ? String(this.selectedPlanDurationId) : '';
                }
                return this.duration ? String(this.duration) : '';
            },

            hasStartDate() {
                if (! this.isPlanCheckout) {
                    return true;
                }
                const localValue = String(this.startDate || '').trim();
                if (localValue.length > 0) {
                    return true;
                }
                const inputValue = String(document.getElementById('start_date_input')?.value || '').trim();
                if (inputValue.length > 0) {
                    this.startDate = inputValue;
                    return true;
                }
                return false;
            },

            startDateValid() {
                if (! this.isPlanCheckout) {
                    return true;
                }
                if (! this.phoneVerified || ! this.minStartDate) {
                    return false;
                }
                const d = String(this.startDate || document.getElementById('start_date_input')?.value || '').trim().slice(0, 10);

                return d !== '' && d >= this.minStartDate;
            },

            coverageReady() {
                if (this.deliveryType === 'pickup') {
                    return true;
                }

                return !!this.selectedAddressId;
            },

            inlineAddressCanSave() {
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return false;
                }
                const lat = String(this.inlineMapLat || (form.querySelector('input[name="delivery_lat"]')?.value ?? '')).trim();
                const lng = String(this.inlineMapLng || (form.querySelector('input[name="delivery_lng"]')?.value ?? '')).trim();
                const district = String(this.inlineMapDistrictId || (form.querySelector('input[name="delivery_district_id"]')?.value ?? '')).trim();
                const timesOk = this.districtDeliveryTimes.length === 0
                    || String(this.selectedRegionDurationId || '').trim() !== '';

                return lat !== '' && lng !== '' && district !== '' && this.coverageOk !== false && timesOk;
            },

            /** Home delivery: map pin confirmed + city + district (no saved-address id required). */
            inlineHomeAddressReady() {
                if (this.deliveryType !== 'home') {
                    return false;
                }
                if (! this.addressConfirmedForSync) {
                    return false;
                }
                if (this.syncAddressError) {
                    return false;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return false;
                }
                const lat = String(this.inlineMapLat || (form.querySelector('input[name="delivery_lat"]')?.value ?? '')).trim();
                const lng = String(this.inlineMapLng || (form.querySelector('input[name="delivery_lng"]')?.value ?? '')).trim();
                const district = String(this.inlineMapDistrictId || (form.querySelector('input[name="delivery_district_id"]')?.value ?? '')).trim();
                const zone = String(this.selectedZoneId || form.querySelector('select[name="zone_id"]')?.value || '').trim();

                return lat !== '' && lng !== '' && district !== '' && zone !== '';
            },

            inlineAddressHasRequiredFields() {
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return false;
                }
                const lat = String(form.querySelector('input[name="delivery_lat"]')?.value ?? '').trim();
                const lng = String(form.querySelector('input[name="delivery_lng"]')?.value ?? '').trim();
                const dist = String(form.querySelector('input[name="delivery_district_id"]')?.value ?? '').trim();
                const zone = String(this.selectedZoneId || form.querySelector('select[name="zone_id"]')?.value || '').trim();
                const street = String(this.addressStreet || '').trim();

                return lat !== '' && lng !== '' && dist !== '' && zone !== '' && street !== '';
            },

            async confirmInlineAddress() {
                if (! this.inlineAddressHasRequiredFields()) {
                    this.syncAddressError = @json(__('checkout.fill_address_fields'));

                    return;
                }
                if (! this.fullPhone966()) {
                    this.syncAddressError = @json(__('checkout.address_sync_needs_phone'));

                    return;
                }
                window.dispatchEvent(new CustomEvent('checkout-confirm-inline-address'));
                await this.$nextTick();
                if (! this.inlineAddressHasRequiredFields()) {
                    this.syncAddressError = @json(__('checkout.fill_address_fields'));

                    return;
                }
                await this.syncExternalAddress();
                if (! this.syncAddressError) {
                    this.addressConfirmedForSync = true;
                    this.resetPaymentSession();
                    this.queueMoyasarBootstrap();
                }
            },

            deliveryReady() {
                if (this.deliveryType === 'pickup') {
                    return !!this.selectedBranchId;
                }

                return !!this.selectedAddressId;
            },

            canProceedToPayment() {
                const hasSelectedPlan = this.hasCartItems;
                const hasSelectedDuration = this.isPlanCheckout
                    ? (this.selectedDurationValue() !== '' || Number(this.cartDurationDaysHint || 0) > 0)
                    : this.selectedDurationValue() !== '';

                return this.deliveryReady()
                    && hasSelectedPlan
                    && hasSelectedDuration
                    && this.startDateValid()
                    && this.coverageReady()
                    && this.deliveryTimeReady();
            },

            showPaymentSection() {
                return this.phoneVerified && this.canProceedToPayment();
            },

            checkoutSetupHint() {
                if (! this.phoneVerified || this.canProceedToPayment()) {
                    return '';
                }

                const hasSelectedDuration = this.isPlanCheckout
                    ? (this.selectedDurationValue() !== '' || Number(this.cartDurationDaysHint || 0) > 0)
                    : this.selectedDurationValue() !== '';

                if (this.isPlanCheckout && ! hasSelectedDuration) {
                    return @json(__('checkout.payment_blocker_home'));
                }
                if (this.isPlanCheckout && ! this.startDateValid()) {
                    return @json(__('checkout.start_date_required'));
                }
                if (this.deliveryType === 'pickup') {
                    return @json(__('checkout.payment_blocker_pickup'));
                }
                if (this.syncAddressError && ! this.selectedAddressId) {
                    const areaMsg = @json(__('checkout.area_not_served'));
                    if (this.syncAddressError !== areaMsg) {
                        return this.syncAddressError;
                    }
                }
                if (! this.deliveryTimeReady()) {
                    return @json(__('checkout.select_delivery_time'));
                }
                if (! this.selectedAddressId) {
                    return @json(__('checkout.confirm_saved_address_before_payment'));
                }

                return @json(__('checkout.payment_blocker_home'));
            },

            paymentBlockerMessage() {
                return this.checkoutSetupHint();
            },

            // Computed: subscription line total is fixed; meals use duration multiplier
            subtotal() {
                if (this.isPlanCheckout) {
                    return Math.round(this.baseSubtotal * 100) / 100;
                }
                const multiplier = this.durationMultipliers[this.duration] || 1;
                return Math.round(this.baseSubtotal * multiplier * 100) / 100;
            },

            // Computed: subtotal including VAT (same as subtotal ? price already includes VAT)
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
                const code = this.couponCode.trim();
                if (! code) {
                    return;
                }

                if (! this.phoneVerified) {
                    this.couponApplied = false;
                    this.discount = 0;
                    this.couponMessage = @json(__('checkout.promo_requires_verified_phone'));
                    this.promptPhoneVerificationForDelivery();

                    return;
                }

                if (this.isPlanCheckout && ! this.selectedPlanDurationId) {
                    this.couponApplied = false;
                    this.discount = 0;
                    this.couponMessage = @json(__('checkout.promo_select_duration'));

                    return;
                }

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
                            code: code,
                            subtotal: this.subtotal(),
                            identifier: this.fullPhone966() || '',
                            program_id: this.selectedPlanId || 0,
                            subscription_plan_id: parseInt(this.selectedSubscriptionPlanId || 0, 10) || 0,
                            plan_duration_id: parseInt(this.selectedPlanDurationId || 0, 10) || 0,
                            plan_calory_id: parseInt(this.selectedPlanCaloryId || 0, 10) || 0,
                            delivery_type: this.deliveryType || 'home',
                            start_date: this.startDate || '',
                            selected_address_id: this.selectedAddressId || '',
                            region_duration_id: this.selectedRegionDurationId || '',
                            branch_id: this.selectedBranchId || '',
                            zone_id: this.selectedZoneId || '',
                        }),
                    });

                    const data = await response.json().catch(() => ({}));
                    let couponMsg = data.message ? String(data.message) : '';
                    if (data.errors && typeof data.errors === 'object') {
                        const flat = Object.values(data.errors).flat().filter(Boolean);
                        if (flat.length > 0) {
                            couponMsg = String(flat[0]);
                        }
                    }

                    const isValid = response.ok && data.valid === true;
                    if (isValid) {
                        this.discount = Number(data.discount || 0);
                        this.couponApplied = true;
                        this.couponMessage = couponMsg || @json(__('checkout.promo_applied_success'));
                        if (! this._moyasarSessionFailed && this.canProceedToPayment()) {
                            this._moyasarFingerprint = '';
                            this.scheduleMoyasarRefresh();
                        }
                    } else {
                        this.discount = 0;
                        this.couponApplied = false;
                        this.couponMessage = couponMsg || @json(__('checkout.promo_invalid'));
                    }
                } catch (error) {
                    this.couponMessage = @json(__('An error occurred. Please try again.'));
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

            publishCustomerSessionUpdate(profile = {}) {
                const name = profile && profile.name ? String(profile.name) : String(this.customerName || '');
                window.dispatchEvent(new CustomEvent('checkout-session-updated', {
                    detail: {
                        loggedIn: true,
                        customerName: name,
                    },
                }));
            },

            // Re-validate coupon when duration changes (subtotal changes)
            async revalidateCoupon() {
                if (this.couponApplied && this.couponCode.trim()) {
                    await this.applyCoupon();
                }
            },

            // Open OTP modal and send code
            async openOtpModal() {
                if (!this.fullPhone966()) return;
                if (@json(config('services.external_api.use_new_auth_flow', false))) {
                    window.dispatchEvent(new CustomEvent('open-checkout-auth', {
                        detail: { phone: this.fullPhone966() },
                    }));
                    return;
                }
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
                if (!this.fullPhone966()) return;

                this.otpLoading = true;
                this.otpMessage = '';
                this.otpDigits = ['', '', '', ''];

                try {
                    const { headers, csrf } = this.buildCsrfHeaders();
                    const response = await fetch('{{ route('otp.send') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers,
                        body: JSON.stringify({ phone: this.fullPhone966(), _token: csrf }),
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
                    this.otpMessage = @json(__('An error occurred. Please try again.'));
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
                    const { headers, csrf } = this.buildCsrfHeaders();
                    const response = await fetch('{{ route('otp.verify') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers,
                        body: JSON.stringify({
                            phone: this.fullPhone966(),
                            otp: code,
                            device_id: this.deviceId,
                            _token: csrf,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        if (data.needs_registration || data.is_continue) {
                            this.otpModalOpen = false;
                            window.dispatchEvent(new CustomEvent('open-checkout-auth', {
                                detail: {
                                    phone: this.fullPhone966(),
                                    startAt: 'register',
                                },
                            }));

                            return;
                        }

                        this.phoneVerified = true;
                        this.syncAddressError = '';
                        this.otpMessageType = 'success';
                        this.otpMessage = data.message;
                        this.savedAddresses = Array.isArray(data.addresses) ? data.addresses : [];
                        this.isContinueUser = false;

                        if (data.profile && data.profile.name) {
                            this.customerName = String(data.profile.name);
                            this.showNameField = false;
                        }

                        this.publishCustomerSessionUpdate(data.profile || {});

                        setTimeout(() => { this.otpModalOpen = false; }, 800);
                        this.$nextTick(() => this.scheduleMoyasarRefresh());
                    } else {
                        this.otpMessageType = 'error';
                        this.otpMessage = data.message;
                        this.otpDigits = ['', '', '', ''];
                        this.$nextTick(() => document.getElementById('otp-input-0')?.focus());
                    }
                } catch (error) {
                    this.otpMessage = @json(__('An error occurred. Please try again.'));
                    this.otpMessageType = 'error';
                }

                this.otpLoading = false;
            },

            // Handle single digit input ? auto-focus next
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

            // Handle backspace ? go to previous input
            handleOtpBackspace(event, index) {
                if (!this.otpDigits[index] && index > 0) {
                    this.$nextTick(() => document.getElementById('otp-input-' + (index - 1))?.focus());
                }
            },

            // Handle paste ? fill all digits
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

            // Form submission ? require phone verification
            submitForm(event) {
                if (!this.phoneVerified) {
                    // Open OTP modal so user can verify
                    this.openOtpModal();
                    return;
                }
                if (!this.canProceedToPayment()) {
                    this.moyasarError = @json(__('payment.fill_delivery_first'));
                    return;
                }
                event.target.submit();
            },

            requestMoyasarBootstrap() {
                this.queueMoyasarBootstrap();
            },

            scheduleMoyasarRefresh() {
                if (this._moyasarSessionFailed) {
                    return;
                }
                this._moyasarFingerprint = '';
                this.queueMoyasarBootstrap();
            },

            buildMoyasarFingerprint(fd) {
                const keys = [
                    'phone', 'start_date', 'plan_duration_id', 'delivery_type',
                    'zone_id', 'selected_address_id', 'region_duration_id', 'branch_id', 'coupon', 'promocode_name',
                ];

                return keys.map((k) => k + '=' + String(fd.get(k) || '')).join('&');
            },

            moyasarWidgetMounted() {
                const el = document.getElementById('moyasar-form-checkout');

                return !!(el && (el.querySelector('.mysr-form') || el.querySelector('form') || el.querySelector('iframe')));
            },

            async bootstrapMoyasarPreview() {
                if (this.phoneVerified) {
                    return;
                }
                if (! this.hasCartItems) {
                    return;
                }
                const hasSdk = await this.waitForMoyasar();
                if (! hasSdk) {
                    this.moyasarError = @json(__('payment.moyasar_load_failed'));

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
                        this.moyasarError = data.message || @json(__('payment.fill_delivery_first'));
                        const el = document.getElementById('moyasar-form-checkout');
                        if (el) {
                            el.innerHTML = '';
                        }

                        return;
                    }
                    this.initMoyasarWidget(data);
                } catch (e) {
                    this.moyasarError = @json(__('An error occurred. Please try again.'));
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

            applyDurationMinimumStartDate() {
                return this.refreshMinStartDateFromApi();
            },

            async refreshMinStartDateFromApi() {
                if (! this.isPlanCheckout || ! this.phoneVerified) {
                    return;
                }
                const deliveryType = this.deliveryType === 'pickup' ? 'pickup' : 'home';
                if (deliveryType === 'pickup' && ! this.selectedBranchId) {
                    return;
                }
                const params = new URLSearchParams();
                if (this.selectedPlanDurationId) {
                    params.set('plan_duration_id', String(this.selectedPlanDurationId));
                }
                params.set('delivery_type', deliveryType);
                if (deliveryType === 'home' && this.selectedAddressId) {
                    params.set('selected_address_id', String(this.selectedAddressId));
                    if (this.selectedZoneId) {
                        params.set('zone_id', String(this.selectedZoneId));
                    }
                }
                if (deliveryType === 'pickup' && this.selectedBranchId) {
                    params.set('branch_id', String(this.selectedBranchId));
                }
                const url = @json(route('checkout.subscription-schedule')) + '?' + params.toString();
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json().catch(() => ({}));
                    if (! res.ok || ! data.success) {
                        return;
                    }
                    const apiMin = String(
                        data.first_available_date_for_subscription || data.min_start_date || ''
                    ).trim().slice(0, 10);
                    if (apiMin) {
                        this.applyApiMinStartDate(apiMin);
                    }
                } catch (e) {}
            },

            applyApiMinStartDate(apiMin) {
                const normalized = String(apiMin || '').trim().slice(0, 10);
                if (! normalized) {
                    return;
                }
                const current = String(this.startDate || document.getElementById('start_date_input')?.value || '').trim().slice(0, 10);
                if (current && current < normalized) {
                    this.startDateNotice = @json(__('checkout.start_date_adjusted_notice'));
                    this.startDateTouched = false;
                }
                this.updateStartDatePickerMinimum(normalized);
                if (! this.startDateTouched || ! current || current < normalized) {
                    this.applyDefaultStartDateIfNeeded(normalized);
                }
            },

            updateStartDatePickerMinimum(minDate) {
                const normalized = String(minDate || '').trim().slice(0, 10);
                if (! normalized) {
                    return;
                }
                this.minStartDate = normalized;
                const input = document.getElementById('start_date_input');
                if (! input) {
                    return;
                }
                const picker = input._flatpickr;
                if (picker) {
                    picker.set('minDate', normalized);
                }
            },

            applyDefaultStartDateIfNeeded(minDate) {
                if (this.startDateTouched) {
                    return;
                }
                const normalized = String(minDate || '').trim().slice(0, 10);
                if (! normalized) {
                    return;
                }
                const input = document.getElementById('start_date_input');
                if (! input) {
                    return;
                }
                const current = String(this.startDate || input.value || '').trim().slice(0, 10);
                const nextDate = (! current || current < normalized) ? normalized : current;
                const picker = input._flatpickr;
                this._skipStartDateTouch = true;
                if (picker) {
                    picker.setDate(nextDate, true);
                } else {
                    input.value = nextDate;
                }
                this.startDate = nextDate;
                this._skipStartDateTouch = false;
            },

            markStartDateTouched() {
                if (this._skipStartDateTouch) {
                    return;
                }
                this.startDateTouched = true;
            },

            async bootstrapMoyasar() {
                if (! this.phoneVerified) {
                    return;
                }
                const hasSdk = await this.waitForMoyasar();
                if (! hasSdk) {
                    this.moyasarError = @json(__('payment.moyasar_load_failed'));

                    return;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return;
                }
                const fd = new FormData(form);
                fd.set('delivery_type', this.deliveryType === 'pickup' ? 'pickup' : 'home');
                fd.set('selected_plan_id', String(this.selectedPlanId || ''));
                fd.set('selected_duration', this.selectedDurationValue());
                fd.set('start_date', String(document.getElementById('start_date_input')?.value || this.startDate || ''));
                if (this.selectedPlanDurationId) {
                    fd.set('plan_duration_id', String(this.selectedPlanDurationId));
                }
                if (this.deliveryType === 'pickup') {
                    if (this.selectedBranchId) {
                        fd.set('branch_id', String(this.selectedBranchId));
                    }
                    fd.delete('selected_address_id');
                } else {
                    if (this.selectedAddressId) {
                        fd.set('selected_address_id', String(this.selectedAddressId));
                    }
                    if (this.selectedZoneId) {
                        fd.set('zone_id', String(this.selectedZoneId));
                    }
                    fd.delete('branch_id');
                }
                if (! this.startDateValid()) {
                    this.moyasarError = @json(__('checkout.start_date_required'));
                    const el = document.getElementById('moyasar-form-checkout');
                    if (el) {
                        el.innerHTML = '';
                    }

                    return;
                }
                const fingerprint = this.buildMoyasarFingerprint(fd);
                if (fingerprint === this._moyasarFingerprint && this.moyasarWidgetMounted()) {
                    return;
                }
                const requestId = ++this._moyasarRequestId;
                this.moyasarError = '';
                try {
                    const res = await fetch('{{ route('checkout.moyasar-session') }}', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        },
                    });
                    if (requestId !== this._moyasarRequestId) {
                        return;
                    }
                    const data = await res.json().catch(() => ({}));
                    if (! this.phoneVerified) {
                        return;
                    }
                    if (! res.ok || ! data.success) {
                        this._moyasarSessionFailed = true;
                        const apiMin = String(data.min_start_date || '').trim().slice(0, 10);
                        if (apiMin && ! this._moyasarStartDateRetry) {
                            this._moyasarStartDateRetry = true;
                            this.applyApiMinStartDate(apiMin);
                            this._moyasarFingerprint = '';
                            await this.bootstrapMoyasar();

                            return;
                        }
                        if (data.errors && data.errors.start_date && data.errors.start_date[0]) {
                            this.moyasarError = data.errors.start_date[0];
                        } else {
                            this.moyasarError = data.message || @json(__('payment.fill_delivery_first'));
                        }
                        if (apiMin) {
                            this.updateStartDatePickerMinimum(apiMin);
                        }
                        this._moyasarFingerprint = '';
                        const el = document.getElementById('moyasar-form-checkout');
                        if (el) {
                            el.innerHTML = '';
                        }

                        return;
                    }
                    this._moyasarStartDateRetry = false;
                    this._moyasarFingerprint = fingerprint;
                    this._moyasarSessionFailed = false;
                    if (data.adjusted_start_date) {
                        this.applyApiMinStartDate(String(data.adjusted_start_date).slice(0, 10));
                    }
                    this.initMoyasarWidget(data);
                } catch (e) {
                    if (requestId === this._moyasarRequestId) {
                        this._moyasarSessionFailed = true;
                        this.moyasarError = @json(__('An error occurred. Please try again.'));
                    }
                }
            },

            initMoyasarWidget(data) {
                const el = document.getElementById('moyasar-form-checkout');
                if (! el || typeof Moyasar === 'undefined') {
                    return;
                }
                const apiCheckout = data.api_checkout === true;
                let publishableKey = String(data.publishable_key || '').trim();
                if (! publishableKey && ! apiCheckout) {
                    publishableKey = String(@json(config('services.moyasar.publishable_key', ''))).trim();
                }
                if (! publishableKey || ! /^pk_(test|live)_[a-zA-Z0-9]+$/.test(publishableKey)) {
                    this.moyasarError = @json(__('payment.moyasar_key_missing'));

                    return;
                }
                el.innerHTML = '';
                let cb = (data.callback_url || '').trim();
                if (data.order_number) {
                    const sep = cb.includes('?') ? '&' : '?';
                    cb = cb + sep + 'order=' + encodeURIComponent(data.order_number);
                }
                if (data.subscription_id) {
                    const sep = cb.includes('?') ? '&' : '?';
                    cb = cb + sep + 'subscription=' + encodeURIComponent(String(data.subscription_id));
                }
                Moyasar.init({
                    element: '#moyasar-form-checkout',
                    amount: data.amount_halalas,
                    currency: data.currency || 'SAR',
                    description: data.description || '',
                    publishable_api_key: publishableKey,
                    callback_url: cb,
                    methods: ['creditcard', 'applepay', 'stcpay'],
                    metadata: data.metadata || {},
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
                window.addEventListener('checkout-auth-success', async (event) => {
                    const detail = event.detail || {};
                    this.phoneVerified = true;
                    this.syncAddressError = '';
                    if (detail.phone && typeof window.dwSaudiPhoneDigits === 'function') {
                        this.phoneLocal = window.dwSaudiPhoneDigits(String(detail.phone));
                    }
                    this.applyCheckoutAddresses(detail.addresses || []);
                    this.isContinueUser = false;
                    if (detail.profile && detail.profile.name) {
                        this.customerName = String(detail.profile.name);
                    }
                    this.showNameField = ! (this.customerName || '').trim();
                    this.publishCustomerSessionUpdate(detail.profile || {});
                    await this.$nextTick();
                    if (this.deliveryType === 'home') {
                        setTimeout(() => window.dispatchEvent(new CustomEvent('checkout-home-map-refresh')), 350);
                    }
                    if (this.deliveryType === 'home' && ! this.selectedAddressId) {
                        const autoSelected = await this.autoSelectSavedAddressIfNeeded();
                        if (autoSelected) {
                            return;
                        }
                    }
                    await this.refreshMinStartDateFromApi();
                    this.$nextTick(() => {
                        if (this.canProceedToPayment()) {
                            this.queueMoyasarBootstrap();
                        }
                    });
                });

                if (this.isPlanCheckout) {
                    try {
                        await this.hydratePlanDurations();
                    } catch (e) {
                        this.durationsLoading = false;
                    }
                } else {
                    this.durationsLoading = false;
                }
                this.$watch('planDurations', () => {
                    this.$nextTick(() => {
                        this.refreshDurationScrollState();
                        this.scrollDurationToSelected();
                    });
                });
                this.$watch('durationsLoading', (loading) => {
                    if (! loading) {
                        this.$nextTick(() => {
                            this.refreshDurationScrollState();
                            this.scrollDurationToSelected();
                        });
                    }
                });
                if (typeof window !== 'undefined') {
                    this._durationResizeHandler = () => this.refreshDurationScrollState();
                    window.addEventListener('resize', this._durationResizeHandler, { passive: true });
                }
                this.$watch('selectedPlanDurationId', (id) => {
                    if (! this.isPlanCheckout || id === undefined || id === null) {
                        return;
                    }
                    this.scrollDurationToSelected();
                    const p = this.planDurationPrices[String(id)];
                    if (p != null) {
                        this.baseSubtotal = Math.round(p * 100) / 100;
                        this.revalidateCoupon();
                    }
                    this.applyDurationMinimumStartDate();
                    if (! this._moyasarSessionFailed && this.canProceedToPayment()) {
                        this._moyasarFingerprint = '';
                        this.scheduleMoyasarRefresh();
                    }
                });
                this.$watch('duration', () => this.revalidateCoupon());
                this.$watch('duration', () => {
                    if (! this._moyasarSessionFailed && this.canProceedToPayment()) {
                        this._moyasarFingerprint = '';
                        this.scheduleMoyasarRefresh();
                    }
                });
                this.$watch('selectedZoneId', () => {
                    if (! this._moyasarSessionFailed && this.canProceedToPayment()) {
                        this._moyasarFingerprint = '';
                        this.scheduleMoyasarRefresh();
                    }
                });
                this.$watch('deliveryType', (v) => {
                    this.resetPaymentSession(true);
                    if (v === 'pickup') {
                        this.syncPickupPhase();
                        this.coverageOk = true;
                        this.coverageMessage = '';
                        this.syncAddressError = '';
                    } else if (this.phoneVerified) {
                        this.coverageOk = this.selectedAddressId ? true : null;
                        this.coverageMessage = '';
                        setTimeout(() => window.dispatchEvent(new CustomEvent('checkout-home-map-refresh')), 300);
                    }
                    if (this.canProceedToPayment()) {
                        this.queueMoyasarBootstrap();
                    } else {
                        this.clearMoyasarWidget();
                    }
                });
                this.$watch('couponApplied', () => {
                    if (! this._moyasarSessionFailed && this.canProceedToPayment()) {
                        this._moyasarFingerprint = '';
                        this.scheduleMoyasarRefresh();
                    }
                });
                if (this.phoneVerified && this.deliveryType === 'home') {
                    setTimeout(() => window.dispatchEvent(new CustomEvent('checkout-home-map-refresh')), 500);
                }

                fetch('{{ route('api.branches') }}')
                    .then(r => r.json())
                    .then(data => {
                        this.branches = data;
                        this.branchesLoading = false;
                        if (this.deliveryType === 'pickup' && !this.selectedBranchId && this.branches.length === 1) {
                            this.selectBranch(this.branches[0].id);
                        }
                        this.syncPickupPhase();
                    })
                    .catch(() => { this.branches = []; this.branchesLoading = false; });

                let bootstrappedViaAddress = false;
                if (this.phoneVerified) {
                    const hydrateTasks = [this.refreshCustomerFromServer()];
                    if (this.isPlanCheckout) {
                        hydrateTasks.push(this.refreshMinStartDateFromApi());
                    }
                    await Promise.all(hydrateTasks);
                    if (this.deliveryType === 'home' && ! this.selectedAddressId) {
                        bootstrappedViaAddress = await this.autoSelectSavedAddressIfNeeded();
                    }
                }
                if (this.isPlanCheckout && this.phoneVerified) {
                    this._minDatePollTimer = setInterval(() => {
                        this.refreshMinStartDateFromApi();
                    }, 5 * 60 * 1000);
                }
                const startDateInput = document.getElementById('start_date_input');
                if (startDateInput) {
                    this.startDate = String(startDateInput.value || this.startDate || '');
                    startDateInput.addEventListener('change', () => {
                        this.markStartDateTouched();
                        this.startDate = String(startDateInput.value || '');
                        if (! this._moyasarSessionFailed && this.canProceedToPayment()) {
                            this._moyasarFingerprint = '';
                            this.scheduleMoyasarRefresh();
                        }
                    });
                }
                if (this.phoneVerified && this.canProceedToPayment() && ! bootstrappedViaAddress) {
                    this.queueMoyasarBootstrap();
                }
            }
        }
    }

    window.checkoutPage = checkoutPage;

    document.addEventListener('DOMContentLoaded', function() {
        const locale = '{{ $locale }}';
        const months = locale === 'ar'
            ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
            : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        function parseLocalYmd(dateStr) {
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(dateStr || '').trim());
            if (! match) {
                return null;
            }

            return {
                day: parseInt(match[3], 10),
                month: parseInt(match[2], 10) - 1,
                year: parseInt(match[1], 10),
            };
        }

        function updateDisplay(dateStr) {
            const parsed = parseLocalYmd(dateStr);
            const dayEl = document.querySelector('.date-picker-label__day');
            const monthEl = document.querySelector('.date-picker-label__month');
            if (! parsed || ! dayEl || ! monthEl) {
                return;
            }
            dayEl.textContent = String(parsed.day).padStart(2, '0');
            monthEl.textContent = months[parsed.month] + ' ' + parsed.year;
        }

        const startDateInput = document.getElementById('start_date_input');
        const serverMinDate = @json($minStartDate);
        let minDateStr = serverMinDate || '';

        if (startDateInput && minDateStr) {
            const currentValue = String(startDateInput.value || '').trim();
            if (! currentValue || currentValue < minDateStr) {
                startDateInput.value = minDateStr;
                updateDisplay(minDateStr);
            }
        }

        if (startDateInput) {
            flatpickr('#start_date_input', {
                dateFormat: 'Y-m-d',
                minDate: minDateStr || undefined,
                defaultDate: startDateInput.value || minDateStr || undefined,
                disableMobile: true,
                @if($locale === 'ar')
                locale: 'ar',
                @endif
                onChange: function(selectedDates, dateStr) {
                    updateDisplay(dateStr);
                    const input = document.getElementById('start_date_input');
                    if (input) {
                        input.dispatchEvent(new CustomEvent('checkout-start-date-changed', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                },
            });
        }

        // Click anywhere on the wrapper to open the picker
        document.getElementById('date_picker_wrap')?.addEventListener('click', function() {
            document.getElementById('start_date_input')?._flatpickr?.open();
        });
    });
</script>
@endpush
