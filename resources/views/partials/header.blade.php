@php
    $mealPlanCategories = app(\App\Services\ExternalDataService::class)->getCategories();

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

    $toMealPlansLocalUrl = function (?string $url): string {
        $url = trim((string) ($url ?? ''));
        if ($url === '') {
            return route('meal-plans.index');
        }

        $parsed = parse_url($url);
        $path = (string) ($parsed['path'] ?? '');
        $query = (string) ($parsed['query'] ?? '');

        if ($path !== '' && str_contains($path, '/meal-plans')) {
            if (preg_match('#/meal-plans/(\d+)#', $path, $m)) {
                return route('meal-plans.show', ['id' => (int) $m[1]]);
            }

            return $query !== ''
                ? route('meal-plans.index').'?'.$query
                : route('meal-plans.index');
        }

        return $url;
    };

    $brandTaglines = [
        ['text' => __('common.brand_tagline_1'), 'lang' => 'en', 'dir' => 'ltr'],
        ['text' => __('common.brand_tagline_2'), 'lang' => 'ar', 'dir' => 'rtl'],
    ];
@endphp
<div class="header-sticky-wrap" id="header-wrap">
<header class="header" id="site-header">
    <nav class="header__nav">
        <div class="header__brand header__brand-lockup">
            <a href="{{ route('home') }}" class="header__logo" aria-label="{{ $siteName }}">
                <img src="{{ $siteLogo }}" alt="{{ $siteName }}" decoding="async" />
            </a>

            <div
                class="header__brand-tagline"
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
                            >
                                <span class="nav-enjoy-wrap" x-cloak x-show="line.lang === 'en' && idx === index" x-transition.opacity.duration.300ms>
                                    <span class="nav-enjoy-text" x-text="line.text"></span>
                                    <img src="{{ asset('assets/images/icons/smile.svg') }}" class="nav-enjoy-smile" alt="" aria-hidden="true" decoding="async" loading="lazy" />
                                </span>
                                <span x-cloak x-show="!(line.lang === 'en' && idx === index)" x-text="line.text"></span>
                            </span>
                        </template>
                    </span>
                </span>
            </div>
        </div>

        <div class="header__actions">
            <div class="header__actions-bar">
            <button
                type="button"
                class="hs-collapse-toggle header__toggle header__action-chip header__action-chip--menu"
                style="--ha-i:3"
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

            <div class="hs-dropdown relative inline-flex header__action-chip header__action-chip--lang" style="--ha-i:2">
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

            <livewire:header.notification-bell />

            <div class="header__action-chip header__action-chip--cart" style="--ha-i:1">
            {{-- Cart Component --}}
            <livewire:cart.cart-manager />
            </div>

            {{-- Profile Dropdown --}}
            @php
                use App\Support\CustomerSession;

                $isLoggedIn = CustomerSession::isLoggedIn();
                $customerName = $isLoggedIn
                    ? CustomerSession::displayName('')
                    : '';
                $customerInitial = $customerName !== ''
                    ? mb_strtoupper(mb_substr($customerName, 0, 1))
                    : '';

                $profileState = [
                    'open' => false,
                    'loggedIn' => $isLoggedIn,
                    'customerName' => $customerName,
                    'customerInitial' => $customerInitial,
                ];
            @endphp

            <div
                class="hs-dropdown relative inline-flex header__action-chip header__action-chip--profile"
                style="--ha-i:0"
                x-data="@js($profileState)"
                @checkout-session-updated.window="
                    loggedIn = !!$event.detail.loggedIn;
                    customerName = $event.detail.customerName || '';
                    customerInitial = customerName ? customerName.trim().charAt(0).toUpperCase() : '';
                "
                @keydown.escape.window="open = false"
            >
                {{-- Trigger button --}}
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    aria-haspopup="true"
                    :aria-expanded="open"
                    aria-label="{{ __('account.my_account') }}"
                    class="header__profile-btn"
                    :class="{ 'header__profile-btn--active': open }"
                >
                    <template x-if="loggedIn && customerInitial">
                        <span class="header__profile-avatar" aria-hidden="true" x-text="customerInitial"></span>
                    </template>
                    <template x-if="!loggedIn || !customerInitial">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                    </template>
                    {{-- Subtle caret --}}
                    <svg class="header__profile-caret" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                {{-- Dropdown panel --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                    x-cloak
                    class="header__profile-dropdown"
                    role="menu"
                    aria-orientation="vertical"
                >
                    <div x-show="loggedIn" x-cloak>
                        <div class="header__profile-welcome">
                            <div class="header__profile-welcome-avatar" aria-hidden="true">
                                <span x-text="customerInitial || '👤'"></span>
                            </div>
                            <div class="header__profile-welcome-text">
                                <p class="header__profile-name">
                                    @if(app()->getLocale() === 'ar')
                                        <span>أهلاً<span x-show="customerName" x-cloak>، <span x-text="customerName"></span></span></span>
                                    @else
                                        <span x-show="customerName" x-cloak>Welcome, <span x-text="customerName"></span></span>
                                        <span x-show="!customerName" x-cloak>Welcome</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="header__profile-divider" role="separator"></div>

                        <nav class="header__profile-nav" aria-label="{{ __('Account navigation') }}">
                            <a href="{{ route('account.dashboard') }}" class="header__profile-item" role="menuitem">
                                <span class="header__profile-item-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                                    </svg>
                                </span>
                                <span>{{ __('account.dashboard') }}</span>
                            </a>

                            <a href="{{ route('account.subscriptions.index') }}" class="header__profile-item" role="menuitem">
                                <span class="header__profile-item-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a3 3 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/>
                                    </svg>
                                </span>
                                <span>{{ __('account.subscriptions') }}</span>
                            </a>

                            <a href="{{ route('account.orders.index') }}" class="header__profile-item" role="menuitem">
                                <span class="header__profile-item-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                                    </svg>
                                </span>
                                <span>{{ __('account.orders') }}</span>
                            </a>

                            <a href="{{ route('account.notifications.index') }}" class="header__profile-item" role="menuitem">
                                <span class="header__profile-item-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                                    </svg>
                                </span>
                                <span>{{ __('account.notifications') }}</span>
                            </a>

                            <a href="{{ route('account.wallet') }}" class="header__profile-item" role="menuitem">
                                <span class="header__profile-item-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/>
                                    </svg>
                                </span>
                                <span>{{ __('account.wallet') }}</span>
                            </a>

                            <a href="{{ route('account.profile') }}" class="header__profile-item" role="menuitem">
                                <span class="header__profile-item-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </span>
                                <span>{{ __('account.profile') }}</span>
                            </a>
                        </nav>

                        <div class="header__profile-divider" role="separator"></div>

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('account.logout') }}" class="header__profile-logout-form">
                            @csrf
                            <button type="submit" class="header__profile-item header__profile-item--danger" role="menuitem">
                                <span class="header__profile-item-icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                                    </svg>
                                </span>
                                <span>{{ __('account.logout') }}</span>
                            </button>
                        </form>
                    </div>

                    <div x-show="!loggedIn" x-cloak>
                        <div class="header__profile-welcome">
                            <div class="header__profile-welcome-avatar" aria-hidden="true">👤</div>
                            <div class="header__profile-welcome-text">
                                <p class="header__profile-greeting">
                                    {{ app()->getLocale() === 'ar' ? 'مرحباً بك' : 'Hello!' }}
                                </p>
                                <p class="header__profile-name" style="font-size:.8rem; font-weight:400; opacity:.7">
                                    {{ app()->getLocale() === 'ar' ? 'سجّل دخولك للوصول لحسابك' : 'Sign in to access your account' }}
                                </p>
                            </div>
                        </div>
                        <div class="header__profile-divider" role="separator"></div>
                        <a href="{{ route('account.login') }}" class="header__profile-item" role="menuitem">
                            <span class="header__profile-item-icon" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                                </svg>
                            </span>
                            <span>{{ __('account.login') }}</span>
                        </a>
                    </div>
                </div>
            </div>
            </div>{{-- /.header__actions-bar --}}

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
                            $isMealPlansDropdown = mb_strtolower(trim((string) $menuItem->label)) === mb_strtolower(__('Meal Plans'));
                            $dropdownChildren = $menuItem->children;
                            if ($isMealPlansDropdown && !empty($mealPlanCategories)) {
                                $dropdownChildren = collect($mealPlanCategories)->map(function ($cat) {
                                    $name = $cat['name'] ?? '';
                                    if (is_array($name)) {
                                        $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?? '';
                                    }

                                    return (object) [
                                        'label' => (string) $name,
                                        'url' => route('meal-plans.index', ['category' => (int) ($cat['id'] ?? 0)]),
                                    ];
                                })->filter(fn($c) => ($c->url ?? '') !== '' && ($c->label ?? '') !== '')->values();
                            }
                            $dropdownActive = collect($dropdownChildren)
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
                                @foreach($dropdownChildren as $subItem)
                                    @php
                                        $childUrl = $isMealPlansDropdown
                                            ? $toMealPlansLocalUrl($subItem->url ?? '')
                                            : ($subItem->url ?? '');
                                        $childActive = $isActiveUrl($childUrl);
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
                        @php
                            $linkIsMealPlans = mb_strtolower(trim((string) $menuItem->label)) === mb_strtolower(__('Meal Plans'));
                            $linkUrl = $linkIsMealPlans
                                ? $toMealPlansLocalUrl($menuItem->url ?? '')
                                : ($menuItem->url ?? '');
                            $active = $isActiveUrl($linkUrl);
                        @endphp
                        <a
                            class="header__link {{ $active ? 'header__link--active' : '' }}"
                            href="{{ $linkUrl }}"
                            @if($active) aria-current="page" @endif
                        >{{ $menuItem->label }}</a>
                    @endif
                @endforeach
            </div>
        </div>
    </nav>
