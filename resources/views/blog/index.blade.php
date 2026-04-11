@extends('layouts.app')

@section('title', __('Blog') . ' - ' . config('app.name'))

@section('content')

{{-- Match /store (meals) hero + section shell --}}
<section class="meals-hero">
    <div class="container">
        <div class="meals-hero__content">
            <span class="meals-hero__badge">{{ __('blog.hero_badge') }}</span>
            <h1 class="meals-hero__title">{{ __('blog.hero_title') }}</h1>
            <p class="meals-hero__desc">{{ __('blog.hero_subtitle') }}</p>
        </div>
    </div>
    <div class="meals-hero__pattern"></div>
</section>

<section class="meals-section blog-store-page">
    <div class="container">
        {{-- Toolbar: same layout as Market (search + category tags) --}}
        <div class="meals-toolbar">
            <form action="{{ route('blog.index') }}" method="GET" class="meals-search">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}" />
                @endif
                @if(request('tag'))
                    <input type="hidden" name="tag" value="{{ request('tag') }}" />
                @endif
                <svg class="meals-search__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    class="meals-search__input"
                    placeholder="{{ __('Search articles...') }}"
                    autocomplete="off"
                />
                @if(request('search'))
                    <a href="{{ route('blog.index', request()->except('search')) }}" class="meals-search__clear" title="{{ __('Clear') }}">
                        <svg style="width:12px;height:12px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </form>

            @if($categories->count() > 0)
                <nav class="meals-tags" aria-label="{{ __('Blog') }}">
                    <a
                        href="{{ route('blog.index', request()->only(['search', 'tag'])) }}"
                        class="meals-tag {{ ! request('category') ? 'meals-tag--active' : '' }}"
                    >{{ __('All') }}</a>
                    @foreach($categories as $cat)
                        <a
                            href="{{ route('blog.index', array_merge(request()->only(['search', 'tag']), ['category' => $cat->slug])) }}"
                            class="meals-tag {{ request('category') === $cat->slug ? 'meals-tag--active' : '' }}"
                        >{{ $cat->name }}</a>
                    @endforeach
                </nav>
            @endif
        </div>

        <div class="meals-info">
            <p class="meals-info__count">
                @if(request('search'))
                    {{ __('Results for') }} "<strong>{{ request('search') }}</strong>"
                @else
                    <strong>{{ $posts->total() }}</strong> {{ __('articles') }}
                @endif
            </p>
        </div>

        @if(request('search') || request('tag'))
            <div class="blog-filter-chips mb-6 flex flex-wrap items-center justify-center gap-2">
                @if(request('tag'))
                    <a href="{{ route('blog.index', request()->except('tag')) }}" class="inline-flex items-center gap-1.5 rounded-full bg-[#279ff9]/10 px-3 py-1.5 text-sm font-medium text-[#279ff9] hover:bg-[#279ff9]/20">
                        #{{ request('tag') }}
                        <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-red-600 hover:underline">{{ __('Clear all') }}</a>
            </div>
        @endif

        @if($posts->count() > 0)
            <div class="meals-grid">
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
            <div class="meals-empty">
                <div class="meals-empty__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </div>
                <h3 class="meals-empty__title">{{ __('No articles found') }}</h3>
                <p class="meals-empty__desc">{{ __('Try adjusting your search or filters.') }}</p>
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
/* ─── Hero (same as /store meals page) ───────────────── */
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
    color: rgba(255,255,255,0.88);
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

.meals-section {
    background: #f5f5fa;
    padding: 0 0 5rem;
    margin-top: -2rem;
    position: relative;
    z-index: 3;
}

