@php
    // Build a deduplicated menu: dynamic items from DB + fallback hardcoded links
    $dynamicLabels = $headerMenu->pluck('label')->map(fn($l) => mb_strtolower(trim($l)))->toArray();
    $dynamicUrls = $headerMenu->map(fn($m) => rtrim($m->url ?? '', '/'))->filter()->toArray();

    $hardcodedLinks = [
        ['label' => __('Meal Plans'), 'url' => route('meal-plans.index'), 'route' => 'meal-plans.*'],
        ['label' => __('Market'),     'url' => route('meals.index'),      'route' => 'meals.*'],
        ['label' => __('Blog'),       'url' => route('blog.index'),       'route' => 'blog.*'],
        ['label' => __('FAQs'),       'url' => '/#faq',                   'route' => null],
    ];

    // Only keep hardcoded links that aren't already in the dynamic menu
    $extraLinks = collect($hardcodedLinks)->filter(function ($link) use ($dynamicLabels, $dynamicUrls) {
        $labelMatch = in_array(mb_strtolower(trim($link['label'])), $dynamicLabels);
        $urlMatch = in_array(rtrim($link['url'], '/'), $dynamicUrls);
        return !$labelMatch && !$urlMatch;
    });

    /**
     * Returns true when the given URL is "active" for the current request.
     *
     * Active when:
     *   • Current path exactly matches the link's path, OR
     *   • Current path is a child of the link's path (prefix match), which
     *     covers child routes such as /blog/my-post matching /blog.
     *
     * Never active when:
     *   • The link is a fragment-only anchor on the home page (e.g. /#faq).
     *     Those are handled client-side by the IntersectionObserver below.
     *   • The link points to an external host.
     *   • The link path is "/" and the current path is not exactly "/".
     */
    $isActiveUrl = function (string $url): bool {
        $currentPath = '/' . ltrim(request()->path(), '/');
        $parsed      = parse_url($url);
        $linkPath    = rtrim($parsed['path'] ?? '/', '/') ?: '/';

        // Fragment-only links on home (e.g. /#faq) — handled by JS observer
        if ($linkPath === '/' && !empty($parsed['fragment'])) {
            return false;
        }

        // External URLs
        if (!empty($parsed['host']) && $parsed['host'] !== request()->getHost()) {
            return false;
        }

        // Home "/" — exact match only (avoids marking every page active)
        if ($linkPath === '/') {
            return $currentPath === '/';
        }

        // Exact match OR prefix/child-route match
        return $currentPath === $linkPath || str_starts_with($currentPath, $linkPath . '/');
    };

    /**
     * Combines named-route pattern matching with URL-path matching so
     * callers need a single call for extra/hardcoded links.
     *
     * Named-route patterns (e.g. "blog.*") are checked first because they
     * are faster and also cover URL structures that differ from the link href.
     */
    $isActiveLink = function (array $link) use ($isActiveUrl): bool {
        if (!empty($link['route']) && request()->routeIs($link['route'])) {
            return true;
        }
        return $isActiveUrl($link['url'] ?? '');
    };

    $brandTaglines = [
        ['text' => __('common.brand_tagline_1'), 'lang' => 'en', 'dir' => 'ltr'],
        ['text' => __('common.brand_tagline_2'), 'lang' => 'ar', 'dir' => 'rtl'],
    ];