</header>
@stack('header-ticker')
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

/* Improve meal-plans dropdown contrast on light backgrounds */
.header__dropdown-menu {
    background: #ffffff !important;
    border: 1px solid #dbe7f3;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.header__dropdown-item {
    color: #1f2937 !important;
    background: transparent;
    border: 1px solid transparent;
}

.header__dropdown-item:hover,
.header__dropdown-item:focus {
    background: #eaf4ff;
    color: #0b72d9 !important;
    border-color: #bfdbfe;
}

.header__dropdown-item--active,
.header__dropdown-item--active:hover,
.header__dropdown-item--active:focus {
    background: #dbeeff !important;
    color: #0b72d9 !important;
    border-color: #93c5fd;
}

/* Navbar smile/tagline animation refinement */
.header__brand-lockup {
    animation: brandLockupIn .6s cubic-bezier(.22,1,.36,1) both;
}
.header__brand-tagline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-inline-start: .35rem;
}
.header__brand-tagline__line.is-active {
    animation: none;
}
@keyframes brandLockupIn {
    from { opacity: 0; transform: translateY(-20px); }
    to   { opacity: 1; transform: translateY(0); }
}
/* ─── Shared green tint filter ─────────────────────── */
/* Converts the original icon color to brand green #10B981 */
.nav-enjoy-smile {
    filter: brightness(0) saturate(100%) invert(56%) sepia(82%)
            saturate(458%) hue-rotate(118deg) brightness(95%) contrast(88%) !important;
}

