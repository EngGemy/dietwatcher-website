@extends('layouts.app')

@section('title', __('Blog') . ' - ' . config('app.name'))

@section('content')

{{-- Hero Banner --}}
<section class="blog-hero">
    <div class="blog-hero__inner">
        <h1 class="blog-hero__title">{{ __('blog.hero_title') }}</h1>
        <p class="blog-hero__subtitle">{{ __('blog.hero_subtitle') }}</p>
    </div>
</section>

<section class="blog-page">
    <div class="container">
        {{-- Category Tabs + Search --}}
        <div class="blog-tabs-bar">
            <div class="blog-tabs-row">
                <nav class="blog-tabs" aria-label="{{ __('Blog') }}">
                    <a
                        href="{{ route('blog.index', request()->only(['search', 'tag'])) }}"
                        class="blog-tab {{ ! request('category') ? 'blog-tab--active' : '' }}"
                    >{{ __('All') }}</a>
                    @foreach($categories as $cat)
                        @php
                            $iconMap = [
                                'recipes' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0L3 16.5m15-3.379a48.474 48.474 0 00-6-.371c-2.032 0-4.034.126-6 .371m12 0c.39.049.777.102 1.163.16 1.07.16 1.837 1.094 1.837 2.175v5.169c0 .621-.504 1.125-1.125 1.125H4.125A1.125 1.125 0 013 20.625V15.326c0-1.081.768-2.015 1.837-2.175A48.604 48.604 0 016 13.12"/></svg>',
                                'health' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>',
                                'weight-loss' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z"/></svg>',
                                'diet-plans' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>',
                                'nutrition' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.047 8.287 8.287 0 009 9.601a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.468 5.99 5.99 0 00-1.925 3.547 5.975 5.975 0 01-2.133-1.001A3.75 3.75 0 0012 18z"/></svg>',
                                'tips' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/></svg>',
                                'balanced-life' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z"/></svg>',
                                'mindfulness' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>',
                                'meal-prep' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                                'diets' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>',
                            ];
                            $icon = $iconMap[$cat->slug] ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>';
                        @endphp
                        <a
                            href="{{ route('blog.index', array_merge(request()->only(['search', 'tag']), ['category' => $cat->slug])) }}"
                            class="blog-tab {{ request('category') === $cat->slug ? 'blog-tab--active' : '' }}"
                        >
                            <span class="blog-tab__icon">{!! $icon !!}</span>
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </nav>

                <form action="{{ route('blog.index') }}" method="GET" class="blog-search">
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}" />
                    @endif
                    @if(request('tag'))
                        <input type="hidden" name="tag" value="{{ request('tag') }}" />
                    @endif
                    <svg class="blog-search__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        class="blog-search__input"
                        placeholder="{{ __('Search articles...') }}"
                        autocomplete="off"
                    />
                    @if(request('search'))
                        <a href="{{ route('blog.index', request()->except('search')) }}" class="blog-search__clear" title="{{ __('Clear') }}">
                            <svg style="width:12px;height:12px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        @if(request('search') || request('tag'))
            <div class="blog-filter-chips">
                @if(request('search'))
                    <span class="blog-filter-chip blog-filter-chip--search">
                        {{ __('Results for') }} "<strong>{{ request('search') }}</strong>"
                    </span>
                @endif
                @if(request('tag'))
                    <a href="{{ route('blog.index', request()->except('tag')) }}" class="blog-filter-chip blog-filter-chip--tag">
                        #{{ request('tag') }}
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                <a href="{{ route('blog.index') }}" class="blog-filter-clear">{{ __('Clear all') }}</a>
            </div>
        @endif

        @if($posts->count() > 0)
            <div class="blog-grid">
                @foreach($posts as $post)
                    @include('blog.partials.post-card', ['post' => $post])
                @endforeach
            </div>

            @if($posts->hasPages())
                <div class="blog-pagination-wrap">
                    {{ $posts->links() }}
                </div>
            @endif
        @else
            <div class="blog-empty">
                <div class="blog-empty__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </div>
                <h3 class="blog-empty__title">{{ __('No articles found') }}</h3>
                <p class="blog-empty__desc">{{ __('Try adjusting your search or filters.') }}</p>
                @if(request('search') || request('category') || request('tag'))
                    <a href="{{ route('blog.index') }}" class="btn btn--primary mt-8">{{ __('Clear filters') }}</a>
                @endif
            </div>
        @endif
    </div>
</section>

@endsection

