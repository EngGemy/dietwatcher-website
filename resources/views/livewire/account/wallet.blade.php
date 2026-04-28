<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('account.my_wallet') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('account.wallet_hint') }}</p>
    </div>

    @if($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</div>
    @endif

    {{-- Balance hero --}}
    <section class="rounded-2xl p-6 md:p-8 relative overflow-hidden"
             style="background: linear-gradient(135deg, #1D4ED8 0%, #3B82F6 55%, #22D3EE 100%); color:#fff;">
        <div class="absolute inset-0 opacity-[.10]" style="background-image: radial-gradient(circle at 20% 20%, #fff 2px, transparent 2px); background-size: 26px 26px;"></div>
        <div class="relative flex items-start justify-between gap-4 flex-wrap">
            <div>
                <p class="text-sm opacity-80">{{ __('account.available_balance') }}</p>
                <p class="text-4xl md:text-5xl font-extrabold mt-1 tabular-nums">
                    @if($balance !== null){{ number_format($balance, 2) }}@else—@endif
                    <span class="text-lg font-semibold opacity-80 ms-2">{{ __('SAR') }}</span>
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="load" class="rounded-lg bg-white/20 backdrop-blur-sm hover:bg-white/25 px-3 py-2 text-sm font-semibold" wire:loading.attr="disabled">
                    {{ __('account.refresh') }}
                </button>
            </div>
        </div>
    </section>

    {{-- Transactions --}}
    <section class="acc-card">
        <div class="acc-card-head">
            <span>{{ __('account.transactions') }}</span>
            <div class="acc-tab-group">
                <button type="button" class="acc-tab {{ $type === 'all' ? 'is-active' : '' }}" wire:click="$set('type','all')">{{ __('account.all') }}</button>
                <button type="button" class="acc-tab {{ $type === 'charge' ? 'is-active' : '' }}" wire:click="$set('type','charge')">{{ __('account.charges') }}</button>
                <button type="button" class="acc-tab {{ $type === 'sale' ? 'is-active' : '' }}" wire:click="$set('type','sale')">{{ __('account.sales') }}</button>
            </div>
        </div>
        <div class="acc-card-body p-0">
            @if($loading)
                <div class="acc-empty">{{ __('account.loading') }}</div>
            @elseif(empty($transactions))
                <div class="acc-empty">{{ __('account.no_transactions') }}</div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach($transactions as $tx)
                        @php
                            $amt = (float) ($tx['amount'] ?? 0);
                            $txType = strtolower((string) ($tx['type'] ?? $tx['kind'] ?? ''));
                            $isCharge = in_array($txType, ['charge','credit','in'], true) || $amt > 0;
                            $desc = $tx['description'] ?? $tx['note'] ?? $tx['purpose'] ?? $txType ?: '—';
                            if (is_array($desc)) $desc = $desc[app()->getLocale()] ?? $desc['en'] ?? '';
                            $when = $tx['created_at'] ?? $tx['date'] ?? '';
                        @endphp
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 {{ $isCharge ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                        @if($isCharge)
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                                        @endif
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $desc }}</p>
                                    <p class="text-xs text-gray-500">{{ $when }}</p>
                                </div>
                            </div>
                            <div class="font-bold {{ $isCharge ? 'text-emerald-600' : 'text-rose-600' }} tabular-nums">
                                {{ $isCharge ? '+' : '-' }}{{ number_format(abs($amt), 2) }} <span class="text-xs font-normal text-gray-500">{{ __('SAR') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</div>
