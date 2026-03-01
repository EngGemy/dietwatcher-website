@extends('layouts.app')

@section('title', __('Meals') . ' - ' . config('app.name'))

@section('content')
{{-- Hero Banner --}}
<section class="meals-hero">
    <div class="container">
        <div class="meals-hero__content">
            <span class="meals-hero__badge">{{ __('Instant Orders') }}</span>
            <h1 class="meals-hero__title">{{ __('Order Individual Meals Anytime') }}</h1>
            <p class="meals-hero__desc">
                {{ __('Explore chef-prepared meals and healthy options available for instant order.') }}
            </p>
        </div>
    </div>
    <div class="meals-hero__pattern"></div>
</section>

{{-- Meals Content --}}
<section class="meals-section">
    <div class="container">
        <livewire:meals.meals-list />
    </div>
</section>
@endsection

@push('styles')
<style>
/* ─── Hero ───────────────────────────────────────── */
.meals-hero {
    position: relative;
    background: linear-gradient(135deg, #279ff9 0%, #1a6dd4 50%, #0d4fa3 100%);
    padding: 4rem 0 5rem;
    overflow: hidden;
}
.meals-hero__content {
    position: relative;
    z-index: 2;
    max-width: 640px;
}
.meals-hero__badge {
    display: inline-block;
    padding: 0.35rem 1rem;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 100px;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 1rem;
}
.meals-hero__title {
    color: #fff;
    font-size: clamp(1.75rem, 4vw, 2.75rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 0.75rem;
}
.meals-hero__desc {
    color: rgba(255,255,255,0.8);
    font-size: 1.05rem;
    line-height: 1.6;
}
.meals-hero__pattern {
    position: absolute;
    top: -40%;
    inset-inline-end: -10%;
    width: 500px;
    height: 500px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    z-index: 1;
}
[dir="rtl"] .meals-hero__pattern { inset-inline-end: auto; inset-inline-start: -10%; }

/* ─── Meals Section ──────────────────────────────── */
.meals-section {
    background: #f5f5fa;
    padding: 0 0 5rem;
    margin-top: -2rem;
    position: relative;
    z-index: 3;
}
</style>
@endpush