@push('styles')
<style>
/* ─── Hero ─────────────────────────────────────────── */
.blog-hero {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 64px 24px;
    background: linear-gradient(90deg, #279FF9 0%, #269CF6 16.67%, #259AF3 33.33%, #2397F0 50%, #2295EE 66.67%, #2192EB 83.33%, #2090E8 100%);
}
.blog-hero__inner {
    text-align: center;
    max-width: 700px;
}
.blog-hero__title {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: clamp(2rem, 5vw, 3.5rem);
    line-height: 1.5;
    color: #FFFFFF;
    margin-bottom: 0.5rem;
}
.blog-hero__subtitle {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 400;
    font-size: clamp(1rem, 2vw, 1.375rem);
    line-height: 1.5;
    color: #FFFFFF;
    opacity: 0.9;
}

/* ─── Blog Page Section ────────────────────────────── */
.blog-page {
    background: #F5F5FA;
    padding: 0 0 5rem;
    position: relative;
    z-index: 3;
}

/* ─── Tabs Bar ─────────────────────────────────────── */
.blog-tabs-bar {
    padding: 1.5rem 0 0;
}
.blog-tabs-row {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
}
@media (min-width: 768px) {
    .blog-tabs-row {
        flex-direction: row;
        align-items: flex-end;
        justify-content: space-between;
        gap: 2rem;
    }
}
.blog-tabs {
    display: flex;
    align-items: center;
    gap: 0;
    flex: 1;
    min-width: 0;
    overflow-x: auto;
    scrollbar-width: none;
    border-bottom: 1px solid rgba(128, 128, 137, 0.3);
}
.blog-tabs::-webkit-scrollbar { display: none; }
.blog-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.75rem 1.25rem;
    font-family: 'Segoe UI', 'Almarai', sans-serif;
    font-size: 1.125rem;
    font-weight: 400;
    line-height: 24px;
    color: rgba(39, 39, 42, 0.7);
    text-decoration: none;
    white-space: nowrap;
    border-bottom: 3px solid transparent;
    margin-bottom: -1px;
    transition: color 0.2s, border-color 0.2s;
}
.blog-tab:hover {
    color: #279FF9;
}
.blog-tab--active {
    color: #279FF9;
    font-weight: 600;
    border-bottom-color: #279FF9;
}
.blog-tab__icon {
    display: inline-flex;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}
.blog-tab__icon svg {
    width: 24px;
    height: 24px;
}

/* ─── Search ───────────────────────────────────────── */
.blog-search {
    position: relative;
    width: 100%;
    flex-shrink: 0;
}
@media (min-width: 768px) {
    .blog-search {
        width: 220px;
        margin-bottom: 0.25rem;
    }
}
.blog-search__icon {
    position: absolute;
    top: 50%;
    inset-inline-start: 14px;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #808089;
    pointer-events: none;
}
.blog-search__input {
    width: 100%;
    height: 44px;
    padding: 10px 14px 10px 40px;
    background: #FFFFFF;
    border: 1px solid rgba(128, 128, 137, 0.3);
    box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1), 0px 1px 2px -1px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    font-family: 'Inter', 'Almarai', sans-serif;
    font-size: 0.9375rem;
    line-height: 22px;
    color: #0a0a0a;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
[dir="rtl"] .blog-search__input { padding: 10px 40px 10px 14px; }
.blog-search__input::placeholder { color: rgba(10, 10, 10, 0.5); }
.blog-search__input:focus {
    border-color: #279ff9;
    box-shadow: 0 0 0 3px rgba(39,159,249,0.1);
}
.blog-search__clear {
    position: absolute;
    top: 50%;
    inset-inline-end: 10px;
    transform: translateY(-50%);
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e8e8ef;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
    padding: 0;
    text-decoration: none;
}
.blog-search__clear:hover { background: #ff707a; color: #fff; }

/* ─── Filter Chips ─────────────────────────────────── */
.blog-filter-chips {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin: 1.5rem 0;
}
.blog-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    color: #808089;
}
.blog-filter-chip--tag {
    padding: 0.375rem 0.75rem;
    border-radius: 100px;
    background: rgba(39, 159, 249, 0.1);
    color: #279ff9;
    font-weight: 500;
    text-decoration: none;
    transition: background 0.2s;
}
.blog-filter-chip--tag:hover { background: rgba(39, 159, 249, 0.2); }
.blog-filter-clear {
    font-size: 0.875rem;
    font-weight: 600;
    color: #dc2626;
    text-decoration: none;
}
.blog-filter-clear:hover { text-decoration: underline; }

/* ─── Grid ─────────────────────────────────────────── */
.blog-grid {
    display: grid;
    align-items: stretch;
    gap: 2rem;
    grid-template-columns: 1fr;
    margin-top: 2rem;
}
@media (min-width: 640px)  { .blog-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .blog-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1280px) { .blog-grid { grid-template-columns: repeat(4, 1fr); } }

