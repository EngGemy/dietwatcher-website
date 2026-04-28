@extends('layouts.app')

@section('title', $post->title . ' - ' . __('Blog'))

@section('content')

{{-- Hero Header: Image + Title/Meta --}}
<section class="bshow-hero">
    <div class="container bshow-hero__container">
        {{-- Cover Image --}}
        <div class="bshow-hero__img-col">
            <div class="bshow-hero__img-wrap">
                <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}" class="bshow-hero__img" loading="eager" />
            </div>
        </div>

        {{-- Content Column --}}
        <div class="bshow-hero__content-col">
            @if($post->category)
                <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="bshow-hero__badge">{{ $post->category->name }}</a>
            @endif

            <h1 class="bshow-hero__title">{{ $post->title }}</h1>

            {{-- Author + Meta --}}
            <div class="bshow-hero__author-row">
                <div class="bshow-hero__author-info">
                    <p class="bshow-hero__author-name">{{ $post->author?->name ?? __('Diet Watchers') }}</p>
                    <div class="bshow-hero__meta">
                        @if($post->reading_time_minutes)
                            <span class="bshow-hero__meta-item">
                                <svg width="16" height="16" fill="none" stroke="#808089" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                {{ $post->reading_time_minutes }} {{ __('min read') }}
                            </span>
                        @endif
                        @if($post->published_at)
                            <span class="bshow-hero__meta-item">
                                <svg width="16" height="16" fill="none" stroke="#808089" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5"/></svg>
                                <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->formatted_date }}</time>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="bshow-hero__author-avatar">
                    {{ mb_substr($post->author?->name ?? 'D', 0, 1) }}
                </div>
            </div>

            {{-- Share Buttons --}}
            <div class="bshow-hero__share-row">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="bshow-share-btn bshow-share-btn--facebook" title="Facebook">
                    <svg width="20" height="20" fill="#fff" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($post->title) }}&url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="bshow-share-btn bshow-share-btn--twitter" title="X / Twitter">
                    <svg width="20" height="20" fill="#fff" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url()->current()) }}&title={{ urlencode($post->title) }}" target="_blank" rel="noopener" class="bshow-share-btn bshow-share-btn--linkedin" title="LinkedIn">
                    <svg width="20" height="20" fill="#fff" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
                <button type="button" class="bshow-share-btn bshow-share-btn--copy" title="{{ __('Copy link') }}" onclick="navigator.clipboard.writeText(window.location.href); this.classList.add('copied'); setTimeout(()=>this.classList.remove('copied'),2000)">
                    <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-4.122a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                </button>
                <span class="bshow-hero__share-label">{{ __('Share') }}:</span>
            </div>
        </div>
    </div>
</section>

{{-- Article Body + Sidebar --}}
<div class="bshow-body" style="background:#F5F5FA">
    <div class="container bshow-body__container">
        {{-- Main Article --}}
        <article class="bshow-article" id="blog-article">
            <div class="bshow-content">
                {!! $post->content !!}
            </div>

            {{-- Tags --}}
            @if($post->tags->count() > 0)
                <div class="bshow-tags">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" class="bshow-tag">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif
        </article>

        {{-- Table of Contents Sidebar --}}
        <aside class="bshow-toc" id="blog-toc">
            <div class="bshow-toc__card">
                <h3 class="bshow-toc__title">{{ __('blog.table_of_contents') }}</h3>
                <nav class="bshow-toc__nav" id="toc-nav" aria-label="{{ __('blog.table_of_contents') }}">
                    {{-- Populated by JS --}}
                </nav>
            </div>
        </aside>
    </div>
</div>

{{-- CTA Banner --}}
<div style="background:#F5F5FA; padding: 0 0 3rem;">
    <div class="container">
        <div class="bshow-cta">
            <h2 class="bshow-cta__title">{{ __('blog.cta_title') }}</h2>
            <p class="bshow-cta__desc">{{ __('blog.cta_subtitle') }}</p>
            <a href="{{ route('meal-plans.index') }}" class="bshow-cta__btn">{{ __('blog.cta_button') }}</a>
        </div>
    </div>
</div>

{{-- Related Articles --}}
@php
$relatedPosts = \App\Models\BlogPost::published()
    ->where('id', '!=', $post->id)
    ->when($post->tags->count() > 0, fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->whereIn('blog_tags.id', $post->tags->pluck('id'))))
    ->with(['author', 'category'])
    ->take(3)
    ->get();