@endphp
<div class="header-sticky-wrap" id="header-wrap">
<header class="header" id="site-header">
    <nav class="header__nav">
        <div class="header__brand">
            <a href="{{ route('home') }}" class="header__logo" aria-label="{{ $siteName }}">
                <img src="{{ $siteLogo }}" alt="{{ $siteName }}" decoding="async" />
            </a>

            <span class="header__brand-divider" aria-hidden="true"></span>

            <div
                class="header__brand-tagline hidden sm:block"
                x-data="brandTaglineRotator(@js($brandTaglines))"
                x-bind:class="{ 'header__brand-tagline--static': reduced }"
                x-init="init()"
                x-on:mouseenter="pause()"
                x-on:mouseleave="resume()"
                x-on:focusin="pause()"
                x-on:focusout="resume()"
            >
                <p id="brand-tagline-announcer" class="sr-only" aria-live="polite" aria-atomic="true" x-text="lines[index].text"></p>
                <span class="header__brand-tagline__stack" aria-hidden="true">
                    <span class="header__brand-tagline__viewport">
                        <template x-for="(line, idx) in lines" :key="idx">
                            <span
                                class="header__brand-tagline__line"
                                x-bind:class="{ 'is-active': idx === index }"
                                x-bind:lang="line.lang"
                                x-bind:dir="line.dir"
                                x-text="line.text"
                            ></span>
                        </template>
                    </span>
                    <img
                        class="header__brand-tagline__icon"
                        src="{{ asset('assets/images/icons/smile.svg') }}"
                        alt=""
                        aria-hidden="true"
                        decoding="async"
                        loading="lazy"
                    />
                </span>
            </div>
        </div>

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
                {{-- Hardcoded links not in the dynamic menu --}}
                @foreach($extraLinks as $link)
                    @php $active = $isActiveLink($link); @endphp
                    <a
                        class="header__link {{ $active ? 'header__link--active' : '' }}"
                        href="{{ $link['url'] }}"
                        @if($active) aria-current="page" @endif
                    >{{ $link['label'] }}</a>
                @endforeach

                {{-- Dynamic menu items from database --}}
                @foreach($headerMenu as $menuItem)
                    @if($menuItem->type === 'dropdown')
                        @php
                            $dropdownActive = collect($menuItem->children)
                                ->contains(fn($c) => $isActiveUrl($c->url ?? ''));
                        @endphp
                        <div class="hs-dropdown [--adaptive:none] [--strategy:static] [--trigger:hover] sm:[--adaptive:adaptive] sm:[--strategy:fixed]">
                            <button
                                id="hs-navbar-{{ $menuItem->id }}-dropdown"
                                type="button"
                                class="hs-dropdown-toggle header__dropdown-toggle {{ $dropdownActive ? 'header__link--active' : '' }}"
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
                                    @php $childActive = $isActiveUrl($subItem->url ?? ''); @endphp
                                    <a
                                        class="header__dropdown-item {{ $childActive ? 'header__dropdown-item--active' : '' }}"
                                        href="{{ $subItem->url }}"
                                        @if($childActive) aria-current="page" @endif
                                    >{{ $subItem->label }}</a>
                                @endforeach
                            </div>
                        </div>
                    @elseif($menuItem->type === 'link')
                        @php $active = $isActiveUrl($menuItem->url ?? ''); @endphp
                        <a
                            class="header__link {{ $active ? 'header__link--active' : '' }}"
                            href="{{ $menuItem->url }}"
                            @if($active) aria-current="page" @endif
                        >{{ $menuItem->label }}</a>
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
/**
 * Navbar secondary taglines: crossfade, 7s (desktop) / 10s (mobile), pause on hover/focus-in,
 * no auto-rotation when prefers-reduced-motion (first line only).
 */
