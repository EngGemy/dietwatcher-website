@php
    use App\Support\SubscriptionLifecycle;

    $profile = (array) session('external_api_profile', []);
    $displayName = trim((string) ($profile['name'] ?? '')) ?: __('account.customer');
    $sub = $activeSubscription ?? [];
    $status = (string) ($sub['status'] ?? '');
    $statusNorm = SubscriptionLifecycle::normalize($status);
    $statusKey = 'account.status_'.$statusNorm;
    $subStatusLabel = __($statusKey);
    if ($subStatusLabel === $statusKey) {
        $subStatusLabel = $status !== '' ? ucfirst($status) : '—';
    }
    $canManage = ! empty($sub) && ! SubscriptionLifecycle::isTerminal($status);
    $chipClass = SubscriptionLifecycle::isActive($status) ? 'acc-chip--success'
        : (SubscriptionLifecycle::isPaused($status) ? 'acc-chip--warn' : 'acc-chip--muted');
    $planName = $sub['plan']['name'] ?? $sub['program']['name'] ?? '';
    if (is_array($planName)) {
        $planName = $planName[app()->getLocale()] ?? $planName['en'] ?? '';
    }
    $startAt = $sub['start_at'] ?? $sub['started_at'] ?? '';
    $endAt = $sub['end_at'] ?? $sub['ended_at'] ?? '';
@endphp

<div class="space-y-6">
    @if(!empty($error))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-center justify-between gap-3 flex-wrap">
            <span>{{ $error }}</span>
            @if($error === __('account.login_required'))
                <a href="{{ route('account.login') }}" class="acc-btn acc-btn--primary acc-btn--sm">{{ __('account.go_to_login') }}</a>
            @endif
        </div>
    @endif

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
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.active_subscription') }}</span>
            @if(!empty($sub))
                <span class="acc-stat__value text-base md:text-lg" style="font-size:1.1rem;">{{ $planName ?: __('account.current_plan') }}</span>
                <span class="acc-stat__meta">
                    @if($status)
                        <span class="acc-chip {{ $chipClass }}">{{ $subStatusLabel }}</span>
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
                    <x-sar :amount="$walletBalance" class="text-sm text-gray-900" />
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

        <div class="acc-stat">
            <span class="acc-stat__label">{{ __('account.notifications') }}</span>
            <span class="acc-stat__value">{{ $unreadNotifications }}</span>
            <span class="acc-stat__meta">
                @if($unreadNotifications > 0)
                    <span class="acc-chip acc-chip--warn">{{ __('account.unread') }}</span>
                @endif
                <a href="{{ route('account.notifications.index') }}" class="text-blue-600 font-semibold text-xs">{{ __('account.view_all') }}</a>
            </span>
        </div>
    </section>

    {{-- Quick links --}}
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <a href="{{ route('account.invoices.index') }}" class="acc-card p-4 flex items-center gap-3 hover:border-blue-200 transition group">
            <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            </span>
            <div>
                <p class="font-semibold text-gray-900 group-hover:text-blue-700">{{ __('account.invoices') }}</p>
                <p class="text-xs text-gray-500">{{ __('account.invoices_hint_short') }}</p>
            </div>
        </a>
        <a href="{{ route('account.addresses.index') }}" class="acc-card p-4 flex items-center gap-3 hover:border-blue-200 transition group">
            <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
            </span>
            <div>
                <p class="font-semibold text-gray-900 group-hover:text-blue-700">{{ __('account.my_addresses') }}</p>
                <p class="text-xs text-gray-500">{{ __('account.addresses_hint_short') }}</p>
            </div>
        </a>
        <a href="{{ route('account.notifications.index') }}" class="acc-card p-4 flex items-center gap-3 hover:border-blue-200 transition group">
            <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 relative">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                @if($unreadNotifications > 0)
                    <span class="absolute -top-1 -end-1 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                @endif
            </span>
            <div>
                <p class="font-semibold text-gray-900 group-hover:text-blue-700">{{ __('account.notifications') }}</p>
                <p class="text-xs text-gray-500">{{ __('account.notifications_hint_short') }}</p>
            </div>
        </a>
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
                                <span class="acc-chip {{ $chipClass }}">{{ $status ? $subStatusLabel : '—' }}</span>
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
                    @if($canManage && ! empty($sub['id']))
                        <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-2">
                            <a href="{{ route('account.subscriptions.show', ['id' => $sub['id']]) }}" class="acc-btn acc-btn--primary acc-btn--sm">
                                {{ __('account.manage') }}
                            </a>
                            @if(SubscriptionLifecycle::canPause($status))
                                <a href="{{ route('account.subscriptions.show', ['id' => $sub['id']]) }}#manage" class="acc-btn acc-btn--muted acc-btn--sm">
                                    {{ __('account.pause') }}
                                </a>
                            @elseif(SubscriptionLifecycle::canResume($status))
                                <a href="{{ route('account.subscriptions.show', ['id' => $sub['id']]) }}#manage" class="acc-btn acc-btn--primary acc-btn--sm">
                                    {{ __('account.resume') }}
                                </a>
                            @endif
                        </div>
                    @endif
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
                                $orderNumber = (string) ($order['order_number'] ?? '');
                                $isLocalWebOrder = (string) ($order['source'] ?? '') === 'web_payment';
                                $oStatus = strtolower((string) ($order['status'] ?? ''));
                                $oStatusKey = 'account.status_'.$oStatus;
                                $oStatusLabel = __($oStatusKey);
                                if ($oStatusLabel === $oStatusKey) $oStatusLabel = ucfirst($oStatus);
                                $oDate = $order['delivery_date'] ?? $order['created_at'] ?? $order['date'] ?? '';
                                $oTotal = $order['total'] ?? $order['amount'] ?? $order['grand_total'] ?? null;
                                $detailsUrl = $oid
                                    ? route('account.orders.show', ['id' => $oid])
                                    : ($isLocalWebOrder && $orderNumber !== '' ? route('payment.result', ['order' => $orderNumber]) : '#');
                                $displayOrderRef = $oid ?: ($orderNumber !== '' ? $orderNumber : '—');
                            @endphp
                            <li class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                                <div class="min-w-0">
                                    <a href="{{ $detailsUrl }}" class="font-semibold text-gray-900 truncate block">#{{ $displayOrderRef }}</a>
                                    <span class="text-xs text-gray-500">{{ $oDate }}</span>
                                </div>
                                <div class="text-right">
                                    @if($oTotal !== null)
                                        <div class="font-semibold text-gray-900"><x-sar :amount="(float)$oTotal" class="text-xs text-gray-900" /></div>
                                    @endif
                                    @if($oStatus)
                                        <span class="acc-chip acc-chip--muted mt-1">{{ $oStatusLabel }}</span>
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
