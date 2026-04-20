{{--
  Google Maps Location Picker — modal (default) or embedded inline (checkout).
--}}
@props([
    'fieldPrefix' => 'location',
    'placeholder' => null,
    'initialLat'  => 24.7136,
    'initialLng'  => 46.6753,
    'districts'   => [],
    'variant'     => 'modal',
])

@php
    $placeholder ??= __('Pick location on map');
    $mapsKey = config('services.google_maps.key', '');
    $uid = 'gmp_' . uniqid();
    $isInline = ($variant ?? 'modal') === 'inline';
@endphp

<div
    x-data="googleMapPicker({
        prefix: @js($fieldPrefix),
        initialLat: {{ $initialLat }},
        initialLng: {{ $initialLng }},
        districts: @js($districts),
        variant: @js($variant ?? 'modal'),
        mapsKeyPresent: @js(filled($mapsKey)),
    })"
    @open-map-picker.window="variant === 'modal' && openModal()"
    @class([
        'gmp-wrapper',
        'gmp-wrapper--inline' => $isInline,
        'gmp-wrapper--missing-api-key' => $isInline && ! filled($mapsKey),
    ])
>
    @if(!$isInline)
    <div class="gmp-trigger" @click="openModal()">
        <span class="gmp-trigger__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20" style="width:20px;height:20px;flex-shrink:0">
                <path fill-rule="evenodd" d="M11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.07-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-2.003 3.5-4.697 3.5-8.327a8 8 0 0 0-16 0c0 3.63 1.556 6.326 3.5 8.327a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.144.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/>
            </svg>
        </span>
        <input
            type="text"
            class="gmp-trigger__input"
            :value="selectedAddress"
            readonly
            placeholder="{{ $placeholder }}"
        />
        <span class="gmp-trigger__arrow">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </span>
    </div>
    @endif

    <input type="hidden" :name="prefix + '_lat'"         :value="form.latitude" />
    <input type="hidden" :name="prefix + '_lng'"         :value="form.longitude" />
    <input type="hidden" :name="prefix + '_description'" :value="form.description" />
    <input type="hidden" :name="prefix + '_type'"        :value="form.type" />
    <input type="hidden" :name="prefix + '_district_id'" :value="form.district_id" />
    <input type="hidden" :name="prefix + '_pickup_type'" :value="form.pickup_type" />
    <input type="hidden" :name="prefix + '_title'"       :value="form.title" />

    @if($isInline)
        {{-- Inline (checkout): never use Alpine x-show on this panel — it can stay display:none after transitions --}}
        <div
            class="gmp-modal gmp-modal--inline-embed"
            @keydown.escape.window.stop=""
        >
    @else
        <div
            x-show="isOpen"
            x-transition
            class="gmp-modal"
            @keydown.escape.window="closeModal()"
        >
    @endif
        <div class="gmp-topbar">
            <button type="button" x-show="variant === 'modal'" @click="closeModal()" class="gmp-topbar__back">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </button>
            <span class="gmp-topbar__back-spacer" x-show="variant === 'inline'" aria-hidden="true"></span>
            <div class="gmp-topbar__search-wrap">
                <svg class="gmp-topbar__search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input
                    type="text"
                    id="{{ $uid }}_search"
                    class="gmp-topbar__search-input"
                    placeholder="{{ __('Search for a location…') }}"
                    autocomplete="off"
                />
            </div>
            <button type="button" @click="useMyLocation()" class="gmp-topbar__gps @if($isInline) gmp-topbar__gps--wide @endif" title="{{ __('Use my location') }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:18px;height:18px;flex-shrink:0" aria-hidden="true">
                    <path fill-rule="evenodd" d="M11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.07-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-2.003 3.5-4.697 3.5-8.327a8 8 0 0 0-16 0c0 3.63 1.556 6.326 3.5 8.327a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.144.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/>
                    <path d="M12 2.25a.75.75 0 0 1 .75.75v1.549a8.253 8.253 0 0 1 6.944 6.944H21.75a.75.75 0 0 1 0 1.5h-2.056a8.253 8.253 0 0 1-6.944 6.944V21.75a.75.75 0 0 1-1.5 0v-2.056a8.253 8.253 0 0 1-6.944-6.944H2.25a.75.75 0 0 1 0-1.5h2.056A8.253 8.253 0 0 1 11.25 4.306V3a.75.75 0 0 1 .75-.75Z"/>
                </svg>
                @if($isInline)
                    <span class="gmp-topbar__gps-label">{{ __('Locate Me') }}</span>
                @endif
            </button>
        </div>

        <div class="gmp-map-stage @if($isInline) gmp-map-stage--inline @endif">
        @if(filled($mapsKey))
        <p class="gmp-map-api-error" x-show="mapsAuthFailed" x-cloak>{{ __('google_maps.api_error_hint') }}</p>
        <div id="{{ $uid }}_map" class="gmp-map"></div>
        @else
        <div class="gmp-map gmp-map--placeholder flex min-h-[280px] flex-col items-center justify-center gap-2 border border-dashed border-gray-300 bg-slate-100 p-4 text-center text-sm text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-10 text-blue" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>
            <span>{{ __('Add GOOGLE_MAPS_API_KEY in .env to show the interactive map.') }}</span>
        </div>
        @endif

        <p class="gmp-map-hint" x-show="variant === 'inline' && !inlineConfirmed">{{ __('Click on the map or drag it to set your delivery location.') }}</p>

        <div class="gmp-pin" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#279ff9" class="gmp-pin__svg" width="44" height="44" style="width:44px;height:44px">
                <path fill-rule="evenodd" d="M11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.07-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-2.003 3.5-4.697 3.5-8.327a8 8 0 0 0-16 0c0 3.63 1.556 6.326 3.5 8.327a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.144.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/>
            </svg>
            <div class="gmp-pin__shadow"></div>
        </div>

        <div class="gmp-geocoding" x-show="geocoding" x-transition>
            <span class="gmp-geocoding__spinner"></span>
            {{ __('Finding address…') }}
        </div>
        </div>

        <div class="gmp-sheet @if($isInline) gmp-sheet--static-inline @endif" x-show="variant !== 'inline' || !inlineConfirmed">
            <div class="gmp-sheet__field">
                <label class="gmp-sheet__label">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    {{ __('Address Details') }}
                </label>
                <textarea
                    x-model="form.description"
                    class="gmp-sheet__textarea"
                    rows="2"
                    placeholder="{{ __('Street name, building number, landmarks…') }}"
                    @input.debounce.400ms="dispatchAddressDraft()"
                ></textarea>
            </div>

            <div class="gmp-sheet__row3">
                <input type="text" x-model="form.building_num" class="gmp-sheet__input gmp-sheet__input--sm" placeholder="{{ __('Building') }}" inputmode="numeric" />
                <input type="text" x-model="form.floor" class="gmp-sheet__input gmp-sheet__input--sm" placeholder="{{ __('Floor') }}" inputmode="numeric" />
                <input type="text" x-model="form.door" class="gmp-sheet__input gmp-sheet__input--sm" placeholder="{{ __('Door') }}" inputmode="numeric" />
            </div>

            <div class="gmp-sheet__types">
                <button type="button" class="gmp-type-chip" :class="{ 'gmp-type-chip--active': form.type === 'home' }" @click="form.type = 'home'">
                    🏠 {{ __('Home') }}
                </button>
                <button type="button" class="gmp-type-chip" :class="{ 'gmp-type-chip--active': form.type === 'work' }" @click="form.type = 'work'">
                    🏢 {{ __('Office') }}
                </button>
                <button type="button" class="gmp-type-chip" :class="{ 'gmp-type-chip--active': form.type === 'other' }" @click="form.type = 'other'">
                    🏢 {{ __('Other') }}
                </button>
            </div>

            <div class="gmp-sheet__field">
                <label class="gmp-sheet__label">{{ __('Delivery instructions') }}</label>
                <div class="gmp-sheet__types gmp-sheet__types--split">
                    <button type="button" class="gmp-type-chip gmp-type-chip--wide" :class="{ 'gmp-type-chip--active': form.pickup_type === 'hand_it_to_me' }" @click="form.pickup_type = 'hand_it_to_me'">
                        🤝 {{ __('Hand it to me') }}
                    </button>
                    <button type="button" class="gmp-type-chip gmp-type-chip--wide" :class="{ 'gmp-type-chip--active': form.pickup_type === 'leave_at_door' }" @click="form.pickup_type = 'leave_at_door'">
                        📍 {{ __('Leave at the spot') }}
                    </button>
                </div>
            </div>

            <div class="gmp-sheet__field" x-show="form.type === 'other'" x-transition>
                <input type="text" x-model="form.title" class="gmp-sheet__input"
                    placeholder="{{ __('e.g. Gym, Clinic, Parents…') }}" />
            </div>

            <div class="gmp-sheet__field">
                <label class="gmp-sheet__label">{{ __('District') }}</label>
                <div class="gmp-select-wrap">
                    <select x-model="form.district_id" class="gmp-sheet__select">
                        <option value="">{{ __('Select district') }}</option>
                        <template x-for="d in districts" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <p class="gmp-sheet__error" x-show="sheetError" x-text="sheetError" x-transition></p>

            <button
                type="button"
                @click="confirmLocation()"
                class="btn btn--primary btn--full gmp-sheet__confirm"
                :disabled="!form.latitude || !form.description || !form.district_id"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
                {{ __('Confirm Address') }}
            </button>
        </div>

        <div
            class="gmp-inline-confirmed"
            x-show="variant === 'inline' && inlineConfirmed"
            x-transition
            x-cloak
        >
            <div class="gmp-inline-confirmed__head">
                <div class="gmp-inline-confirmed__title">
                    <span class="gmp-inline-confirmed__check" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                    </span>
                    <span>{{ __('Address Confirmed') }}</span>
                </div>
                <button type="button" class="gmp-inline-confirmed__edit" @click="editInlineAddress()">{{ __('Edit') }}</button>
            </div>
            <p class="gmp-inline-confirmed__addr" x-text="confirmedAddressText"></p>
            <p class="gmp-inline-confirmed__meta" x-show="confirmedBuildingLine" x-text="confirmedBuildingLine"></p>
            <p class="gmp-inline-confirmed__meta"><span class="opacity-80">{{ __('Location') }}:</span> <span x-text="confirmedLocationLabel"></span></p>
        </div>
    </div>
