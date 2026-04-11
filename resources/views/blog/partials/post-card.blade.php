@props(['post'])

@php
    $href = route('blog.show', $post->translate(app()->getLocale())->slug);
    $authorName = $post->author?->name ?? __('Diet Watchers');
@endphp

<article class="mcard">
    <a href="{{ $href }}" class="mcard__img-wrap" aria-label="{{ $post->title }}">
        <img
            src="{{ $post->cover_image_url }}"
            alt="{{ $post->title }}"
            class="mcard__img"
            loading="lazy"
        />
        @if($post->category)
            <span class="mcard__badge">{{ $post->category->name }}</span>
        @endif
    </a>

    <div class="mcard__body">
        <div class="mcard__blog-meta">
            @if($post->published_at)
                <span class="mcard__blog-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5"/>
                    </svg>
                    <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->formatted_date }}</time>
                </span>
            @endif
            @if($post->reading_time_minutes)
                <span class="mcard__blog-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    {{ $post->reading_time_minutes }} {{ __('min read') }}
                </span>
            @endif
        </div>

        <h2 class="mcard__name mcard__name--blog">
            <a href="{{ $href }}">{{ $post->title }}</a>
        </h2>

        @if($post->excerpt)
            <p class="mcard__blog-excerpt">{{ Str::limit(strip_tags($post->excerpt), 160) }}</p>
        @endif

        <div class="mcard__footer mcard__footer--blog">
            <a href="{{ $href }}" class="mcard__blog-read">
                {{ __('Read More') }}
                <svg class="mcard__blog-read-icon rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
            </a>
            <span class="mcard__blog-by">{{ __('By') }} {{ $authorName }}</span>
        </div>
    </div>
</article>
