@php
    $o = $order;
    $oStatus = strtolower((string) ($o['status'] ?? ''));
    $oDate = $o['delivery_date'] ?? $o['date'] ?? $o['created_at'] ?? '';
    $addr = $o['address']['description'] ?? $o['address']['line1'] ?? $o['address_line'] ?? '';
    $branch = $o['branch']['name'] ?? '';
    if (is_array($branch)) $branch = $branch[app()->getLocale()] ?? $branch['en'] ?? '';
    $items = $o['items'] ?? $o['meals'] ?? [];
    if (! is_array($items)) $items = [];
    $oTotal = $o['total'] ?? $o['amount'] ?? $o['grand_total'] ?? null;
    $oSubtotal = $o['subtotal'] ?? null;
    $oDelivery = $o['delivery_fee'] ?? null;
    $oVat = $o['vat'] ?? $o['tax'] ?? null;
    $oDiscount = $o['discount'] ?? null;
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <a href="{{ route('account.orders.index') }}" class="text-sm text-blue-600 font-semibold inline-flex items-center gap-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                {{ __('account.back_to_orders') }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ __('account.order') }} #{{ $orderId }}</h1>
        </div>
        <a href="{{ $invoiceUrl }}" target="_blank" rel="noopener" class="acc-btn acc-btn--primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            {{ __('account.download_invoice') }}
        </a>
    </div>

    @if($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</div>
    @endif

    @if($loading)
        <div class="acc-card"><div class="acc-empty">{{ __('account.loading') }}</div></div>
    @elseif(empty($order))
        <div class="acc-card"><div class="acc-empty">{{ __('account.order_not_found') }}</div></div>
    @else
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="acc-card lg:col-span-2">
                <div class="acc-card-head">{{ __('account.order_items') }}</div>
                <div class="acc-card-body p-0">
                    @if(empty($items))
                        <div class="acc-empty">{{ __('account.no_items') }}</div>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach($items as $item)
                                @php
                                    $img = $item['image'] ?? $item['meal']['image'] ?? '';
                                    $name = $item['name'] ?? $item['meal']['name'] ?? '';
                                    if (is_array($name)) $name = $name[app()->getLocale()] ?? $name['en'] ?? '';
                                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                                    $price = $item['price'] ?? $item['amount'] ?? null;
                                @endphp
                                <li class="flex items-center gap-3 px-5 py-3">
                                    @if($img)
                                        <img src="{{ $img }}" class="w-14 h-14 rounded-lg object-cover flex-shrink-0" alt="" onerror="this.style.display='none'">
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-gray-900 truncate">{{ $name ?: '—' }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ __('Qty') }}: {{ $qty }}</div>
                                    </div>
                                    @if($price !== null)
                                        <div class="font-semibold text-gray-900">{{ number_format((float)$price * $qty, 2) }} <span class="text-xs text-gray-500">{{ __('SAR') }}</span></div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                <div class="acc-card">
                    <div class="acc-card-head">{{ __('account.order_summary') }}</div>
                    <div class="acc-card-body text-sm space-y-2">
                        <div class="flex justify-between"><span class="text-gray-500">{{ __('account.status') }}</span><span><span class="acc-chip acc-chip--muted">{{ $oStatus ?: '—' }}</span></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">{{ __('account.delivery_date') }}</span><span>{{ $oDate ?: '—' }}</span></div>
                        @if($branch)
                            <div class="flex justify-between"><span class="text-gray-500">{{ __('account.branch') }}</span><span>{{ $branch }}</span></div>
                        @endif
                        @if($addr)
                            <div class="flex justify-between gap-3"><span class="text-gray-500 flex-shrink-0">{{ __('account.address') }}</span><span class="text-end truncate">{{ $addr }}</span></div>
                        @endif
                        <div class="border-t border-gray-100 pt-2 mt-2 space-y-1.5">
                            @if($oSubtotal !== null)
                                <div class="flex justify-between"><span class="text-gray-500">{{ __('account.subtotal') }}</span><span>{{ number_format((float)$oSubtotal, 2) }} {{ __('SAR') }}</span></div>
                            @endif
                            @if($oDelivery !== null)
                                <div class="flex justify-between"><span class="text-gray-500">{{ __('account.delivery_fee') }}</span><span>{{ number_format((float)$oDelivery, 2) }} {{ __('SAR') }}</span></div>
                            @endif
                            @if($oDiscount !== null && (float)$oDiscount > 0)
                                <div class="flex justify-between text-emerald-700"><span>{{ __('account.discount') }}</span><span>-{{ number_format((float)$oDiscount, 2) }} {{ __('SAR') }}</span></div>
                            @endif
                            @if($oVat !== null)
                                <div class="flex justify-between text-gray-400 text-xs"><span>{{ __('account.vat') }}</span><span>{{ number_format((float)$oVat, 2) }} {{ __('SAR') }}</span></div>
                            @endif
                            @if($oTotal !== null)
                                <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-100"><span>{{ __('account.total') }}</span><span>{{ number_format((float)$oTotal, 2) }} {{ __('SAR') }}</span></div>
                            @endif
                        </div>
                    </div>
                </div>

                @if(! empty($trackings))
                    <div class="acc-card">
                        <div class="acc-card-head">{{ __('account.tracking') }}</div>
                        <div class="acc-card-body p-0">
                            <ul class="divide-y divide-gray-100">
                                @foreach($trackings as $t)
                                    @php
                                        $tStatus = $t['status'] ?? $t['name'] ?? '';
                                        if (is_array($tStatus)) $tStatus = $tStatus[app()->getLocale()] ?? $tStatus['en'] ?? '';
                                        $tAt = $t['at'] ?? $t['created_at'] ?? $t['time'] ?? '';
                                    @endphp
                                    <li class="px-4 py-2 text-sm flex items-center justify-between gap-2">
                                        <span class="text-gray-800 font-medium">{{ $tStatus ?: '—' }}</span>
                                        <span class="text-xs text-gray-400">{{ $tAt }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif
</div>
