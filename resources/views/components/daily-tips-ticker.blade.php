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
                background: linear-gradient(90deg, #0B1220 0%, #14532D 52%, #166534 100%);
                color: #F8FAFC;
                border-top: 1px solid rgba(255, 255, 255, .06);
                overflow: hidden;
                min-height: 40px;
            }
            .site-tips-ticker__badge {
                display: flex;
                align-items: center;
                gap: .45rem;
                flex-shrink: 0;
                padding: .5rem .9rem;
                background: rgba(255, 255, 255, .1);
                font-size: .72rem;
                font-weight: 800;
                letter-spacing: .05em;
                text-transform: uppercase;
                border-inline-end: 1px solid rgba(255, 255, 255, .12);
            }
            .site-tips-ticker__live {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: #F87171;
                box-shadow: 0 0 0 0 rgba(248, 113, 113, .65);
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
                font-size: .84rem;
                font-weight: 600;
                white-space: nowrap;
                color: #E2E8F0;
            }
            .site-tips-ticker__sep {
                color: rgba(255, 255, 255, .35);
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
                0% { box-shadow: 0 0 0 0 rgba(248, 113, 113, .65); }
                70% { box-shadow: 0 0 0 8px rgba(248, 113, 113, 0); }
                100% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0); }
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
