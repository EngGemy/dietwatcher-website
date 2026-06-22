@extends('layouts.app')

@section('title', __('Blog') . ' - ' . config('app.name'))

@section('content')
@php
    $isFirstPage = ! request()->has('page') || (int) request('page', 1) <= 1;
    $hasFilters = request('search') || request('category') || request('tag');
    $featuredPost = ($isFirstPage && ! $hasFilters) ? $posts->first() : null;
    $regularPosts = $featuredPost ? $posts->slice(1) : $posts;
@endphp

<section class="blogx-hero">
    <div class="container blogx-hero__inner">
        <p class="blogx-hero__eyebrow">{{ __('Diet Watchers') }}</p>
        <h1 class="blogx-hero__title">{{ __('blog.hero_title') }}</h1>
        <p class="blogx-hero__subtitle">{{ __('blog.hero_subtitle') }}</p>

        <form action="{{ route('blog.index') }}" method="GET" class="blogx-search">
            @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
            @if(request('tag'))<input type="hidden" name="tag" value="{{ request('tag') }}">@endif
            <svg class="blogx-search__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="search" name="search" value="{{ request('search') }}" class="blogx-search__input" placeholder="{{ __('blog.search_placeholder') }}" autocomplete="off">
            <button type="submit" class="blogx-search__btn">{{ __('blog.search') }}</button>
        </form>
    </div>
</section>

<section class="blogx-page">
    <div class="container">
        <div class="blogx-toolbar">
            <div class="blogx-cats" role="tablist" aria-label="{{ __('blog.categories') }}">
                <a href="{{ route('blog.index', request()->only(['search', 'tag'])) }}" class="blogx-cat {{ !request('category') ? 'is-active' : '' }}">{{ __('All') }}</a>
                @foreach($categories as $cat)
                    <a href="{{ route('blog.index', array_merge(request()->only(['search', 'tag']), ['category' => $cat->slug])) }}" class="blogx-cat {{ request('category') === $cat->slug ? 'is-active' : '' }}">
                        <span class="blogx-cat__dot"></span>
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>

            <div class="blogx-toolbar__meta">
                <span>{{ __('blog.articles_count_label') }}: <strong>{{ $posts->total() }}</strong></span>
                @if(request('search'))
                    <span>{{ __('blog.results_for') }} <strong>"{{ request('search') }}"</strong></span>
                @endif
            </div>
        </div>

        @if(request('search') || request('tag') || request('category'))
            <div class="blogx-filters">
                @if(request('category'))
                    <a href="{{ route('blog.index', request()->except('category')) }}" class="blogx-chip">{{ __('blog.category_label') }}: {{ request('category') }} &times;</a>
                @endif
                @if(request('tag'))
                    <a href="{{ route('blog.index', request()->except('tag')) }}" class="blogx-chip">#{{ request('tag') }} &times;</a>
                @endif
                @if(request('search'))
                    <a href="{{ route('blog.index', request()->except('search')) }}" class="blogx-chip">{{ __('blog.search') }}: {{ request('search') }} &times;</a>
                @endif
                <a href="{{ route('blog.index') }}" class="blogx-clear">{{ __('Clear all') }}</a>
            </div>
        @endif

        @if($featuredPost)
            @php
                $featuredHref = $featuredPost->showUrl();
            @endphp
            <article class="blogx-featured">
                <a href="{{ $featuredHref }}" class="blogx-featured__media" aria-label="{{ $featuredPost->title }}">
                    <img src="{{ $featuredPost->cover_image_url }}" alt="{{ $featuredPost->title }}" loading="lazy">
                </a>
                <div class="blogx-featured__body">
                    @if($featuredPost->category)
                        <a href="{{ route('blog.index', ['category' => $featuredPost->category->slug]) }}" class="blogx-featured__badge">{{ $featuredPost->category->name }}</a>
                    @endif
                    <h2 class="blogx-featured__title"><a href="{{ $featuredHref }}">{{ $featuredPost->title }}</a></h2>
                    <p class="blogx-featured__excerpt">{{ Str::limit(strip_tags($featuredPost->excerpt ?: $featuredPost->content), 220) }}</p>
                    <div class="blogx-featured__meta">
                        <span>{{ $featuredPost->formatted_date }}</span>
                        @if($featuredPost->reading_time_minutes)<span>&bull; {{ $featuredPost->reading_time_minutes }} {{ __('min read') }}</span>@endif
                    </div>
                    <a href="{{ $featuredHref }}" class="blogx-featured__cta">{{ __('blog.read_article') }}</a>
                </div>
            </article>
        @endif

        @if($regularPosts->count() > 0)
            <div class="blogx-grid">
                @foreach($regularPosts as $post)
                    @include('blog.partials.post-card', ['post' => $post])
                @endforeach
            </div>

            @if($posts->hasPages())
                <div class="blogx-pagination">{{ $posts->links() }}</div>
            @endif
        @elseif(! $featuredPost)
            <div class="blogx-empty">
                <h3>{{ __('No articles found') }}</h3>
                <p>{{ __('blog.try_different_keywords') }}</p>
                <a href="{{ route('blog.index') }}" class="blogx-empty__btn">{{ __('blog.browse_all_articles') }}</a>
            </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