window.brandTaglineRotator = function (lines) {
    return {
        lines: Array.isArray(lines) && lines.length
            ? lines
            : [
                  { text: 'Enjoy it', lang: 'en', dir: 'ltr' },
                  { text: 'كلها محسوبة!', lang: 'ar', dir: 'rtl' },
              ],
        index: 0,
        timer: null,
        paused: false,
        reduced: false,
        mobile: false,
        _onReduce: null,
        _onMobile: null,
        init: function () {
            var self = this;
            self.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            self.mobile = window.matchMedia('(max-width: 639px)').matches;
            var mqR = window.matchMedia('(prefers-reduced-motion: reduce)');
            var mqM = window.matchMedia('(max-width: 639px)');
            self._onReduce = function (e) {
                self.reduced = e.matches;
                self.resetTimer();
            };
            self._onMobile = function (e) {
                self.mobile = e.matches;
                self.resetTimer();
            };
            mqR.addEventListener('change', self._onReduce);
            mqM.addEventListener('change', self._onMobile);
            if (!self.reduced) {
                self.startTimer();
            }
        },
        intervalMs: function () {
            if (this.reduced) {
                return null;
            }
            return this.mobile ? 10000 : 7000;
        },
        clearTimer: function () {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        startTimer: function () {
            this.clearTimer();
            var ms = this.intervalMs();
            if (!ms) {
                return;
            }
            var self = this;
            this.timer = setInterval(function () {
                if (!self.paused) {
                    self.next();
                }
            }, ms);
        },
        resetTimer: function () {
            this.clearTimer();
            if (!this.reduced) {
                this.startTimer();
            }
        },
        next: function () {
            this.index = (this.index + 1) % this.lines.length;
        },
        pause: function () {
            this.paused = true;
        },
        resume: function () {
            this.paused = false;
        },
    };
};

(function () {
    /* ─── 1. Sticky header — scroll shadow + spacer sync ─── */
    var wrap   = document.getElementById('header-wrap');
    var spacer = document.getElementById('header-spacer');
    if (!wrap) return;

    function syncSpacer() {
        if (spacer) spacer.style.height = wrap.offsetHeight + 'px';
    }
    syncSpacer();
    window.addEventListener('resize', syncSpacer);

    window.addEventListener('scroll', function () {
        wrap.classList.toggle('is-scrolled', window.scrollY > 10);
        syncSpacer();
    }, { passive: true });

    /* ─── 2. Section IntersectionObserver ─────────────────
     *
     * Deferred to DOMContentLoaded so that page sections (which are
     * rendered AFTER this header partial) are in the DOM when we call
     * getElementById(). Without deferring, the script runs while only
     * the header exists, so getElementById('faq') would return null.
     *
     * Works for both desktop and mobile: Preline collapses the menu
     * visually but the .header__link elements stay in the DOM.
     */
    function initSectionObserver() {
        var allNavLinks = document.querySelectorAll('.header__link[href]');
        var observed    = [];

        allNavLinks.forEach(function (link) {
            var href = link.getAttribute('href') || '';
            if (href.indexOf('#') === -1) return;

            try {
                var parsed   = new URL(href, location.origin);
                var fragment = parsed.hash.slice(1);
                if (!fragment) return;

                // Only observe sections that live on the current page
                var linkPath = parsed.pathname.replace(/\/$/, '') || '/';
                var curPath  = location.pathname.replace(/\/$/, '') || '/';
                if (linkPath !== curPath) return;

                var section = document.getElementById(fragment);
                if (section) observed.push({ link: link, section: section });
            } catch (e) { /* malformed href — skip */ }
        });

        if (observed.length === 0) return;

        var sectionObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var match = null;
                for (var i = 0; i < observed.length; i++) {
                    if (observed[i].section === entry.target) { match = observed[i]; break; }
                }
                if (!match) return;

                match.link.classList.toggle('header__link--section-active', entry.isIntersecting);

                if (entry.isIntersecting) {
                    match.link.setAttribute('aria-current', 'true');
                } else {
                    match.link.removeAttribute('aria-current');
                }
            });
        }, {
            // -80px top margin accounts for the fixed header height.
            rootMargin: '-80px 0px -20% 0px',
            threshold:  0.15
        });

        observed.forEach(function (o) { sectionObserver.observe(o.section); });
    }

    // Defer until the full page DOM is ready; handles both fresh loads
    // (document.readyState === 'loading') and late-executing scripts.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSectionObserver);
    } else {
        initSectionObserver();
    }
})();
</script>