/* ─── NAVBAR: "Enjoy it" + smile underneath ────────── */
.nav-enjoy-wrap {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    line-height: 1;
    gap: 2px;
    padding-bottom: 6px;
    overflow: visible;
}

.nav-enjoy-text {
    font-size: 0.875rem;
    font-style: italic;
    color: #10B981;
    font-weight: 500;
    white-space: nowrap;
}

.nav-enjoy-smile {
    display: block;
    width: 74px;
    height: 20px;
    object-fit: contain;
    margin-top: 3px;
    opacity: 0;
    transform: scale(0.3);
    animation:
        smileFlashIn 0.95s cubic-bezier(0.22, 1, 0.36, 1) 0.28s forwards,
        smileBreathe 3s ease-in-out 1.4s infinite;
    filter: drop-shadow(0 0 0 rgba(16,185,129,0));
}

/* ─── Animations ───────────────────────────────────── */
@keyframes smileFlashIn {
    0% {
        opacity: 0;
        transform: scale(0.2) rotate(-16deg) translateY(-10px);
        filter: drop-shadow(0 0 0 rgba(16,185,129,0));
    }
    42% {
        opacity: 1;
        transform: scale(1.34) rotate(8deg) translateY(0);
        filter: drop-shadow(0 0 16px rgba(16,185,129,.55));
    }
    70% {
        opacity: 1;
        transform: scale(0.96) rotate(-3deg);
        filter: drop-shadow(0 0 8px rgba(16,185,129,.25));
    }
    100% {
        opacity: 1;
        transform: scale(1) rotate(0deg);
        filter: drop-shadow(0 0 0 rgba(16,185,129,0));
    }
}