@endphp
@if($relatedPosts->count() > 0)
<section class="bshow-related" style="background:#F5F5FA">
    <div class="container">
        <h2 class="bshow-related__title">{{ __('Related Articles') }}</h2>
        <div class="bshow-related__grid">
            @foreach($relatedPosts as $related)
                <a href="{{ route('blog.show', $related->translate(app()->getLocale())->slug) }}" class="bshow-related-card">
                    <div class="bshow-related-card__img-wrap">
                        <img src="{{ $related->cover_image_url }}" alt="{{ $related->title }}" class="bshow-related-card__img" loading="lazy" />
                    </div>
                    <h3 class="bshow-related-card__title">{{ $related->title }}</h3>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════
   Blog Detail Page — matches Figma design
   ═══════════════════════════════════════════════════════ */

/* ─── Hero Section ─────────────────────────────────── */
.bshow-hero {
    background: #FFFFFF;
    padding: 64px 0;
}
.bshow-hero__container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    align-items: center;
}
@media (min-width: 1024px) {
    .bshow-hero__container {
        flex-direction: row;
        gap: 48px;
        align-items: center;
    }
}

/* Image Column */
.bshow-hero__img-col {
    flex-shrink: 0;
    width: 100%;
    max-width: 652px;
}
.bshow-hero__img-wrap {
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0px 25px 50px -12px rgba(0, 0, 0, 0.25);
    position: relative;
}
.bshow-hero__img {
    width: 100%;
    height: auto;
    max-height: 500px;
    object-fit: cover;
    display: block;
}

/* Content Column */
.bshow-hero__content-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 27px;
    min-width: 0;
}

/* Category Badge */
.bshow-hero__badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 24px;
    background: linear-gradient(90deg, #279FF9 0%, #2090E8 100%);
    border-radius: 9999px;
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 500;
    font-size: 0.875rem;
    line-height: 21px;
    color: #FFFFFF;
    text-decoration: none;
    transition: opacity 0.2s;
}
.bshow-hero__badge:hover { opacity: 0.9; }

/* Title */
.bshow-hero__title {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: clamp(1.75rem, 4vw, 4rem);
    line-height: 1.25;
    color: #27272A;
    text-align: end;
    width: 100%;
}

