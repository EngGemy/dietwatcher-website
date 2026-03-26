{{--
  Google Maps Location Picker Component
  Expert-app UX: fixed crosshair + draggable map (Uber/Careem/Talabat style)

  Props:
    $fieldPrefix  — prefix for hidden input names (e.g. "delivery" → delivery_lat, delivery_lng …)
    $placeholder  — text shown in the trigger input
    $initialLat   — starting map latitude  (default: 24.7136 — Riyadh)
    $initialLng   — starting map longitude (default: 46.6753)
    $districts    — pre-loaded districts array (optional, lazy-loaded if empty)
--}}
@props([
    'fieldPrefix' => 'location',
    'placeholder' => null,
    'initialLat'  => 24.7136,
    'initialLng'  => 46.6753,
    'districts'   => [],
])

@php
    $placeholder ??= __('Pick location on map');
    $mapsKey = config('services.google_maps.key', '');
    $uid = 'gmp_' . uniqid();
@endphp

<div
    x-data="googleMapPicker({
        prefix:      '{{ $fieldPrefix }}',
        initialLat:  {{ $initialLat }},
        initialLng:  {{ $initialLng }},
        districts:   {{ json_encode($districts) }},
    })"
    @open-map-picker.window="openModal()"
    class="gmp-wrapper"
