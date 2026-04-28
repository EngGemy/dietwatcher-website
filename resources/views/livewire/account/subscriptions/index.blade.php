<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('account.my_subscriptions') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('account.subscriptions_hint') }}</p>
        </div>
        <button type="button" wire:click="reload" class="acc-btn acc-btn--muted acc-btn--sm" wire:loading.attr="disabled">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M2.985 19.644l4.992-4.992m0 0a8.25 8.25 0 0113.803-3.7L21 12M8.017 14.652h-4.992v4.992M3 12a9 9 0 0115.383-6.364L20 7.356"/></svg>
            {{ __('account.refresh') }}
        </button>
    </div>

    @if($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-center justify-between gap-3 flex-wrap">
            <span>{{ $error }}</span>
            @if($error === __('account.login_required'))
                <a href="{{ route('account.login') }}" class="acc-btn acc-btn--primary acc-btn--sm">{{ __('account.go_to_login') }}</a>
            @endif
        </div>
    @endif

    <div class="acc-card">
        @if($loading)
            <div class="acc-empty">{{ __('account.loading') }}</div>
        @elseif(empty($subscriptions))
            <div class="acc-empty">
                <div class="acc-empty__icon">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25"/></svg>
                </div>
                <p>{{ __('account.no_subscriptions') }}</p>
                <a href="{{ route('meal-plans.index') }}" class="acc-btn acc-btn--primary mt-4 inline-flex">{{ __('account.browse_plans') }}</a>
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($subscriptions as $s)
                    @php
                        $id = $s['id'] ?? null;
                        $status = strtolower((string) ($s['status'] ?? ''));
                        $plan = $s['plan']['name'] ?? $s['program']['name'] ?? $s['name'] ?? '';
                        if (is_array($plan)) $plan = $plan[app()->getLocale()] ?? $plan['en'] ?? '';
                        $startAt = $s['start_at'] ?? $s['started_at'] ?? '';
                        $endAt   = $s['end_at']   ?? $s['ended_at']   ?? '';
                        $total   = $s['total']    ?? $s['amount']     ?? null;
                        $remain  = $s['remaining_days'] ?? $s['days_remaining'] ?? null;
                        $isActive = in_array($status, ['active','running','started'], true);
                        $statusKey = 'account.status_'.$status;
                        $statusLabel = __($statusKey);
                        if ($statusLabel === $statusKey) $statusLabel = ucfirst($status);
                    @endphp
                    <li class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 p-4 md:p-5 hover:bg-gray-50 transition">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900">{{ $plan ?: (__('account.subscription') . ' #' . ($id ?? '—')) }}</span>
                                @if($status)
                                    <span class="acc-chip {{ $isActive ? 'acc-chip--success' : 'acc-chip--muted' }}">{{ $statusLabel }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1 flex items-center gap-3 flex-wrap">
                                @if($id) <span>#{{ $id }}</span> @endif
                                @if($startAt) <span>{{ __('account.starts') }}: {{ $startAt }}</span> @endif
                                @if($endAt)   <span>{{ __('account.ends') }}: {{ $endAt }}</span> @endif
                                @if($remain !== null) <span>{{ $remain }} {{ __('days') }}</span> @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($total !== null)
                                <span class="font-semibold text-gray-900">{{ number_format((float)$total, 2) }} {{ __('SAR') }}</span>
                            @endif
                            <a href="{{ $id ? route('account.subscriptions.show', ['id' => $id]) : '#' }}"
                               class="acc-btn acc-btn--ghost acc-btn--sm">
                                {{ __('account.manage') }}
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