/* Author Row */
.bshow-hero__author-row {
    display: flex;
    flex-direction: row;
    justify-content: flex-end;
    align-items: center;
    gap: 24px;
    width: 100%;
}
.bshow-hero__author-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
}
.bshow-hero__author-name {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: 1.125rem;
    line-height: 27px;
    color: #27272A;
}
.bshow-hero__meta {
    display: flex;
    align-items: center;
    gap: 16px;
}
.bshow-hero__meta-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 400;
    font-size: 0.875rem;
    line-height: 21px;
    color: #808089;
}
.bshow-hero__author-avatar {
    width: 64px;
    height: 64px;
    border-radius: 9999px;
    background: linear-gradient(135deg, #279FF9, #2090E8);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: 1.5rem;
    color: #FFFFFF;
    flex-shrink: 0;
}

/* Share Buttons */
.bshow-hero__share-row {
    display: flex;
    flex-direction: row-reverse;
    align-items: center;
    gap: 16px;
    width: 100%;
    justify-content: flex-start;
}
.bshow-hero__share-label {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 600;
    font-size: 1rem;
    line-height: 24px;
    color: #27272A;
}
.bshow-share-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 9999px;
    border: none;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.2s;
    text-decoration: none;
}
.bshow-share-btn:hover { opacity: 0.85; transform: scale(1.05); }
.bshow-share-btn--facebook { background: #1877F2; }
.bshow-share-btn--twitter { background: #1DA1F2; }
.bshow-share-btn--linkedin { background: #0A66C2; }
.bshow-share-btn--copy { background: #808089; }
.bshow-share-btn--copy.copied { background: #22c55e; }

/* ─── Article Body ─────────────────────────────────── */
.bshow-body {
    padding: 0 0 3rem;
}
.bshow-body__container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}
@media (min-width: 1024px) {
    .bshow-body__container {
        flex-direction: row;
        gap: 2.5rem;
    }
}

/* Main Article */
.bshow-article {
    flex: 1;
    min-width: 0;
    background: #FFFFFF;
    border: 0.667px solid #F3F4F6;
    box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1), 0px 1px 2px -1px rgba(0, 0, 0, 0.1);
    padding: 48px;
}
@media (max-width: 767px) {
    .bshow-article { padding: 24px 16px; }
}

/* Blog Content Styles */
.bshow-content { text-align: start; }
.bshow-content h2 {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: clamp(1.5rem, 3vw, 2.5rem);
    line-height: 1.25;
    color: #27272A;
    margin-top: 2em;
    margin-bottom: 0.75em;
    scroll-margin-top: 100px;
}
.bshow-content h2:first-child { margin-top: 0; }
.bshow-content h3 {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: 1.5rem;
    line-height: 1.35;
    color: #27272A;
    margin-top: 1.6em;
    margin-bottom: 0.6em;
}
.bshow-content p {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 400;
    font-size: clamp(1rem, 1.5vw, 1.5rem);
    line-height: 1.35;
    color: #52525B;
    margin-bottom: 1.25em;
}
.bshow-content strong {
    font-weight: 700;
    color: #27272A;
}
.bshow-content ul, .bshow-content ol {
    margin-bottom: 1.25em;
    padding-inline-start: 1.625em;
    font-size: clamp(1rem, 1.5vw, 1.5rem);
    line-height: 1.35;
    color: #52525B;
}
.bshow-content ul { list-style-type: disc; }
.bshow-content ol { list-style-type: decimal; }
.bshow-content li { margin-bottom: 0.5em; }

/* Content Images */
.bshow-content img {
    border-radius: 16px;
    box-shadow: 0px 10px 15px -3px rgba(0, 0, 0, 0.1), 0px 4px 6px -4px rgba(0, 0, 0, 0.1);
    margin: 2em 0;
    max-width: 100%;
    height: auto;
}

/* Blockquotes — warm gradient bg + red border */
.bshow-content blockquote {
    background: linear-gradient(90deg, #FFF7ED 0%, #FFEDD5 100%);
    border: none;
    border-inline-end: 4px solid #FF6B6B;
    border-radius: 14px;
    padding: 32px;
    margin: 2em 0;
    position: relative;
}
.bshow-content blockquote::before {
    content: '\201C';
    font-family: 'Georgia', serif;
    font-size: 60px;
    line-height: 1;
    color: #FF6B6B;
    opacity: 0.2;
    position: absolute;
    top: 16px;
    inset-inline-end: 16px;
}
.bshow-content blockquote p {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-style: italic;
    font-weight: 600;
    font-size: clamp(1rem, 1.3vw, 1.375rem);
    line-height: 1.65;
    color: #27272A;
    margin: 0;
}

/* Gradient Divider (for hr) */
.bshow-content hr {
    border: none;
    height: 1px;
    background: linear-gradient(90deg, rgba(0,0,0,0) 0%, #D1D5DC 50%, rgba(0,0,0,0) 100%);
    margin: 2.5em 0;
}

/* Links */
.bshow-content a { color: #279ff9; text-decoration: underline; text-underline-offset: 3px; }
.bshow-content a:hover { color: #1a7fd4; }

/* Code */
.bshow-content pre { background: #27272A; color: #e5e7eb; padding: 1em 1.25em; border-radius: 12px; overflow-x: auto; margin: 1.5em 0; font-size: 0.875rem; }
.bshow-content code { background: #f5f5fa; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875em; }
.bshow-content pre code { background: none; padding: 0; }

/* Tables */
.bshow-content table { width: 100%; border-collapse: collapse; margin: 1.5em 0; }
.bshow-content th, .bshow-content td { border: 1px solid #e5e7eb; padding: 0.625rem 0.875rem; text-align: start; }
.bshow-content th { background: #f5f5fa; font-weight: 600; color: #27272A; }

/* Tags */
.bshow-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 2.5rem;
    padding-top: 2rem;
    border-top: 1px solid #F3F4F6;
}
.bshow-tag {
    display: inline-flex;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    border: 1px solid #e8e8ee;
    background: #f5f5fa;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #279ff9;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
}
.bshow-tag:hover { background: #279ff9; color: #fff; }

/* ─── Table of Contents Sidebar ────────────────────── */
.bshow-toc {
    width: 100%;
}
@media (min-width: 1024px) {
    .bshow-toc {
        width: 380px;
        flex-shrink: 0;
        position: sticky;
        top: 100px;
        align-self: flex-start;
    }
}
.bshow-toc__card {
    background: #FFFFFF;
    border: 0.667px solid #F3F4F6;
    box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1), 0px 1px 2px -1px rgba(0, 0, 0, 0.1);
    padding: 24px;
}
.bshow-toc__title {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: 1.25rem;
    line-height: 30px;
    color: #27272A;
    margin-bottom: 1rem;
}
.bshow-toc__nav {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.bshow-toc__link {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 4px;
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 400;
    font-size: 0.875rem;
    line-height: 21px;
    color: #808089;
    text-decoration: none;
    transition: all 0.2s;
}
.bshow-toc__link:hover {
    background: #f5f5fa;
    color: #27272A;
}
.bshow-toc__link--active {
    background: linear-gradient(90deg, #279FF9 0%, #2090E8 100%);
    color: #FFFFFF;
    font-weight: 600;
    border-radius: 4px;
}
.bshow-toc__link--active:hover {
    background: linear-gradient(90deg, #279FF9 0%, #2090E8 100%);
    color: #FFFFFF;
}
.bshow-toc__link-arrow {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    display: none;
}
.bshow-toc__link--active .bshow-toc__link-arrow {
    display: inline-flex;
}

/* ─── CTA Banner ───────────────────────────────────── */
.bshow-cta {
    background: linear-gradient(90deg, #279FF9 0%, #2090E8 100%);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}
@media (min-width: 768px) {
    .bshow-cta { padding: 2.5rem 3rem; }
}
.bshow-cta__title {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: clamp(1.5rem, 3vw, 2rem);
    line-height: 1.5;
    color: #FFFFFF;
}
.bshow-cta__desc {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 400;
    font-size: 1.125rem;
    line-height: 27px;
    color: #FFFFFF;
    opacity: 0.9;
    max-width: 640px;
}
.bshow-cta__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 32px;
    background: #FFFFFF;
    border-radius: 10px;
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 600;
    font-size: 1rem;
    line-height: 24px;
    color: #279FF9;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.bshow-cta__btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* ─── Related Articles ─────────────────────────────── */
.bshow-related {
    padding: 0 0 4rem;
}
.bshow-related__title {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 700;
    font-size: 2.25rem;
    line-height: 54px;
    color: #27272A;
    text-align: center;
    margin-bottom: 3rem;
}
.bshow-related__grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 66px;
}
.bshow-related-card {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 273px;
    text-decoration: none;
    transition: transform 0.2s;
}
.bshow-related-card:hover { transform: translateY(-4px); }
.bshow-related-card__img-wrap {
    width: 100%;
    height: 200px;
    border-radius: 4px;
    overflow: hidden;
    background: #f0f0f5;
}
.bshow-related-card__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}
.bshow-related-card:hover .bshow-related-card__img { transform: scale(1.05); }
.bshow-related-card__title {
    font-family: 'Inter', 'Almarai', sans-serif;
    font-weight: 600;
    font-size: 1.125rem;
    line-height: 27px;
    color: #27272A;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    // Build Table of Contents from article headings
    var article = document.getElementById('blog-article');
    var tocNav = document.getElementById('toc-nav');
    if (!article || !tocNav) return;

    var headings = article.querySelectorAll('h2, h3');
    if (headings.length === 0) {
        // Hide TOC if no headings
        var tocEl = document.getElementById('blog-toc');
        if (tocEl) tocEl.style.display = 'none';
        return;
    }

    var tocLinks = [];
    headings.forEach(function(h, i) {
        // Assign ID if missing
        if (!h.id) {
            h.id = 'section-' + i;
        }

        var link = document.createElement('a');
        link.href = '#' + h.id;
        link.className = 'bshow-toc__link';
        link.textContent = h.textContent.replace(/^\d+\.\s*/, '').substring(0, 40);
        if (link.textContent.length >= 40) link.textContent += '...';

        // Arrow icon for active state
        var arrow = document.createElement('span');
        arrow.className = 'bshow-toc__link-arrow';
        arrow.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>';
        link.prepend(arrow);

        tocNav.appendChild(link);
        tocLinks.push({ el: link, target: h });
    });

    // Activate first by default
    if (tocLinks.length > 0) {
        tocLinks[0].el.classList.add('bshow-toc__link--active');
    }

    // Intersection Observer for active heading
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                tocLinks.forEach(function(item) {
                    item.el.classList.remove('bshow-toc__link--active');
                });
                for (var j = 0; j < tocLinks.length; j++) {
                    if (tocLinks[j].target === entry.target) {
                        tocLinks[j].el.classList.add('bshow-toc__link--active');
                        break;
                    }
                }
            }
        });
    }, { rootMargin: '-100px 0px -60% 0px', threshold: 0.1 });

    tocLinks.forEach(function(item) { observer.observe(item.target); });
})();
</script>
@endpush
