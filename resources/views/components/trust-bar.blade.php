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
            margin-top: clamp(1.5rem, 3vw, 2.5rem);
            width: 100%;
            border-top: 1px solid rgba(15, 23, 42, .08);
            border-radius: 0 0 0.375rem 0.375rem;
            overflow: hidden;
            background: linear-gradient(
                180deg,
                rgba(255, 255, 255, .62) 0%,
                rgba(255, 255, 255, .78) 100%
            );
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .hero-trust__list {
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
            gap: .65rem;
            min-height: 4.25rem;
            padding: .85rem 1rem;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid rgba(15, 23, 42, .06);
            transition: background .2s ease;
        }
        @media (min-width: 768px) {
            .hero-trust__item {
                min-height: 4.5rem;
                border-bottom: none;
            }
            .hero-trust__item:not(:last-child) {
                border-inline-end: 1px solid rgba(15, 23, 42, .08);
            }
        }
        .hero-trust__item:nth-child(odd) {
            border-inline-end: 1px solid rgba(15, 23, 42, .06);
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
            background: rgba(39, 159, 249, .05);
        }
        a.hero-trust__item--green:hover {
            background: rgba(63, 181, 54, .06);
        }
        .hero-trust__icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
        }
        .hero-trust__icon svg {
            width: 1.05rem;
            height: 1.05rem;
        }
        .hero-trust__icon--blue {
            color: var(--color-blue, #279ff9);
            box-shadow: 0 0 0 1px rgba(39, 159, 249, .14);
        }
        .hero-trust__icon--green {
            color: var(--color-green, #3fb536);
            box-shadow: 0 0 0 1px rgba(63, 181, 54, .16);
        }
        .hero-trust__copy {
            min-width: 0;
            text-align: start;
        }
        .hero-trust__headline {
            display: block;
            font-size: .82rem;
            font-weight: 800;
            line-height: 1.25;
            color: #0f172a;
            letter-spacing: -.01em;
        }
        .hero-trust__label {
            display: block;
            margin-top: .12rem;
            font-size: .68rem;
            font-weight: 500;
            line-height: 1.3;
            color: rgba(15, 23, 42, .58);
        }
        @media (min-width: 1024px) {
            .hero-trust__headline { font-size: .88rem; }
            .hero-trust__label { font-size: .72rem; }
        }
    </style>
@endonce

<div class="hero-trust" aria-label="{{ __('home.trust.aria') }}">
    <ul class="hero-trust__list">
        @foreach($items as $item)
            @php
                $tag = $item['href'] ? 'a' : 'li';
            @endphp
            <{{ $tag }}
                @class([
                    'hero-trust__item',
                    'hero-trust__item--green' => $item['tone'] === 'green' && $item['href'],
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
                    <span class="hero-trust__headline">{{ $item['headline'] }}</span>
                    <span class="hero-trust__label">{{ $item['label'] }}</span>
                </span>
            </{{ $tag }}>
        @endforeach
    </ul>
</div>