@keyframes smileBreathe {
    0%, 100% {
        transform: scale(1) rotate(0deg);
    }
    25% {
        transform: scale(1.08) rotate(2deg);
    }
    50% {
        transform: scale(1.12) rotate(0deg);
    }
    75% {
        transform: scale(1.08) rotate(-2deg);
    }
}

@media (max-width: 639px) {
    .header-sticky-wrap::after {
        content: "";
        position: absolute;
        inset-inline: 0;
        bottom: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, rgba(39, 159, 249, 0.28) 35%, rgba(63, 181, 54, 0.28) 65%, transparent 100%);
        pointer-events: none;
    }

    .header__brand-tagline {
        display: none !important;
    }
    .header__brand-lockup {
        min-width: 0;
        flex: 1 1 auto;
        max-width: calc(100% - 12.5rem);
    }
    .header__logo img {
        max-height: 2rem;
        filter: drop-shadow(0 2px 6px rgba(39, 159, 249, 0.08));
    }
    .header__actions {
        flex-shrink: 0;
        gap: 0;
        margin-inline-start: auto;
    }

    .header__actions-bar {
        display: inline-flex;
        align-items: center;
        gap: 0.15rem;
        padding: 0.2rem;
        border-radius: 999px;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.94) 100%);
        border: 1px solid rgba(148, 163, 184, 0.28);
        box-shadow:
            0 10px 28px rgba(15, 23, 42, 0.07),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    .header__action-chip {
        position: relative;
        animation: mobileHeaderChipIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
        animation-delay: calc(var(--ha-i, 0) * 0.06s + 0.05s);
    }

    .header__action-chip + .header__action-chip::before {
        content: "";
        position: absolute;
        inset-inline-start: -0.1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1px;
        height: 1.15rem;
        background: rgba(148, 163, 184, 0.32);
        pointer-events: none;
    }

    .header__nav {
        align-items: center;
        row-gap: 0.75rem;
    }

    .header__toggle {
        flex-shrink: 0;
        z-index: 2;
        width: 2.375rem;
        height: 2.375rem;
        border: none;
        border-radius: 999px;
        box-shadow: none;
        background: transparent;
        color: #334155;
        transition: background 0.22s ease, color 0.22s ease, transform 0.22s ease;
    }

    .header__toggle[aria-expanded="true"] {
        background: linear-gradient(135deg, rgba(39, 159, 249, 0.16) 0%, rgba(63, 181, 54, 0.12) 100%);
        color: #279ff9;
        transform: scale(1.02);
    }

    .header__toggle svg {
        width: 1.125rem;
        height: 1.125rem;
    }

    .header__lang {
        min-width: 2.375rem;
        height: 2.375rem;
        padding: 0 0.55rem;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        background: transparent;
    }

    .header__lang svg {
        width: 0.65rem;
        height: 0.65rem;
        opacity: 0.55;
    }

    .header__notif-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.375rem;
        height: 2.375rem;
        border-radius: 999px;
        color: #334155;
        text-decoration: none;
        transition: background 0.22s ease, color 0.22s ease;
    }

    .header__notif-btn svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .header__notif-btn:hover {
        background: rgba(39, 159, 249, 0.1);
        color: #279ff9;
    }

    .header__notif-badge {
        position: absolute;
        top: 0.15rem;
        inset-inline-end: 0.1rem;
        min-width: 1.05rem;
        height: 1.05rem;
        padding: 0 0.25rem;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 0.625rem;
        font-weight: 800;
        line-height: 1.05rem;
        text-align: center;
        border: 2px solid #fff;
    }

    .header__profile-btn {
        width: 2.375rem;
        min-width: 2.375rem;
        height: 2.375rem;
        padding: 0;
        justify-content: center;
        border: none;
        border-radius: 999px;
        background: transparent;
        box-shadow: none;
    }

    .header__profile-btn--active,
    .header__profile-btn:hover {
        background: rgba(39, 159, 249, 0.1);
        border-color: transparent;
    }

    .header__profile-caret {
        display: none;
    }

    .header__profile-avatar {
        width: 1.75rem;
        height: 1.75rem;
        font-size: 0.75rem;
    }

    .cart-btn {
        width: 2.375rem;
        height: 2.375rem;
        border: none;
        border-radius: 999px;
        background: transparent;
        box-shadow: none;
    }

    .cart-btn:hover {
        background: rgba(39, 159, 249, 0.1);
        box-shadow: none;
    }

    .cart-btn__badge {
        top: -0.2rem;
        inset-inline-end: -0.25rem;
        min-width: 1.125rem;
        height: 1.125rem;
        font-size: 0.625rem;
        background: linear-gradient(135deg, #279ff9 0%, #3fb536 100%);
        border-width: 1.5px;
        box-shadow: 0 0 0 3px rgba(39, 159, 249, 0.16);
        animation: cartBadgePulse 2.4s ease-in-out infinite;
    }

    .header__toggle.hidden {
        display: inline-flex !important;
    }

    .header__collapse:not(.hidden) {
        margin-top: 0.65rem;
        padding: 0.85rem;
        border-radius: 1rem;
        background: linear-gradient(165deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.96) 100%);
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        animation: mobileMenuReveal 0.42s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    .header__collapse:not(.hidden) .header__menu {
        margin-top: 0;
        gap: 0.35rem;
    }

    .header__collapse:not(.hidden) .header__link,
    .header__collapse:not(.hidden) .header__dropdown-toggle {
        display: flex;
        width: 100%;
        padding: 0.85rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.9375rem;
        font-weight: 600;
        color: #334155;
        background: transparent;
        transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }

    .header__collapse:not(.hidden) .header__link--active,
    .header__collapse:not(.hidden) .header__dropdown-toggle.header__link--active {
        background: linear-gradient(90deg, #279ff9 0%, #3fb536 100%);
        color: #fff;
        box-shadow: 0 8px 18px rgba(39, 159, 249, 0.22);
    }

    .header__collapse:not(.hidden) .header__link:active,
    .header__collapse:not(.hidden) .header__dropdown-toggle:active {
        transform: scale(0.98);
    }

    .header__collapse:not(.hidden) .header__dropdown-menu {
        margin-top: 0.35rem;
        margin-inline-start: 0.75rem;
        padding: 0.35rem;
        border-radius: 0.75rem;
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: none;
        background: rgba(241, 245, 249, 0.65) !important;
    }

    .header__collapse:not(.hidden) .header__dropdown-item {
        border-radius: 0.55rem;
        padding: 0.65rem 0.75rem;
        font-size: 0.875rem;
    }
}

@keyframes mobileHeaderChipIn {
    from {
        opacity: 0;
        transform: translateY(-8px) scale(0.92);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes mobileMenuReveal {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes cartBadgePulse {
    0%, 100% {
        box-shadow: 0 0 0 3px rgba(39, 159, 249, 0.14);
    }
    50% {
        box-shadow: 0 0 0 5px rgba(63, 181, 54, 0.18);
    }
}

@media (min-width: 640px) {
    .header__actions-bar {
        display: contents;
    }
}

@media (prefers-reduced-motion: reduce) {
    .header__brand-lockup,
    .nav-enjoy-smile,
    .header__brand-tagline__line.is-active,
    .header__action-chip,
    .cart-btn__badge {
        animation: none !important;
        transform: none !important;
        transition: none !important;
    }
    .nav-enjoy-smile {
        opacity: 1 !important;
        transform: scale(1) !important;
    }
}

/* ─── Profile Dropdown ─────────────────────────────── */
.header__profile-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    height: 40px;
    min-width: 40px;
    padding: 0 8px;
    border: 1.5px solid #e5e7eb;
    border-radius: 999px;
    background: #fff;
    color: #374151;
    cursor: pointer;
    transition: border-color .2s, box-shadow .2s, background .2s;
    font-size: .88rem;
}
.header__profile-btn:hover,
.header__profile-btn--active {
    border-color: #279ff9;
    box-shadow: 0 0 0 3px rgba(39,159,249,.12);
    color: #279ff9;
}
.header__profile-caret {
    width: 13px;
    height: 13px;
    transition: transform .2s ease;
    opacity: .5;
}

/* Avatar circle with initial */
.header__profile-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: linear-gradient(135deg, #279ff9 0%, #1e8de0 100%);
    color: #fff;
    font-size: .78rem;
    font-weight: 700;
    line-height: 1;
    flex-shrink: 0;
    letter-spacing: 0;
}

/* Dropdown panel */
.header__profile-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    inset-inline-end: 0;
    z-index: 9990;
    min-width: 240px;
    max-width: 290px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(15, 23, 42, .14), 0 2px 8px rgba(15, 23, 42, .06);
    overflow: hidden;
    transform-origin: top right;
}
[dir="rtl"] .header__profile-dropdown {
    transform-origin: top left;
}

/* Welcome section */
.header__profile-welcome {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: 1rem 1.1rem .85rem;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}
.header__profile-welcome-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #279ff9 0%, #1e8de0 100%);
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(39,159,249,.35);
}
.header__profile-welcome-text {
    min-width: 0;
    flex: 1;
}
.header__profile-greeting {
    font-size: .75rem;
    color: #6b7280;
    margin: 0 0 1px;
    font-weight: 500;
}
.header__profile-name {
    font-size: .95rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Divider */
.header__profile-divider {
    height: 1px;
    background: #f3f4f6;
    margin: 0;
}

/* Nav items */
.header__profile-nav {
    padding: .4rem .5rem;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.header__profile-item {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .55rem .7rem;
    border-radius: 10px;
    font-size: .88rem;
    font-weight: 500;
    color: #374151;
    text-decoration: none;
    transition: background .15s, color .15s;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: start;
}
.header__profile-item:hover {
    background: #f0f9ff;
    color: #279ff9;
}
.header__profile-item:hover .header__profile-item-icon {
    color: #279ff9;
}
.header__profile-item--danger {
    color: #dc2626;
}
.header__profile-item--danger:hover {
    background: #fff1f1;
    color: #dc2626;
}
.header__profile-item--danger .header__profile-item-icon {
    color: #dc2626;
}
.header__profile-item--danger:hover .header__profile-item-icon {
    color: #dc2626;
}
.header__profile-item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    color: #9ca3af;
    transition: color .15s;
}
.header__profile-item-icon svg {
    width: 18px;
    height: 18px;
}

/* Logout form */
.header__profile-logout-form {
    padding: .4rem .5rem;
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