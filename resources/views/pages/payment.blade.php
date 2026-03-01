@extends('layouts.app')

@section('title', __('payment.title') . ' | ' . $siteName)

@section('content')
<section class="bg-gray-200 pt-10 pb-28">
    <div class="container max-w-[600px]">
        {{-- Breadcrumb --}}
        <ol class="breadcrumb mb-6">
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('home') }}">{{ __('Home') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </li>
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('checkout.index') }}">{{ __('Checkout') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </li>
            <li class="breadcrumb__item breadcrumb__item--active" aria-current="page">{{ __('Payment') }}</li>
        </ol>

        {{-- Payment Card --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">
            {{-- Order Summary --}}
            <div class="mb-6 rounded-lg bg-gray-50 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-500">{{ __('payment.order_number') }}</span>
                    <span class="font-mono text-sm font-semibold">{{ $payment->order_number }}</span>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-500">{{ __('payment.customer') }}</span>
                    <span class="text-sm font-medium">{{ $payment->customer_name }}</span>
                </div>
                <div class="border-t border-gray-200 mt-3 pt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold">{{ __('Total') }}</span>
                        <span class="text-xl font-bold text-green-600">SAR {{ $amountDisplay }}</span>
                    </div>
                </div>
            </div>

            {{-- Moyasar Payment Form Container --}}
            <div class="mb-4">
                <h3 class="text-lg font-semibold mb-4">{{ __('payment.choose_method') }}</h3>
                <div id="moyasar-form"></div>
            </div>

            {{-- Security Note --}}
            <div class="flex items-center gap-2 text-sm text-gray-500 mt-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span>{{ __('payment.secure_note') }}</span>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/moyasar-payment-form/dist/moyasar.css" />
<style>
    .breadcrumb { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; font-size: 0.875rem; color: #666; }
    .breadcrumb__item { display: flex; align-items: center; }
    .breadcrumb__link { transition: color 0.2s; }
    .breadcrumb__link:hover { color: #279ff9; }
    .breadcrumb__separator { margin: 0 4px; width: 16px; height: 16px; }
    .breadcrumb__item--active { color: #111; font-weight: 500; }

    /* Moyasar Form Styling */
    .mysr-form {
        font-family: inherit !important;
    }
    .mysr-form .mysr-form-group input {
        border-radius: 10px !important;
        border: 2px solid #e5e7eb !important;
        padding: 12px 14px !important;
        font-size: 0.95rem !important;
        transition: border-color 0.2s !important;
    }
    .mysr-form .mysr-form-group input:focus {
        border-color: #279ff9 !important;
        box-shadow: 0 0 0 3px rgba(39,159,249,0.1) !important;
    }
    .mysr-form button[type="submit"],
    .mysr-form .mysr-form-button {
        background: #279ff9 !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 14px !important;
        font-size: 1rem !important;
        font-weight: 700 !important;
        margin-top: 8px !important;
        transition: background 0.2s !important;
    }
    .mysr-form button[type="submit"]:hover,
    .mysr-form .mysr-form-button:hover {
        background: #1a8ae0 !important;
    }
</style>
@endpush

@push('head')
{{-- Moyasar Payment SDK --}}
<script src="https://cdn.jsdelivr.net/npm/moyasar-payment-form/dist/moyasar.umd.min.js"></script>
@endpush

@push('scripts')
<script>
    Moyasar.init({
        element: '#moyasar-form',
        amount: {{ $amountInHalalas }},
        currency: '{{ $currency }}',
        description: @json($description),
        publishable_api_key: '{{ $publishableKey }}',
        callback_url: '{{ $callbackUrl }}?order={{ $payment->order_number }}',
        methods: ['creditcard', 'applepay', 'stcpay'],
        supported_networks: ['visa', 'mastercard', 'mada'],
        apple_pay: {
            country: 'SA',
            label: 'Diet Watchers',
            validate_merchant_url: 'https://api.moyasar.com/v1/applepay/initiate',
        },
        on_completed: async function(payment) {
            console.log('Payment completed:', payment);
        },
        language: '{{ app()->getLocale() }}',
    });
</script>
@endpush
