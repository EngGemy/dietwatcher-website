@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $isAr = $locale === 'ar';
@endphp

@section('title', ($success ? __('payment.success_title') : __('payment.failed_title')) . ' | ' . $siteName)

@section('content')
<section class="bg-gray-200 pt-10 pb-28">
    <div class="container max-w-[700px]">

        @if($success)
            {{-- ── Invoice / Receipt ──────────────────────── --}}
            <div class="invoice" id="invoice">
                {{-- Header --}}
                <div class="invoice__header">
                    <div class="invoice__brand">
                        @if(!empty($siteLogo))
                            <img src="{{ $siteLogo }}" alt="{{ $siteName ?? 'Diet Watchers' }}" class="invoice__logo" />
                        @else
                            <span class="invoice__company">{{ $siteName ?? 'Diet Watchers' }}</span>
                        @endif
                    </div>
                    <div class="invoice__badge">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span>{{ __('Paid') }} / {{ $isAr ? 'Paid' : 'مدفوع' }}</span>
                    </div>
                </div>

                {{-- Title --}}
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('Invoice') }} / {{ $isAr ? 'Invoice' : 'فاتورة' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ __('Thank you for your order!') }} / {{ $isAr ? 'Thank you for your order!' : 'شكراً لطلبك!' }}</p>
                </div>

                {{-- Order Info Grid --}}
                <div class="invoice__info-grid">
                    <div class="invoice__info-block">
                        <p class="invoice__info-label">{{ __('Order Number') }} / {{ $isAr ? 'Order No.' : 'رقم الطلب' }}</p>
                        <p class="invoice__info-value font-mono">{{ $payment->order_number }}</p>
                    </div>
                    <div class="invoice__info-block">
                        <p class="invoice__info-label">{{ __('Date') }} / {{ $isAr ? 'Date' : 'التاريخ' }}</p>
                        <p class="invoice__info-value">{{ $payment->updated_at->format('d/m/Y - H:i') }}</p>
                    </div>
                    <div class="invoice__info-block">
                        <p class="invoice__info-label">{{ __('Customer') }} / {{ $isAr ? 'Customer' : 'العميل' }}</p>
                        <p class="invoice__info-value">{{ $payment->customer_name }}</p>
                    </div>
                    <div class="invoice__info-block">
                        <p class="invoice__info-label">{{ __('Phone') }} / {{ $isAr ? 'Phone' : 'الهاتف' }}</p>
                        <p class="invoice__info-value" dir="ltr">{{ $payment->customer_phone }}</p>
                    </div>
                    @if($payment->customer_email)
                    <div class="invoice__info-block sm:col-span-2">
                        <p class="invoice__info-label">{{ __('Email') }} / {{ $isAr ? 'Email' : 'البريد' }}</p>
                        <p class="invoice__info-value" dir="ltr">{{ $payment->customer_email }}</p>
                    </div>
                    @endif
                </div>

                {{-- Subscription Details --}}
                <div class="invoice__section">
                    <h3 class="invoice__section-title">{{ __('Subscription Details') }} / {{ $isAr ? 'Subscription Details' : 'تفاصيل الاشتراك' }}</h3>
                    <div class="invoice__detail-grid">
                        @if($payment->start_date)
                        <div class="invoice__detail-item">
                            <span class="invoice__detail-label">{{ __('Start Date') }} / {{ $isAr ? 'Start Date' : 'تاريخ البداية' }}</span>
                            <span class="invoice__detail-value">{{ $payment->start_date }}</span>
                        </div>
                        @endif
                        @if($payment->duration)
                        <div class="invoice__detail-item">
                            <span class="invoice__detail-label">{{ __('Duration') }} / {{ $isAr ? 'Duration' : 'المدة' }}</span>
                            <span class="invoice__detail-value">{{ __(ucfirst($payment->duration)) }}</span>
                        </div>
                        @endif
                        <div class="invoice__detail-item">
                            <span class="invoice__detail-label">{{ __('Delivery') }} / {{ $isAr ? 'Delivery' : 'التوصيل' }}</span>
                            <span class="invoice__detail-value">{{ $payment->delivery_type === 'home' ? __('Home Delivery') : __('Pickup from Kitchen') }}</span>
                        </div>
                        @if($payment->delivery_type === 'home' && $payment->city)
                        <div class="invoice__detail-item">
                            <span class="invoice__detail-label">{{ __('Address') }} / {{ $isAr ? 'Address' : 'العنوان' }}</span>
                            <span class="invoice__detail-value">{{ __(ucfirst($payment->city)) }}{{ $payment->street ? ', ' . $payment->street : '' }}{{ $payment->building ? ', ' . $payment->building : '' }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Items Table --}}
                @if(!empty($payment->cart_items))
                <div class="invoice__section">
                    <h3 class="invoice__section-title">{{ __('Items') }} / {{ $isAr ? 'Items' : 'العناصر' }}</h3>
                    <div class="invoice__table-wrap">
                        <table class="invoice__table">
                            <thead>
                                <tr>
                                    <th class="text-start">{{ __('Item') }}</th>
                                    <th class="text-center">{{ __('Qty') }}</th>
                                    <th class="text-end">{{ __('Price') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment->cart_items as $item)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            @php $img = $item['image'] ?? ''; @endphp
                                            <img src="{{ str_starts_with($img, 'http') ? $img : asset('assets/images/plan-1.png') }}"
                                                 alt="" class="h-10 w-10 rounded-lg object-cover flex-shrink-0" />
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $item['name'] ?? '' }}</p>
                                                @if(!empty($item['options']['mealType']))
                                                    <p class="text-xs text-gray-500">{{ __(ucfirst($item['options']['mealType'])) }}
                                                        @if(!empty($item['options']['calories']))
                                                            - {{ $item['options']['calories'] }} {{ __('Kcal') }}
                                                        @endif
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $item['quantity'] ?? 1 }}</td>
                                    <td class="text-end" dir="ltr">{{ number_format($item['price'] ?? 0, 2) }}</td>
                                    <td class="text-end font-semibold" dir="ltr">{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Totals --}}
                <div class="invoice__totals">
                    <div class="invoice__total-row">
                        <span>{{ __('Subtotal') }} / {{ $isAr ? 'Subtotal' : 'المجموع الفرعي' }}</span>
                        <span dir="ltr">SAR {{ number_format($payment->subtotal / 100, 2) }}</span>
                    </div>

                    @if($payment->delivery_fee > 0)
                    <div class="invoice__total-row">
                        <span>{{ __('Delivery fees') }} / {{ $isAr ? 'Delivery' : 'التوصيل' }}</span>
                        <span dir="ltr">SAR {{ number_format($payment->delivery_fee / 100, 2) }}</span>
                    </div>
                    @else
                    <div class="invoice__total-row">
                        <span>{{ __('Delivery fees') }} / {{ $isAr ? 'Delivery' : 'التوصيل' }}</span>
                        <span class="text-green-600">{{ __('Free') }} / {{ $isAr ? 'Free' : 'مجاني' }}</span>
                    </div>
                    @endif

                    @if($payment->discount_amount > 0)
                    <div class="invoice__total-row text-green-600">
                        <span>{{ __('Discount') }} / {{ $isAr ? 'Discount' : 'الخصم' }} @if($payment->coupon)({{ $payment->coupon }})@endif</span>
                        <span dir="ltr">- SAR {{ number_format($payment->discount_amount / 100, 2) }}</span>
                    </div>
                    @endif

                    <div class="invoice__total-row">
                        <span>{{ __('VAT') }} (15%) / {{ $isAr ? 'VAT' : 'ضريبة القيمة المضافة' }}</span>
                        <span dir="ltr">SAR {{ number_format($payment->vat_amount / 100, 2) }}</span>
                    </div>

                    <div class="invoice__total-row invoice__total-row--grand">
                        <span>{{ __('Total') }} / {{ $isAr ? 'Total' : 'الإجمالي' }}</span>
                        <span dir="ltr">SAR {{ number_format($payment->amount_in_sar, 2) }}</span>
                    </div>
                </div>

                {{-- Payment Method --}}
                @if($payment->card_type || $payment->payment_method)
                <div class="invoice__payment-method">
                    <span class="text-sm text-gray-500">{{ __('Paid with') }} / {{ $isAr ? 'Paid with' : 'الدفع عبر' }}:</span>
                    <span class="text-sm font-semibold">
                        {{ $payment->payment_method ? $payment->payment_method->label() : '' }}
                        @if($payment->card_type) ({{ ucfirst($payment->card_type) }}) @endif
                        @if($payment->masked_pan) {{ $payment->masked_pan }} @endif
                    </span>
                </div>
                @endif

                {{-- Footer --}}
                <div class="invoice__footer">
                    <p>{{ $siteName ?? 'Diet Watchers' }}</p>
                    @if(!empty($contactPhone))<p dir="ltr">{{ $contactPhone }}</p>@endif
                    @if(!empty($contactEmail))<p>{{ $contactEmail }}</p>@endif
                </div>
            </div>

            {{-- Actions (outside invoice for print) --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center mt-6 no-print">
                <button onclick="window.print()" class="btn btn--outline btn--md flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg>
                    {{ __('Print Invoice') }}
                </button>
                <a href="{{ route('home') }}" class="btn btn--primary btn--md">
                    {{ __('payment.back_to_home') }}
                </a>
            </div>

        @else
            {{-- ── Failed State ──────────────────────────── --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 md:p-10 shadow-sm text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-10 w-10 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('payment.failed_heading') }}</h1>
                <p class="text-gray-600 mb-6">
                    {{ $errorMessage ?? __('payment.failed_message') }}
                </p>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a href="{{ route('payment.form', ['order' => $payment->order_number]) }}" class="btn btn--primary btn--md">
                        {{ __('payment.try_again') }}
                    </a>
                    <a href="{{ route('checkout.index') }}" class="btn btn--outline btn--md">
                        {{ __('payment.back_to_checkout') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
    /* ─── Invoice Styles ──────────────────────────── */
    .invoice {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    }
    .invoice__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f3f4f6;
    }
    .invoice__logo {
        height: 48px;
        width: auto;
        object-fit: contain;
    }
    .invoice__company {
        font-size: 1.5rem;
        font-weight: 800;
        color: #279ff9;
    }
    .invoice__badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 16px;
        background: #dcfce7;
        color: #16a34a;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .invoice__info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 24px;
        padding: 16px;
        background: #f9fafb;
        border-radius: 12px;
    }
    .invoice__info-label {
        font-size: 0.75rem;
        color: #9ca3af;
        margin-bottom: 2px;
    }
    .invoice__info-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1f2937;
    }

    .invoice__section {
        margin-bottom: 20px;
    }
    .invoice__section-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #374151;
        margin-bottom: 10px;
        padding-bottom: 6px;
        border-bottom: 1px solid #f3f4f6;
    }
    .invoice__detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .invoice__detail-item {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
    }
    .invoice__detail-label {
        font-size: 0.8rem;
        color: #6b7280;
    }
    .invoice__detail-value {
        font-size: 0.8rem;
        font-weight: 600;
        color: #1f2937;
    }

    .invoice__table-wrap {
        overflow-x: auto;
    }
    .invoice__table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    .invoice__table th {
        padding: 10px 12px;
        background: #f9fafb;
        color: #6b7280;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e5e7eb;
    }
    .invoice__table td {
        padding: 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
    }
    .invoice__table tbody tr:last-child td {
        border-bottom: none;
    }

    .invoice__totals {
        background: #f9fafb;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
    }
    .invoice__total-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 0.875rem;
        color: #4b5563;
    }
    .invoice__total-row--grand {
        border-top: 2px solid #e5e7eb;
        margin-top: 8px;
        padding-top: 12px;
        font-size: 1.1rem;
        font-weight: 800;
        color: #16a34a;
    }

    .invoice__payment-method {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        background: #eff6ff;
        border-radius: 10px;
        margin-bottom: 16px;
    }

    .invoice__footer {
        text-align: center;
        padding-top: 16px;
        border-top: 1px dashed #d1d5db;
        color: #9ca3af;
        font-size: 0.8rem;
        line-height: 1.6;
    }

    /* ─── Print Styles ────────────────────────────── */
    @media print {
        body * { visibility: hidden; }
        .invoice, .invoice * { visibility: visible; }
        .invoice {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none;
            border: none;
            padding: 20px;
        }
        .no-print { display: none !important; }
        section { background: #fff !important; padding: 0 !important; }
    }

    @media (max-width: 640px) {
        .invoice { padding: 20px; }
        .invoice__info-grid { grid-template-columns: 1fr; }
        .invoice__detail-grid { grid-template-columns: 1fr; }
        .invoice__table th:nth-child(3),
        .invoice__table td:nth-child(3) { display: none; }
    }
</style>
@endpush
