@php
    $profile = (array) session('external_api_profile', []);
    $displayName = trim((string) ($profile['name'] ?? '')) ?: __('account.customer');
    $sub = $activeSubscription ?? [];
    $subStatus = strtolower((string) ($sub['status'] ?? ''));
    $planName = $sub['plan']['name'] ?? $sub['program']['name'] ?? '';
    if (is_array($planName)) {
        $planName = $planName[app()->getLocale()] ?? $planName['en'] ?? '';
    }
    $startAt = $sub['start_at'] ?? $sub['started_at'] ?? '';
    $endAt = $sub['end_at'] ?? $sub['ended_at'] ?? '';
@endphp

<div class="space-y-6">

    {{-- Greeting row --}}
    <section class="acc-card acc-card-head-less p-5 md:p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
             style="background:linear-gradient(120deg, #EEF4FF 0%, #F0FDF4 100%); border:1px solid #DBEAFE;">
        <div>
            <p class="text-sm text-gray-600">{{ __('account.welcome_back') }}</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-1">{{ $displayName }} 👋</h1>
            <p class="text-sm text-gray-600 mt-1">{{ __('account.dashboard_subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('account.subscriptions.index') }}" class="acc-btn acc-btn--primary">{{ __('account.view_subscriptions') }}</a>
            <a href="{{ route('meal-plans.index') }}" class="acc-btn acc-btn--ghost">{{ __('account.browse_plans') }}</a>
        </div>
    </section>

    {{-- Stat cards --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.active_subscription') }}</span>
            @if(!empty($sub))
                <span class="acc-stat__value text-base md:text-lg" style="font-size:1.1rem;">{{ $planName ?: __('account.current_plan') }}</span>
                <span class="acc-stat__meta">
                    @if($subStatus)
                        <span class="acc-chip {{ in_array($subStatus, ['active','running','started'], true) ? 'acc-chip--success' : 'acc-chip--muted' }}">{{ __('account.status_'.$subStatus, [], app()->getLocale()) !== 'account.status_'.$subStatus ? __('account.status_'.$subStatus) : ucfirst($subStatus) }}</span>
                    @endif
                    @if($startAt) · {{ $startAt }} @if($endAt) → {{ $endAt }} @endif @endif
                </span>
            @else
                <span class="acc-stat__value" style="font-size:1.05rem; color:#64748B;">{{ __('account.no_active_subscription') }}</span>
                <span class="acc-stat__meta">
                    <a href="{{ route('meal-plans.index') }}" class="text-blue-600 font-semibold text-xs">{{ __('account.start_plan') }}</a>
                </span>
            @endif
        </div>

        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.wallet_balance') }}</span>
            <span class="acc-stat__value">
                @if($walletBalance !== null)
                    {{ number_format($walletBalance, 2) }} <span class="text-sm font-normal text-gray-500">{{ __('SAR') }}</span>
                @else
                    <span class="text-gray-400 text-lg">—</span>
                @endif
            </span>
            <span class="acc-stat__meta">
                <a href="{{ route('account.wallet') }}" class="text-blue-600 font-semibold text-xs">{{ __('account.view_transactions') }}</a>
            </span>
        </div>

        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.recent_orders') }}</span>
            <span class="acc-stat__value">{{ count($recentOrders) }}</span>
            <span class="acc-stat__meta">
                <a href="{{ route('account.orders.index') }}" class="text-blue-600 font-semibold text-xs">{{ __('account.view_all') }}</a>
            </span>
        </div>
    </section>

    {{-- Two-col: subscription at-a-glance + recent orders --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Subscription summary --}}
        <div class="acc-card lg:col-span-2">
            <div class="acc-card-head">
                <span>{{ __('account.subscription_summary') }}</span>
                @if(!empty($sub) && ! empty($sub['id']))
                    <a href="{{ route('account.subscriptions.show', ['id' => $sub['id']]) }}" class="acc-btn acc-btn--ghost acc-btn--sm">{{ __('account.open') }}</a>
                @endif
            </div>
            <div class="acc-card-body">
                @if(empty($sub))
                    <div class="acc-empty">
                        <div class="acc-empty__icon">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                        </div>
                        <p>{{ __('account.no_active_subscription') }}</p>
                        <a href="{{ route('meal-plans.index') }}" class="acc-btn acc-btn--primary mt-3 inline-flex">{{ __('account.browse_plans') }}</a>
                    </div>
                @else
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">{{ __('account.plan') }}</dt>
                            <dd class="font-semibold mt-1">{{ $planName ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('account.status') }}</dt>
                            <dd class="mt-1">
                                <span class="acc-chip {{ in_array($subStatus, ['active','running','started'], true) ? 'acc-chip--success' : 'acc-chip--muted' }}">{{ $subStatus ?: '—' }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('account.start_date') }}</dt>
                            <dd class="font-semibold mt-1">{{ $startAt ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('account.end_date') }}</dt>
                            <dd class="font-semibold mt-1">{{ $endAt ?: '—' }}</dd>
                        </div>
                    </dl>
                @endif
            </div>
        </div>

        {{-- Recent orders --}}
        <div class="acc-card">
            <div class="acc-card-head">
                <span>{{ __('account.recent_orders') }}</span>
                <a href="{{ route('account.orders.index') }}" class="acc-btn acc-btn--ghost acc-btn--sm">{{ __('account.view_all') }}</a>
            </div>
            <div class="acc-card-body p-0">
                @if(empty($recentOrders))
                    <div class="acc-empty">{{ __('account.no_orders_yet') }}</div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($recentOrders as $order)
                            @php
                                $oid = $order['id'] ?? $order['order_id'] ?? null;
                                $oStatus = strtolower((string) ($order['status'] ?? ''));
                                $oDate = $order['delivery_date'] ?? $order['created_at'] ?? $order['date'] ?? '';
                                $oTotal = $order['total'] ?? $order['amount'] ?? $order['grand_total'] ?? null;
                            @endphp
                            <li class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                                <div class="min-w-0">
                                    <a href="{{ $oid ? route('account.orders.show', ['id' => $oid]) : '#' }}" class="font-semibold text-gray-900 truncate block">#{{ $oid ?: '—' }}</a>
                                    <span class="text-xs text-gray-500">{{ $oDate }}</span>
                                </div>
                                <div class="text-right">
                                    @if($oTotal !== null)
                                        <div class="font-semibold text-gray-900">{{ number_format((float)$oTotal, 2) }} <span class="text-xs text-gray-500">{{ __('SAR') }}</span></div>
                                    @endif
                                    @if($oStatus)
                                        <span class="acc-chip acc-chip--muted mt-1">{{ $oStatus }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
</div>
