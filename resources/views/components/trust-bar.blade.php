@php
    $whatsappUrl = (! empty($socialWhatsapp) && $socialWhatsapp !== '#') ? $socialWhatsapp : null;

    $items = [
        [
            'tone' => 'blue',
            'icon' => 'meals',
            'headline' => __('home.trust.meals_value'),
            'label' => __('home.trust.meals_label'),
            'href' => null,
        ],
        [
            'tone' => 'green',
            'icon' => 'delivery',
            'headline' => __('home.trust.daily_label'),
            'label' => __('home.trust.daily_desc'),
            'href' => null,
        ],
        [
            'tone' => 'blue',
            'icon' => 'calories',
            'headline' => __('home.trust.calories_label'),
            'label' => __('home.trust.calories_desc'),
            'href' => null,
        ],
        [
            'tone' => 'green',
            'icon' => 'support',
            'headline' => __('home.trust.support_label'),
            'label' => __('home.trust.support_desc'),
            'href' => $whatsappUrl,
        ],
    ];
@endphp

@once
    <style>
        .hero-trust {
            position: relative;
            z-index: 25;
            margin-top: clamp(1.25rem, 3vw, 2.25rem);
            width: 100%;
            border-radius: 0 0 0.375rem 0.375rem;
            overflow: hidden;
            isolation: isolate;
            background:
                linear-gradient(180deg, rgba(15, 23, 42, .04) 0%, rgba(15, 23, 42, .72) 100%),
                linear-gradient(135deg, rgba(39, 159, 249, .08) 0%, rgba(63, 181, 54, .06) 100%);
            backdrop-filter: blur(14px) saturate(1.2);
            -webkit-backdrop-filter: blur(14px) saturate(1.2);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .22),
                0 -12px 40px rgba(15, 23, 42, .08);
        }
        .hero-trust::before {
            content: '';
            position: absolute;
            inset-inline: 0;
            top: 0;
            height: 2px;
            background: linear-gradient(
                90deg,
                transparent 0%,
                var(--color-blue, #279ff9) 20%,
                var(--color-green, #3fb536) 50%,
                var(--color-blue, #279ff9) 80%,
                transparent 100%
            );
            background-size: 200% 100%;
            animation: heroTrustBeam 4.5s ease-in-out infinite;
            z-index: 2;
        }
        .hero-trust::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: .035;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(255, 255, 255, .35) 2px,
                rgba(255, 255, 255, .35) 3px
            );
            mix-blend-mode: overlay;
            z-index: 1;
        }
        .hero-trust__sweep {
            position: absolute;
            inset-block: 0;
            width: 40%;
            background: linear-gradient(
                105deg,
                transparent 30%,
                rgba(255, 255, 255, .12) 50%,
                transparent 70%
            );
            transform: translateX(-120%);
            animation: heroTrustSweep 7s ease-in-out 1.5s infinite;
            pointer-events: none;
            z-index: 1;
        }
        [dir="rtl"] .hero-trust__sweep {
            animation-name: heroTrustSweepRtl;
        }
        .hero-trust__list {
            position: relative;
            z-index: 3;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            list-style: none;
            margin: 0;
            padding: 0;
        }
        @media (min-width: 768px) {
            .hero-trust__list {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .hero-trust__item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .7rem;
            min-height: 4.35rem;
            padding: .9rem 1rem;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            opacity: 0;
            transform: translateY(14px);
            filter: blur(4px);
            animation: heroTrustItemIn .85s cubic-bezier(.16, 1, .3, 1) forwards;
            transition: background .25s ease, transform .25s ease;
        }
        .hero-trust__item:nth-child(1) { animation-delay: 1.05s; }
        .hero-trust__item:nth-child(2) { animation-delay: 1.18s; }
        .hero-trust__item:nth-child(3) { animation-delay: 1.31s; }
        .hero-trust__item:nth-child(4) { animation-delay: 1.44s; }
        @media (min-width: 768px) {
            .hero-trust__item {
                min-height: 4.65rem;
                border-bottom: none;
            }
            .hero-trust__item:not(:last-child) {
                border-inline-end: 1px solid rgba(255, 255, 255, .1);
            }
        }
        .hero-trust__item:nth-child(odd) {
            border-inline-end: 1px solid rgba(255, 255, 255, .08);
        }
        @media (min-width: 768px) {
            .hero-trust__item:nth-child(odd) {
                border-inline-end: none;
            }
        }
        @media (max-width: 767px) {
            .hero-trust__item:nth-last-child(-n+2) {
                border-bottom: none;
            }
        }
        a.hero-trust__item:hover {
            background: rgba(255, 255, 255, .06);
            transform: translateY(-2px);
        }
        .hero-trust__icon {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .94);
            transition: transform .3s cubic-bezier(.16, 1, .3, 1), box-shadow .3s ease;
        }
        .hero-trust__icon svg {
            width: 1.08rem;
            height: 1.08rem;
            position: relative;
            z-index: 1;
        }
        .hero-trust__icon--blue {
            color: var(--color-blue, #279ff9);
            box-shadow:
                0 0 0 1px rgba(39, 159, 249, .25),
                0 0 18px rgba(39, 159, 249, .25);
        }
        .hero-trust__icon--green {
            color: var(--color-green, #3fb536);
            box-shadow:
                0 0 0 1px rgba(63, 181, 54, .28),
                0 0 18px rgba(63, 181, 54, .22);
        }
        .hero-trust__item:hover .hero-trust__icon {
            transform: scale(1.08);
        }
        .hero-trust__copy {
            min-width: 0;
            text-align: start;
        }
        .hero-trust__headline {
            display: block;
            font-size: .84rem;
            font-weight: 800;
            line-height: 1.25;
            color: #fff;
            letter-spacing: -.01em;
            text-shadow: 0 1px 12px rgba(0, 0, 0, .25);
        }
        .hero-trust__headline--glow {
            background: linear-gradient(90deg, #fff 0%, #bae6fd 45%, #bbf7d0 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-trust__label {
            display: block;
            margin-top: .14rem;
            font-size: .68rem;
            font-weight: 500;
            line-height: 1.35;
            color: rgba(255, 255, 255, .72);
        }
        @media (min-width: 1024px) {
            .hero-trust__headline { font-size: .9rem; }
            .hero-trust__label { font-size: .72rem; }
        }
        @keyframes heroTrustBeam {
            0%, 100% { background-position: 100% 0; opacity: .65; }
            50% { background-position: 0% 0; opacity: 1; }
        }
        @keyframes heroTrustSweep {
            0%, 72%, 100% { transform: translateX(-120%); opacity: 0; }
            8% { opacity: 1; }
            36% { transform: translateX(280%); opacity: 1; }
            44% { opacity: 0; }
        }
        @keyframes heroTrustSweepRtl {
            0%, 72%, 100% { transform: translateX(120%); opacity: 0; }
            8% { opacity: 1; }
            36% { transform: translateX(-280%); opacity: 1; }
            44% { opacity: 0; }
        }
        @keyframes heroTrustItemIn {
            to {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .hero-trust::before,
            .hero-trust__sweep { animation: none; }
            .hero-trust__item {
                opacity: 1;
                transform: none;
                filter: none;
                animation: none;
            }
        }
    </style>
@endonce

<div class="hero-trust" aria-label="{{ __('home.trust.aria') }}">
    <span class="hero-trust__sweep" aria-hidden="true"></span>
    <ul class="hero-trust__list">
        @foreach($items as $item)
            @php
                $tag = $item['href'] ? 'a' : 'li';
                $isStat = $item['icon'] === 'meals';
            @endphp
            <{{ $tag }}
                @class([
                    'hero-trust__item',
                ])
                @if($item['href'])
                    href="{{ $item['href'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                @endif
            >
                <span @class(['hero-trust__icon', 'hero-trust__icon--'.$item['tone']]) aria-hidden="true">
                    @switch($item['icon'])
                        @case('meals')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.87c1.355 0 2.697.055 4.024.165C17.155 8.51 18 9.473 18 10.608v2.513M9 20.25h6M5.25 8.25h13.5"/>
                            </svg>
                            @break
                        @case('delivery')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12 0V9.75"/>
                            </svg>
                            @break
                        @case('calories')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                            </svg>
                            @break
                        @case('support')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                            </svg>
                            @break
                    @endswitch
                </span>
                <span class="hero-trust__copy">
                    <span @class(['hero-trust__headline', 'hero-trust__headline--glow' => $isStat])>{{ $item['headline'] }}</span>
                    <span class="hero-trust__label">{{ $item['label'] }}</span>
                </span>
            </{{ $tag }}>
        @endforeach
    </ul>
</div>
