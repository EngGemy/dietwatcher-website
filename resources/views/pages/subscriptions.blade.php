@extends('layouts.app')

@section('title', __('My Subscriptions') . ' | ' . $siteName)
@section('description', __('Track and manage your meal plan subscriptions'))

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
            <li class="breadcrumb__item breadcrumb__item--active" aria-current="page">
                {{ __('My Subscriptions') }}
            </li>
        </ol>

        {{-- Lookup Form --}}
        <div class="rounded-md border border-gray-200 bg-white p-5 mb-8">
            <h2 class="text-2xl font-bold mb-4">{{ __('Find Your Subscriptions') }}</h2>
            <p class="text-gray-600 mb-4">{{ __('Enter the phone number you used when subscribing to view your subscriptions.') }}</p>

            <form action="{{ route('subscriptions.index') }}" method="GET" class="flex gap-3">
                <input type="tel" name="phone" class="form-control flex-1"
                       placeholder="{{ __('Enter your phone number') }}"
                       value="{{ $phone ?? '' }}" required dir="ltr" />
                <button type="submit" class="btn btn--primary btn--md">
                    {{ __('Search') }}
                </button>
            </form>
        </div>

        @if($phone)
            @if(!empty($subscriptions))
                <div class="space-y-4">
                    @foreach($subscriptions as $sub)
                        @php
                            $locale = app()->getLocale();
                            $subName = is_array($sub['plan_name'] ?? null)
                                ? ($sub['plan_name'][$locale] ?? $sub['plan_name']['en'] ?? '')
                                : ($sub['plan_name'] ?? '');

                            $statusColors = [
                                'active' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'paused' => 'bg-blue-100 text-blue-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                'expired' => 'bg-gray-100 text-gray-600',
                            ];
                            $statusColor = $statusColors[strtolower($sub['status'])] ?? 'bg-gray-100 text-gray-600';

                            $statusLabels = [
                                'active' => __('Active'),
                                'pending' => __('Pending'),
                                'paused' => __('Paused'),
                                'cancelled' => __('Cancelled'),
                                'expired' => __('Expired'),
                            ];
                            $statusLabel = $statusLabels[strtolower($sub['status'])] ?? ucfirst($sub['status']);

                            $subImage = $sub['plan_image'] ?? '';
                            $subImageUrl = str_starts_with($subImage, 'http') ? $subImage : asset('assets/images/plan-1.png');
                        @endphp

                        <a href="{{ route('subscriptions.show', $sub['id']) }}" class="block">
                            <div class="flex items-center gap-4 rounded-md border border-gray-200 bg-white p-4 hover:shadow-md transition-shadow">
                                <img src="{{ $subImageUrl }}" alt="{{ $subName }}"
                                     class="h-20 w-20 rounded-md object-cover flex-shrink-0"
                                     onerror="this.src='{{ asset('assets/images/plan-1.png') }}'" />

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-lg font-bold text-gray-900 truncate">{{ $subName }}</h3>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                                        @if($sub['duration_days'])
                                            <span>{{ $sub['duration_days'] }} {{ __('Days') }}</span>
                                        @endif
                                        @if($sub['start_at'])
                                            <span>{{ __('From') }}: {{ \Carbon\Carbon::parse($sub['start_at'])->format('d M Y') }}</span>
                                        @endif
                                        @if($sub['end_at'])
                                            <span>{{ __('To') }}: {{ \Carbon\Carbon::parse($sub['end_at'])->format('d M Y') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-end flex-shrink-0">
                                    <p class="text-lg font-bold text-gray-900">SAR {{ number_format($sub['total'], 2) }}</p>
                                    <p class="text-xs text-gray-500">{{ __('Incl. VAT') }}</p>
                                </div>

                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 flex-shrink-0 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if(($meta['lastPage'] ?? 1) > 1)
                    <div class="mt-6 flex justify-center gap-2">
                        @for($p = 1; $p <= $meta['lastPage']; $p++)
                            <a href="{{ route('subscriptions.index', ['phone' => $phone, 'page' => $p]) }}"
                               class="px-4 py-2 rounded-md text-sm font-medium {{ ($meta['currentPage'] ?? 1) == $p ? 'bg-blue text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                                {{ $p }}
                            </a>
                        @endfor
                    </div>
                @endif
            @else
                <div class="text-center py-16 rounded-md border border-gray-200 bg-white">
                    <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-black mb-2">{{ __('No subscriptions found') }}</h3>
                    <p class="text-black/60 mb-4">{{ __('No subscriptions found for this phone number.') }}</p>
                    <a href="{{ route('meal-plans.index') }}" class="btn btn--primary btn--md">
                        {{ __('Browse Meal Plans') }}
                    </a>
                </div>
            @endif
        @endif
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