/* ─── Blog Post Card ───────────────────────────────── */
.bpost-card {
    background: #FFFFFF;
    box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1), 0px 1px 2px -1px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.bpost-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

/* Card Image */
.bpost-card__img-wrap {
    position: relative;
    width: 100%;
    height: 240px;
    overflow: hidden;
    background: #f0f0f5;
    flex-shrink: 0;
    display: block;
    text-decoration: none;
}
.bpost-card__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.bpost-card:hover .bpost-card__img { transform: scale(1.06); }

/* Category Badge */
.bpost-card__badge {
    position: absolute;
    top: 13px;
    inset-inline-start: 16px;
    display: inline-flex;
    align-items: center;
    padding: 6px 16px;
    background: #279FF9;
    border-radius: 9999px;
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 500;
    font-size: 0.875rem;
    line-height: 21px;
    color: #FFFFFF;
    pointer-events: none;
    z-index: 2;
}

/* Card Body */
.bpost-card__body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    position: relative;
    background: #FFFFFF;
}

/* Meta Row */
.bpost-card__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem;
    font-family: 'Inter', 'Almarai', sans-serif;
    font-size: 0.875rem;
    line-height: 21px;
    color: #808089;
    margin-bottom: 0.75rem;
}
.bpost-card__meta-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.bpost-card__meta-item svg {
    width: 16px;
    height: 16px;
    stroke: #808089;
    flex-shrink: 0;
}

/* Title */
.bpost-card__title {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: 1.375rem;
    line-height: 33px;
    color: #27272A;
    margin-bottom: 0.75rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
[dir="rtl"] .bpost-card__title { text-align: right; }
.bpost-card__title a {
    color: inherit;
    text-decoration: none;
    transition: color 0.2s;
}
.bpost-card__title a:hover { color: #279FF9; }

/* Excerpt */
.bpost-card__excerpt {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 400;
    font-size: 1rem;
    line-height: 24px;
    color: #808089;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
    min-height: 0;
}
[dir="rtl"] .bpost-card__excerpt { text-align: right; }

/* Footer */
.bpost-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: auto;
    flex-shrink: 0;
    padding-top: 0.75rem;
    border-top: 0.667px solid rgba(128, 128, 137, 0.2);
}
.bpost-card__read-more {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 500;
    font-size: 1rem;
    line-height: 24px;
    color: #279FF9;
    text-decoration: none;
    transition: color 0.2s;
}
.bpost-card__read-more:hover { color: #1e8de0; }
.bpost-card__read-more-icon {
    width: 16px;
    height: 16px;
}
.bpost-card__author {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 400;
    font-size: 0.875rem;
    line-height: 21px;
    color: #808089;
    text-align: end;
    max-width: 55%;
}

/* Card Animation */
@keyframes bpost-card-in {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}
.bpost-card { animation: bpost-card-in 0.4s ease both; }
.bpost-card:nth-child(2) { animation-delay: .05s; }
.bpost-card:nth-child(3) { animation-delay: .1s; }
.bpost-card:nth-child(4) { animation-delay: .15s; }
.bpost-card:nth-child(5) { animation-delay: .2s; }
.bpost-card:nth-child(6) { animation-delay: .25s; }
.bpost-card:nth-child(7) { animation-delay: .3s; }
.bpost-card:nth-child(8) { animation-delay: .35s; }

/* ─── Empty State ──────────────────────────────────── */
.blog-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 1rem;
}
.blog-empty__icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.25rem;
    background: #e8e8ef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.blog-empty__icon svg { width: 36px; height: 36px; color: #bbb; }
.blog-empty__title { font-size: 1.25rem; font-weight: 700; color: #2e2e30; margin-bottom: 0.5rem; }
.blog-empty__desc { color: #808089; font-size: 0.9rem; }

/* ─── Pagination ───────────────────────────────────── */
.blog-pagination-wrap { margin-top: 2.5rem; display: flex; justify-content: center; }
.blog-page nav[role="navigation"] .inline-flex.shadow-sm.rounded-md a[href]:not([aria-disabled]),
.blog-page nav[role="navigation"] .inline-flex.shadow-sm.rounded-md span[aria-current="page"] span { border-radius: 10px !important; margin: 0 2px !important; }
.blog-page nav[role="navigation"] [aria-current="page"] span { border-color: #279ff9 !important; background: #279ff9 !important; color: #fff !important; }
.blog-page nav[role="navigation"] a:hover { border-color: #279ff9 !important; color: #279ff9 !important; }
</style>
@endpush
