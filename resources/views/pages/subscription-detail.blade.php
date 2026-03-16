@extends('layouts.app')

@php
$locale = app()->getLocale();

$subName = is_array($subscription['plan_name'] ?? null)
    ? ($subscription['plan_name'][$locale] ?? $subscription['plan_name']['en'] ?? '')
    : ($subscription['plan_name'] ?? __('Subscription'));

$statusColors = [
    'active' => 'bg-green-100 text-green-700',
    'pending' => 'bg-yellow-100 text-yellow-700',
    'paused' => 'bg-blue-100 text-blue-700',
    'cancelled' => 'bg-red-100 text-red-700',
    'expired' => 'bg-gray-100 text-gray-600',
];
$status = strtolower($subscription['status']);
$statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-600';

$statusLabels = [
    'active' => __('Active'),
    'pending' => __('Pending'),
    'paused' => __('Paused'),
    'cancelled' => __('Cancelled'),
    'expired' => __('Expired'),
];
$statusLabel = $statusLabels[$status] ?? ucfirst($subscription['status']);

$subImage = $subscription['plan_image'] ?? '';
$subImageUrl = str_starts_with($subImage, 'http') ? $subImage : asset('assets/images/plan-1.png');
@endphp

@section('title', $subName . ' | ' . $siteName)