.blogx-hero { background: radial-gradient(1400px 560px at 80% -10%, rgba(255,255,255,.4), transparent), linear-gradient(125deg, #279ff9 0%, #1876d1 100%); padding: 5.25rem 0 4.5rem; }
.blogx-hero__inner { text-align: center; max-width: 880px; }
.blogx-hero__eyebrow { margin: 0 0 .6rem; color: rgba(255,255,255,.9); font-weight: 700; letter-spacing: .08em; text-transform: uppercase; font-size: .8rem; }
.blogx-hero__title { margin: 0; color: #fff; font-size: clamp(2rem, 4vw, 3.3rem); font-weight: 800; line-height: 1.2; }
.blogx-hero__subtitle { margin: 1rem auto 0; max-width: 680px; color: rgba(255,255,255,.92); font-size: clamp(1rem, 2vw, 1.2rem); }
.blogx-search { margin: 1.7rem auto 0; max-width: 680px; display: grid; grid-template-columns: 1fr auto; gap: .7rem; position: relative; }
.blogx-search__icon { position: absolute; top: 50%; transform: translateY(-50%); inset-inline-start: 14px; width: 18px; height: 18px; color: #8ea4bf; pointer-events: none; }
.blogx-search__input { height: 50px; border-radius: 14px; border: 1px solid rgba(255,255,255,.35); background: #fff; padding-inline: 2.4rem .9rem; outline: none; }
.blogx-search__input:focus { border-color: #fff; box-shadow: 0 0 0 4px rgba(255,255,255,.24); }
.blogx-search__btn { height: 50px; border: 0; border-radius: 14px; background: #0f172a; color: #fff; padding: 0 1.2rem; font-weight: 700; }

.blogx-page { background: #f4f7fb; padding: 2rem 0 5rem; font-family: 'Almarai', 'Inter', sans-serif; }
.blogx-toolbar { display: flex; flex-direction: column; gap: .95rem; margin-bottom: 1.2rem; }
.blogx-cats { display: flex; gap: .55rem; overflow-x: auto; padding-bottom: .2rem; }
.blogx-cat { display: inline-flex; align-items: center; gap: .4rem; white-space: nowrap; text-decoration: none; color: #1f2937; border: 1px solid #d7deea; background: #fff; border-radius: 999px; padding: .5rem .9rem; font-size: .88rem; font-weight: 600; transition: .2s; }
.blogx-cat__dot { width: 6px; height: 6px; border-radius: 50%; background: #91a3be; }
.blogx-cat.is-active, .blogx-cat:hover { border-color: #279ff9; color: #279ff9; background: #eff7ff; }
.blogx-toolbar__meta { display: flex; flex-wrap: wrap; gap: .9rem; color: #64748b; font-size: .9rem; }

.blogx-filters { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: 1.2rem; }
.blogx-chip { text-decoration: none; color: #0f172a; background: #e9eff7; border-radius: 999px; padding: .36rem .75rem; font-size: .82rem; }
.blogx-clear { color: #dc2626; font-size: .85rem; font-weight: 700; text-decoration: none; margin-inline-start: .35rem; }

.blogx-featured { background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; overflow: hidden; box-shadow: 0 28px 60px rgba(15,23,42,.12); margin-bottom: 1.6rem; display: grid; grid-template-columns: 1.15fr 1fr; }
.blogx-featured__media img { width: 100%; height: 100%; object-fit: cover; min-height: 320px; }
.blogx-featured__media { aspect-ratio: 16 / 10; }
.blogx-featured__body { padding: 1.7rem; display: flex; flex-direction: column; }
.blogx-featured__badge { align-self: flex-start; text-decoration: none; background: #279ff9; color: #fff; border-radius: 999px; padding: .38rem .8rem; font-size: .76rem; font-weight: 700; }
.blogx-featured__title { margin: .9rem 0 .6rem; font-size: clamp(1.35rem,2.2vw,2rem); line-height: 1.3; }
.blogx-featured__title a { color: #0f172a; text-decoration: none; }
.blogx-featured__title a:hover { color: #279ff9; }
.blogx-featured__excerpt { color: #64748b; line-height: 1.8; margin: 0 0 1rem; }
.blogx-featured__meta { color: #94a3b8; font-size: .85rem; margin-bottom: 1rem; }
.blogx-featured__cta { text-decoration: none; display: inline-flex; align-items: center; justify-content: center; align-self: flex-start; border-radius: 12px; background: #0f172a; color: #fff; padding: .65rem 1rem; font-weight: 700; }

.blogx-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
.blogx-pagination { margin-top: 2rem; display: flex; justify-content: center; }
.blogx-empty { background: #fff; border: 1px dashed #cbd5e1; border-radius: 18px; text-align: center; padding: 3rem 1rem; }
.blogx-empty h3 { margin: 0 0 .45rem; color: #0f172a; font-size: 1.2rem; font-weight: 800; }
.blogx-empty p { color: #64748b; margin: 0 0 1rem; }
.blogx-empty__btn { text-decoration: none; border-radius: 12px; padding: .65rem 1rem; background: #279ff9; color: #fff; font-weight: 700; }

.bpost-card { background: #fff; border-radius: 18px; border: 1px solid #dbe5f1; overflow: hidden; box-shadow: 0 8px 20px rgba(15,23,42,.05); transition: .25s ease; }
.bpost-card:hover { transform: translateY(-4px); box-shadow: 0 16px 30px rgba(15,23,42,.12); border-color: #bfdbfe; }
.bpost-card__img-wrap { display: block; height: 210px; overflow: hidden; position: relative; }
.bpost-card__img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
.bpost-card:hover .bpost-card__img { transform: scale(1.04); }
.bpost-card__badge { position: absolute; top: 10px; inset-inline-start: 10px; z-index: 2; background: rgba(15,23,42,.82); color: #fff; border-radius: 999px; padding: .3rem .65rem; font-size: .72rem; }
.bpost-card__body { padding: .95rem; display: flex; flex-direction: column; gap: .55rem; }
.bpost-card__meta { color: #94a3b8; font-size: .78rem; display: flex; flex-wrap: wrap; gap: .7rem; }
.bpost-card__meta-item { display: inline-flex; align-items: center; gap: .35rem; }
.bpost-card__meta-item svg { width: 14px; height: 14px; }
.bpost-card__title { margin: 0; font-size: 1rem; line-height: 1.5; font-weight: 800; color: #0f172a; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
.bpost-card__title a { color: inherit; text-decoration: none; }
.bpost-card__title a:hover { color: #279ff9; }
.bpost-card__excerpt { margin: 0; color: #64748b; font-size: .9rem; line-height: 1.7; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; }
.bpost-card__footer { margin-top: .2rem; padding-top: .7rem; border-top: 1px solid #eef2f7; display: flex; justify-content: space-between; align-items: center; gap: .7rem; }
.bpost-card__read-more { text-decoration: none; color: #279ff9; font-weight: 700; font-size: .84rem; display: inline-flex; gap: .35rem; align-items: center; }
.bpost-card__read-more-icon { width: 14px; height: 14px; }
.bpost-card__author { color: #94a3b8; font-size: .76rem; }

@media (max-width: 1200px) { .blogx-grid { grid-template-columns: repeat(3,minmax(0,1fr)); } }
@media (max-width: 992px) {
    .blogx-featured { grid-template-columns: 1fr; }
    .blogx-featured__media img { min-height: 250px; }
    .blogx-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
}
@media (max-width: 640px) {
    .blogx-search { grid-template-columns: 1fr; }
    .blogx-search__btn { width: 100%; }
    .blogx-grid { grid-template-columns: 1fr; }
}
</style>
@endpush
