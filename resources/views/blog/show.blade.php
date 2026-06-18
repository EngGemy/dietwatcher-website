@extends('layouts.app')

@section('title', $post->title . ' - ' . __('Blog'))

@section('content')
<section class="bshowx-hero">
    <div class="container bshowx-hero__grid">
        <div class="bshowx-hero__media">
            <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}" loading="eager">
        </div>

        <div class="bshowx-hero__content">
            <a href="{{ route('blog.index') }}" class="bshowx-back">&larr; {{ __('blog.back_to_blog') }}</a>

            @if($post->category)
                <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="bshowx-badge">{{ $post->category->name }}</a>
            @endif

            <h1 class="bshowx-title">{{ $post->title }}</h1>

            <div class="bshowx-meta">
                <span>{{ __('By') }} {{ $post->author?->name ?? __('Diet Watchers') }}</span>
                @if($post->published_at)<span>&bull; {{ $post->formatted_date }}</span>@endif
                @if($post->reading_time_minutes)<span>&bull; {{ $post->reading_time_minutes }} {{ __('min read') }}</span>@endif
                <span>&bull; {{ number_format((int) $post->views_count) }} {{ __('blog.views') }}</span>
            </div>

            <div class="bshowx-share">
                <span>{{ __('Share') }}:</span>
                <a target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text={{ urlencode($post->title) }}&url={{ urlencode(url()->current()) }}">X</a>
                <a target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}">Facebook</a>
                <a target="_blank" rel="noopener" href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url()->current()) }}&title={{ urlencode($post->title) }}">LinkedIn</a>
                <button type="button" onclick="navigator.clipboard.writeText(window.location.href);this.classList.add('copied');setTimeout(()=>this.classList.remove('copied'),1400)">{{ __('Copy link') }}</button>
            </div>
        </div>
    </div>
</section>

