@php
    use App\Services\DailyHealthTipsService;

    $dailyTips = app(DailyHealthTipsService::class)->tips();
@endphp

@if($dailyTips !== [])
    <div class="site-tips-ticker" aria-label="{{ __('account.daily_tips') }}">
        <div class="site-tips-ticker__badge">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
            </svg>
            <span>{{ __('account.daily_tips') }}</span>
        </div>
        <div class="site-tips-ticker__viewport">
            <div class="site-tips-ticker__track">
                @foreach(array_merge($dailyTips, $dailyTips) as $tip)
                    <span class="site-tips-ticker__item">
                        <span class="site-tips-ticker__dot" aria-hidden="true"></span>
                        {{ $tip }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
@endif
