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

    /**
     * Rewrite a dropdown sub-item URL so that "Meal Plans" children deep-link
     * into the meal-plans page filtered by the matching category id.
     *
     * Match is by label (en or ar) against the API category names. If no match,
     * the original URL is returned as-is (graceful fallback).
     */
    $resolvePlanChildUrl = function ($subItem) use ($planCategoryLookup) {
        $candidates = array_filter([
            $subItem->label_en ?? null,
            $subItem->label_ar ?? null,
            $subItem->label    ?? null,
        ]);

        foreach ($candidates as $label) {
            $key = mb_strtolower(trim((string) $label));
            if (isset($planCategoryLookup[$key])) {
                return route('meal-plans.index', ['category' => $planCategoryLookup[$key]]);
            }
        }

        // No matching (non-empty) category → caller should hide this item.
        return null;
    };

    /** True when the parent dropdown is the "Meal Plans" group. */
    $isMealPlansDropdown = function ($menuItem) {
        $labels = [
            mb_strtolower(trim((string) ($menuItem->label_en ?? ''))),
            mb_strtolower(trim((string) ($menuItem->label_ar ?? ''))),
            mb_strtolower(trim((string) ($menuItem->label ?? ''))),
        ];
        return in_array('meal plans', $labels, true)
            || in_array('خطط الوجبات', $labels, true);
    };

    /**
     * Active check that respects the `?category=X` query string — so when the
     * user is on /meal-plans?category=2 only THAT dropdown item lights up,
     * not all three.
     */
    $isActiveCategoryLink = function (string $url): bool {
        if (! request()->routeIs('meal-plans.*')) {
            return false;
        }
        $query = parse_url($url, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);
        $linkCat = (string) ($params['category'] ?? '');

        $currentCat = (string) request()->query('category', '');

        // Treat numeric vs slug equivalents as the same selection.
        return $linkCat !== '' && $linkCat === $currentCat;
    };

    // Customer session bits — drive the profile menu in the header.
    $customerToken   = (string) session('customer_token', '');
    $customerProfile = session('customer_profile');
    $customerName    = is_array($customerProfile) ? ($customerProfile['name'] ?? null) : ($customerProfile->name ?? null);
    $customerName    = $customerName ?: __('My Account');
    $customerPhone   = (string) session('customer_phone', '');
    $customerInitial = mb_substr(trim($customerName) !== '' ? $customerName : '#', 0, 1);
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

            {{-- Customer profile dropdown — shown only when signed in --}}
            @if($customerToken !== '')
            <div class="hs-dropdown relative inline-flex">
                <button
                    id="hs-dropdown-profile"
                    type="button"
                    class="hs-dropdown-toggle header__profile-btn"
                    aria-haspopup="menu"
                    aria-expanded="false"
                    aria-label="{{ __('My Account') }}"
                >
                    <span class="header__profile-avatar">{{ mb_strtoupper($customerInitial) }}</span>
                    <svg class="header__profile-chevron">
                        <use href="{{ asset('assets/images/icons/sprite.svg#chevron-down') }}"></use>
                    </svg>
                </button>

                <div
                    class="hs-dropdown-menu header__profile-dropdown"
                    role="menu"
                    aria-orientation="vertical"
                    aria-labelledby="hs-dropdown-profile"
                >
                    <div class="header__profile-info">
                        <div class="header__profile-info-avatar">{{ mb_strtoupper($customerInitial) }}</div>
                        <div class="header__profile-info-text">
                            <p class="header__profile-info-name">{{ $customerName }}</p>
                            @if($customerPhone !== '')
                                <p class="header__profile-info-phone" dir="ltr">{{ $customerPhone }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="header__profile-divider"></div>

                    <form method="POST" action="{{ route('auth.logout') }}" class="header__profile-form">
                        @csrf
                        <button type="submit" class="header__profile-logout">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="header__profile-logout-icon">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>{{ __('Sign Out') }}</span>
                        </button>
                    </form>
                </div>
            </div>
            @endif

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
                            $isPlansGroupOuter = $isMealPlansDropdown($menuItem);

                            // Pre-resolve children URLs once so we can both hide
                            // empty plan-categories and skip the whole dropdown
                            // when nothing remains.
                            $resolvedChildren = collect($menuItem->children)
                                ->map(function ($c) use ($isPlansGroupOuter, $resolvePlanChildUrl) {
                                    $url = $isPlansGroupOuter
                                        ? $resolvePlanChildUrl($c)
                                        : ($c->url ?? '#');
                                    return $url === null ? null : ['item' => $c, 'url' => $url];
                                })
                                ->filter()
                                ->values();

                            // If the Meal Plans dropdown has no live categories,
                            // skip rendering the whole dropdown entirely.
                            if ($isPlansGroupOuter && $resolvedChildren->isEmpty()) {
                                continue;
                            }

                            $dropdownActive = $resolvedChildren->contains(fn($r) => $isActiveUrl($r['url']));
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
                                @foreach($resolvedChildren as $resolved)
                                    @php
                                        $subItem = $resolved['item'];
                                        $childUrl = $resolved['url'];
                                        $childActive = $isPlansGroupOuter
                                            ? $isActiveCategoryLink($childUrl)
                                            : $isActiveUrl($childUrl);
                                    @endphp
                                    <a
                                        class="header__dropdown-item {{ $childActive ? 'header__dropdown-item--active' : '' }}"
                                        href="{{ $childUrl }}"
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

/* ─── Profile dropdown ──────────────────────────── */
.header__profile-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px 4px 4px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #334155;
}
.header__profile-btn:hover {
    border-color: #279ff9;
    box-shadow: 0 2px 10px rgba(39,159,249,0.12);
}
.header__profile-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #279ff9 0%, #1a7ed4 100%);
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    line-height: 1;
    flex-shrink: 0;
}
.header__profile-chevron {
    width: 14px;
    height: 14px;
    color: #94a3b8;
    transition: transform 0.2s;
}
.hs-dropdown-open .header__profile-chevron {
    transform: rotate(180deg);
}

.header__profile-dropdown {
    min-width: 240px;
    padding: 8px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    box-shadow: 0 12px 32px rgba(0,0,0,0.08);
    margin-top: 8px;
}
.header__profile-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 8px;
}
.header__profile-info-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #279ff9 0%, #1a7ed4 100%);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
}
.header__profile-info-text {
    min-width: 0;
}
.header__profile-info-name {
    font-weight: 700;
    color: #0f172a;
    font-size: 14px;
    line-height: 1.2;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.header__profile-info-phone {
    color: #64748b;
    font-size: 12px;
    margin: 2px 0 0;
}
.header__profile-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 6px 0;
}
.header__profile-form { margin: 0; }
.header__profile-logout {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 12px;
    border: none;
    background: transparent;
    border-radius: 10px;
    color: #dc2626;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.15s;
}
.header__profile-logout:hover {
    background: #fef2f2;
}
.header__profile-logout-icon {
    width: 18px;
    height: 18px;
}
</style>

<script>
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
