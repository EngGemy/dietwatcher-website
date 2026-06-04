{{-- Horizontal swipe row for plan duration cards (checkout + meal plan detail) --}}
<div class="duration-swiper" x-ref="durationSwiperRoot">
    <button
        type="button"
        class="duration-swiper__nav duration-swiper__nav--prev"
        :class="{ 'duration-swiper__nav--disabled': durationScrollAtStart }"
        :disabled="durationScrollAtStart"
        @click="scrollDurationBy(-1)"
        aria-label="{{ __('Previous') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
    </button>

    <div
        class="duration-swiper__viewport"
        x-ref="durationViewport"
        @scroll.passive="onDurationViewportScroll()"
    >
        <div class="duration-pills duration-pills--carousel">
            {{ $slot }}
        </div>
    </div>

    <button
        type="button"
        class="duration-swiper__nav duration-swiper__nav--next"
        :class="{ 'duration-swiper__nav--disabled': durationScrollAtEnd }"
        :disabled="durationScrollAtEnd"
        @click="scrollDurationBy(1)"
        aria-label="{{ __('Next') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
    </button>

    <p class="duration-swiper__hint" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" class="duration-swiper__hint-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
        </svg>
        {{ __('checkout.duration_swipe_hint') }}
    </p>
</div>
