@php
    $whatsappUrl = (! empty($socialWhatsapp) && $socialWhatsapp !== '#') ? $socialWhatsapp : null;

    $items = [
        [
            'icon' => 'meals',
            'value' => __('home.trust.meals_value'),
            'label' => __('home.trust.meals_label'),
            'desc' => __('home.trust.meals_desc'),
            'href' => null,
        ],
        [
            'icon' => 'delivery',
            'value' => null,
            'label' => __('home.trust.daily_label'),
            'desc' => __('home.trust.daily_desc'),
            'href' => null,
        ],
        [
            'icon' => 'calories',
            'value' => null,
            'label' => __('home.trust.calories_label'),
            'desc' => __('home.trust.calories_desc'),
            'href' => null,
        ],
        [
            'icon' => 'support',
            'value' => null,
            'label' => __('home.trust.support_label'),
            'desc' => __('home.trust.support_desc'),
            'href' => $whatsappUrl,
        ],
    ];
@endphp

@once
    <style>
        .trust-bar {
            margin-top: 1.25rem;
            margin-bottom: .25rem;
        }
        .trust-bar__inner {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
            padding: .85rem 1rem;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
            border: 1px solid #e8edf3;
            box-shadow: 0 8px 24px -18px rgba(15, 23, 42, .35);
        }
        @media (min-width: 768px) {
            .trust-bar__inner {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: .5rem;
                padding: 1rem 1.25rem;
            }
        }
        .trust-bar__item {
            display: flex;
            align-items: center;
            gap: .7rem;
            min-width: 0;
            padding: .45rem .35rem;
            border-radius: 10px;
            color: inherit;
            text-decoration: none;
            transition: background .15s ease, transform .15s ease;
        }
        a.trust-bar__item:hover {
            background: rgba(39, 159, 249, .06);
            transform: translateY(-1px);
        }
        .trust-bar__icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 12px;
            background: linear-gradient(145deg, rgba(39, 159, 249, .12), rgba(63, 181, 54, .1));
            color: #0b72d9;
        }
        .trust-bar__icon svg {
            width: 1.2rem;
            height: 1.2rem;
        }
        .trust-bar__text {
            min-width: 0;
        }
        .trust-bar__value {
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
            letter-spacing: -.01em;
        }
        .trust-bar__label {
            font-size: .78rem;
            font-weight: 700;
            line-height: 1.25;
            color: #0f172a;
        }
        .trust-bar__desc {
            font-size: .68rem;
            line-height: 1.35;
            color: #64748b;
            margin-top: .1rem;
        }
        @media (min-width: 1024px) {
            .trust-bar__value { font-size: 1.05rem; }
            .trust-bar__label { font-size: .82rem; }
            .trust-bar__desc { font-size: .72rem; }
        }
    </style>
@endonce

<div class="trust-bar container" aria-label="{{ __('home.trust.aria') }}">
    <div class="trust-bar__inner">
        @foreach($items as $item)
            @php
                $tag = $item['href'] ? 'a' : 'div';
            @endphp
            <{{ $tag }}
                @class(['trust-bar__item'])
                @if($item['href'])
                    href="{{ $item['href'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                @endif
            >
                <span class="trust-bar__icon" aria-hidden="true">
                    @switch($item['icon'])
                        @case('meals')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.87c1.355 0 2.697.055 4.024.165C17.155 8.51 18 9.473 18 10.608v2.513M9 20.25h6M5.25 8.25h13.5"/>
                            </svg>
                            @break
                        @case('delivery')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12 0V9.75"/>
                            </svg>
                            @break
                        @case('calories')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                            </svg>
                            @break
                        @case('support')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                            </svg>
                            @break
                    @endswitch
                </span>
                <span class="trust-bar__text">
                    @if($item['value'])
                        <span class="trust-bar__value">{{ $item['value'] }}</span>
                    @endif
                    <span class="trust-bar__label">{{ $item['label'] }}</span>
                    <span class="trust-bar__desc">{{ $item['desc'] }}</span>
                </span>
            </{{ $tag }}>
        @endforeach
    </div>
</div>