@section('content')
<section class="bg-gray-200 pt-10 pb-28">
    <div class="container max-w-[1000px]">
        {{-- Breadcrumb --}}
        <ol class="breadcrumb">
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('home') }}">{{ __('Home') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </li>
            <li class="breadcrumb__item">
                <a class="breadcrumb__link" href="{{ route('subscriptions.index', ['phone' => $subscription['customer_phone'] ?? '']) }}">{{ __('My Subscriptions') }}</a>
                <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </li>
            <li class="breadcrumb__item breadcrumb__item--active" aria-current="page">
                {{ $subName }}
            </li>
        </ol>

        <div class="grid gap-6 md:grid-cols-3">
            {{-- Main Info --}}
            <div class="md:col-span-2 space-y-6">
                {{-- Plan Card --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <div class="flex items-start gap-4">
                        <img src="{{ $subImageUrl }}" alt="{{ $subName }}"
                             class="h-24 w-24 rounded-md object-cover flex-shrink-0"
                             onerror="this.src='{{ asset('assets/images/plan-1.png') }}'" />
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h1 class="text-2xl font-bold">{{ $subName }}</h1>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                                @if($subscription['duration_days'])
                                    <span>{{ $subscription['duration_days'] }} {{ __('Days') }}</span>
                                @endif
                                @if($subscription['calorie_range'])
                                    <span>{{ $subscription['calorie_range'] }} {{ __('kcal') }}</span>
                                @endif
                                @if($subscription['with_weekend'])
                                    <span>{{ __('Weekends included') }}</span>
                                @else
                                    <span>{{ __('Weekdays only') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <h2 class="text-xl font-bold mb-4">{{ __('Schedule') }}</h2>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="rounded-lg bg-green-50 p-4">
                            <p class="text-sm text-gray-600 mb-1">{{ __('Start Date') }}</p>
                            <p class="text-lg font-bold text-green-700">
                                @if($subscription['start_at'])
                                    {{ \Carbon\Carbon::parse($subscription['start_at'])->translatedFormat('d M Y') }}
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div class="rounded-lg bg-red-50 p-4">
                            <p class="text-sm text-gray-600 mb-1">{{ __('End Date') }}</p>
                            <p class="text-lg font-bold text-red-700">
                                @if($subscription['end_at'])
                                    {{ \Carbon\Carbon::parse($subscription['end_at'])->translatedFormat('d M Y') }}
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($status === 'active' && $subscription['start_at'] && $subscription['end_at'])
                        @php
                            $start = \Carbon\Carbon::parse($subscription['start_at']);
                            $end = \Carbon\Carbon::parse($subscription['end_at']);
                            $now = now();
                            $totalDays = $start->diffInDays($end);
                            $passedDays = $start->diffInDays($now);
                            $progress = $totalDays > 0 ? min(100, round(($passedDays / $totalDays) * 100)) : 0;
                            $remainingDays = max(0, $end->diffInDays($now));
                        @endphp
                        <div class="mt-2">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>{{ __('Progress') }}</span>
                                <span>{{ $remainingDays }} {{ __('days remaining') }}</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-200">
                                <div class="h-2 rounded-full bg-green-500 transition-all" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Delivery Days Timeline --}}
                @if(!empty($subscription['days']))
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <h2 class="text-xl font-bold mb-4">{{ __('Delivery Days') }}</h2>

                    <div class="space-y-2 max-h-[400px] overflow-y-auto">
                        @foreach($subscription['days'] as $day)
                            @php
                                $dayStatus = $day['status'] ?? 'pending';
                                $dayStatusColors = [
                                    'pending' => 'border-yellow-200 bg-yellow-50',
                                    'received' => 'border-green-200 bg-green-50',
                                    'skipped' => 'border-gray-200 bg-gray-50',
                                    'reparation' => 'border-blue-200 bg-blue-50',
                                ];
                                $dayColor = $dayStatusColors[$dayStatus] ?? 'border-gray-200 bg-gray-50';
                            @endphp
                            <div class="flex items-center gap-3 rounded-lg border p-3 {{ $dayColor }}">
                                <div class="text-center flex-shrink-0 w-12">
                                    <p class="text-lg font-bold">{{ \Carbon\Carbon::parse($day['date'] ?? '')->format('d') }}</p>
                                    <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($day['date'] ?? '')->translatedFormat('M') }}</p>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium">{{ \Carbon\Carbon::parse($day['date'] ?? '')->translatedFormat('l') }}</p>
                                    <p class="text-xs text-gray-500">{{ ucfirst($day['type'] ?? 'regular') }}</p>
                                </div>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                                    {{ $dayStatus === 'received' ? 'bg-green-200 text-green-800' : '' }}
                                    {{ $dayStatus === 'pending' ? 'bg-yellow-200 text-yellow-800' : '' }}
                                    {{ $dayStatus === 'skipped' ? 'bg-gray-200 text-gray-600' : '' }}
                                    {{ $dayStatus === 'reparation' ? 'bg-blue-200 text-blue-800' : '' }}
                                ">
                                    {{ __(ucfirst($dayStatus)) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar: Payment Info --}}
            <div class="space-y-6">
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <h2 class="text-xl font-bold mb-4">{{ __('Payment Details') }}</h2>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">{{ __('Subtotal') }}</span>
                            <span class="font-semibold">SAR {{ number_format($subscription['total'] - ($subscription['tax'] ?? 0), 2) }}</span>
                        </div>
                        @if($subscription['discount'] > 0)
                            <div class="flex justify-between">
                                <span class="text-green-600">{{ __('Discount') }}</span>
                                <span class="font-semibold text-green-600">- SAR {{ number_format($subscription['discount'], 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-sm text-gray-400">
                            <span>{{ __('VAT included') }}</span>
                            <span>SAR {{ number_format($subscription['tax'] ?? 0, 2) }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-3 flex justify-between">
                            <span class="text-lg font-bold">{{ __('Total') }}</span>
                            <span class="text-lg font-bold text-green-600">SAR {{ number_format($subscription['total'], 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Delivery Zone --}}
                @if($subscription['zone_name'])
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <h2 class="text-xl font-bold mb-3">{{ __('Delivery Zone') }}</h2>
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        @php
                            $zoneName = is_array($subscription['zone_name'])
                                ? ($subscription['zone_name'][$locale] ?? $subscription['zone_name']['en'] ?? '')
                                : $subscription['zone_name'];
                        @endphp
                        <span class="font-medium">{{ $zoneName }}</span>
                    </div>
                </div>
                @endif

                {{-- Customer Info --}}
                @if($subscription['customer_name'] || $subscription['customer_phone'])
                <div class="rounded-md border border-gray-200 bg-white p-5">
                    <h2 class="text-xl font-bold mb-3">{{ __('Customer') }}</h2>
                    <div class="space-y-2 text-sm">
                        @if($subscription['customer_name'])
                            <p><span class="text-gray-500">{{ __('Name') }}:</span> {{ $subscription['customer_name'] }}</p>
                        @endif
                        @if($subscription['customer_phone'])
                            <p><span class="text-gray-500">{{ __('Phone') }}:</span> <span dir="ltr">{{ $subscription['customer_phone'] }}</span></p>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
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
</style>
@endpush