<section class="bshowx-layout">
    <div class="container bshowx-layout__grid">
        <article class="bshowx-article" id="blog-article">
            <div class="bshowx-progress" id="read-progress"></div>

            <div class="bshowx-content">
                {!! $post->content !!}
            </div>

            @if($post->tags->isNotEmpty())
                <div class="bshowx-tags">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif
        </article>

        <aside class="bshowx-aside" id="blog-toc">
            <div class="bshowx-card">
                <h3>{{ __('blog.table_of_contents') }}</h3>
                <nav id="toc-nav" class="bshowx-toc"></nav>
            </div>

            @if($latestPosts->isNotEmpty())
                <div class="bshowx-card">
                    <h3>{{ __('blog.latest_articles') }}</h3>
                    <div class="bshowx-latest">
                        @foreach($latestPosts->take(4) as $latest)
                            <a href="{{ route('blog.show', $latest->translate(app()->getLocale())->slug) }}" class="bshowx-latest__item">
                                <img src="{{ $latest->cover_image_url }}" alt="{{ $latest->title }}" loading="lazy">
                                <div>
                                    <p>{{ Str::limit($latest->title, 62) }}</p>
                                    <small>{{ $latest->formatted_date }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
</section>

<section class="bshowx-cta-wrap">
    <div class="container">
        <div class="bshowx-cta">
            <h2>{{ __('blog.cta_title') }}</h2>
            <p>{{ __('blog.cta_subtitle') }}</p>
            <a href="{{ route('meal-plans.index') }}">{{ __('blog.cta_button') }}</a>
        </div>
    </div>
</section>

@if($latestPosts->isNotEmpty())
<section class="bshowx-related">
    <div class="container">
        <div class="bshowx-related__head">
            <h2>{{ __('blog.related_articles') }}</h2>
            <a href="{{ route('blog.index') }}">{{ __('blog.view_all') }}</a>
        </div>
        <div class="bshowx-related__grid">
            @foreach($latestPosts->take(3) as $related)
                <a href="{{ route('blog.show', $related->translate(app()->getLocale())->slug) }}" class="bshowx-r-card">
                    <img src="{{ $related->cover_image_url }}" alt="{{ $related->title }}" loading="lazy">
                    <div>
                        @if($related->category)
                            <span>{{ $related->category->name }}</span>
                        @endif
                        <h3>{{ Str::limit($related->title, 70) }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection

@push('styles')
<style>
.bshowx-hero { background: radial-gradient(1200px 480px at 85% -30%, rgba(39,159,249,.16), transparent), linear-gradient(180deg, #ffffff, #f8fbff); padding: 3.25rem 0; border-bottom: 1px solid #e7eef7; font-family: 'Almarai', 'Inter', sans-serif; }
.bshowx-hero__grid { display: grid; grid-template-columns: 1.12fr 1fr; gap: 1.4rem; align-items: center; }
.bshowx-hero__media { border-radius: 24px; overflow: hidden; box-shadow: 0 30px 70px rgba(15,23,42,.2); aspect-ratio: 16 / 10; }
.bshowx-hero__media img { width: 100%; height: 100%; min-height: 340px; max-height: 500px; object-fit: cover; display: block; }
.bshowx-back { color: #64748b; text-decoration: none; font-weight: 600; font-size: .86rem; }
.bshowx-back:hover { color: #279ff9; }
.bshowx-badge { display: inline-flex; margin-top: .8rem; text-decoration: none; border-radius: 999px; padding: .36rem .8rem; background: #eff7ff; color: #279ff9; font-weight: 700; font-size: .76rem; }
.bshowx-title { margin: .85rem 0 .75rem; color: #0f172a; font-size: clamp(1.5rem, 3.5vw, 2.6rem); line-height: 1.3; font-weight: 800; }
.bshowx-meta { display: flex; flex-wrap: wrap; gap: .5rem; color: #64748b; font-size: .9rem; }
.bshowx-share { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; }
.bshowx-share a, .bshowx-share button { border: 1px solid #d8e2ef; background: #fff; color: #0f172a; border-radius: 10px; padding: .4rem .7rem; font-size: .82rem; text-decoration: none; cursor: pointer; }
.bshowx-share button.copied { background: #22c55e; color: #fff; border-color: #22c55e; }

.bshowx-layout { background: #f4f7fb; padding: 1.7rem 0 2.7rem; font-family: 'Almarai', 'Inter', sans-serif; }
.bshowx-layout__grid { display: grid; grid-template-columns: minmax(0,1fr) 330px; gap: 1rem; }
.bshowx-article { position: relative; background: #fff; border: 1px solid #dbe5f1; border-radius: 18px; padding: 1.3rem; box-shadow: 0 8px 20px rgba(15,23,42,.05); }
.bshowx-progress { position: sticky; top: 74px; height: 4px; border-radius: 999px; background: linear-gradient(90deg,#279ff9,var(--progress-stop, transparent)); margin: -1.3rem -1.3rem 1.2rem; }
.bshowx-content h2, .bshowx-content h3 { color: #0f172a; margin-top: 1.4em; margin-bottom: .55em; line-height: 1.35; scroll-margin-top: 100px; }
.bshowx-content h2 { font-size: clamp(1.35rem,2.5vw,2rem); font-weight: 800; }
.bshowx-content h3 { font-size: clamp(1.1rem,2vw,1.45rem); font-weight: 700; }
.bshowx-content p, .bshowx-content li { color: #334155; line-height: 2.05; font-size: clamp(1rem, 1.25vw, 1.12rem); }
.bshowx-content ul, .bshowx-content ol { padding-inline-start: 1.35rem; margin-bottom: 1rem; }
.bshowx-content blockquote { margin: 1.3rem 0; border-inline-start: 4px solid #279ff9; background: #f6faff; border-radius: 12px; padding: .95rem 1rem; color: #0f172a; }
.bshowx-content img { max-width: 100%; height: auto; border-radius: 16px; margin: 1rem 0; }
.bshowx-content a { color: #279ff9; text-decoration: underline; text-underline-offset: 2px; }
.bshowx-tags { margin-top: 1.4rem; padding-top: 1rem; border-top: 1px solid #eef2f7; display: flex; flex-wrap: wrap; gap: .45rem; }
.bshowx-tags a { text-decoration: none; border-radius: 999px; background: #eff7ff; color: #279ff9; font-size: .8rem; font-weight: 700; padding: .33rem .62rem; }

.bshowx-aside { display: flex; flex-direction: column; gap: .8rem; position: sticky; top: 88px; align-self: start; }
.bshowx-card { background: #fff; border: 1px solid #dbe5f1; border-radius: 16px; padding: .95rem; box-shadow: 0 8px 20px rgba(15,23,42,.05); }
.bshowx-card h3 { margin: 0 0 .7rem; color: #0f172a; font-size: .98rem; font-weight: 800; }
.bshowx-toc { display: flex; flex-direction: column; gap: .32rem; }
.bshowx-toc a { text-decoration: none; color: #64748b; border-radius: 10px; padding: .4rem .55rem; font-size: .84rem; display: block; }
.bshowx-toc a.active, .bshowx-toc a:hover { background: #eff7ff; color: #279ff9; }
.bshowx-latest { display: flex; flex-direction: column; gap: .6rem; }
.bshowx-latest__item { display: grid; grid-template-columns: 58px 1fr; gap: .55rem; text-decoration: none; }
.bshowx-latest__item img { width: 58px; height: 58px; object-fit: cover; border-radius: 10px; }
.bshowx-latest__item p { margin: 0 0 .15rem; color: #0f172a; font-size: .82rem; line-height: 1.45; font-weight: 700; }
.bshowx-latest__item small { color: #94a3b8; font-size: .74rem; }

.bshowx-cta-wrap { background: #f4f7fb; padding-bottom: 2rem; }
.bshowx-cta { border-radius: 18px; background: linear-gradient(115deg,#279ff9,#1876d1); text-align: center; padding: 1.8rem 1rem; box-shadow: 0 22px 45px rgba(39,159,249,.28); }
.bshowx-cta h2 { margin: 0; color: #fff; font-size: clamp(1.25rem,2.8vw,2rem); font-weight: 800; }
.bshowx-cta p { margin: .55rem auto 1rem; max-width: 720px; color: rgba(255,255,255,.95); }
.bshowx-cta a { text-decoration: none; background: #fff; color: #279ff9; padding: .65rem 1rem; border-radius: 11px; font-weight: 800; display: inline-flex; }

.bshowx-related { background: #f4f7fb; padding: 0 0 4rem; }
.bshowx-related__head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .95rem; }
.bshowx-related__head h2 { margin: 0; color: #0f172a; font-size: 1.5rem; font-weight: 800; }
.bshowx-related__head a { color: #279ff9; text-decoration: none; font-weight: 700; }
.bshowx-related__grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 1rem; }
.bshowx-r-card { background: #fff; border: 1px solid #dbe5f1; border-radius: 16px; overflow: hidden; text-decoration: none; box-shadow: 0 10px 18px rgba(15,23,42,.06); transition: .25s; }
.bshowx-r-card:hover { transform: translateY(-4px); box-shadow: 0 18px 30px rgba(15,23,42,.12); }
.bshowx-r-card img { width: 100%; height: 200px; object-fit: cover; }
.bshowx-r-card div { padding: .8rem; }
.bshowx-r-card span { display: inline-flex; margin-bottom: .4rem; background: #eff7ff; color: #279ff9; font-size: .7rem; padding: .3rem .55rem; border-radius: 999px; font-weight: 700; }
.bshowx-r-card h3 { margin: 0; color: #0f172a; font-size: 1rem; line-height: 1.45; }

@media (max-width: 1024px) {
    .bshowx-hero__grid { grid-template-columns: 1fr; }
    .bshowx-layout__grid { grid-template-columns: 1fr; }
    .bshowx-aside { position: static; }
    .bshowx-related__grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
}
@media (max-width: 640px) {
    .bshowx-hero { padding-top: 1.6rem; }
    .bshowx-hero__media img { min-height: 240px; }
    .bshowx-share { gap: .35rem; }
    .bshowx-share a, .bshowx-share button { font-size: .74rem; padding: .35rem .58rem; }
    .bshowx-related__grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var article = document.getElementById('blog-article');
    var toc = document.getElementById('toc-nav');
    if (!article || !toc) return;

    var headings = article.querySelectorAll('h2, h3');
    var links = [];

    if (headings.length === 0) {
        var tocWrap = document.getElementById('blog-toc');
        if (tocWrap) tocWrap.style.display = 'none';
    } else {
        headings.forEach(function (h, i) {
            if (!h.id) h.id = 'sec-' + i;
            var a = document.createElement('a');
            a.href = '#' + h.id;
            a.textContent = h.textContent.trim();
            a.setAttribute('title', h.textContent.trim());
            toc.appendChild(a);
            links.push({ link: a, target: h });
        });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                links.forEach(function (item) { item.link.classList.remove('active'); });
                var found = links.find(function (item) { return item.target === entry.target; });
                if (found) found.link.classList.add('active');
            });
        }, { rootMargin: '-90px 0px -60% 0px', threshold: 0.1 });

        links.forEach(function (item) { observer.observe(item.target); });
    }

    var progress = document.getElementById('read-progress');
    var onScroll = function () {
        if (!progress || !article) return;
        var rect = article.getBoundingClientRect();
        var articleTop = window.scrollY + rect.top;
        var articleHeight = article.offsetHeight;
        var viewportBottom = window.scrollY + window.innerHeight;
        var traveled = Math.min(Math.max(viewportBottom - articleTop, 0), articleHeight);
        var pct = articleHeight > 0 ? Math.round((traveled / articleHeight) * 100) : 0;
        progress.style.setProperty('--progress-stop', '#279ff9 ' + pct + '%, #e2e8f0 ' + pct + '%');
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
})();
</script>
@endpush
