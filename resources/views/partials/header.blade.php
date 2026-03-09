@php
    // Build a deduplicated menu: dynamic items from DB + fallback hardcoded links
    $dynamicLabels = $headerMenu->pluck('label')->map(fn($l) => mb_strtolower(trim($l)))->toArray();
    $dynamicUrls = $headerMenu->map(fn($m) => rtrim($m->url ?? '', '/'))->filter()->toArray();

    $hardcodedLinks = [
        ['label' => __('Meal Plans'), 'url' => route('meal-plans.index')],
        ['label' => __('Meals'),      'url' => route('meals.index')],
        ['label' => __('FAQs'),       'url' => '/#faq'],
        ['label' => __('Contact Us'), 'url' => route('contact.index')],
    ];

    // Only keep hardcoded links that aren't already in the dynamic menu
    $extraLinks = collect($hardcodedLinks)->filter(function ($link) use ($dynamicLabels, $dynamicUrls) {
        $labelMatch = in_array(mb_strtolower(trim($link['label'])), $dynamicLabels);
        $urlMatch = in_array(rtrim($link['url'], '/'), $dynamicUrls);
        return !$labelMatch && !$urlMatch;
    });
@endphp
<div class="header-sticky-wrap" id="header-wrap">
<header class="header" id="site-header">
    <nav class="header__nav">
        <a href="{{ route('home') }}" class="header__logo">
            <img src="{{ $siteLogo }}" alt="{{ $siteName }}" />
        </a>

        <div class="header__actions">
            <button
                type="button"
                class="hs-collapse-toggle header__toggle"
                id="hs-navbar-alignment-collapse"
                aria-expanded="false"
                aria-controls="hs-navbar-alignment"
                aria-label="{{ __('Toggle navigation') }}"
                data-hs-collapse="#hs-navbar-alignment"
            >
                <svg>
                    <use href="{{ asset('assets/images/icons/sprite.svg#menu') }}"></use>
                </svg>
                <span class="sr-only">{{ __('Toggle') }}</span>
            </button>

            <div class="hs-dropdown relative inline-flex">
                <button
                    id="hs-dropdown-lang"
                    type="button"
                    class="hs-dropdown-toggle header__lang"
                    aria-haspopup="menu"
                    aria-expanded="false"
                    aria-label="{{ __('Language Switch') }}"
                >
                    {{ strtoupper($currentLocale) }}
                    <svg>
                        <use href="{{ asset('assets/images/icons/sprite.svg#chevron-down') }}"></use>
                    </svg>
                </button>

                <div
                    class="hs-dropdown-menu header__lang-dropdown"
                    role="menu"
                    aria-orientation="vertical"
                    aria-labelledby="hs-dropdown-lang"
                >
                    @foreach($availableLocales as $locale => $name)
                        <a class="header__dropdown-item" href="{{ route('locale.switch', $locale) }}">
                            {{ $name }} ({{ strtoupper($locale) }})
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Cart Component --}}
            <livewire:cart.cart-manager />

            @foreach($headerActions as $action)
                @if($action->type === 'button')
                    <a href="{{ $action->url }}" class="{{ $action->meta['classes'] ?? 'btn btn--primary' }}">
                        {{ $action->label }}
                    </a>
                @endif
            @endforeach
        </div>

        <div
            id="hs-navbar-alignment"
            class="hs-collapse header__collapse hidden sm:block"
            aria-labelledby="hs-navbar-alignment-collapse"
            role="region"
        >
            <div class="header__menu">
                {{-- Hardcoded links that are NOT in the dynamic menu --}}
                @foreach($extraLinks as $link)
                    <a class="header__link" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                @endforeach

                {{-- Dynamic menu items from database --}}
                @foreach($headerMenu as $menuItem)
                    @if($menuItem->type === 'dropdown')
                        <div class="hs-dropdown [--adaptive:none] [--strategy:static] [--trigger:hover] sm:[--adaptive:adaptive] sm:[--strategy:fixed]">
                            <button
                                id="hs-navbar-{{ $menuItem->id }}-dropdown"
                                type="button"
                                class="hs-dropdown-toggle header__dropdown-toggle"
                                aria-haspopup="menu"
                                aria-expanded="false"
                                aria-label="{{ __('Mega Menu') }}"
                            >
                                {{ $menuItem->label }}
                                <svg>
                                    <use href="{{ asset('assets/images/icons/sprite.svg#chevron-down') }}"></use>
                                </svg>
                            </button>

                            <div
                                class="hs-dropdown-menu header__dropdown-menu"
                                role="menu"
                                aria-orientation="vertical"
                                aria-labelledby="hs-navbar-{{ $menuItem->id }}-dropdown"
                            >
                                @foreach($menuItem->children as $subItem)
                                    <a class="header__dropdown-item" href="{{ $subItem->url }}">
                                        {{ $subItem->label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @elseif($menuItem->type === 'link')
                        <a class="header__link" href="{{ $menuItem->url }}">
                            {{ $menuItem->label }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </nav>
</header>
</div>
<div class="header-spacer" id="header-spacer"></div>

<style>
/* ─── Fixed Header ──────────────────────────────── */
.header-sticky-wrap {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: #fff;
    transition: box-shadow 0.3s ease;
}
.header-sticky-wrap.is-scrolled {
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
}
.header-sticky-wrap.is-scrolled .header {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}
.header-sticky-wrap.is-scrolled .header__logo img {
    transition: height 0.3s ease;
    max-height: 32px;
}
/* Spacer to prevent content from hiding behind the fixed header */
.header-spacer {
    display: block;
}
</style>

<script>
(function() {
    var wrap = document.getElementById('header-wrap');
    var spacer = document.getElementById('header-spacer');
    if (!wrap) return;

    // Set spacer height to match header
    function setSpacer() {
        if (spacer) spacer.style.height = wrap.offsetHeight + 'px';
    }
    setSpacer();
    window.addEventListener('resize', setSpacer);

    function onScroll() {
        if (window.scrollY > 10) {
            wrap.classList.add('is-scrolled');
        } else {
            wrap.classList.remove('is-scrolled');
        }
        setSpacer();
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
</script>
