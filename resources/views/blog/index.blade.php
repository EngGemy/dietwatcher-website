@extends('layouts.app')

@section('title', __('Blog') . ' - ' . config('app.name'))

@section('content')

@php
    $firstPost = $featuredPost ?? $posts->first();
    $gridPosts = $featuredPost ? $posts : $posts->slice(1);
@endphp

{{-- Hero --}}
@if($firstPost && $posts->onFirstPage() && !request('search') && !request('tag'))
<section class="bg-gray-200 pt-6 pb-0">
    <div class="container">
        <div class="blog-hero">
            <div class="blog-hero__img">
                <a href="{{ route('blog.show', $firstPost->translate(app()->getLocale())->slug) }}">
                    <img src="{{ $firstPost->cover_image_url }}" alt="{{ $firstPost->title }}" />
                </a>
            </div>
            <div class="blog-hero__content">
                <div class="blog-hero__meta">
                    @if($firstPost->category)
                        <a href="{{ route('blog.index', ['category' => $firstPost->category->slug]) }}" class="blog-hero__cat">{{ $firstPost->category->name }}</a>
                    @endif
                    <time>{{ $firstPost->formatted_date }}</time>
                    @if($firstPost->reading_time_minutes)
                        <span>{{ $firstPost->reading_time_minutes }} {{ __('min read') }}</span>
                    @endif
                </div>
                <h1 class="blog-hero__title">
                    <a href="{{ route('blog.show', $firstPost->translate(app()->getLocale())->slug) }}">{{ $firstPost->title }}</a>
                </h1>
                @if($firstPost->excerpt)
                    <p class="blog-hero__excerpt">{{ Str::limit($firstPost->excerpt, 160) }}</p>
                @endif
                <a href="{{ route('blog.show', $firstPost->translate(app()->getLocale())->slug) }}" class="btn btn--primary mt-4">
                    {{ __('Read Article') }}
                    <svg class="size-5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Search + Categories --}}
<section class="py-10 md:py-14">
    <div class="container">
        <header class="section-header section-header--center">
            <h4 class="section-header__subtitle">{{ __('Blog') }}</h4>
            <h2 class="section-header__title">{{ __('Latest Articles') }}</h2>
            <p class="section-header__desc">{{ __('Discover expert advice, healthy recipes, and wellness tips to help you achieve your goals.') }}</p>
        </header>

        {{-- Search --}}
        <div class="mx-auto mb-8 max-w-lg">
            <form action="{{ route('blog.index') }}" method="GET">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                        <svg class="size-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search articles...') }}"
                        class="form-control ps-12" />
                </div>
            </form>
        </div>

        {{-- Category Tabs --}}
        @if(isset($categories) && $categories->count() > 0)
            <div class="tag-list">
                <a href="{{ route('blog.index', request()->only('search')) }}"
                   class="tag" aria-pressed="{{ !request('category') ? 'true' : 'false' }}">{{ __('All') }}</a>
                @foreach($categories as $cat)
                    <a href="{{ route('blog.index', array_merge(request()->only('search'), ['category' => $cat->slug])) }}"
                       class="tag" aria-pressed="{{ request('category') === $cat->slug ? 'true' : 'false' }}">{{ $cat->name }}</a>
                @endforeach
            </div>
        @endif

        {{-- Active Filters --}}
        @if(request('search') || request('tag'))
            <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                @if(request('search'))
                    <a href="{{ route('blog.index', request()->except('search')) }}" class="inline-flex items-center gap-1.5 rounded-full bg-gray-200 px-3 py-1 text-sm text-black hover:bg-gray-300">
                        "{{ request('search') }}"
                        <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                @if(request('tag'))
                    <a href="{{ route('blog.index', request()->except('tag')) }}" class="inline-flex items-center gap-1.5 rounded-full bg-blue/10 px-3 py-1 text-sm text-blue hover:bg-blue/20">
                        #{{ request('tag') }}
                        <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                <a href="{{ route('blog.index') }}" class="text-sm text-red hover:underline">{{ __('Clear all') }}</a>
            </div>
        @endif
    </div>
</section>

