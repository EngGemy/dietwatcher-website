<div class="space-y-6">
    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('account.my_wallet') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('account.wallet_hint') }}</p>
        </div>
        <button type="button"
                wire:click="load"
                class="acc-btn acc-btn--muted acc-btn--sm"
                wire:loading.attr="disabled">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
            {{ __('account.refresh') }}
        </button>
    </div>

    @if($error)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-center justify-between gap-3 flex-wrap">
            <span>{{ $error }}</span>
            @if($error === __('account.login_required'))
                <a href="{{ route('account.login') }}" class="acc-btn acc-btn--primary acc-btn--sm">{{ __('account.go_to_login') }}</a>
            @endif
        </div>
    @endif

    <section class="acc-wallet-hero">
        <div class="acc-wallet-hero__glow" aria-hidden="true"></div>
        <div class="acc-wallet-hero__grid" aria-hidden="true"></div>

        <div class="acc-wallet-hero__inner">
            <div class="acc-wallet-hero__chip" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>

            <div class="acc-wallet-hero__content">
                <p class="acc-wallet-hero__label">{{ __('account.available_balance') }}</p>
                <div class="acc-wallet-hero__amount">
                    @if($loading && $balance === null)
                        <span class="acc-wallet-hero__skeleton"></span>
                    @elseif($balance !== null)
                        <x-sar :amount="$balance" class="text-white text-4xl md:text-5xl font-extrabold" size="1.15rem" />
                    @else
                        <span class="text-4xl md:text-5xl font-extrabold text-white/90">—</span>
                    @endif
                </div>
                <p class="acc-wallet-hero__meta">{{ __('account.wallet_secure_note') }}</p>
            </div>

            <div class="acc-wallet-hero__brand" aria-hidden="true">
                <span class="acc-wallet-hero__logo">DW</span>
                <span class="acc-wallet-hero__network">WALLET</span>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="acc-wallet-mini">
            <p class="acc-wallet-mini__label">{{ __('account.wallet_incoming') }}</p>
            <p class="acc-wallet-mini__value text-emerald-600">
                @if($loading)
                    —
                @else
                    <x-sar :amount="$creditsTotal" class="text-emerald-600 font-bold" />
                @endif
            </p>
        </div>
        <div class="acc-wallet-mini">
            <p class="acc-wallet-mini__label">{{ __('account.wallet_outgoing') }}</p>
            <p class="acc-wallet-mini__value text-rose-600">
                @if($loading)
                    —
                @else
                    <x-sar :amount="$debitsTotal" class="text-rose-600 font-bold" />
                @endif
            </p>
        </div>
        <div class="acc-wallet-mini">
            <p class="acc-wallet-mini__label">{{ __('account.transactions') }}</p>
            <p class="acc-wallet-mini__value text-slate-900">{{ $loading ? '—' : $transactionsTotal }}</p>
        </div>
    </section>

    <section class="acc-wallet-panel">
        <div class="acc-wallet-panel__head">
            <div>
                <h2 class="acc-wallet-panel__title">{{ __('account.transactions') }}</h2>
                <p class="acc-wallet-panel__hint">{{ __('account.wallet_transactions_hint') }}</p>
            </div>
            <div class="acc-tab-group">
                <button type="button" class="acc-tab {{ $type === 'all' ? 'is-active' : '' }}" wire:click="$set('type','all')">{{ __('account.all') }}</button>
                <button type="button" class="acc-tab {{ $type === 'charge' ? 'is-active' : '' }}" wire:click="$set('type','charge')">{{ __('account.charges') }}</button>
                <button type="button" class="acc-tab {{ $type === 'sale' ? 'is-active' : '' }}" wire:click="$set('type','sale')">{{ __('account.sales') }}</button>
            </div>
        </div>

        <div class="acc-wallet-panel__body">
            @if($loading)
                <div class="acc-wallet-panel__empty">
                    <div class="acc-skeleton acc-skeleton-block" style="max-width:100%; height:4.5rem;"></div>
                    <div class="acc-skeleton acc-skeleton-block" style="max-width:100%; height:4.5rem;"></div>
                </div>
            @elseif(empty($transactions))
                <div class="acc-wallet-panel__empty">
                    <div class="acc-empty__icon mx-auto">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M3.75 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">{{ __('account.no_transactions') }}</p>
                </div>
            @else
                <ul class="acc-wallet-tx-list">
                    @foreach($transactions as $tx)
                        @php
                            $isCredit = (bool) ($tx['_is_credit'] ?? true);
                            $amount = (float) ($tx['_amount'] ?? 0);
                            $label = (string) ($tx['_label'] ?? __('account.transaction'));
                            $when = (string) ($tx['_when'] ?? '');
                        @endphp
                        <li class="acc-wallet-tx {{ $isCredit ? 'is-credit' : 'is-debit' }}">
                            <div class="acc-wallet-tx__icon" aria-hidden="true">
                                @if($isCredit)
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                @else
                                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
                                @endif
                            </div>
                            <div class="acc-wallet-tx__main">
                                <div class="acc-wallet-tx__row">
                                    <p class="acc-wallet-tx__title">{{ $label }}</p>
                                    <p class="acc-wallet-tx__amount">
                                        <span dir="ltr">{{ $isCredit ? '+' : '−' }}</span>
                                        <x-sar :amount="abs($amount)" class="font-bold {{ $isCredit ? 'text-emerald-600' : 'text-rose-600' }}" />
                                    </p>
                                </div>
                                <div class="acc-wallet-tx__row acc-wallet-tx__row--meta">
                                    <span class="acc-wallet-tx__badge">{{ $isCredit ? __('account.wallet_incoming') : __('account.wallet_outgoing') }}</span>
                                    @if($when !== '')
                                        <span class="acc-wallet-tx__date">{{ $when }}</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</div>
