@extends('layouts.app')

@section('title', __('FAQs'))

@section('content')
    <section class="bg-gray-200 pt-20 pb-28">
        <div class="container">
            {{-- Header --}}
            <header class="section-header max-w-3xl">
                <h2 class="section-header__title">
                    {{ $faqHeader?->title() ?? __('Frequently Asked Questions') }}
                </h2>
                <p class="section-header__desc">
                    {{ $faqHeader?->subtitle() ?? __('Find clear answers to common questions about our meal plans, subscriptions, deliveries, and payments.') }}
                </p>
            </header>

            {{-- Filters & Search --}}
            <div class="mb-10 flex flex-col gap-4 md:mb-20 md:flex-row md:items-center md:justify-between">
                {{-- Category Filters --}}
                <div class="tag-list m-0">
                    <a href="{{ route('faqs.index') }}" 
                       class="tag {{ !$categorySlug ? 'tag--active' : '' }}"
                       aria-pressed="{{ !$categorySlug ? 'true' : 'false' }}">
                        {{ __('All') }}
                    </a>
                    @foreach($categories as $category)
                        <a href="{{ route('faqs.index', ['category' => $category->slug]) }}" 
                           class="tag {{ $categorySlug == $category->slug ? 'tag--active' : '' }}"
                           aria-pressed="{{ $categorySlug == $category->slug ? 'true' : 'false' }}">
                            @if($category->icon)
                                <svg class="me-1 inline-block size-4">
                                    <use href="{{ asset('assets/images/icons/sprite.svg#' . $category->icon) }}"></use>
                                </svg>
                            @endif
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>

                {{-- Search --}}
                <form action="{{ route('faqs.index') }}" method="GET" class="relative">
                    <input
                        class="form-control--underline min-w-[225px] px-4"
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('Search') }}"
                    />
                    <button type="submit" class="absolute end-2.5 top-1/2 -translate-y-1/2">
                        <svg class="size-6 shrink-0 text-gray-500">
                            <use href="{{ asset('assets/images/icons/sprite.svg#search') }}"></use>
                        </svg>
                    </button>
                </form>
            </div>

            {{-- FAQs Accordion --}}
            <div class="hs-accordion-group mx-auto max-w-4xl space-y-4">
                @forelse ($faqs as $index => $faq)
                    <div
                        class="hs-accordion {{ $index === 0 ? 'active' : '' }} hs-accordion-active:border-blue/10 hs-accordion-active:bg-white rounded-xl border border-transparent">
                        <button
                            class="hs-accordion-toggle inline-flex w-full items-center justify-between gap-x-3 px-5 py-4 text-start text-lg font-medium text-black focus:outline-hidden md:text-xl"
                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-controls="hs-faq-collapse-{{ $faq->id }}">
                            {{ $faq->question }}
                            <svg class="hs-accordion-active:hidden size-5">
                                <use href="{{ asset('assets/images/icons/sprite.svg#plus') }}"></use>
                            </svg>
                            <svg class="hs-accordion-active:block hidden size-5">
                                <use href="{{ asset('assets/images/icons/sprite.svg#minus') }}"></use>
                            </svg>
                        </button>
                        <div
                            role="region"
                            id="hs-faq-collapse-{{ $faq->id }}"
                            class="hs-accordion-content {{ $index === 0 ? '' : 'hidden' }} w-full overflow-hidden transition-[height] duration-300">
                            <div class="px-5 pb-4">
                                <div class="prose prose-gray max-w-none text-gray-600">
                                    {!! $faq->answer !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-10">
                        @if($search)
                            {{ __('No FAQs found matching your search.') }}
                        @elseif($categorySlug)
                            {{ __('No FAQs found in this category.') }}
                        @else
                            {{ __('No FAQs available yet.') }}
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
