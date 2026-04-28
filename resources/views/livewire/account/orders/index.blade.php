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
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</div>
    @endif

    <div class="acc-card">
        @if($loading)
            <div class="acc-empty">{{ __('account.loading') }}</div>
        @elseif(empty($orders))
            <div class="acc-empty">
                <div class="acc-empty__icon">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                </div>
                <p>{{ __('account.no_orders_in_status') }}</p>
                <a href="{{ route('meals.index') }}" class="acc-btn acc-btn--primary mt-3 inline-flex">{{ __('account.browse_meals') }}</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>#</th>
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
                                $oid   = $order['id'] ?? $order['order_id'] ?? null;
                                $oDate = $order['delivery_date'] ?? $order['date'] ?? $order['created_at'] ?? '';
                                $items = $order['items'] ?? $order['meals'] ?? [];
                                $count = is_array($items) ? count($items) : 0;
                                $oStatus = strtolower((string) ($order['status'] ?? ''));
                                $oTotal = $order['total'] ?? $order['amount'] ?? $order['grand_total'] ?? null;
                            @endphp
                            <tr>
                                <td class="font-semibold">#{{ $oid ?: '—' }}</td>
                                <td>{{ $oDate ?: '—' }}</td>
                                <td>{{ $count }}</td>
                                <td>
                                    @if($oStatus)
                                        <span class="acc-chip acc-chip--muted">{{ $oStatus }}</span>
                                    @else — @endif
                                </td>
                                <td class="text-end font-semibold">
                                    @if($oTotal !== null) {{ number_format((float) $oTotal, 2) }} <span class="text-xs text-gray-500">{{ __('SAR') }}</span> @else — @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ $oid ? route('account.orders.show', ['id' => $oid]) : '#' }}" class="acc-btn acc-btn--ghost acc-btn--sm">
                                        {{ __('account.details') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
