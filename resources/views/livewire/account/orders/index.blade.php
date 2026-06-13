<div class="space-y-6">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('account.my_orders') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('account.orders_hint') }}</p>
        </div>
        <div class="acc-tab-group">
            <button type="button" class="acc-tab {{ $status === 'active' ? 'is-active' : '' }}" wire:click="$set('status', 'active')">{{ __('account.status_active') }}</button>
            <button type="button" class="acc-tab {{ $status === 'completed' ? 'is-active' : '' }}" wire:click="$set('status', 'completed')">{{ __('account.status_completed') }}</button>
            <button type="button" class="acc-tab {{ $status === 'cancelled' ? 'is-active' : '' }}" wire:click="$set('status', 'cancelled')">{{ __('account.status_cancelled') }}</button>
        </div>
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
            <div class="acc-card-body space-y-3">
                @for($i = 0; $i < 4; $i++)
                    <div class="acc-skeleton acc-skeleton-line" style="width: {{ 65 + ($i * 8) }}%; height: 4.5rem;"></div>
                @endfor
            </div>
        @elseif(empty($orders))
            <div class="acc-empty">
                <div class="acc-empty__icon">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                </div>
                <p>{{ __('account.no_orders_in_status') }}</p>
                <a href="{{ route('meals.index') }}" class="acc-btn acc-btn--primary mt-3 inline-flex">{{ __('account.browse_meals') }}</a>
            </div>
        @else
            {{-- Mobile cards --}}
            <div class="acc-mobile-only acc-record-list">
                @foreach($orders as $order)
                    @php
                        $oid = $order['id'] ?? $order['order_id'] ?? null;
                        $orderNumber = trim((string) ($order['order_number'] ?? $order['number'] ?? $order['external_order_number'] ?? ''));
                        $displayRef = $orderNumber !== '' ? $orderNumber : ($oid ? '#'.$oid : '—');
                        $isLocalWebOrder = (string) ($order['source'] ?? '') === 'web_payment';
                        $oDate = $order['delivery_date'] ?? $order['date'] ?? $order['created_at'] ?? '';
                        $items = $order['items'] ?? $order['meals'] ?? [];
                        $count = is_array($items) ? count($items) : (int) ($order['quantity'] ?? $order['items_count'] ?? 0);
                        $oStatus = strtolower((string) ($order['status'] ?? ''));
                        $oStatusKey = 'account.status_'.$oStatus;
                        $oStatusLabel = __($oStatusKey);
                        if ($oStatusLabel === $oStatusKey) {
                            $oStatusLabel = $oStatus !== '' ? ucfirst($oStatus) : '—';
                        }
                        $chipClass = match ($oStatus) {
                            'paid', 'completed', 'active', 'delivered', 'authorized' => 'acc-chip--success',
                            'pending' => 'acc-chip--warn',
                            'cancelled', 'failed', 'expired' => 'acc-chip--danger',
                            default => 'acc-chip--muted',
                        };
                        $oTotal = $order['total'] ?? $order['amount'] ?? $order['grand_total'] ?? null;
                        $detailsUrl = $oid
                            ? route('account.orders.show', ['id' => $oid])
                            : ($isLocalWebOrder && $orderNumber !== '' ? route('payment.result', ['order' => $orderNumber]) : '#');
                    @endphp
                    <article class="acc-record">
                        <div class="acc-record__head">
                            <span class="acc-record__id">{{ $displayRef }}</span>
                            @if($oStatus)
                                <span class="acc-chip {{ $chipClass }}">{{ $oStatusLabel }}</span>
                            @endif
                        </div>
                        <div class="acc-record__meta">
                            <div class="acc-record__field">
                                <label>{{ __('account.delivery_date') }}</label>
                                <span>{{ $oDate ?: '—' }}</span>
                            </div>
                            <div class="acc-record__field">
                                <label>{{ __('account.items') }}</label>
                                <span>{{ $count > 0 ? $count : '—' }}</span>
                            </div>
                            <div class="acc-record__field">
                                <label>{{ __('account.total') }}</label>
                                <span>
                                    @if($oTotal !== null)
                                        <x-sar :amount="(float) $oTotal" class="text-xs text-gray-900" />
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        </div>
                        @if($detailsUrl !== '#')
                            <div class="acc-record__actions">
                                <a href="{{ $detailsUrl }}" class="acc-btn acc-btn--ghost acc-btn--sm">{{ __('account.details') }}</a>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="acc-desktop-only overflow-x-auto">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.order_number') }}</th>
                            <th>{{ __('account.delivery_date') }}</th>
                            <th>{{ __('account.items') }}</th>
                            <th>{{ __('account.status') }}</th>
                            <th class="text-end">{{ __('account.total') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            @php
                                $oid = $order['id'] ?? $order['order_id'] ?? null;
                                $orderNumber = trim((string) ($order['order_number'] ?? $order['number'] ?? $order['external_order_number'] ?? ''));
                                $displayRef = $orderNumber !== '' ? $orderNumber : ($oid ? '#'.$oid : '—');
                                $isLocalWebOrder = (string) ($order['source'] ?? '') === 'web_payment';
                                $oDate = $order['delivery_date'] ?? $order['date'] ?? $order['created_at'] ?? '';
                                $items = $order['items'] ?? $order['meals'] ?? [];
                                $count = is_array($items) ? count($items) : (int) ($order['quantity'] ?? $order['items_count'] ?? 0);
                                $oStatus = strtolower((string) ($order['status'] ?? ''));
                                $oStatusKey = 'account.status_'.$oStatus;
                                $oStatusLabel = __($oStatusKey);
                                if ($oStatusLabel === $oStatusKey) {
                                    $oStatusLabel = $oStatus !== '' ? ucfirst($oStatus) : '—';
                                }
                                $chipClass = match ($oStatus) {
                                    'paid', 'completed', 'active', 'delivered', 'authorized' => 'acc-chip--success',
                                    'pending' => 'acc-chip--warn',
                                    'cancelled', 'failed', 'expired' => 'acc-chip--danger',
                                    default => 'acc-chip--muted',
                                };
                                $oTotal = $order['total'] ?? $order['amount'] ?? $order['grand_total'] ?? null;
                                $detailsUrl = $oid
                                    ? route('account.orders.show', ['id' => $oid])
                                    : ($isLocalWebOrder && $orderNumber !== '' ? route('payment.result', ['order' => $orderNumber]) : '#');
                            @endphp
                            <tr>
                                <td class="font-semibold text-gray-900">{{ $displayRef }}</td>
                                <td>{{ $oDate ?: '—' }}</td>
                                <td>{{ $count > 0 ? $count : '—' }}</td>
                                <td>
                                    @if($oStatus)
                                        <span class="acc-chip {{ $chipClass }}">{{ $oStatusLabel }}</span>
                                    @else — @endif
                                </td>
                                <td class="text-end font-semibold">
                                    @if($oTotal !== null) <x-sar :amount="(float) $oTotal" class="text-xs text-gray-900" /> @else — @endif
                                </td>
                                <td class="text-end">
                                    @if($detailsUrl !== '#')
                                        <a href="{{ $detailsUrl }}" class="acc-btn acc-btn--ghost acc-btn--sm">{{ __('account.details') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