>

    {{-- ─── Trigger Input ──────────────────────────────────── --}}
    <div class="gmp-trigger" @click="openModal()">
        <span class="gmp-trigger__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20" style="width:20px;height:20px;flex-shrink:0">
                <path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.07-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-2.003 3.5-4.697 3.5-8.327a8 8 0 0 0-16 0c0 3.63 1.556 6.326 3.5 8.327a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.144.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/>
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

    {{-- ─── Hidden Inputs ──────────────────────────────────── --}}
    <input type="hidden" :name="prefix + '_lat'"         :value="form.latitude" />
    <input type="hidden" :name="prefix + '_lng'"         :value="form.longitude" />
    <input type="hidden" :name="prefix + '_description'" :value="form.description" />
    <input type="hidden" :name="prefix + '_type'"        :value="form.type" />
    <input type="hidden" :name="prefix + '_district_id'" :value="form.district_id" />

    {{-- ─── Full-screen Modal ──────────────────────────────── --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="gmp-modal"
        style="display:none"
        @keydown.escape.window="closeModal()"
    >
        {{-- Top bar --}}
        <div class="gmp-topbar">
            <button type="button" @click="closeModal()" class="gmp-topbar__back">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </button>
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
            <button type="button" @click="useMyLocation()" class="gmp-topbar__gps" title="{{ __('Use my location') }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px">
                    <path fill-rule="evenodd" d="M11.54 22.351... M12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/>
                    <path d="M12 2.25a.75.75 0 0 1 .75.75v1.549a8.253 8.253 0 0 1 6.944 6.944H21.75a.75.75 0 0 1 0 1.5h-2.056a8.253 8.253 0 0 1-6.944 6.944V21.75a.75.75 0 0 1-1.5 0v-2.056a8.253 8.253 0 0 1-6.944-6.944H2.25a.75.75 0 0 1 0-1.5h2.056A8.253 8.253 0 0 1 11.25 4.306V3a.75.75 0 0 1 .75-.75Z"/>
                </svg>
            </button>
        </div>

        {{-- Map container --}}
        <div id="{{ $uid }}_map" class="gmp-map"></div>

        {{-- Fixed crosshair pin in center --}}
        <div class="gmp-pin" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#279ff9" class="gmp-pin__svg" width="44" height="44" style="width:44px;height:44px">
                <path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.07-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-2.003 3.5-4.697 3.5-8.327a8 8 0 0 0-16 0c0 3.63 1.556 6.326 3.5 8.327a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.144.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"/>
            </svg>
            <div class="gmp-pin__shadow"></div>
        </div>

        {{-- Loading address indicator --}}
        <div class="gmp-geocoding" x-show="geocoding" x-transition>
            <span class="gmp-geocoding__spinner"></span>
            {{ __('Finding address…') }}
        </div>

        {{-- ─── Bottom Sheet ────────────────────────────────── --}}
        <div class="gmp-sheet" x-show="isOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0">

            {{-- Address text (editable) --}}
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
                ></textarea>
            </div>

            {{-- Address type chips --}}
            <div class="gmp-sheet__types">
                <button type="button" class="gmp-type-chip" :class="{ 'gmp-type-chip--active': form.type === 'home' }" @click="form.type = 'home'">
                    🏠 {{ __('Home') }}
                </button>
                <button type="button" class="gmp-type-chip" :class="{ 'gmp-type-chip--active': form.type === 'work' }" @click="form.type = 'work'">
                    💼 {{ __('Work') }}
                </button>
                <button type="button" class="gmp-type-chip" :class="{ 'gmp-type-chip--active': form.type === 'other' }" @click="form.type = 'other'">
                    📍 {{ __('Other') }}
                </button>
            </div>

            {{-- Custom title (only when type = other) --}}
            <div class="gmp-sheet__field" x-show="form.type === 'other'" x-transition>
                <input type="text" x-model="form.title" class="gmp-sheet__input"
                    placeholder="{{ __('e.g. Gym, Clinic, Parents…') }}" />
            </div>

            {{-- District dropdown --}}
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

            {{-- Confirm button --}}
            <button
                type="button"
                @click="confirmLocation()"
                class="btn btn--primary btn--full gmp-sheet__confirm"
                :disabled="!form.latitude || !form.description || !form.district_id"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
                {{ __('Confirm Location') }}
            </button>
        </div>
    </div>
</div>

{{-- ─── Styles ──────────────────────────────────────────────── --}}
<style>
/* Trigger */
.gmp-wrapper { position:relative; }
.gmp-trigger { display:flex; align-items:center; gap:.6rem; padding:.7rem 1rem; border:2px solid #e8c84a; border-radius:10px; background:#fff; cursor:pointer; transition:border .2s, box-shadow .2s; }
.gmp-trigger:hover { border-color:#279ff9; box-shadow:0 0 0 3px rgba(39,159,249,.1); }
.gmp-trigger__icon { color:#e8c84a; flex-shrink:0; }
.gmp-trigger__icon svg { width:20px; height:20px; }
.gmp-trigger__input { flex:1; border:none; outline:none; font-size:.92rem; color:#2e2e30; background:transparent; cursor:pointer; min-width:0; }
.gmp-trigger__input::placeholder { color:#aaa; }
.gmp-trigger__arrow { color:#ccc; flex-shrink:0; }

/* Modal */
.gmp-modal { position:fixed; inset:0; z-index:9980; display:flex; flex-direction:column; background:#fff; }

/* Top bar */
.gmp-topbar { position:absolute; top:0; inset-inline:0; z-index:10; display:flex; align-items:center; gap:.5rem; padding:.75rem .75rem; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.1); }
.gmp-topbar__back { width:38px; height:38px; border:none; background:#f5f5fa; border-radius:10px; display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; color:#2e2e30; transition:background .2s; }
.gmp-topbar__back:hover { background:#e0e0e8; }
.gmp-topbar__search-wrap { flex:1; position:relative; }
.gmp-topbar__search-icon { position:absolute; top:50%; inset-inline-start:10px; transform:translateY(-50%); width:16px; height:16px; color:#999; pointer-events:none; }
.gmp-topbar__search-input { width:100%; padding:.6rem .75rem .6rem 2.2rem; border:1.5px solid #e0e0e8; border-radius:10px; font-size:.88rem; outline:none; background:#f5f5fa; transition:border .2s; }
[dir="rtl"] .gmp-topbar__search-input { padding:.6rem 2.2rem .6rem .75rem; }
.gmp-topbar__search-input:focus { border-color:#279ff9; background:#fff; }
.gmp-topbar__gps { width:38px; height:38px; border:none; background:#279ff9; border-radius:10px; display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; color:#fff; transition:background .2s; }
.gmp-topbar__gps:hover { background:#1e8de0; }

/* Map */
.gmp-map { flex:1; width:100%; margin-top:64px; }

/* Crosshair pin */
.gmp-pin { position:absolute; top:50%; left:50%; transform:translate(-50%, calc(-100% + 32px)); z-index:5; pointer-events:none; }
.gmp-pin__svg { width:44px; height:44px; filter:drop-shadow(0 3px 6px rgba(0,0,0,.3)); }
.gmp-pin__shadow { width:16px; height:6px; background:rgba(0,0,0,.2); border-radius:50%; margin:0 auto; }

/* Geocoding indicator */
.gmp-geocoding { position:absolute; top:76px; left:50%; transform:translateX(-50%); z-index:8; background:rgba(255,255,255,.95); backdrop-filter:blur(6px); border-radius:100px; padding:.4rem 1rem; font-size:.8rem; font-weight:600; color:#555; display:flex; align-items:center; gap:.5rem; box-shadow:0 2px 12px rgba(0,0,0,.1); }
.gmp-geocoding__spinner { width:14px; height:14px; border:2px solid #e0e0e8; border-top-color:#279ff9; border-radius:50%; animation:gmp-spin .6s linear infinite; }
@keyframes gmp-spin { to { transform:rotate(360deg); } }

/* Bottom Sheet */
.gmp-sheet { position:absolute; bottom:0; inset-inline:0; z-index:9; background:#fff; border-radius:20px 20px 0 0; padding:1.25rem 1.25rem 1.5rem; box-shadow:0 -4px 24px rgba(0,0,0,.12); display:flex; flex-direction:column; gap:.85rem; max-height:55vh; overflow-y:auto; }
.gmp-sheet::before { content:''; display:block; width:40px; height:4px; background:#e0e0e8; border-radius:100px; margin:0 auto .75rem; }
.gmp-sheet__label { font-size:.78rem; font-weight:600; color:#555; display:flex; align-items:center; gap:.3rem; margin-bottom:.3rem; }
.gmp-sheet__textarea { width:100%; padding:.65rem .85rem; border:1.5px solid #e0e0e8; border-radius:10px; font-size:.88rem; color:#2e2e30; resize:none; outline:none; transition:border .2s; font-family:inherit; }
.gmp-sheet__textarea:focus { border-color:#279ff9; box-shadow:0 0 0 3px rgba(39,159,249,.1); }
.gmp-sheet__input { width:100%; padding:.65rem .85rem; border:1.5px solid #e0e0e8; border-radius:10px; font-size:.88rem; color:#2e2e30; outline:none; transition:border .2s; }
.gmp-sheet__input:focus { border-color:#279ff9; box-shadow:0 0 0 3px rgba(39,159,249,.1); }
.gmp-sheet__field { display:flex; flex-direction:column; }
.gmp-select-wrap { position:relative; }
.gmp-sheet__select { width:100%; padding:.65rem .85rem; border:1.5px solid #e0e0e8; border-radius:10px; font-size:.88rem; color:#2e2e30; outline:none; background:#fff; appearance:none; cursor:pointer; transition:border .2s; }
.gmp-sheet__select:focus { border-color:#279ff9; }
.gmp-select-wrap::after { content:'▾'; position:absolute; top:50%; inset-inline-end:.85rem; transform:translateY(-50%); pointer-events:none; color:#aaa; font-size:.8rem; }

/* Type chips */
.gmp-sheet__types { display:flex; gap:.5rem; flex-wrap:wrap; }
.gmp-type-chip { padding:.4rem .9rem; border-radius:100px; border:1.5px solid #e0e0e8; background:#fff; font-size:.82rem; font-weight:600; cursor:pointer; transition:all .2s; color:#555; }
.gmp-type-chip:hover { border-color:#279ff9; color:#279ff9; }
.gmp-type-chip--active { background:#279ff9; color:#fff; border-color:#279ff9; }

.gmp-sheet__confirm { margin-top:.25rem; height:48px; font-size:.95rem; gap:.5rem; }
.gmp-sheet__error { font-size:.78rem; color:#ff707a; text-align:center; }
</style>

{{-- ─── Google Maps Script (loaded once) ──────────────────── --}}
@once
@if($mapsKey)
<script>
window._gmpLoaded = false;
window._gmpCallbacks = [];
window.initGoogleMaps = function() {
    window._gmpLoaded = true;
    window._gmpCallbacks.forEach(fn => fn());
    window._gmpCallbacks = [];
};
</script>
<script
    src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=initGoogleMaps&loading=async"
    async defer
></script>
@endif
@endonce

{{-- ─── Alpine Component ────────────────────────────────────── --}}
<script>
(function() {
const UID = '{{ $uid }}';

function googleMapPicker(opts) {
    return {
        prefix:          opts.prefix || 'location',
        isOpen:          false,
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
        },
        _map:       null,
        _geocoder:  null,
        _autocomplete: null,
        _center:    { lat: opts.initialLat || 24.7136, lng: opts.initialLng || 46.6753 },
        _dragTimeout: null,

        openModal() {
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            this.sheetError = '';

            // Load districts if not already loaded
            if (!this.districts.length) this.loadDistricts();

            this.$nextTick(() => this.initMap());
        },

        closeModal() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },

        async loadDistricts() {
            try {
                const res = await fetch('/api/districts');
                const data = await res.json();
                this.districts = data.data || data || [];
            } catch(e) {}
        },

        initMap() {
            const mapEl = document.getElementById(UID + '_map');
            if (!mapEl) return;

            const doInit = () => {
                if (this._map) return;

                this._map     = new google.maps.Map(mapEl, {
                    center:            this._center,
                    zoom:              15,
                    disableDefaultUI:  true,
                    zoomControl:       true,
                    gestureHandling:   'greedy',
                });
                this._geocoder = new google.maps.Geocoder();

                // Reverse geocode initial position
                this.reverseGeocode(this._center);

                // On drag end → reverse geocode map center
                this._map.addListener('dragend', () => {
                    clearTimeout(this._dragTimeout);
                    this._dragTimeout = setTimeout(() => {
                        const c = this._map.getCenter();
                        this._center = { lat: c.lat(), lng: c.lng() };
                        this.reverseGeocode(this._center);
                    }, 400);
                });

                // Places Autocomplete
                const searchInput = document.getElementById(UID + '_search');
                if (searchInput) {
                    this._autocomplete = new google.maps.places.Autocomplete(searchInput, {
                        componentRestrictions: { country: 'sa' },
                    });
                    this._autocomplete.addListener('place_changed', () => {
                        const place = this._autocomplete.getPlace();
                        if (!place.geometry) return;
                        const loc = place.geometry.location;
                        this._center = { lat: loc.lat(), lng: loc.lng() };
                        this._map.panTo(this._center);
                        this._map.setZoom(16);
                        this.reverseGeocode(this._center);
                    });
                }
            };

            if (window._gmpLoaded && window.google && window.google.maps) {
                doInit();
            } else {
                window._gmpCallbacks = window._gmpCallbacks || [];
                window._gmpCallbacks.push(doInit);
            }
        },

        reverseGeocode(latlng) {
            if (!this._geocoder) return;
            this.geocoding = true;
            this.form.latitude  = latlng.lat;
            this.form.longitude = latlng.lng;

            this._geocoder.geocode({ location: latlng }, (results, status) => {
                this.geocoding = false;
                if (status === 'OK' && results[0]) {
                    this.form.description = results[0].formatted_address;
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

            // Emit to parent (profile page, checkout, etc.)
            this.$dispatch('address-selected', { ...this.form });

            this.closeModal();
        },
    };
}

// Register globally for Alpine
if (window.Alpine) {
    window.Alpine.data('googleMapPicker', googleMapPicker);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('googleMapPicker', googleMapPicker);
    });
}
})();
</script>