</div>

<style>
.gmp-wrapper { position:relative; }
.gmp-wrapper--inline { width:100%; min-height:360px; position:relative; z-index:2; isolation:isolate; }
.gmp-wrapper--inline .gmp-modal,
.gmp-modal--inline-embed {
    position:relative !important;
    inset:auto !important;
    display:flex !important;
    flex-direction:column !important;
    width:100% !important;
    min-height:min(480px, 78vh) !important;
    height:auto !important;
    max-height:680px;
    z-index:1;
    flex-shrink:0;
    border-radius:12px;
    border:1px solid #e5e7eb;
    overflow:hidden;
    background:#fff;
    opacity:1 !important;
    visibility:visible !important;
}
.gmp-wrapper--inline .gmp-topbar { position:relative; box-shadow:none; border-bottom:1px solid #eef0f4; flex-wrap:nowrap; }
.gmp-topbar__back-spacer { width:38px; flex-shrink:0; }
/* Map stage: pin + geocode anchored to map area only (checkout inline) */
.gmp-map-stage { position:relative; width:100%; flex:1 1 auto; min-height:0; display:flex; flex-direction:column; }
.gmp-map-stage--inline { min-height:260px; isolation:isolate; }
.gmp-map-stage--inline .gmp-map { flex:1 1 auto; margin-top:0; min-height:220px; min-width:100%; background:#e8eaed; position:relative; z-index:0; }
.gmp-map-stage--inline .gmp-map-hint { flex-shrink:0; }
.gmp-map-stage--inline .gmp-pin { top:42%; z-index:6; }
.gmp-map-stage--inline .gmp-geocoding { top:38%; z-index:8; }
.gmp-wrapper--inline .gmp-sheet { transition: none !important; }
/* Checkout: form below map (not covered by map canvas) */
.gmp-sheet--static-inline {
    position: relative !important;
    bottom: auto !important;
    inset-inline: 0 !important;
    max-height: min(560px, 62vh) !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
    border-radius: 0 0 12px 12px !important;
    box-shadow: none !important;
    border-top: 1px solid #eef0f4;
    z-index: 2;
}
.gmp-sheet--static-inline::before { margin-bottom: 0.35rem; }
/* No API key: show only the placeholder block (hide search UI shell) */
.gmp-wrapper--missing-api-key .gmp-modal { display:block !important; min-height:auto !important; max-height:none !important; }
.gmp-wrapper--missing-api-key .gmp-modal > *:not(.gmp-map-stage) { display:none !important; }
.gmp-wrapper--missing-api-key .gmp-map-stage > *:not(.gmp-map--placeholder) { display:none !important; }
.gmp-wrapper--missing-api-key .gmp-modal .gmp-map--placeholder { margin-top:0 !important; min-height:min(360px,70vh) !important; }
.gmp-map-api-error { margin:0; padding:.65rem .85rem; font-size:.8rem; font-weight:600; color:#991b1b; background:#fef2f2; border-bottom:1px solid #fecaca; text-align:center; }
.gmp-map-hint { margin:0; padding:.35rem .75rem .5rem; font-size:.78rem; color:#6b7280; text-align:center; background:#fafafa; border-bottom:1px solid #eef0f4; }
.gmp-wrapper--inline .gmp-pin { transform:translate(-50%, calc(-100% + 28px)); }
.gmp-wrapper--inline .gmp-geocoding { top:120px; }

.gmp-topbar__gps--wide { width:auto !important; min-width:38px; padding-inline:.65rem !important; gap:.4rem; }
.gmp-topbar__gps-label { font-size:.8rem; font-weight:700; white-space:nowrap; }

.gmp-inline-confirmed {
    margin:0;
    padding:1rem 1.1rem;
    background:#f0fdf4;
    border-top:1px solid #bbf7d0;
    color:#166534;
    font-size:.88rem;
}
.gmp-inline-confirmed__head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.5rem; }
.gmp-inline-confirmed__title { display:flex; align-items:center; gap:.5rem; font-weight:700; }
.gmp-inline-confirmed__check {
    display:inline-flex; align-items:center; justify-content:center;
    width:22px; height:22px; border-radius:999px; background:#22c55e; color:#fff;
}
.gmp-inline-confirmed__edit { background:none; border:none; color:#15803d; font-weight:700; cursor:pointer; font-size:.88rem; padding:0; }
.gmp-inline-confirmed__edit:hover { text-decoration:underline; }
.gmp-inline-confirmed__addr { font-weight:600; line-height:1.45; margin:0 0 .35rem; }
.gmp-inline-confirmed__meta { margin:.15rem 0 0; font-size:.82rem; }

.gmp-trigger { display:flex; align-items:center; gap:.6rem; padding:.7rem 1rem; border:2px solid #e8c84a; border-radius:10px; background:#fff; cursor:pointer; transition:border .2s, box-shadow .2s; }
.gmp-trigger:hover { border-color:#279ff9; box-shadow:0 0 0 3px rgba(39,159,249,.1); }
.gmp-trigger__icon { color:#e8c84a; flex-shrink:0; }
.gmp-trigger__icon svg { width:20px; height:20px; }
.gmp-trigger__input { flex:1; border:none; outline:none; font-size:.92rem; color:#2e2e30; background:transparent; cursor:pointer; min-width:0; }
.gmp-trigger__input::placeholder { color:#aaa; }
.gmp-trigger__arrow { color:#ccc; flex-shrink:0; }

.gmp-modal { position:fixed; inset:0; z-index:9980; display:flex; flex-direction:column; background:#fff; }

.gmp-topbar { position:absolute; top:0; inset-inline:0; z-index:10; display:flex; align-items:center; gap:.5rem; padding:.75rem .75rem; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.1); }
.gmp-topbar__back { width:38px; height:38px; border:none; background:#f5f5fa; border-radius:10px; display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; color:#2e2e30; transition:background .2s; }
.gmp-topbar__back:hover { background:#e0e0e8; }
.gmp-topbar__search-wrap { flex:1; position:relative; }
.gmp-topbar__search-icon { position:absolute; top:50%; inset-inline-start:10px; transform:translateY(-50%); width:16px; height:16px; color:#999; pointer-events:none; }
.gmp-topbar__search-input { width:100%; padding:.6rem .75rem .6rem 2.2rem; border:1.5px solid #e0e0e8; border-radius:10px; font-size:.88rem; outline:none; background:#f5f5fa; transition:border .2s; }
[dir="rtl"] .gmp-topbar__search-input { padding:.6rem 2.2rem .6rem .75rem; }
.gmp-topbar__search-input:focus { border-color:#279ff9; background:#fff; }
.gmp-topbar__gps { width:38px; height:38px; border:none; background:#279ff9; border-radius:10px; display:flex; flex-direction:row; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; color:#fff; transition:background .2s; }
.gmp-topbar__gps:hover { background:#1e8de0; }

.gmp-map { flex:1; width:100%; margin-top:64px; }
.gmp-pin { position:absolute; top:50%; left:50%; transform:translate(-50%, calc(-100% + 32px)); z-index:5; pointer-events:none; }
.gmp-pin__svg { width:44px; height:44px; filter:drop-shadow(0 3px 6px rgba(0,0,0,.3)); }
.gmp-pin__shadow { width:16px; height:6px; background:rgba(0,0,0,.2); border-radius:50%; margin:0 auto; }

.gmp-geocoding { position:absolute; top:76px; left:50%; transform:translateX(-50%); z-index:8; background:rgba(255,255,255,.95); backdrop-filter:blur(6px); border-radius:100px; padding:.4rem 1rem; font-size:.8rem; font-weight:600; color:#555; display:flex; align-items:center; gap:.5rem; box-shadow:0 2px 12px rgba(0,0,0,.1); }
.gmp-geocoding__spinner { width:14px; height:14px; border:2px solid #e0e0e8; border-top-color:#279ff9; border-radius:50%; animation:gmp-spin .6s linear infinite; }
@keyframes gmp-spin { to { transform:rotate(360deg); } }

.gmp-sheet { position:absolute; bottom:0; inset-inline:0; z-index:9; background:#fff; border-radius:20px 20px 0 0; padding:1.25rem 1.25rem 1.5rem; box-shadow:0 -4px 24px rgba(0,0,0,.12); display:flex; flex-direction:column; gap:.85rem; max-height:55vh; overflow-y:auto; }
.gmp-sheet::before { content:''; display:block; width:40px; height:4px; background:#e0e0e8; border-radius:100px; margin:0 auto .75rem; }
.gmp-sheet__label { font-size:.78rem; font-weight:600; color:#555; display:flex; align-items:center; gap:.3rem; margin-bottom:.3rem; }
.gmp-sheet__textarea { width:100%; padding:.65rem .85rem; border:1.5px solid #e0e0e8; border-radius:10px; font-size:.88rem; color:#2e2e30; resize:none; outline:none; transition:border .2s; font-family:inherit; }
.gmp-sheet__textarea:focus { border-color:#279ff9; box-shadow:0 0 0 3px rgba(39,159,249,.1); }
.gmp-sheet__input { width:100%; padding:.65rem .85rem; border:1.5px solid #e0e0e8; border-radius:10px; font-size:.88rem; color:#2e2e30; outline:none; transition:border .2s; }
.gmp-sheet__input:focus { border-color:#279ff9; box-shadow:0 0 0 3px rgba(39,159,249,.1); }
.gmp-sheet__field { display:flex; flex-direction:column; }
.gmp-sheet__row3 { display:grid; grid-template-columns:repeat(3, 1fr); gap:.5rem; }
.gmp-sheet__input--sm { font-size:.82rem !important; padding:.5rem .55rem !important; }
.gmp-select-wrap { position:relative; }
.gmp-sheet__select { width:100%; padding:.65rem .85rem; border:1.5px solid #e0e0e8; border-radius:10px; font-size:.88rem; color:#2e2e30; outline:none; background:#fff; appearance:none; cursor:pointer; transition:border .2s; }
.gmp-sheet__select:focus { border-color:#279ff9; }
.gmp-select-wrap::after { content:'▾'; position:absolute; top:50%; inset-inline-end:.85rem; transform:translateY(-50%); pointer-events:none; color:#aaa; font-size:.8rem; }

.gmp-sheet__types { display:flex; gap:.5rem; flex-wrap:wrap; }
.gmp-sheet__types--split { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
.gmp-type-chip--wide { width:100%; justify-content:center; }
.gmp-type-chip { padding:.4rem .9rem; border-radius:100px; border:1.5px solid #e0e0e8; background:#fff; font-size:.82rem; font-weight:600; cursor:pointer; transition:all .2s; color:#555; }
.gmp-type-chip:hover { border-color:#279ff9; color:#279ff9; }
.gmp-type-chip--active { background:#279ff9; color:#fff; border-color:#279ff9; }

.gmp-sheet__confirm { margin-top:.25rem; height:48px; font-size:.95rem; gap:.5rem; }
.gmp-sheet__error { font-size:.78rem; color:#ff707a; text-align:center; }
</style>

@once
@if($mapsKey)
<script>
window._gmpLoaded = false;
window._gmpCallbacks = [];
window._gmpNotify = function() {
    window._gmpLoaded = true;
    (window._gmpCallbacks || []).forEach(function(fn) {
        try {
            fn();
        } catch (e) {}
    });
    window._gmpCallbacks = [];
};
window.initGoogleMaps = function() {
    // Preload the libraries so the Maps JS API bootstrap (`v=weekly`) moves out of
    // the legacy-callback path and into the importLibrary dispatcher. Using the new
    // Places library pulls PlaceAutocompleteElement instead of the deprecated
    // google.maps.places.Autocomplete constructor.
    try {
        if (window.google && window.google.maps && typeof window.google.maps.importLibrary === 'function') {
            Promise.all([
                window.google.maps.importLibrary('maps'),
                window.google.maps.importLibrary('places'),
            ]).finally(window._gmpNotify);
            return;
        }
    } catch (e) {}
    window._gmpNotify();
};
window.gm_authFailure = function() {
    window.dispatchEvent(new CustomEvent('gmp-maps-auth-failed', { bubbles: true }));
};
</script>
<script
    src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&v=weekly&language={{ app()->getLocale() }}&region=SA&callback=initGoogleMaps&loading=async"
    async defer
></script>
@endif
@endonce

<script>
(function() {
const UID = '{{ $uid }}';

function googleMapPicker(opts) {
    const L_BUILD = @json(__('Building'));
    const L_FLOOR = @json(__('Floor'));
    const L_DOOR = @json(__('Door'));
    const L_HOME = @json(__('Home'));
    const L_WORK = @json(__('Office'));
    const L_OTHER = @json(__('Other'));

    return {
        prefix:          opts.prefix || 'location',
        variant:         opts.variant || 'modal',
        isOpen:          false,
        inlineConfirmed: false,
        confirmedAddressText: '',
        confirmedBuildingLine: '',
        confirmedLocationLabel: '',
        geocoding:       false,
        selectedAddress: '',
        sheetError:      '',
        districts:       opts.districts || [],
        form: {
            latitude:    null,
            longitude:   null,
            description: '',
            type:        'home',
            district_id: '',
            title:       '',
            building_num: '',
            floor:       '',
            door:        '',
            pickup_type: 'hand_it_to_me',
        },
        _map:       null,
        _geocoder:  null,
        _autocomplete: null,
        _center:    { lat: opts.initialLat || 24.7136, lng: opts.initialLng || 46.6753 },
        _dragTimeout: null,
        mapsKeyPresent: opts.mapsKeyPresent !== false,
        mapsAuthFailed: false,

        dispatchAddressDraft() {
            if (this.variant !== 'inline') {
                return;
            }
            const t = (this.form.description || '').trim();
            if (! t) {
                return;
            }
            this.$dispatch('map-address-draft', { description: t });
        },

        applyExternalAddressDetail(a) {
            if (this.variant !== 'inline') {
                return;
            }
            const lat = parseFloat(a.latitude);
            const lng = parseFloat(a.longitude);
            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }
            this.form.latitude = lat;
            this.form.longitude = lng;
            this.form.description = (a.description || '').trim();
            const rawType = String(a.type || 'residential').toLowerCase();
            if (rawType === 'commercial' || rawType === 'work') {
                this.form.type = 'work';
            } else if (rawType === 'other' || rawType === 'others') {
                this.form.type = 'other';
            } else {
                this.form.type = 'home';
            }
            this.form.district_id = a.district_id != null && a.district_id !== '' ? String(a.district_id) : '';
            this.form.title = (a.title || '').trim();
            if (a.pickup_type && (a.pickup_type === 'hand_it_to_me' || a.pickup_type === 'leave_at_door')) {
                this.form.pickup_type = a.pickup_type;
            }
            this.form.building_num = '';
            this.form.floor = '';
            this.form.door = '';
            this._center = { lat, lng };
            if (this._map && window.google && window.google.maps) {
                this._map.panTo(this._center);
                this._map.setZoom(16);
            }
            const building_notes = this.buildingLine();
            this.confirmedAddressText = this.form.description;
            this.confirmedBuildingLine = building_notes;
            this.confirmedLocationLabel = this.locationTypeLabel();
            this.inlineConfirmed = true;
            this.$dispatch('address-selected', {
                ...this.form,
                building_notes,
                location_label: this.locationTypeLabel(),
                pickup_type: this.form.pickup_type || 'hand_it_to_me',
            });
            this.dispatchAddressDraft();
        },

        init() {
            if (this.variant === 'inline') {
                this.isOpen = true;
                this.sheetError = '';
                if (!this.districts.length) this.loadDistricts();
                this._onGmpAuthFail = () => {
                    this.mapsAuthFailed = true;
                };
                window.addEventListener('gmp-maps-auth-failed', this._onGmpAuthFail);
                this._boundApplyExternalAddress = (ev) => {
                    this.applyExternalAddressDetail(ev.detail || {});
                };
                window.addEventListener('gmp-external-address-apply', this._boundApplyExternalAddress);
                if (this.mapsKeyPresent) {
                    this.$nextTick(() => this.initMap());
                }
                window.addEventListener('checkout-home-map-refresh', () => {
                    this.$nextTick(() => {
                        if (! this.mapsKeyPresent) {
                            return;
                        }
                        if (this._map && window.google && window.google.maps) {
                            window.google.maps.event.trigger(this._map, 'resize');
                        } else {
                            this.initMap();
                        }
                        [80, 400].forEach((ms) => setTimeout(() => {
                            if (this._map && window.google && window.google.maps) {
                                window.google.maps.event.trigger(this._map, 'resize');
                            }
                        }, ms));
                    });
                });
            }
        },

        destroy() {
            if (this._boundApplyExternalAddress) {
                window.removeEventListener('gmp-external-address-apply', this._boundApplyExternalAddress);
            }
            if (this._onGmpAuthFail) {
                window.removeEventListener('gmp-maps-auth-failed', this._onGmpAuthFail);
            }
        },

        buildingLine() {
            const p = [];
            if (this.form.building_num && String(this.form.building_num).trim() !== '') {
                p.push(L_BUILD + ': ' + String(this.form.building_num).trim());
            }
            if (this.form.floor && String(this.form.floor).trim() !== '') {
                p.push(L_FLOOR + ': ' + String(this.form.floor).trim());
            }
            if (this.form.door && String(this.form.door).trim() !== '') {
                p.push(L_DOOR + ': ' + String(this.form.door).trim());
            }
            return p.join(', ');
        },

        locationTypeLabel() {
            if (this.form.type === 'work') return L_WORK;
            if (this.form.type === 'other') return (this.form.title && this.form.title.trim()) ? this.form.title.trim() : L_OTHER;
            return L_HOME;
        },

        openModal() {
            if (this.variant === 'inline') return;
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            this.sheetError = '';
            if (!this.districts.length) this.loadDistricts();
            this.$nextTick(() => this.initMap());
        },

        closeModal() {
            if (this.variant === 'inline') return;
            this.isOpen = false;
            document.body.style.overflow = '';
        },

        editInlineAddress() {
            this.inlineConfirmed = false;
            this.$nextTick(() => {
                if (this._map && window.google && window.google.maps) {
                    window.google.maps.event.trigger(this._map, 'resize');
                }
            });
        },

        async loadDistricts() {
            try {
                const res = await fetch('/api/districts');
                const data = await res.json();
                this.districts = data.data || data || [];
            } catch (e) {}
        },

        initMap() {
            if (!this.mapsKeyPresent) return;
            const mapEl = document.getElementById(UID + '_map');
            if (!mapEl) return;

            const doInit = () => {
                if (this._map) return;
                if (!window.google || !window.google.maps) return;

                this._map = new google.maps.Map(mapEl, {
                    center: this._center,
                    zoom: 15,
                    disableDefaultUI: true,
                    zoomControl: true,
                    gestureHandling: 'greedy',
                });
                this._geocoder = new google.maps.Geocoder();
                this.reverseGeocode(this._center);

                const resizeMap = () => {
                    if (!this._map || !window.google || !window.google.maps) return;
                    window.google.maps.event.trigger(this._map, 'resize');
                    this._map.setCenter(this._center);
                };
                this.$nextTick(() => resizeMap());
                [50, 200, 500].forEach((ms) => setTimeout(() => resizeMap(), ms));
                if (!this._gmpResizeBound) {
                    this._gmpResizeBound = true;
                    window.addEventListener('resize', resizeMap);
                }

                this._map.addListener('dragend', () => {
                    clearTimeout(this._dragTimeout);
                    this._dragTimeout = setTimeout(() => {
                        const c = this._map.getCenter();
                        this._center = { lat: c.lat(), lng: c.lng() };
                        this.reverseGeocode(this._center);
                    }, 400);
                });

                this._map.addListener('click', (e) => {
                    const loc = { lat: e.latLng.lat(), lng: e.latLng.lng() };
                    this._center = loc;
                    this._map.panTo(loc);
                    this.reverseGeocode(loc);
                });

                const searchInput = document.getElementById(UID + '_search');
                if (searchInput) {
                    this.mountPlaceAutocomplete(searchInput);
                }
            };

            if (window._gmpLoaded && window.google && window.google.maps) {
                doInit();
            } else {
                window._gmpCallbacks = window._gmpCallbacks || [];
                window._gmpCallbacks.push(doInit);
                const catchUp = () => {
                    if (this._map) {
                        return;
                    }
                    if (window._gmpLoaded && window.google && window.google.maps) {
                        doInit();
                    }
                };
                setTimeout(catchUp, 0);
                setTimeout(catchUp, 150);
                setTimeout(catchUp, 600);
                setTimeout(catchUp, 2000);
            }
        },

        async mountPlaceAutocomplete(inputEl) {
            if (!inputEl || !window.google || !window.google.maps) return;
            const handlePick = (place) => {
                if (!place) return;
                const loc = place.location || (place.geometry && place.geometry.location) || null;
                if (!loc) return;
                const lat = typeof loc.lat === 'function' ? loc.lat() : loc.lat;
                const lng = typeof loc.lng === 'function' ? loc.lng() : loc.lng;
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                this._center = { lat, lng };
                this._map.panTo(this._center);
                this._map.setZoom(16);
                this.reverseGeocode(this._center);
            };

            try {
                if (typeof google.maps.importLibrary === 'function') {
                    const placesLib = await google.maps.importLibrary('places');
                    const PlaceAutocompleteElement = placesLib && placesLib.PlaceAutocompleteElement;
                    if (PlaceAutocompleteElement) {
                        const el = new PlaceAutocompleteElement({
                            componentRestrictions: { country: 'sa' },
                        });
                        el.className = 'gmp-topbar__search-input gmp-topbar__search-input--pac';
                        el.setAttribute('placeholder', inputEl.getAttribute('placeholder') || '');
                        inputEl.replaceWith(el);
                        this._autocomplete = el;
                        el.addEventListener('gmp-placeselect', async (ev) => {
                            const picked = ev.place || (ev.detail && ev.detail.place);
                            if (!picked) return;
                            try {
                                await picked.fetchFields({ fields: ['displayName', 'formattedAddress', 'location'] });
                            } catch (_) {}
                            handlePick(picked);
                        });
                        return;
                    }
                }
            } catch (e) {}

            // Fallback for older Maps SDK versions still cached by the browser.
            if (google.maps.places && google.maps.places.Autocomplete) {
                this._autocomplete = new google.maps.places.Autocomplete(inputEl, {
                    componentRestrictions: { country: 'sa' },
                });
                this._autocomplete.addListener('place_changed', () => {
                    const place = this._autocomplete.getPlace();
                    handlePick(place);
                });
            }
        },

        reverseGeocode(latlng) {
            if (!this._geocoder) return;
            this.geocoding = true;
            const lat = typeof latlng.lat === 'function' ? latlng.lat() : latlng.lat;
            const lng = typeof latlng.lng === 'function' ? latlng.lng() : latlng.lng;
            this.form.latitude = lat;
            this.form.longitude = lng;

            this._geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                this.geocoding = false;
                if (status === 'OK' && results[0]) {
                    this.form.description = results[0].formatted_address;
                    this.dispatchAddressDraft();
                }
            });
        },

        useMyLocation() {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition(pos => {
                const loc = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                this._center = loc;
                if (this._map) {
                    this._map.panTo(loc);
                    this._map.setZoom(16);
                }
                this.reverseGeocode(loc);
            });
        },

        confirmLocation() {
            this.sheetError = '';

            if (!this.form.latitude || !this.form.longitude) {
                this.sheetError = '{{ __("Please pick a location on the map") }}';
                return;
            }
            if (!this.form.description.trim()) {
                this.sheetError = '{{ __("Please enter address details") }}';
                return;
            }
            if (!this.form.district_id) {
                this.sheetError = '{{ __("Please select a district") }}';
                return;
            }
            if (this.form.type === 'other' && !this.form.title.trim()) {
                this.sheetError = '{{ __("Please enter a label for this address") }}';
                return;
            }

            this.selectedAddress = this.form.description;
            const building_notes = this.buildingLine();
            const payload = {
                ...this.form,
                building_notes,
                location_label: this.locationTypeLabel(),
                pickup_type: this.form.pickup_type || 'hand_it_to_me',
            };

            if (this.variant === 'inline') {
                this.confirmedAddressText = this.form.description;
                this.confirmedBuildingLine = building_notes;
                this.confirmedLocationLabel = this.locationTypeLabel();
                this.inlineConfirmed = true;
            }

            this.$dispatch('address-selected', payload);

            if (this.variant !== 'inline') {
                this.closeModal();
            }
        },
    };
}

if (window.Alpine) {
    window.Alpine.data('googleMapPicker', googleMapPicker);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('googleMapPicker', googleMapPicker);
    });
}
})();
</script>
