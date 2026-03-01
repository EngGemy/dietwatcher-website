@extends('layouts.app')

@section('title', $post->title . ' - ' . __('Blog'))

@section('content')

{{-- Breadcrumb --}}
<div class="container pt-6 pb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb__item">
            <a class="breadcrumb__link" href="{{ route('home') }}">{{ __('Home') }}</a>
            <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="breadcrumb__item">
            <a class="breadcrumb__link" href="{{ route('blog.index') }}">{{ __('Blog') }}</a>
            <svg class="breadcrumb__separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </li>
        <li class="breadcrumb__item breadcrumb__item--active" aria-current="page">
            {{ Str::limit($post->title, 50) }}
        </li>
    </ol>
</div>

{{-- Cover Image --}}
@if($post->cover_image_exists)
<section class="container pb-8">
    <div class="blog-detail-hero">
        <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}" />
    </div>
</section>
@endif

{{-- Article --}}
<section class="container pb-16">
    <div class="mx-auto max-w-[860px]">

        {{-- Meta --}}
        <div class="blog-detail__meta">
            @if($post->category)
                <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="blog-detail__cat">{{ $post->category->name }}</a>
            @endif
            <time>{{ $post->formatted_date }}</time>
            @if($post->reading_time_minutes)
                <span>&middot; {{ $post->reading_time_minutes }} {{ __('min read') }}</span>
            @endif
        </div>

        {{-- Title --}}
        <h1 class="blog-detail__title">{{ $post->title }}</h1>

        {{-- Excerpt --}}
        @if($post->excerpt)
            <p class="blog-detail__excerpt">{{ $post->excerpt }}</p>
        @endif

        {{-- Author + Tags --}}
        <div class="blog-detail__bar">
            @if($post->author)
                <div class="blog-detail__author">
                    <div class="blog-detail__avatar">{{ mb_substr($post->author->name, 0, 1) }}</div>
                    <div>
                        <p class="font-semibold text-black">{{ $post->author->name }}</p>
                        <p class="text-sm text-gray-600">{{ __('Author') }}</p>
                    </div>
                </div>
            @endif
            @if($post->tags->count() > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" class="btn btn--outline btn--sm">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Content --}}
        <div class="blog-content">
            {!! $post->content !!}
        </div>

        {{-- Share --}}
        <div class="blog-detail__share">
            <h4 class="mb-4 text-lg font-bold text-black">{{ __('Share this article') }}</h4>
            <div class="flex flex-wrap items-center gap-3">
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($post->title) }}&url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener"
                   class="blog-detail__share-btn" title="X / Twitter">
                    <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener"
                   class="blog-detail__share-btn" title="Facebook">
                    <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="https://wa.me/?text={{ urlencode($post->title . ' ' . url()->current()) }}" target="_blank" rel="noopener"
                   class="blog-detail__share-btn" title="WhatsApp">
                    <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
                <button onclick="navigator.clipboard.writeText(window.location.href);this.querySelector('span').textContent='{{ __("Copied!") }}';setTimeout(()=>this.querySelector('span').textContent='{{ __("Copy link") }}',2000)"
                        class="btn btn--outline btn--sm">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-4.122a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                    <span>{{ __('Copy link') }}</span>
                </button>
            </div>
        </div>

        {{-- Back --}}
        <div class="mt-8 border-t border-gray-300 pt-8">
            <a href="{{ route('blog.index') }}" class="btn btn--outline">
                <svg class="size-5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                {{ __('Back to Blog') }}
            </a>
        </div>
    </div>
</section>

{{-- Related Posts --}}
@php
$relatedPosts = \App\Models\BlogPost::published()
    ->where('id', '!=', $post->id)
    ->when($post->tags->count() > 0, fn($q) => $q->whereHas('tags', fn($tq) => $tq->whereIn('blog_tags.id', $post->tags->pluck('id'))))
    ->with(['author', 'category'])
    ->take(3)
    ->get();
@endphp
@if($relatedPosts->count() > 0)
<section class="bg-gray-200 py-14 md:py-20">
    <div class="container">
        <header class="section-header section-header--center">
            <h4 class="section-header__subtitle">{{ __('Keep Reading') }}</h4>
            <h2 class="section-header__title">{{ __('Related Articles') }}</h2>
        </header>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($relatedPosts as $related)
                <div class="blog-grid-card">
                    <div class="blog-grid-card__img">
                        <a href="{{ route('blog.show', $related->translate(app()->getLocale())->slug) }}">
                            <img src="{{ $related->cover_image_url }}" alt="{{ $related->title }}" loading="lazy" />
                        </a>
                    </div>
                    <div class="blog-grid-card__body">
                        <div class="blog-grid-card__meta">
                            @if($related->category)
                                <a href="{{ route('blog.index', ['category' => $related->category->slug]) }}" class="blog-grid-card__cat">{{ $related->category->name }}</a>
                            @endif
                            <time>{{ $related->formatted_date }}</time>
                        </div>
                        <h3 class="blog-grid-card__title">
                            <a href="{{ route('blog.show', $related->translate(app()->getLocale())->slug) }}">{{ $related->title }}</a>
                        </h3>
                        <a href="{{ route('blog.show', $related->translate(app()->getLocale())->slug) }}" class="blog-grid-card__link">
                            {{ __('Read More') }}
                            <svg class="size-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection

@push('styles')
<style>
/* Blog Detail - Hero Image */
.blog-detail-hero {
    overflow: hidden;
    border-radius: 1rem;
}
.blog-detail-hero img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    object-position: center;
    display: block;
}
@media (min-width: 768px) {
    .blog-detail-hero img {
        height: 450px;
    }
}

/* Blog Detail - Content Area */
.blog-detail__meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #808089;
    margin-bottom: 1rem;
}
.blog-detail__cat {
    background: #279ff9;
    color: #fff;
    padding: 0.2rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.blog-detail__cat:hover { opacity: 0.85; }
.blog-detail__title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2e2e30;
    line-height: 1.3;
    margin-bottom: 0.75rem;
}
@media (min-width: 768px) {
    .blog-detail__title { font-size: 2.5rem; }
}
.blog-detail__excerpt {
    font-size: 1.0625rem;
    color: #808089;
    line-height: 1.7;
    margin-bottom: 1.5rem;
}
.blog-detail__bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1.25rem 0;
    border-top: 1px solid #e8e8ee;
    border-bottom: 1px solid #e8e8ee;
    margin-bottom: 2rem;
}
.blog-detail__author {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.blog-detail__avatar {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 9999px;
    background: #f5f5fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #808089;
    font-size: 1rem;
}
.blog-detail__share {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #e8e8ee;
}
.blog-detail__share-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 9999px;
    border: 1px solid #d1d5db;
    color: #808089;
    transition: all 0.2s ease;
}
.blog-detail__share-btn:hover {
    border-color: #279ff9;
    background: #279ff9;
    color: #fff;
}

/* Blog Content Typography */
.blog-content { font-size: 1.0625rem; line-height: 1.8; color: #374151; }
.blog-content h2 { font-size: 1.5rem; font-weight: 700; margin-top: 2em; margin-bottom: 0.75em; color: #2e2e30; }
.blog-content h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.6em; margin-bottom: 0.6em; color: #2e2e30; }
.blog-content p { margin-bottom: 1.25em; }
.blog-content ul, .blog-content ol { margin-bottom: 1.25em; padding-inline-start: 1.625em; }
.blog-content ul { list-style-type: disc; }
.blog-content ol { list-style-type: decimal; }
.blog-content li { margin-bottom: 0.5em; }
.blog-content blockquote { border-inline-start: 4px solid #279ff9; padding: 1em 1.25em; font-style: italic; color: #808089; margin: 1.5em 0; background: #f5f5fa; border-radius: 0 0.5rem 0.5rem 0; }
[dir="rtl"] .blog-content blockquote { border-radius: 0.5rem 0 0 0.5rem; }
.blog-content img { border-radius: 0.75rem; margin: 1.5em 0; max-width: 100%; height: auto; }
.blog-content a { color: #279ff9; text-decoration: underline; text-underline-offset: 3px; }
.blog-content a:hover { color: #1a7fd4; }
.blog-content pre { background: #2e2e30; color: #e5e7eb; padding: 1em 1.25em; border-radius: 0.75rem; overflow-x: auto; margin: 1.5em 0; font-size: 0.875rem; }
.blog-content code { background: #f5f5fa; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875em; }
.blog-content pre code { background: none; padding: 0; }
.blog-content table { width: 100%; border-collapse: collapse; margin: 1.5em 0; }
.blog-content th, .blog-content td { border: 1px solid #e5e7eb; padding: 0.625rem 0.875rem; text-align: start; }
.blog-content th { background: #f5f5fa; font-weight: 600; color: #2e2e30; }
.blog-content hr { border: none; border-top: 1px solid #e5e7eb; margin: 2em 0; }

/* Blog Grid Card - reused in index + related */
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
.blog-grid-card__img { overflow: hidden; }
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
.blog-grid-card__cat:hover { background: #279ff9; color: #fff; }
.blog-grid-card__title {
    font-size: 1.0625rem;
    font-weight: 700;
    color: #2e2e30;
    line-height: 1.4;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.blog-grid-card__title a:hover { color: #279ff9; }
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
.blog-grid-card__link:hover { text-decoration: underline; }
</style>
@endpush
