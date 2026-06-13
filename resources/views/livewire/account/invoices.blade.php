@php
    use App\Services\AccountApiService;
    /** @var AccountApiService $api */
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('account.invoices') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('account.invoices_hint') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if(! $loading && ! empty($invoices))
                <a href="{{ route('account.invoices.export', array_filter(['subscription' => $subscriptionFilter])) }}"
                   class="acc-btn acc-btn--primary acc-btn--sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 11.25L12 15.75m0 0l4.5-4.5M12 15.75V3"/></svg>
                    {{ __('account.export_invoices') }}
                </a>
            @endif
            <button type="button" wire:click="load" class="acc-btn acc-btn--muted acc-btn--sm" wire:loading.attr="disabled">
                {{ __('account.refresh') }}
            </button>
        </div>
    </div>

    @if($subscriptionFilter)
        <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-2 text-sm text-blue-800 flex items-center justify-between gap-3">
            <span>{{ __('account.filtered_by_subscription') }} #{{ $subscriptionFilter }}</span>
            <a href="{{ route('account.invoices.index') }}" class="font-semibold underline">{{ __('account.clear_filter') }}</a>
        </div>
    @endif

    @if($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $error }}</div>
    @endif

    <div class="acc-card">
        @if($loading)
            <div class="acc-card-body space-y-3">
                @for($i = 0; $i < 5; $i++)
                    <div class="acc-skeleton acc-skeleton-line" style="width: {{ 70 + ($i * 5) }}%;"></div>
                @endfor
            </div>
        @elseif(empty($invoices))
            <div class="acc-empty">
                <div class="acc-empty__icon">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </div>
                <p>{{ __('account.no_invoices') }}</p>
            </div>
        @else
            <div class="acc-mobile-only acc-record-list">
                @foreach($invoices as $inv)
                    @php
                        $invId = $inv['id'] ?? $inv['invoice_id'] ?? null;
                        $invNumber = trim((string) ($inv['number'] ?? $inv['invoice_number'] ?? ''));
                        $displayRef = $invNumber !== '' ? $invNumber : ($invId ? '#'.$invId : '—');
                        $invDate = $inv['created_at'] ?? $inv['date'] ?? $inv['issued_at'] ?? '';
                        $subId = $inv['subscription_id'] ?? $inv['subscription']['id'] ?? null;
                        $amount = $inv['total'] ?? $inv['amount'] ?? $inv['grand_total'] ?? null;
                        $downloadUrl = $api->resolveInvoiceDownloadUrl($inv);
                    @endphp
                    <article class="acc-record">
                        <div class="acc-record__head">
                            <span class="acc-record__id">{{ $displayRef }}</span>
                            @if($subId)
                                <span class="acc-chip acc-chip--muted">#{{ $subId }}</span>
                            @endif
                        </div>
                        <div class="acc-record__meta">
                            <div class="acc-record__field">
                                <label>{{ __('account.date') }}</label>
                                <span>{{ $invDate ?: '—' }}</span>
                            </div>
                            <div class="acc-record__field">
                                <label>{{ __('account.total') }}</label>
                                <span>
                                    @if($amount !== null)
                                        <x-sar :amount="(float) $amount" class="text-xs text-gray-900" />
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        </div>
                        @if($downloadUrl)
                            <div class="acc-record__actions">
                                <a href="{{ $downloadUrl }}" target="_blank" rel="noopener" class="acc-btn acc-btn--ghost acc-btn--sm">
                                    {{ __('account.download_invoice') }}
                                </a>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="acc-desktop-only overflow-x-auto">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.invoice_number') }}</th>
                            <th>{{ __('account.date') }}</th>
                            <th>{{ __('account.subscription') }}</th>
                            <th class="text-end">{{ __('account.total') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                            @php
                                $invId = $inv['id'] ?? $inv['invoice_id'] ?? null;
                                $invNumber = trim((string) ($inv['number'] ?? $inv['invoice_number'] ?? ''));
                                $displayRef = $invNumber !== '' ? $invNumber : ($invId ? '#'.$invId : '—');
                                $invDate = $inv['created_at'] ?? $inv['date'] ?? $inv['issued_at'] ?? '';
                                $subId = $inv['subscription_id'] ?? $inv['subscription']['id'] ?? null;
                                $amount = $inv['total'] ?? $inv['amount'] ?? $inv['grand_total'] ?? null;
                                $downloadUrl = $api->resolveInvoiceDownloadUrl($inv);
                            @endphp
                            <tr>
                                <td class="font-semibold text-gray-900">{{ $displayRef }}</td>
                                <td>{{ $invDate ?: '—' }}</td>
                                <td>
                                    @if($subId)
                                        <a href="{{ route('account.subscriptions.show', ['id' => $subId]) }}" class="text-blue-600 font-semibold hover:underline">#{{ $subId }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end font-semibold">
                                    @if($amount !== null)
                                        <x-sar :amount="(float) $amount" class="text-xs text-gray-900" />
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($downloadUrl)
                                        <a href="{{ $downloadUrl }}" target="_blank" rel="noopener" class="acc-btn acc-btn--ghost acc-btn--sm">
                                            {{ __('account.download_invoice') }}
                                        </a>
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
