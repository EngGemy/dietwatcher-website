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
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('account.add_address') }}
            </button>
            <button type="button" wire:click="reload" class="acc-btn acc-btn--muted acc-btn--sm" wire:loading.attr="disabled">
                {{ __('account.refresh') }}
            </button>
        </div>
    </div>

    @if($notice)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $notice }}</div>
    @endif
    @if($error)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $error }}</div>
    @endif

    <div wire:loading wire:target="saveNewAddress" class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        {{ __('account.saving_address') }}
    </div>

    @if($loading)
        <div class="acc-address-grid">
            @for($i = 0; $i < 2; $i++)
                <div class="acc-address-tile acc-address-tile--skeleton">
                    <div class="acc-skeleton acc-skeleton-block"></div>
                    <div class="acc-skeleton acc-skeleton-line" style="width: 70%;"></div>
                    <div class="acc-skeleton acc-skeleton-line" style="width: 50%;"></div>
                </div>
            @endfor
        </div>
    @elseif(empty($addresses))
        <div class="acc-card">
            <div class="acc-empty">
                <div class="acc-empty__icon">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                </div>
                <p>{{ __('account.no_addresses') }}</p>
                <button type="button" class="acc-btn acc-btn--primary mt-3 inline-flex" @click="$dispatch('open-map-picker')">
                    {{ __('account.add_address') }}
                </button>
            </div>
        </div>
    @else
        <div class="acc-address-grid">
            @foreach($addresses as $addr)
                @php
                    $addrId = (int) ($addr['id'] ?? 0);
                    $title = $addr['title'] ?? $addr['name'] ?? __('account.address');
                    $desc = $addr['description'] ?? $addr['address'] ?? $addr['full_address'] ?? '';
                    $type = strtolower((string) ($addr['type'] ?? $addr['address_type'] ?? ''));
                    $typeKey = 'account.address_type_'.$type;
                    $typeLabel = __($typeKey);
                    if ($typeLabel === $typeKey && $type !== '') {
                        $typeLabel = ucfirst($type);
                    }
                    $district = $addr['district']['name'] ?? $addr['district_name'] ?? '';
                    if (is_array($district)) {
                        $district = $district[app()->getLocale()] ?? $district['en'] ?? '';
                    }
                    $isActive = \App\Services\ApiAuthService::isAddressRowActive($addr);
                    $cantModify = \App\Support\AddressCheckoutHelper::isCantModify($addr, $lockedAddressIds ?? []);
                    $days = is_array($addr['days'] ?? null) ? $addr['days'] : [];
                    $deliveryTimeLabel = trim((string) ($addr['delivery_time_label'] ?? ''));
                    $deliveryTimeSlots = is_array($addr['delivery_time_slots'] ?? null) ? $addr['delivery_time_slots'] : [];
                    $selectedRegionId = (int) ($addr['region_duration_id'] ?? 0);
                @endphp
                <article class="acc-address-tile {{ $cantModify ? 'is-locked' : '' }} {{ $isActive ? 'is-active' : '' }}">
                    <div class="acc-address-tile__accent" aria-hidden="true"></div>

                    <header class="acc-address-tile__head">
                        <div class="acc-address-tile__icon" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="acc-address-tile__title">{{ $title }}</h2>
                                @if($typeLabel !== '')
                                    <span class="acc-chip acc-chip--muted">{{ $typeLabel }}</span>
                                @endif
                            </div>
                            @if($district)
                                <p class="acc-address-tile__district">{{ $district }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                            <span class="acc-chip {{ $isActive ? 'acc-chip--success' : 'acc-chip--warn' }}">
                                {{ $isActive ? __('account.active') : __('account.inactive') }}
                            </span>
                            @if($cantModify)
                                <span class="acc-chip acc-chip--warn">{{ __('address.in_use') }}</span>
                            @endif
                        </div>
                    </header>

                    @if($desc)
                        <p class="acc-address-tile__desc">{{ $desc }}</p>
                    @endif

                    <section class="acc-address-section">
                        <div class="acc-address-section__label">
                            <svg class="acc-address-section__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ __('checkout.delivery_time') }}</span>
                        </div>
                        @if($deliveryTimeSlots !== [])
                            <div class="acc-time-slots">
                                @foreach($deliveryTimeSlots as $slot)
                                    @php
                                        $slotId = (int) ($slot['id'] ?? 0);
                                        $isSelectedSlot = $selectedRegionId > 0
                                            ? $slotId === $selectedRegionId
                                            : $deliveryTimeLabel !== '' && ($slot['label'] ?? '') === $deliveryTimeLabel;
                                    @endphp
                                    <span class="acc-time-slot {{ $isSelectedSlot ? 'is-selected' : '' }}">
                                        {{ $slot['label'] ?? '' }}
                                    </span>
                                @endforeach
                            </div>
                        @elseif($deliveryTimeLabel !== '')
                            <p class="acc-address-section__value">{{ $deliveryTimeLabel }}</p>
                        @else
                            <p class="acc-address-section__empty">{{ __('checkout.no_delivery_time_slots') }}</p>
                        @endif
                    </section>

                    <section class="acc-address-section">
                        <div class="acc-address-section__label">
                            <svg class="acc-address-section__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            <span>{{ __('account.delivery_days') }}</span>
                        </div>
                        @if(! empty($days))
                            <div class="acc-day-row">
                                @foreach(range(1, 7) as $day)
                                    <span class="acc-day-pill {{ in_array($day, $days, true) ? 'is-selected' : '' }}">
                                        {{ __('account.weekday_'.$day) }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="acc-address-section__empty">{{ __('account.delivery_days_not_set') }}</p>
                        @endif
                    </section>

                    @if($cantModify)
                        <div class="acc-address-lock" role="note">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            <span>{{ __('address.cant_modify_hint') }}</span>
                        </div>
                    @else
                        <footer class="acc-address-tile__actions">
                            <button type="button" class="acc-btn acc-btn--ghost acc-btn--sm" wire:click="openDeliveryDays({{ $addrId }})">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                {{ __('account.edit_delivery_days') }}
                            </button>
                            <button type="button"
                                    class="acc-btn acc-btn--danger acc-btn--sm"
                                    wire:click="deleteAddress({{ $addrId }})"
                                    wire:confirm="{{ __('account.confirm_delete_address') }}">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                {{ __('account.delete') }}
                            </button>
                        </footer>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    <x-google-map-picker field-prefix="account_address" variant="modal" :show-trigger="false" />

    @if($editingAddressId)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center px-0 sm:px-4 bg-slate-900/60 backdrop-blur-[2px]" wire:click.self="closeDeliveryDays">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[92vh] flex flex-col" role="dialog" aria-modal="true">
                <div class="p-5 border-b border-gray-100 shrink-0">
                    <h3 class="text-lg font-bold text-gray-900">{{ __('account.edit_delivery_days') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ __('account.delivery_days_hint') }}</p>
                </div>

                <div class="p-5 space-y-5 overflow-y-auto flex-1">
                    <section>
                        <div class="acc-address-section__label mb-2">
                            <svg class="acc-address-section__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ __('checkout.delivery_time') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mb-3">{{ __('checkout.delivery_time_hint') }}</p>

                        @if(count($deliveryTimeSlots) > 0)
                            <div class="acc-time-picker">
                                @foreach($deliveryTimeSlots as $slot)
                                    @php $slotId = (int) ($slot['id'] ?? 0); @endphp
                                    <button type="button"
                                            wire:click="selectDeliveryTime({{ $slotId }})"
                                            class="acc-time-picker__btn {{ (int) $selectedRegionDurationId === $slotId ? 'is-selected' : '' }}">
                                        <span class="acc-time-picker__radio" aria-hidden="true"></span>
                                        <span>{{ $slot['label'] ?? '' }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif($deliveryTimeLabel !== '')
                            <p class="text-sm font-semibold text-gray-800 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3">{{ $deliveryTimeLabel }}</p>
                        @else
                            <p class="text-sm text-gray-500 rounded-xl border border-dashed border-gray-200 px-4 py-3">{{ __('checkout.no_delivery_time_slots') }}</p>
                        @endif
                    </section>

                    <section>
                        <div class="acc-address-section__label mb-2">
                            <svg class="acc-address-section__icon" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            <span>{{ __('account.delivery_days') }}</span>
                        </div>
                        <div class="acc-day-picker">
                            @foreach(range(1, 7) as $day)
                                <button type="button"
                                        wire:click="toggleDay({{ $day }})"
                                        class="acc-day-picker__btn {{ in_array($day, $selectedDays, true) ? 'is-selected' : '' }}">
                                    {{ __('account.weekday_'.$day) }}
                                </button>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="p-5 border-t border-gray-100 flex justify-end gap-2 shrink-0 bg-gray-50/80">
                    <button type="button" class="acc-btn acc-btn--muted" wire:click="closeDeliveryDays">{{ __('Cancel') }}</button>
                    <button type="button" class="acc-btn acc-btn--primary" wire:click="saveDeliveryDays" wire:loading.attr="disabled">
                        {{ __('account.save_changes') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
