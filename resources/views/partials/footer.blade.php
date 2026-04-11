<footer class="footer footer--dark">
    <div class="footer__container">
        <div class="footer__top">
            <div class="footer__brand">
                <a href="{{ route('home') }}" class="footer__logo">
                    <img src="{{ $footerLogo }}" alt="{{ $siteName }}" />
                </a>
                <p class="footer__desc">
                    {{ $footerDescription }}
                </p>

                <div class="footer__socials">
                    @if($socialInstagram)
                        <a href="{{ $socialInstagram }}" class="footer__social-link" target="_blank" rel="noopener" aria-label="{{ __('Instagram') }}">
                            <svg class="icon">
                                <use href="{{ asset('assets/images/icons/sprite.svg#instagram') }}"></use>
                            </svg>
                        </a>
                    @endif
                    @if($socialFacebook)
                        <a href="{{ $socialFacebook }}" class="footer__social-link" target="_blank" rel="noopener" aria-label="{{ __('Facebook') }}">
                            <svg class="icon">
                                <use href="{{ asset('assets/images/icons/sprite.svg#facebook') }}"></use>
                            </svg>
                        </a>
                    @endif
                    @if($socialTwitter)
                        <a href="{{ $socialTwitter }}" class="footer__social-link" target="_blank" rel="noopener" aria-label="{{ __('Twitter') }}">
                            <svg class="icon">
                                <use href="{{ asset('assets/images/icons/sprite.svg#twitter') }}"></use>
                            </svg>
                        </a>
                    @endif
                    @if($socialYouTube)
                        <a href="{{ $socialYouTube }}" class="footer__social-link" target="_blank" rel="noopener" aria-label="{{ __('YouTube') }}">
                            <svg class="icon">
                                <use href="{{ asset('assets/images/icons/sprite.svg#youtube') }}"></use>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="footer__column">
                <h3 class="footer__title">{{ __('Navigation') }}</h3>
                <ul class="footer__list">
                    <li class="footer__item">
                        <a href="{{ route('meal-plans.index') }}" class="footer__link">{{ __('Meal Plans') }}</a>
                    </li>
                    <li class="footer__item">
                        <a href="{{ route('store.index') }}" class="footer__link">{{ __('Market') }}</a>
                    </li>
                    <li class="footer__item">
                        <a href="{{ route('blog.index') }}" class="footer__link">{{ __('Blog') }}</a>
                    </li>
                    <li class="footer__item">
                        <a href="{{ route('faqs.index') }}" class="footer__link">{{ __('FAQ') }}</a>
                    </li>
                    <li class="footer__item">
                        <a href="{{ route('contact.index') }}" class="footer__link">{{ __('Contact Us') }}</a>
                    </li>
                </ul>
            </div>

            <div class="footer__column">
                <h3 class="footer__title">{{ __('Legal') }}</h3>
                <ul class="footer__list">
                    <li class="footer__item">
                        <a href="{{ route('privacy') }}" class="footer__link">{{ __('Privacy Policy') }}</a>
                    </li>
                    <li class="footer__item">
                        <a href="{{ route('terms') }}" class="footer__link">{{ __('Terms & Conditions') }}</a>
                    </li>
                </ul>
            </div>

            <div class="footer__column md:col-span-1 lg:col-span-1">
                <h3 class="footer__title">{{ __('Download App') }}</h3>
                <div class="footer__apps">
                    <a href="{{ $playStoreUrl }}" class="footer__app-link" target="_blank" rel="noopener">
                        <img src="{{ asset('assets/images/play.png') }}" alt="{{ __('Google Play') }}" />
                    </a>
                    <a href="{{ $appStoreUrl }}" class="footer__app-link" target="_blank" rel="noopener">
                        <img src="{{ asset('assets/images/store.png') }}" alt="{{ __('App Store') }}" />
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer__bottom">
        <div class="footer__container">
            <p class="footer__copyright">
                {{ $copyright }}
            </p>
        </div>
    </div>
</footer>
