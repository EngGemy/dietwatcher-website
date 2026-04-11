@extends('layouts.app')

@section('title', $post->title . ' - ' . __('Blog'))

@section('content')

<div class="min-h-[50vh] pb-8 md:pb-16" style="background-color:#F5F5FA">
    <div class="container max-w-[1420px] pt-6 pb-4">
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
                {{ Str::limit($post->title, 56) }}
            </li>
        </ol>
    </div>

    <div class="container max-w-[1420px]">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-[minmax(0,1fr)_min(280px,32%)] lg:gap-10 xl:gap-14">
            {{-- Main column (first in DOM: start side = article in RTL/LTR grid flow) --}}
            <article class="min-w-0 rounded-2xl border border-[#e8e8ee] bg-white p-6 shadow-sm md:p-8 lg:p-10">
                <header class="text-start">
                    <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-[#808089]">
                        @if($post->category)
                            <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="rounded-full bg-[#279ff9] px-3 py-0.5 text-xs font-semibold text-white hover:opacity-90">{{ $post->category->name }}</a>
                        @endif
                        <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->formatted_date }}</time>
                        @if($post->reading_time_minutes)
                            <span>&middot; {{ $post->reading_time_minutes }} {{ __('min read') }}</span>
                        @endif
                    </div>

                    <h1 class="mb-5 text-2xl font-extrabold leading-tight text-[#2e2e30] md:text-3xl lg:text-[2.125rem]">{{ $post->title }}</h1>

                    {{-- Share (Figma: circular icons under title) --}}
                    <div class="mb-8 flex flex-wrap items-center gap-3">
                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($post->title) }}&url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="blog-share-btn" title="X">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="blog-share-btn" title="Facebook">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="https://wa.me/?text={{ urlencode($post->title . ' ' . url()->current()) }}" target="_blank" rel="noopener" class="blog-share-btn" title="WhatsApp">
                            <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                        <button type="button" class="blog-share-btn" title="{{ __('Copy link') }}" onclick="navigator.clipboard.writeText(window.location.href); this.setAttribute('data-copied','1'); setTimeout(()=>this.removeAttribute('data-copied'),2000)">
                            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-4.122a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                        </button>
                    </div>
                </header>

                @if($post->cover_image_exists)
                    <div class="blog-detail-cover mb-8 overflow-hidden rounded-2xl">
                        <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}" class="h-auto w-full max-h-[480px] object-cover" loading="eager" />
                    </div>
                @endif

                @if($post->excerpt)
                    <p class="mb-8 text-lg leading-relaxed text-[#808089]">{{ $post->excerpt }}</p>
                @endif

                @if($post->author)
                    <div class="mb-10 flex flex-wrap items-center gap-4 rounded-xl bg-[#f5f5fa] px-4 py-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-base font-bold text-[#279ff9] shadow-sm ring-1 ring-[#e8e8ee]">{{ mb_substr($post->author->name, 0, 1) }}</div>
                        <div>
                            <p class="font-bold text-[#2e2e30]">{{ $post->author->name }}</p>
                            <p class="text-sm text-[#808089]">{{ __('Author') }}</p>
                        </div>
                        @if($post->tags->count() > 0)
                            <div class="flex w-full flex-wrap gap-2 sm:ms-auto sm:w-auto">
                                @foreach($post->tags as $tag)
                                    <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" class="rounded-full border border-[#e8e8ee] bg-white px-3 py-1 text-xs font-semibold text-[#279ff9] hover:bg-[#279ff9] hover:text-white">#{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @elseif($post->tags->count() > 0)
                    <div class="mb-10 flex flex-wrap gap-2">
                        @foreach($post->tags as $tag)
                            <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" class="rounded-full border border-[#e8e8ee] bg-[#f5f5fa] px-3 py-1 text-xs font-semibold text-[#279ff9]">#{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif

                <div class="blog-content">
                    {!! $post->content !!}
                </div>

                <div class="mt-10 border-t border-[#e8e8ee] pt-8">
                    <a href="{{ route('blog.index') }}" class="btn btn--outline">
                        <svg class="size-5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('Back to Blog') }}
                    </a>
                </div>
            </article>

            {{-- Sidebar: search + latest + categories (Figma) --}}
            <aside class="min-w-0 lg:sticky lg:top-24 lg:self-start">
                <div class="space-y-8 rounded-2xl border border-[#e8e8ee] bg-white p-6 shadow-sm">
                    <div>
                        <p class="mb-3 text-sm font-bold text-[#2e2e30]">{{ __('Search') }}</p>
                        <form action="{{ route('blog.index') }}" method="GET" class="relative">
                            <input type="search" name="search" placeholder="{{ __('Search articles...') }}" class="w-full rounded-xl border border-[#e8e8ee] bg-[#f5f5fa] py-2.5 ps-3 pe-3 text-sm text-[#2e2e30] outline-none transition focus:border-[#279ff9] focus:bg-white focus:ring-2 focus:ring-[#279ff9]/20" autocomplete="off" />
                        </form>
                    </div>

                    @if($latestPosts->count() > 0)
                        <div>
                            <p class="mb-4 text-sm font-bold text-[#2e2e30]">{{ __('blog.latest_articles') }}</p>
                            <ul class="space-y-4">
                                @foreach($latestPosts as $lp)
                                    <li>
                                        <a href="{{ route('blog.show', $lp->translate(app()->getLocale())->slug) }}" class="group flex gap-3">
                                            <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-[#f5f5fa]">
                                                <img src="{{ $lp->cover_image_url }}" alt="" class="size-full object-cover transition group-hover:scale-105" loading="lazy" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="line-clamp-2 text-sm font-semibold leading-snug text-[#2e2e30] group-hover:text-[#279ff9]">{{ $lp->title }}</p>
                                                <time class="mt-1 block text-xs text-[#808089]">{{ $lp->formatted_date }}</time>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($categories->count() > 0)
                        <div>
                            <p class="mb-3 text-sm font-bold text-[#2e2e30]">{{ __('blog.sidebar_categories') }}</p>
                            <ul class="space-y-1">
                                @foreach($categories as $cat)
                                    <li>
                                        <a href="{{ route('blog.index', ['category' => $cat->slug]) }}" class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-[#2e2e30] hover:bg-[#f5f5fa]">
                                            <span>{{ $cat->name }}</span>
                                            <svg class="size-4 text-[#c4c4cc] rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>

@php
$relatedPosts = \App\Models\BlogPost::published()
    ->where('id', '!=', $post->id)
    ->when($post->tags->count() > 0, fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->whereIn('blog_tags.id', $post->tags->pluck('id'))))
    ->with(['author', 'category'])
    ->take(3)
    ->get();
@endphp
@if($relatedPosts->count() > 0)
<section class="border-t border-[#e8e8ee] py-14 md:py-20" style="background-color:#F5F5FA">
    <div class="container max-w-[1420px]">
        <header class="mb-10 text-center md:mb-12">
            <p class="mb-2 text-sm font-bold uppercase tracking-wide text-[#279ff9]">{{ __('Keep Reading') }}</p>
            <h2 class="text-2xl font-extrabold text-[#2e2e30] md:text-3xl">{{ __('Related Articles') }}</h2>
        </header>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
            @foreach($relatedPosts as $related)
                @include('blog.partials.post-card', ['post' => $related])
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection

@push('styles')
<style>
.blog-share-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 9999px;
    border: 1px solid #e5e7eb;
    color: #6b7280;
    background: #fff;
    transition: border-color 0.2s, background 0.2s, color 0.2s;
}
.blog-share-btn:hover {
    border-color: #279ff9;
    background: #279ff9;
    color: #fff;
}
.blog-content { font-size: 1.0625rem; line-height: 1.85; color: #374151; text-align: start; }
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
</style>
@endpush
