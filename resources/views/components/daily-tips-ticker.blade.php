@php
    use App\Services\DailyHealthTipsService;

    $dailyTips = app(DailyHealthTipsService::class)->tips();
@endphp

@if($dailyTips !== [])
    @once
        <style>
            .site-tips-ticker {
                position: relative;
                display: flex;
                align-items: stretch;
                overflow: hidden;
                min-height: 40px;
                isolation: isolate;
                background:
                    linear-gradient(90deg, #0a1628 0%, #122a4a 42%, #0b3320 100%);
                color: rgba(255, 255, 255, .92);
                border-bottom: 1px solid rgba(39, 159, 249, .18);
                box-shadow: 0 8px 24px -16px rgba(15, 23, 42, .45);
            }
            .site-tips-ticker::before {
                content: '';
                position: absolute;
                inset: 0;
                pointer-events: none;
                opacity: .04;
                background: repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 2px,
                    rgba(255, 255, 255, .5) 2px,
                    rgba(255, 255, 255, .5) 3px
                );
                z-index: 1;
            }
            .site-tips-ticker::after {
                content: '';
                position: absolute;
                inset-block: 0;
                inset-inline-start: 0;
                width: 35%;
                background: linear-gradient(
                    105deg,
                    transparent 25%,
                    rgba(255, 255, 255, .07) 50%,
                    transparent 75%
                );
                transform: translateX(-120%);
                animation: siteTipsSweep 8s ease-in-out infinite;
                pointer-events: none;
                z-index: 1;
            }
            [dir="rtl"] .site-tips-ticker::after {
                animation-name: siteTipsSweepRtl;
            }
            .site-tips-ticker__badge {
                position: relative;
                z-index: 2;
                display: flex;
                align-items: center;
                gap: .5rem;
                flex-shrink: 0;
                padding: .5rem .95rem;
                background: linear-gradient(135deg, rgba(39, 159, 249, .22), rgba(63, 181, 54, .18));
                font-size: .68rem;
                font-weight: 800;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #fff;
                border-inline-end: 1px solid rgba(255, 255, 255, .12);
                box-shadow: inset -1px 0 0 rgba(255, 255, 255, .06);
            }
            .site-tips-ticker__live {
                position: relative;
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: #ef4444;
                box-shadow: 0 0 10px rgba(239, 68, 68, .75);
                animation: siteTipsLive 1.6s ease-out infinite;
            }
            .site-tips-ticker__live::after {
                content: '';
                position: absolute;
                inset: -3px;
                border-radius: inherit;
                border: 1px solid rgba(239, 68, 68, .45);
                animation: siteTipsLiveRing 1.6s ease-out infinite;
            }
            .site-tips-ticker__viewport {
                position: relative;
                z-index: 2;
                flex: 1;
                overflow: hidden;
            }
            .site-tips-ticker__viewport::before,
            .site-tips-ticker__viewport::after {
                content: '';
                position: absolute;
                inset-block: 0;
                width: 3.5rem;
                z-index: 2;
                pointer-events: none;
            }
            .site-tips-ticker__viewport::before {
                inset-inline-start: 0;
                background: linear-gradient(to right, #0a1628, transparent);
            }
            .site-tips-ticker__viewport::after {
                inset-inline-end: 0;
                background: linear-gradient(to left, #0b3320, transparent);
            }
            [dir="rtl"] .site-tips-ticker__viewport::before {
                background: linear-gradient(to left, #0a1628, transparent);
            }
            [dir="rtl"] .site-tips-ticker__viewport::after {
                background: linear-gradient(to right, #0b3320, transparent);
            }
            .site-tips-ticker__track {
                display: flex;
                align-items: center;
                gap: 2.75rem;
                width: max-content;
                padding: .68rem 1.25rem;
                animation: site-tips-marquee 85s linear infinite;
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
                gap: .55rem;
                font-size: .8rem;
                font-weight: 600;
                white-space: nowrap;
                color: rgba(255, 255, 255, .88);
                text-shadow: 0 1px 8px rgba(0, 0, 0, .35);
                transition: color .2s ease, text-shadow .2s ease;
            }
            .site-tips-ticker:hover .site-tips-ticker__item:hover {
                color: #fff;
                text-shadow: 0 0 16px rgba(39, 159, 249, .45);
            }
            .site-tips-ticker__dot {
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--color-blue, #279ff9), var(--color-green, #3fb536));
                flex-shrink: 0;
                box-shadow: 0 0 8px rgba(39, 159, 249, .55);
            }
            .site-tips-ticker__sep {
                color: rgba(255, 255, 255, .22);
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
            @keyframes siteTipsLive {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: .55; transform: scale(.88); }
            }
            @keyframes siteTipsLiveRing {
                0% { transform: scale(.8); opacity: .8; }
                100% { transform: scale(1.8); opacity: 0; }
            }
            @keyframes siteTipsSweep {
                0%, 70%, 100% { transform: translateX(-120%); opacity: 0; }
                10% { opacity: 1; }
                40% { transform: translateX(320%); opacity: .85; }
                50% { opacity: 0; }
            }
            @keyframes siteTipsSweepRtl {
                0%, 70%, 100% { transform: translateX(120%); opacity: 0; }
                10% { opacity: 1; }
                40% { transform: translateX(-320%); opacity: .85; }
                50% { opacity: 0; }
            }
            @media (prefers-reduced-motion: reduce) {
                .site-tips-ticker::after,
                .site-tips-ticker__track,
                .site-tips-ticker__live,
                .site-tips-ticker__live::after {
                    animation: none !important;
                }
                .site-tips-ticker__track {
                    flex-wrap: wrap;
                    width: 100%;
                }
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
                        <span class="site-tips-ticker__sep" aria-hidden="true">|</span>
                    @endif
                    <span class="site-tips-ticker__item">
                        <span class="site-tips-ticker__dot" aria-hidden="true"></span>
                        {{ $tip }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
@endif
