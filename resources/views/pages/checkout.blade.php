@extends('layouts.app')

@php
$locale = app()->getLocale();

// Get first cart item for breadcrumb
$firstItem = collect($cart)->first();
$planName = $firstItem['name'] ?? __('Order');
$cartCount = collect($cart)->sum('quantity');
$hasPlanItems = collect($cart)->contains(fn($item) => !empty($item['options']['duration_days']));
@endphp

@section('title', __('Checkout') . ' | ' . $siteName)
@section('description', __('Complete your order to start your healthy journey'))

@section('content')
<section class="bg-gray-200 pt-10 pb-28">
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

        <form action="{{ route('checkout.store') }}" method="POST" x-data="checkoutPage()" @submit.prevent="submitForm($event)">
            @csrf

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                {{-- ── Left Column: Options & Info ─────────────────── --}}
                <div>
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

                            {{-- Duration (only for meal plan subscriptions) --}}
                            @if($hasPlanItems)
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
                            @else
                                <input type="hidden" name="duration" value="once" />
                            @endif

                            {{-- Delivery Preference --}}
                            <div>
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

                                {{-- Branch selector — shown right under the pickup option --}}
                                <div x-show="deliveryType === 'pickup'" x-transition class="mt-4">
                                    <template x-if="branchesLoading">
                                        <p class="text-sm text-gray-500">{{ __('Loading branches...') }}</p>
                                    </template>
                                    <template x-if="!branchesLoading">
                                        <div class="space-y-3">
                                            <select name="branch_id" class="form-control" x-model="selectedBranchId">
                                                <option value="">{{ __('Select pickup branch') }}</option>
                                                <template x-for="branch in branches" :key="branch.id">
                                                    <option :value="branch.id" x-text="(typeof branch.name === 'object' ? (branch.name['{{ app()->getLocale() }}'] || branch.name['en'] || '') : branch.name) + (branch.address ? ' — ' + branch.address : '')"></option>
                                                </template>
                                            </select>

                                            {{-- Selected branch detail card --}}
                                            <template x-if="selectedBranchId">
                                                <div class="rounded-lg bg-blue-50 p-3">
                                                    <template x-for="branch in branches.filter(b => String(b.id) === String(selectedBranchId))" :key="branch.id">
                                                        <div class="flex items-start gap-3">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;flex-shrink:0;margin-top:2px;color:#279ff9" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                                            </svg>
                                                            <div>
                                                                <p class="font-semibold text-sm" x-text="typeof branch.name === 'object' ? (branch.name['{{ app()->getLocale() }}'] || branch.name['en'] || '') : branch.name"></p>
                                                                <p class="text-xs text-gray-600" x-show="branch.address" x-text="branch.address"></p>
                                                                <p class="text-xs text-gray-600" x-show="branch.phone" dir="ltr" x-text="branch.phone"></p>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>

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
                    <div class="mt-6 rounded-md border border-gray-200 bg-white p-5">
                        <h3 class="mb-6 text-2xl font-semibold md:text-2xl">{{ __('User Information') }}</h3>

                        <div class="space-y-4">
                            <div>
                                <input type="text" name="name" class="form-control @error('name') border-red-500 @enderror"
                                       placeholder="{{ __('Add your name') }}" value="{{ old('name') }}" required />
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="form-input-action">
                                    <input type="tel" name="phone" class="form-control @error('phone') border-red-500 @enderror"
                                           placeholder="{{ __('Add your phone number') }}" value="{{ old('phone') }}" required dir="ltr"
                                           x-model="phone" :disabled="phoneVerified" />
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

                            <div>
                                <input type="email" name="email" class="form-control @error('email') border-red-500 @enderror"
                                       placeholder="{{ __('Add your email') }}" value="{{ old('email') }}" required dir="ltr"
                                       x-model="email" />
                                @error('email')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Delivery Address --}}
                    <div class="mt-6 rounded-md border border-gray-200 bg-white p-5" x-show="deliveryType === 'home'" x-transition>
                        <h3 class="mb-6 text-2xl font-semibold md:text-2xl">{{ __('Delivery Address') }}</h3>

                        <div class="space-y-4">

                            {{-- Zone (kept for delivery-fee calculation) --}}
                            <div>
                                <select name="zone_id" class="form-control @error('zone_id') border-red-500 @enderror"
                                        x-model="selectedZoneId" @change="onZoneChange()">
                                    <option value="">{{ __('Select Zone') }}</option>
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

                            {{-- Google Maps Address Picker --}}
                            <div
                                x-data="{
                                    streetVal: '{{ old('street') }}',
                                    buildingVal: '{{ old('building') }}',
                                    onAddressSelected(detail) {
                                        this.streetVal   = detail.description || '';
                                        this.buildingVal = detail.description || '';
                                    }
                                }"
                                @address-selected.window="onAddressSelected($event.detail)"
                            >
                                {{-- Map picker trigger --}}
                                <x-google-map-picker
                                    field-prefix="delivery"
                                    :placeholder="__('Pick delivery location on map')"
                                />

                                {{-- Hidden fields sent to CheckoutController --}}
                                <input type="hidden" name="street"   x-model="streetVal" />
                                <input type="hidden" name="building" x-model="buildingVal" />

                                {{-- Editable confirmation of picked address --}}
                                <div x-show="streetVal" x-transition class="mt-3">
                                    <label class="block text-sm font-medium text-gray-600 mb-1">{{ __('Address Notes') }}</label>
                                    <input
                                        type="text"
                                        x-model="buildingVal"
                                        @input="streetVal = buildingVal"
                                        class="form-control"
                                        placeholder="{{ __('Apartment, floor, landmark…') }}"
                                    />
                                </div>

                                @error('street')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                @error('building')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Payment Info --}}
                    <div class="mt-6 rounded-md border border-gray-200 bg-white p-5">
                        <h3 class="mb-4 text-2xl font-semibold md:text-2xl">{{ __('Payment') }}</h3>
                        <div class="flex items-center gap-3 rounded-lg bg-blue-50 p-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ __('payment.secure_checkout') }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ __('payment.methods_available') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Right Column: Order Summary ──────────────────── --}}
                <div class="space-y-6">
                    <div class="rounded-md border border-gray-200 bg-white p-5">
                        <h3 class="mb-6 text-2xl font-semibold md:text-2xl">{{ __('Order Summary') }}</h3>

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
                                    </div>
                                    <span class="font-bold text-gray-900 whitespace-nowrap">SAR {{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                                </div>
                            @endforeach

                            {{-- Payment Summary --}}
                            <div class="border-y border-gray-300 py-4">
                                <div>
                                    <p class="mb-3 text-lg md:text-xl">{{ __('Payment Summary') }}</p>

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">{{ __('Items Total') }} <span class="text-xs">({{ __('Incl. VAT') }})</span></span>
                                            <span class="font-bold text-gray-900">SAR <span x-text="subtotalInclVat().toFixed(2)"></span></span>
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

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

    [x-cloak] { display: none !important; }

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
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@if($locale === 'ar')
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
@endif
<script>
    function checkoutPage() {
        return {
            // Reactive state
            baseSubtotal: {{ $baseSubtotal }},
            duration: '{{ old('duration', 'monthly') }}',
            deliveryType: '{{ old('delivery_type', 'home') }}',
            vatRate: {{ $vatRate }},
            deliveryFeeAmount: {{ $deliveryFeeAmount }},
            discount: 0,
            email: '{{ old('email', '') }}',

            // Zone state
            selectedZoneId: '{{ old('zone_id', '') }}',
            zones: @json($zones),

            // Plan durations from API
            planDurations: @json($planDurations ?? []),

            // Branch pickup state
            selectedBranchId: '{{ old('branch_id', '') }}',
            branches: [],
            branchesLoading: true,

            // Duration multiplier map from backend
            durationMultipliers: @json($durationMultipliers),

            // Phone / OTP state
            phone: '{{ old('phone', '') }}',
            phoneVerified: false,
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

            // ─── PRICES FROM API ARE VAT-INCLUSIVE (like mobile app) ───
            // The baseSubtotal already includes VAT. We extract VAT for display only.

            // Computed: subtotal with duration multiplier (VAT-inclusive price from API)
            subtotal() {
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
                            identifier: this.email || '',
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
                    // Show OTP in message for testing (backend sends it in non-production)
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
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.phoneVerified = true;
                        this.otpMessageType = 'success';
                        this.otpMessage = data.message;
                        setTimeout(() => { this.otpModalOpen = false; }, 800);
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

            // Watch for duration changes to re-validate coupon
            init() {
                this.$watch('duration', () => this.revalidateCoupon());

                // Fetch branches for pickup option
                fetch('{{ route('api.branches') }}')
                    .then(r => r.json())
                    .then(data => { this.branches = data; this.branchesLoading = false; })
                    .catch(() => { this.branches = []; this.branchesLoading = false; });
            }
        }
    }

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
