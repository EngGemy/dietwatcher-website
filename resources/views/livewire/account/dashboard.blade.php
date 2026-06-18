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
    $hasActiveSub = ! empty($sub);
    $showAddressWarning = $hasActiveSub && $lockedAddressesCount === 0 && $savedAddressesCount === 0;
@endphp

<div class="space-y-6">
    @if(!empty($error))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-center justify-between gap-3 flex-wrap">
            <span>{{ $error }}</span>
            @if($error === __('account.login_required'))
                <a href="{{ route('account.login') }}" class="acc-btn acc-btn--primary acc-btn--sm">{{ __('account.go_to_login') }}</a>
            @endif
        </div>
    @endif

    @if($showAddressWarning)
        <div class="acc-dash-alert">
            <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <div>
                <p class="font-bold">{{ __('account.address_missing_warning_title') }}</p>
                <p class="mt-1">{{ __('account.address_missing_warning_body') }}</p>
                <a href="{{ route('account.addresses.index') }}" class="inline-flex mt-2 text-amber-900 font-bold underline">{{ __('account.manage_addresses') }}</a>
            </div>
        </div>
    @endif

    <section class="acc-dash-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">
            <div>
                <p class="acc-dash-hero__eyebrow">{{ __('account.welcome_back') }}</p>
                <h1 class="acc-dash-hero__title">{{ $displayName }} 👋</h1>
                <p class="acc-dash-hero__sub">{{ __('account.dashboard_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('account.subscriptions.index') }}" class="acc-btn acc-btn--primary">{{ __('account.view_subscriptions') }}</a>
                <a href="{{ route('meal-plans.index') }}" class="acc-btn acc-btn--ghost" style="background:rgba(255,255,255,.12); border-color:rgba(255,255,255,.25); color:#fff;">{{ __('account.browse_plans') }}</a>
            </div>
        </div>
    </section>

    <section class="acc-dash-stat-grid">
        <div class="acc-dash-stat">
            <div class="acc-dash-stat__icon acc-dash-stat__icon--green">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/></svg>
            </div>
            <p class="acc-dash-stat__label">{{ __('account.active_subscription') }}</p>
            @if($hasActiveSub)
                <p class="acc-dash-stat__value text-base">{{ $planName ?: __('account.current_plan') }}</p>
                <p class="acc-dash-stat__meta">
                    <span class="acc-chip {{ $chipClass }}">{{ $subStatusLabel }}</span>
                </p>
            @else
                <p class="acc-dash-stat__value text-base" style="color:#64748B;">{{ __('account.no_active_subscription') }}</p>
                <p class="acc-dash-stat__meta"><a href="{{ route('meal-plans.index') }}">{{ __('account.start_plan') }}</a></p>
            @endif
        </div>

        <div class="acc-dash-stat">
            <div class="acc-dash-stat__icon acc-dash-stat__icon--blue">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/></svg>
            </div>
            <p class="acc-dash-stat__label">{{ __('account.wallet_balance') }}</p>
            <p class="acc-dash-stat__value">
                @if($walletBalance !== null)
                    <x-sar :amount="$walletBalance" class="text-sm text-gray-900" />
                @else
                    <span style="color:#94A3B8;">—</span>
                @endif
            </p>
            <p class="acc-dash-stat__meta"><a href="{{ route('account.wallet') }}">{{ __('account.view_transactions') }}</a></p>
        </div>

        <div class="acc-dash-stat">
            <div class="acc-dash-stat__icon acc-dash-stat__icon--amber">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
            </div>
            <p class="acc-dash-stat__label">{{ __('account.recent_orders') }}</p>
            <p class="acc-dash-stat__value">{{ count($recentOrders) }}</p>
            <p class="acc-dash-stat__meta"><a href="{{ route('account.orders.index') }}">{{ __('account.view_all') }}</a></p>
        </div>

        <div class="acc-dash-stat">
            <div class="acc-dash-stat__icon acc-dash-stat__icon--violet">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
            </div>
            <p class="acc-dash-stat__label">{{ __('account.my_addresses') }}</p>
            <p class="acc-dash-stat__value">{{ $savedAddressesCount }}</p>
            <p class="acc-dash-stat__meta">
                @if($lockedAddressesCount > 0)
                    <span class="acc-chip acc-chip--warn">{{ __('account.locked_addresses_count', ['count' => $lockedAddressesCount]) }}</span>
                @endif
                <a href="{{ route('account.addresses.index') }}">{{ __('account.manage_addresses') }}</a>
            </p>
        </div>
    </section>

    @if(!empty($dailyTips))
        <section class="acc-dash-panel">
            <div class="acc-dash-panel__head">
                <span>{{ __('account.daily_tips_section') }}</span>
                @if($tipsGeminiPowered)
                    <span class="acc-chip acc-chip--muted">{{ __('ai.powered_by_gemini') }}</span>
                @endif
            </div>
            <div class="acc-dash-panel__body">
                <p class="text-sm text-gray-500 mb-3">{{ __('account.daily_tips_section_hint') }}</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach(array_slice($dailyTips, 0, 6) as $tip)
                        <div class="flex items-start gap-2 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2.5 text-sm text-slate-700">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-gradient-to-br from-sky-500 to-emerald-500"></span>
                            <span>{{ $tip }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="acc-dash-quick">
        <a href="{{ route('account.invoices.index') }}" class="acc-dash-quick__item">
            <span class="acc-dash-quick__icon acc-dash-stat__icon--blue">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            </span>
            <span class="font-bold text-gray-900 text-sm">{{ __('account.invoices') }}</span>
        </a>
        <a href="{{ route('account.addresses.index') }}" class="acc-dash-quick__item">
            <span class="acc-dash-quick__icon acc-dash-stat__icon--green">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
            </span>
            <span class="font-bold text-gray-900 text-sm">{{ __('account.my_addresses') }}</span>
        </a>
        <a href="{{ route('account.notifications.index') }}" class="acc-dash-quick__item">
            <span class="acc-dash-quick__icon acc-dash-stat__icon--amber">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
            </span>
            <span class="font-bold text-gray-900 text-sm">{{ __('account.notifications') }}</span>
        </a>
        <a href="{{ route('account.profile') }}" class="acc-dash-quick__item">
            <span class="acc-dash-quick__icon acc-dash-stat__icon--violet">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            </span>
            <span class="font-bold text-gray-900 text-sm">{{ __('account.profile') }}</span>
        </a>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="acc-dash-panel lg:col-span-2">
            <div class="acc-dash-panel__head">
                <span>{{ __('account.subscription_summary') }}</span>
                @if($hasActiveSub && ! empty($sub['id']))
                    <a href="{{ route('account.subscriptions.show', ['id' => $sub['id']]) }}" class="acc-btn acc-btn--ghost acc-btn--sm">{{ __('account.open') }}</a>
                @endif
            </div>
            <div class="acc-dash-panel__body">
                @if(! $hasActiveSub)
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
                            <dd class="mt-1"><span class="acc-chip {{ $chipClass }}">{{ $subStatusLabel }}</span></dd>
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
                            <a href="{{ route('account.subscriptions.show', ['id' => $sub['id']]) }}" class="acc-btn acc-btn--primary acc-btn--sm">{{ __('account.manage') }}</a>
                            @if(SubscriptionLifecycle::canPause($status))
                                <a href="{{ route('account.subscriptions.show', ['id' => $sub['id']]) }}#manage" class="acc-btn acc-btn--muted acc-btn--sm">{{ __('account.pause') }}</a>
                            @elseif(SubscriptionLifecycle::canResume($status))
                                <a href="{{ route('account.subscriptions.show', ['id' => $sub['id']]) }}#manage" class="acc-btn acc-btn--primary acc-btn--sm">{{ __('account.resume') }}</a>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="acc-dash-panel">
            <div class="acc-dash-panel__head">
                <span>{{ __('account.recent_orders') }}</span>
                <a href="{{ route('account.orders.index') }}" class="acc-btn acc-btn--ghost acc-btn--sm">{{ __('account.view_all') }}</a>
            </div>
            <div class="acc-dash-panel__body p-0">
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
                                <div class="text-end">
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
