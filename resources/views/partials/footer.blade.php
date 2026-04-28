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
                        <a href="{{ $socialInstagram }}" class="footer__social-link footer__social-link--wow" data-platform="instagram" data-tooltip="{{ __('Instagram') }}" target="_blank" rel="noopener" aria-label="{{ __('Instagram') }}">
                            <svg class="icon footer__social-icon-svg">
                                <use href="{{ asset('assets/images/icons/sprite.svg#instagram') }}"></use>
                            </svg>
                        </a>
                    @endif
                    @if($socialFacebook)
                        <a href="{{ $socialFacebook }}" class="footer__social-link footer__social-link--wow" data-platform="facebook" data-tooltip="{{ __('Facebook') }}" target="_blank" rel="noopener" aria-label="{{ __('Facebook') }}">
                            <img src="{{ asset('assets/images/icons/facebook.svg') }}" alt="" class="footer__social-icon-img" style="width:20px;height:20px;object-fit:contain;filter:brightness(0) invert(1);" />
                        </a>
                    @endif
                    @if($socialTwitter)
                        <a href="{{ $socialTwitter }}" class="footer__social-link footer__social-link--wow" data-platform="twitter" data-tooltip="{{ __('Twitter') }}" target="_blank" rel="noopener" aria-label="{{ __('Twitter') }}">
                            <img src="{{ asset('assets/images/icons/twitter.svg') }}" alt="" class="footer__social-icon-img" style="width:20px;height:20px;object-fit:contain;filter:brightness(0) invert(1);" />
                        </a>
                    @endif
                    @if(!empty($socialLinkedIn) && $socialLinkedIn !== '#')
                        <a href="{{ $socialLinkedIn }}" class="footer__social-link footer__social-link--wow" data-platform="linkedin" data-tooltip="{{ __('LinkedIn') }}" target="_blank" rel="noopener" aria-label="{{ __('LinkedIn') }}">
                            <img src="{{ asset('assets/images/icons/linkedint.svg') }}" alt="" class="footer__social-icon-img" style="width:20px;height:20px;object-fit:contain;filter:brightness(0) invert(1);" />
                        </a>
                    @endif
                    @if($socialYouTube)
                        <a href="{{ $socialYouTube }}" class="footer__social-link footer__social-link--wow" data-platform="youtube" data-tooltip="{{ __('YouTube') }}" target="_blank" rel="noopener" aria-label="{{ __('YouTube') }}">
                            <svg class="icon footer__social-icon-svg" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="currentColor" d="M21.6 7.2a2.7 2.7 0 0 0-1.9-1.9C18 4.8 12 4.8 12 4.8s-6 0-7.7.5A2.7 2.7 0 0 0 2.4 7.2 28 28 0 0 0 2 12a28 28 0 0 0 .4 4.8 2.7 2.7 0 0 0 1.9 1.9c1.7.5 7.7.5 7.7.5s6 0 7.7-.5a2.7 2.7 0 0 0 1.9-1.9A28 28 0 0 0 22 12a28 28 0 0 0-.4-4.8ZM10 15.5v-7l6 3.5-6 3.5Z"/>
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