{{-- Blog Grid --}}
<section class="pb-16 md:pb-20">
    <div class="container">
        @if($gridPosts->count() > 0)
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($gridPosts as $post)
                    <div class="blog-grid-card">
                        <div class="blog-grid-card__img">
                            <a href="{{ route('blog.show', $post->translate(app()->getLocale())->slug) }}">
                                <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}" loading="lazy" />
                            </a>
                        </div>
                        <div class="blog-grid-card__body">
                            <div class="blog-grid-card__meta">
                                @if($post->category)
                                    <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="blog-grid-card__cat">{{ $post->category->name }}</a>
                                @endif
                                <time>{{ $post->formatted_date }}</time>
                                @if($post->reading_time_minutes)
                                    <span>&middot; {{ $post->reading_time_minutes }} {{ __('min') }}</span>
                                @endif
                            </div>
                            <h3 class="blog-grid-card__title">
                                <a href="{{ route('blog.show', $post->translate(app()->getLocale())->slug) }}">{{ $post->title }}</a>
                            </h3>
                            @if($post->excerpt)
                                <p class="blog-grid-card__excerpt">{{ Str::limit($post->excerpt, 100) }}</p>
                            @endif
                            <a href="{{ route('blog.show', $post->translate(app()->getLocale())->slug) }}" class="blog-grid-card__link">
                                {{ __('Read More') }}
                                <svg class="size-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($posts->hasPages())
                <div class="mt-12 flex justify-center">
                    {{ $posts->links() }}
                </div>
            @endif
        @elseif($posts->count() === 0)
            <div class="py-20 text-center">
                <svg class="mx-auto mb-4 size-16 text-gray-600/40" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z"/></svg>
                <h3 class="mb-2 text-xl font-bold text-black">{{ __('No articles found') }}</h3>
                <p class="text-gray-600">{{ __('Try adjusting your search or filters.') }}</p>
                @if(request('search') || request('category') || request('tag'))
                    <a href="{{ route('blog.index') }}" class="btn btn--primary mt-6">{{ __('Clear filters') }}</a>
                @endif
            </div>
        @endif
    </div>
</section>

{{-- Topics --}}
@if(isset($tags) && $tags->count() > 0 && !request('search'))
<section class="bg-gray-200 py-14 md:py-20">
    <div class="container">
        <header class="section-header section-header--center">
            <h4 class="section-header__subtitle">{{ __('Topics') }}</h4>
            <h2 class="section-header__title">{{ __('Explore Topics') }}</h2>
            <p class="section-header__desc">{{ __('Find articles on the topics you care about') }}</p>
        </header>
        <div class="flex flex-wrap justify-center gap-3">
            @foreach($tags as $tag)
                <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                   class="btn {{ request('tag') === $tag->slug ? 'btn--primary' : 'btn--outline' }}">
                    #{{ $tag->name }}
                    <span class="text-xs opacity-60">{{ $tag->posts_count }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection

@push('styles')
<style>
/* Blog Hero - Featured Post */
.blog-hero {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0;
    overflow: hidden;
    border-radius: 1rem;
    background: #fff;
}
@media (min-width: 768px) {
    .blog-hero {
        grid-template-columns: 1.1fr 1fr;
    }
}
.blog-hero__img {
    overflow: hidden;
}
.blog-hero__img img {
    width: 100%;
    height: 280px;
    object-fit: cover;
    object-position: center;
    display: block;
}
@media (min-width: 768px) {
    .blog-hero__img img {
        height: 100%;
        min-height: 380px;
    }
}
.blog-hero__content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
@media (min-width: 768px) {
    .blog-hero__content {
        padding: 2.5rem;
    }
}
.blog-hero__meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #808089;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
}
.blog-hero__cat {
    background: #279ff9;
    color: #fff;
    padding: 0.2rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.blog-hero__cat:hover {
    opacity: 0.85;
}
.blog-hero__title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2e2e30;
    line-height: 1.3;
    margin-bottom: 0.5rem;
}
@media (min-width: 768px) {
    .blog-hero__title {
        font-size: 2rem;
    }
}
.blog-hero__title a:hover {
    color: #279ff9;
}
.blog-hero__excerpt {
    color: #808089;
    font-size: 0.9375rem;
    line-height: 1.6;
}

/* Blog Grid Card */
.blog-grid-card {
    background: #fff;
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid #e8e8ee;
    transition: box-shadow 0.25s ease, transform 0.25s ease;
    display: flex;
    flex-direction: column;
}
.blog-grid-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}
.blog-grid-card__img {
    overflow: hidden;
}
.blog-grid-card__img img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    object-position: center;
    display: block;
    transition: transform 0.4s ease;
}
.blog-grid-card:hover .blog-grid-card__img img {
    transform: scale(1.05);
}
.blog-grid-card__body {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.blog-grid-card__meta {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8125rem;
    color: #808089;
    margin-bottom: 0.625rem;
    flex-wrap: wrap;
}
.blog-grid-card__cat {
    background: #f5f5fa;
    color: #279ff9;
    padding: 0.15rem 0.625rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.blog-grid-card__cat:hover {
    background: #279ff9;
    color: #fff;
}
.blog-grid-card__title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #2e2e30;
    line-height: 1.4;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.blog-grid-card__title a:hover {
    color: #279ff9;
}
.blog-grid-card__excerpt {
    font-size: 0.875rem;
    color: #808089;
    line-height: 1.6;
    margin-bottom: auto;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.blog-grid-card__link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #279ff9;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f0f0f5;
}
.blog-grid-card__link:hover {
    text-decoration: underline;
}
</style>
@endpush
