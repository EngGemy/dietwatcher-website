@php
    use App\Services\DailyHealthTipsService;

    $dailyTips = app(DailyHealthTipsService::class)->tips();
@endphp

@if($dailyTips !== [])
    @once
        <style>
            .site-tips-ticker {
                display: flex;
                align-items: stretch;
                background: var(--color-gray-200, #f5f5fa);
                color: var(--color-black, #2e2e30);
                border-bottom: 1px solid rgba(15, 23, 42, .07);
                overflow: hidden;
                min-height: 38px;
            }
            .site-tips-ticker__badge {
                display: flex;
                align-items: center;
                gap: .45rem;
                flex-shrink: 0;
                padding: .45rem .85rem;
                background: linear-gradient(135deg, rgba(39, 159, 249, .12), rgba(63, 181, 54, .1));
                font-size: .7rem;
                font-weight: 800;
                letter-spacing: .04em;
                text-transform: uppercase;
                color: #0f172a;
                border-inline-end: 1px solid rgba(15, 23, 42, .08);
            }
            .site-tips-ticker__live {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: var(--color-green, #3fb536);
                box-shadow: 0 0 0 0 rgba(63, 181, 54, .45);
                animation: site-tips-live 1.8s ease-out infinite;
            }
            .site-tips-ticker__viewport {
                flex: 1;
                overflow: hidden;
                position: relative;
                mask-image: linear-gradient(90deg, transparent, #000 2rem, #000 calc(100% - 2rem), transparent);
                -webkit-mask-image: linear-gradient(90deg, transparent, #000 2rem, #000 calc(100% - 2rem), transparent);
            }
            [dir="rtl"] .site-tips-ticker__viewport {
                mask-image: linear-gradient(270deg, transparent, #000 2rem, #000 calc(100% - 2rem), transparent);
                -webkit-mask-image: linear-gradient(270deg, transparent, #000 2rem, #000 calc(100% - 2rem), transparent);
            }
            .site-tips-ticker__track {
                display: flex;
                align-items: center;
                gap: 3rem;
                width: max-content;
                padding: .62rem 1rem;
                animation: site-tips-marquee 70s linear infinite;
                will-change: transform;
            }
            [dir="rtl"] .site-tips-ticker__track {
                animation-name: site-tips-marquee-rtl;
            }
            .site-tips-ticker:hover .site-tips-ticker__track {
                animation-play-state: paused;
            }
            .site-tips-ticker__item {
                display: inline-flex;
                align-items: center;
                gap: .65rem;
                font-size: .8rem;
                font-weight: 600;
                white-space: nowrap;
                color: rgba(15, 23, 42, .78);
            }
            .site-tips-ticker__sep {
                color: rgba(39, 159, 249, .45);
                font-weight: 700;
                user-select: none;
            }
            @keyframes site-tips-marquee {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
            @keyframes site-tips-marquee-rtl {
                0% { transform: translateX(0); }
                100% { transform: translateX(50%); }
            }
            @keyframes site-tips-live {
                0% { box-shadow: 0 0 0 0 rgba(63, 181, 54, .45); }
                70% { box-shadow: 0 0 0 7px rgba(63, 181, 54, 0); }
                100% { box-shadow: 0 0 0 0 rgba(63, 181, 54, 0); }
            }
            @media (prefers-reduced-motion: reduce) {
                .site-tips-ticker__track { animation: none !important; flex-wrap: wrap; width: 100%; }
                .site-tips-ticker__live { animation: none; }
            }
        </style>
    @endonce

    <div class="site-tips-ticker" aria-label="{{ __('account.daily_tips') }}">
        <div class="site-tips-ticker__badge">
            <span class="site-tips-ticker__live" aria-hidden="true"></span>
            <span>{{ __('account.daily_tips') }}</span>
        </div>
        <div class="site-tips-ticker__viewport">
            <div class="site-tips-ticker__track">
                @foreach(array_merge($dailyTips, $dailyTips) as $index => $tip)
                    @if($index > 0)
                        <span class="site-tips-ticker__sep" aria-hidden="true">•</span>
                    @endif
                    <span class="site-tips-ticker__item">{{ $tip }}</span>
                @endforeach
            </div>
        </div>
    </div>
@endif
