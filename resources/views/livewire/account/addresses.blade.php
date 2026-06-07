<div class="space-y-6" x-data @address-selected.window="$wire.saveNewAddress($event.detail)">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('account.my_addresses') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('account.addresses_hint') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    class="acc-btn acc-btn--primary acc-btn--sm"
                    @click="$dispatch('open-map-picker')"
                    wire:loading.attr="disabled"
                    wire:target="saveNewAddress">
                {{ __('account.add_address') }}
            </button>
            <button type="button" wire:click="reload" class="acc-btn acc-btn--muted acc-btn--sm" wire:loading.attr="disabled">
                {{ __('account.refresh') }}
            </button>
        </div>
    </div>

    @if($notice)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $notice }}</div>
    @endif
    @if($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $error }}</div>
    @endif

    <div wire:loading wire:target="saveNewAddress" class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        {{ __('account.saving_address') }}
    </div>

    <div class="acc-card">
        @if($loading)
            <div class="acc-empty">{{ __('account.loading') }}</div>
        @elseif(empty($addresses))
            <div class="acc-empty">
                <div class="acc-empty__icon">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                </div>
                <p>{{ __('account.no_addresses') }}</p>
                <button type="button" class="acc-btn acc-btn--primary mt-3 inline-flex" @click="$dispatch('open-map-picker')">
                    {{ __('account.add_address') }}
                </button>
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($addresses as $addr)
                    @php
                        $addrId = (int) ($addr['id'] ?? 0);
                        $title = $addr['title'] ?? $addr['name'] ?? __('account.address');
                        $desc = $addr['description'] ?? $addr['address'] ?? $addr['full_address'] ?? '';
                        $type = $addr['type'] ?? $addr['address_type'] ?? '';
                        $district = $addr['district']['name'] ?? $addr['district_name'] ?? '';
                        if (is_array($district)) {
                            $district = $district[app()->getLocale()] ?? $district['en'] ?? '';
                        }
                        $isActive = \App\Services\ApiAuthService::isAddressRowActive($addr);
                        $days = is_array($addr['days'] ?? null) ? $addr['days'] : [];
                    @endphp
                    <li class="p-4 md:p-5 hover:bg-gray-50 transition">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-gray-900">{{ $title }}</span>
                                    @if($type)
                                        <span class="acc-chip acc-chip--muted">{{ ucfirst((string) $type) }}</span>
                                    @endif
                                    <span class="acc-chip {{ $isActive ? 'acc-chip--success' : 'acc-chip--warn' }}">
                                        {{ $isActive ? __('account.active') : __('account.inactive') }}
                                    </span>
                                </div>
                                @if($desc)
                                    <p class="text-sm text-gray-600 mt-1">{{ $desc }}</p>
                                @endif
                                @if($district)
                                    <p class="text-xs text-gray-500 mt-1">{{ $district }}</p>
                                @endif
                                @if(! empty($days))
                                    <p class="text-xs text-gray-500 mt-2">
                                        {{ __('account.delivery_days') }}:
                                        @foreach($days as $d)
                                            <span class="acc-chip acc-chip--muted">{{ __('account.weekday_'.(int) $d) }}</span>
                                        @endforeach
                                    </p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" class="acc-btn acc-btn--ghost acc-btn--sm" wire:click="openDeliveryDays({{ $addrId }})">
                                    {{ __('account.edit_delivery_days') }}
                                </button>
                                <button type="button"
                                        class="acc-btn acc-btn--danger acc-btn--sm"
                                        wire:click="deleteAddress({{ $addrId }})"
                                        wire:confirm="{{ __('account.confirm_delete_address') }}">
                                    {{ __('account.delete') }}
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <x-google-map-picker field-prefix="account_address" variant="modal" :show-trigger="false" />

    @if($editingAddressId)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-slate-900/60" wire:click.self="closeDeliveryDays">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">{{ __('account.edit_delivery_days') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('account.delivery_days_hint') }}</p>
                </div>
                <div class="p-5 grid grid-cols-2 gap-2">
                    @foreach(range(1, 7) as $day)
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" wire:model="selectedDays" value="{{ $day }}" class="rounded text-blue-600" />
                            <span class="text-sm font-medium">{{ __('account.weekday_'.$day) }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="p-5 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" class="acc-btn acc-btn--muted" wire:click="closeDeliveryDays">{{ __('Cancel') }}</button>
                    <button type="button" class="acc-btn acc-btn--primary" wire:click="saveDeliveryDays" wire:loading.attr="disabled">
                        {{ __('account.save_changes') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