/* ─── Toolbar (from Market /store) ───────────────────── */
.meals-toolbar{display:flex;flex-direction:column;gap:1rem;background:#fff;border-radius:16px;padding:1.25rem 1.5rem;box-shadow:0 4px 24px rgba(0,0,0,.06);margin-top:-2rem;position:relative;z-index:10;margin-bottom:2rem}
@media(min-width:768px){.meals-toolbar{flex-direction:row;align-items:center}}
.meals-search{position:relative;flex:1;min-width:0}
.meals-search__icon{position:absolute;top:50%;inset-inline-start:14px;transform:translateY(-50%);width:18px;height:18px;color:#999;pointer-events:none}
.meals-search__input{width:100%;padding:.7rem .75rem .7rem 2.6rem;border:1.5px solid #e8e8ef;border-radius:10px;font-size:.9rem;color:#2e2e30;background:#f9f9fc;transition:all .2s;outline:none}
[dir="rtl"] .meals-search__input{padding:.7rem 2.6rem .7rem .75rem}
.meals-search__input::placeholder{color:#aaa}
.meals-search__input:focus{border-color:#279ff9;background:#fff;box-shadow:0 0 0 3px rgba(39,159,249,.1)}
.meals-search__clear{position:absolute;top:50%;inset-inline-end:10px;transform:translateY(-50%);width:22px;height:22px;border-radius:50%;background:#e8e8ef;color:#666;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;transition:all .15s;padding:0;text-decoration:none}
.meals-search__clear:hover{background:#ff707a;color:#fff}
.meals-tags{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
.meals-tag{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem 1rem;border-radius:100px;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s;border:1.5px solid #e0e0e8;background:#fff;color:#555;white-space:nowrap;text-decoration:none}
.meals-tag:hover{border-color:#279ff9;color:#279ff9}
.meals-tag--active{background:#279ff9;color:#fff;border-color:#279ff9;box-shadow:0 2px 8px rgba(39,159,249,.3)}
.meals-tag--active:hover{background:#1e8de0;border-color:#1e8de0;color:#fff}
.meals-info{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.5rem}
.meals-info__count{font-size:.9rem;color:#808089}
.meals-info__count strong{color:#2e2e30;font-weight:700}

/* ─── Grid (4 columns on xl — same as store) ────────── */
.meals-grid{display:grid;align-items:stretch;gap:1.5rem;grid-template-columns:1fr}
@media(min-width:640px){.meals-grid{grid-template-columns:repeat(2,1fr)}}
@media(min-width:1024px){.meals-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:1280px){.meals-grid{grid-template-columns:repeat(4,1fr)}}

/* ─── Card (Market mcard, blog body) ─────────────────── */
.mcard{background:#fff;border-radius:16px;overflow:hidden;transition:all .3s cubic-bezier(.25,.46,.45,.94);border:1px solid transparent;position:relative;display:flex;flex-direction:column;height:100%}
.mcard:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);border-color:rgba(39,159,249,.15)}
.mcard__img-wrap{position:relative;aspect-ratio:4/3;overflow:hidden;background:#f0f0f5;flex-shrink:0;display:block;text-decoration:none}
.mcard__img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
.mcard:hover .mcard__img{transform:scale(1.06)}
.mcard__badge{position:absolute;top:10px;inset-inline-start:10px;display:inline-flex;align-items:center;gap:4px;padding:.28rem .65rem;background:rgba(232,244,255,.95);backdrop-filter:blur(6px);border-radius:100px;font-size:.68rem;font-weight:700;color:#1a6dd4;pointer-events:none;max-width:calc(100% - 20px)}
.mcard__body{padding:.9rem 1rem 1rem;flex:1;display:flex;flex-direction:column;min-height:0}
.mcard__blog-meta{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem .75rem;font-size:.72rem;font-weight:600;color:#808089;margin-bottom:.45rem}
.mcard__blog-meta-item{display:inline-flex;align-items:center;gap:.28rem}
.mcard__blog-meta-item svg{width:14px;height:14px;opacity:.85}
.mcard__name{font-size:.92rem;font-weight:700;color:#2e2e30;line-height:1.35;margin-bottom:.45rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;min-height:0}
.mcard__name--blog{margin-bottom:.35rem}
.mcard__name a{color:inherit;text-decoration:none;transition:color .2s}
.mcard__name a:hover{color:#279ff9}
.mcard__blog-excerpt{font-size:.82rem;color:#555;line-height:1.55;margin-bottom:.5rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;flex:1;min-height:0}
.mcard__footer{display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-top:auto;flex-shrink:0;padding-top:.65rem;border-top:1px solid #f0f0f5}
.mcard__footer--blog{align-items:flex-end}
.mcard__blog-read{display:inline-flex;align-items:center;gap:.28rem;font-size:.82rem;font-weight:700;color:#279ff9;text-decoration:none;transition:color .2s}
.mcard__blog-read:hover{color:#1e8de0;text-decoration:underline}
.mcard__blog-read-icon{width:15px;height:15px}
.mcard__blog-by{font-size:.75rem;color:#808089;text-align:end;line-height:1.3;max-width:55%}

@keyframes mcard-in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.mcard{animation:mcard-in .4s ease both}
.mcard:nth-child(2){animation-delay:.05s}.mcard:nth-child(3){animation-delay:.1s}
.mcard:nth-child(4){animation-delay:.15s}.mcard:nth-child(5){animation-delay:.2s}
.mcard:nth-child(6){animation-delay:.25s}.mcard:nth-child(7){animation-delay:.3s}
.mcard:nth-child(8){animation-delay:.35s}

/* ─── Empty ──────────────────────────────────────────── */
.meals-empty{grid-column:1/-1;text-align:center;padding:4rem 1rem}
.meals-empty__icon{width:80px;height:80px;margin:0 auto 1.25rem;background:#e8e8ef;border-radius:50%;display:flex;align-items:center;justify-content:center}
.meals-empty__icon svg{width:36px;height:36px;color:#bbb}
.meals-empty__title{font-size:1.25rem;font-weight:700;color:#2e2e30;margin-bottom:.5rem}
.meals-empty__desc{color:#808089;font-size:.9rem}

/* ─── Pagination (Tailwind default — align to brand) ─── */
.blog-pagination-wrap{margin-top:2.5rem;display:flex;justify-content:center}
.blog-store-page nav[role="navigation"] .inline-flex.shadow-sm.rounded-md a[href]:not([aria-disabled]),
.blog-store-page nav[role="navigation"] .inline-flex.shadow-sm.rounded-md span[aria-current="page"] span{border-radius:10px!important;margin:0 2px!important}
.blog-store-page nav[role="navigation"] [aria-current="page"] span{border-color:#279ff9!important;background:#279ff9!important;color:#fff!important}
.blog-store-page nav[role="navigation"] a:hover{border-color:#279ff9!important;color:#279ff9!important}
</style>
@endpush
