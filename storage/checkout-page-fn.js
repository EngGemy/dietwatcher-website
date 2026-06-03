function checkoutPage() {
        return {
            // Reactive state
            baseSubtotal: 4430.72,
            isPlanCheckout: true,
            duration: "once",
            selectedPlanDurationId: "5",
            planDurationPrices: {"5":4430.72},
            deliveryType: "home",
            selectedPlanId: 13,
            selectedSubscriptionPlanId: 0,
            selectedPlanCaloryId: 0,
            hasCartItems: true,
            startDate: "2026-06-03",
            minStartDate: "2026-06-03",
            _moyasarFingerprint: '',
            _moyasarRequestId: 0,
            lastAppliedMinStartDate: '',
            vatRate: 0.15,
            deliveryFeeAmount: 0,
            discount: 0,
            addressStreet: "",
            buildingNotes: "",
            customerName: "",
            showNameField: false,
            isContinueUser: false,
            savedAddresses: [],
            selectedAddressId: null,
            addingNewAddress: false,
            savingNewAddress: false,
            newAddressError: '',
            sarSymbol: '\u20C1',
            uiLabels: {
                cancel: "\u0625\u0644\u063a\u0627\u0621",
                addNewAddress: "\u0625\u0636\u0627\u0641\u0629 \u0639\u0646\u0648\u0627\u0646 \u062c\u062f\u064a\u062f",
                resendIn: "\u0625\u0639\u0627\u062f\u0629 \u0625\u0631\u0633\u0627\u0644 \u0628\u0639\u062f",
                resend: "\u0625\u0639\u0627\u062f\u0629 \u0625\u0631\u0633\u0627\u0644",
            },
            addressPhoneLocal: "",
            deviceId: (function () {
                try {
                    const k = 'dw_checkout_device_id';
                    let v = localStorage.getItem(k);
                    if (! v && typeof crypto !== 'undefined' && crypto.randomUUID) {
                        v = 'web-' + crypto.randomUUID();
                        localStorage.setItem(k, v);
                    }

                    return v || 'web-checkout-device';
                } catch (e) {
                    return 'web-checkout-device';
                }
            })(),
            deliveryBuilding: '',
            deliveryFloor: '',
            deliveryDoor: '',
            addressConfirmedForSync: false,
            _syncExtTimer: null,

            // Zone state
            selectedZoneId: "",
            zones: [{"id":1,"name":"Riyadh","subscription_delivery_price":0,"order_delivery_price":25,"is_active":true}],

            checkoutProgramId: 13,
            /** Matches cart line duration_days ? used when API duration_id differs from list ids */
            cartDurationDaysHint: 28,
            cartDurationFallback: null,
            durationsLoading: false,
            // Plan durations (filled from server, client fetch, or cart fallback)
            planDurations: [{"id":5,"days":28,"price":4430,"offer_price":0,"effective_price":4430.72,"price_per_day":23.14,"label":{"en":"28 days","ar":"28 \u064a\u0648\u0645"}}],

            // Branch pickup state
            selectedBranchId: "",
            branches: [],
            branchesLoading: true,
            pickupPhase: "cta",
            branchSearch: '',

            // Duration multiplier map from backend
            durationMultipliers: {"once":1,"weekly":0.25,"monthly":1,"3months":3},

            // Phone / OTP state (local = 9 digits after +966)
            phoneLocal: "",
            phoneVerified: false,
            otpModalOpen: false,
            otpSent: false,
            otpDigits: ['', '', '', ''],
            otpLoading: false,
            otpMessage: '',
            otpMessageType: '',
            otpCooldown: 0,

            // Coupon state
            couponCode: "",
            couponApplied: false,
            couponLoading: false,
            couponMessage: '',

            moyasarError: '',
            /** Set when POST /checkout/sync-address fails (silent sync or user-visible). */
            syncAddressError: '',
            _moyasarTimer: null,

            getCsrfToken() {
                const fromMeta = document.querySelector('meta[name="csrf-token"]')?.content;
                if (fromMeta) {
                    return fromMeta;
                }
                const fromForm = this.$refs.checkoutForm?.querySelector('input[name="_token"]')?.value;
                if (fromForm) {
                    return fromForm;
                }
                return '';
            },

            getXsrfTokenFromCookie() {
                const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
                return m ? decodeURIComponent(m[1]) : '';
            },

            buildCsrfHeaders() {
                const csrf = this.getCsrfToken();
                const xsrf = this.getXsrfTokenFromCookie();
                const headers = {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                };
                if (xsrf) {
                    headers['X-XSRF-TOKEN'] = xsrf;
                }
                return { headers, csrf };
            },

            fullPhone966() {
                return typeof window.dwSaudiPhone966 === 'function' ? window.dwSaudiPhone966(this.phoneLocal) : '';
            },

            displayPhone() {
                const f = this.fullPhone966();
                return f ? ('+' + f) : '';
            },

            addressPhone966() {
                return typeof window.dwSaudiPhone966 === 'function' ? window.dwSaudiPhone966(this.addressPhoneLocal) : '';
            },

            // --- PRICES FROM API ARE VAT-INCLUSIVE (like mobile app) ---
            // The baseSubtotal already includes VAT. We extract VAT for display only.

            durationCardTitle(d) {
                if (! d) {
                    return '';
                }
                let label = d.label;
                if (typeof label === 'object' && label !== null && ! Array.isArray(label)) {
                    label = label['ar'] || label['en'] || '';
                }
                if (label) {
                    return String(label);
                }
                if (d.days) {
                    return String(d.days) + ' ' + "\u064a\u0648\u0645";
                }

                return '';
            },

            /** Total SAR when available (matches meal-plan duration chips); else price / day */
            durationPillPriceLine(d) {
                if (! d) {
                    return '';
                }
                const total = parseFloat(d.effective_price) || 0;
                if (total > 0) {
                    const n = Math.round(total * 100) / 100;

                    return '\u20C1 ' + (Number.isInteger(n) ? String(n) : n.toFixed(2));
                }
                const ppd = parseFloat(d.price_per_day) || 0;
                if (ppd > 0) {
                    return '\u20C1 ' + ppd.toFixed(2) + ' / ' + "\u064a\u0648\u0645";
                }

                return '';
            },

            money(value) {
                const n = Number(value);
                if (! Number.isFinite(n)) {
                    return '0.00';
                }

                return n.toFixed(2);
            },

            durationPlanHasOffer(d) {
                if (! d) {
                    return false;
                }
                if (d.has_offer === true) {
                    return true;
                }
                const p = parseFloat(d.price) || 0;
                const o = parseFloat(d.offer_price) || 0;

                return o > 0 && o < p;
            },

            durationPlanListTotalStr(d) {
                const lp = parseFloat(d.list_price);
                const raw = ! Number.isNaN(lp) && lp > 0 ? lp : parseFloat(d.price) || 0;
                const n = Math.round(raw * 100) / 100;

                return Number.isInteger(n) ? String(n) : n.toFixed(2);
            },

            durationPlanEffectiveTotal(d) {
                const eff = parseFloat(d.effective_price);
                if (! Number.isNaN(eff) && eff > 0) {
                    return eff;
                }
                const p = parseFloat(d.price) || 0;
                const o = parseFloat(d.offer_price) || 0;

                return o > 0 && o < p ? o : p;
            },

            durationPlanEffectiveTotalStr(d) {
                const n = Math.round(this.durationPlanEffectiveTotal(d) * 100) / 100;

                return Number.isInteger(n) ? String(n) : n.toFixed(2);
            },

            durationStrikeLine(d) {
                return this.sarSymbol + ' ' + this.durationPlanListTotalStr(d);
            },

            durationTotalLine(d) {
                return this.sarSymbol + ' ' + this.durationPlanEffectiveTotalStr(d);
            },

            newAddressToggleLabel() {
                return this.addingNewAddress ? this.uiLabels.cancel : this.uiLabels.addNewAddress;
            },

            otpResendLabel() {
                return this.otpCooldown > 0
                    ? this.uiLabels.resendIn + ' ' + this.otpCooldown + 's'
                    : this.uiLabels.resend;
            },

            durationPlanAvgLine(d) {
                const days = parseInt(d.days, 10) || 0;
                const e = this.durationPlanEffectiveTotal(d);
                if (days <= 0 || e <= 0) {
                    return '';
                }
                const avg = Math.round((e / days) * 100) / 100;
                const ns = Number.isInteger(avg) ? String(avg) : avg.toFixed(2);

                return "\u20c1" + ' ' + ns + ' - ' + "\u0641\u064a \u0627\u0644\u064a\u0648\u0645";
            },

            planSelectedAvgPerDayAmount() {
                if (! this.isPlanCheckout) {
                    return '';
                }
                const id = this.selectedPlanDurationId;
                const row = (this.planDurations || []).find((r) => String(r.id) === String(id));
                if (! row) {
                    return '';
                }
                const days = parseInt(row.days, 10) || 0;
                const e = this.durationPlanEffectiveTotal(row);
                if (days <= 0 || e <= 0) {
                    return '';
                }
                const avg = Math.round((e / days) * 100) / 100;

                return "\u20c1" + ' ' + (Number.isInteger(avg) ? String(avg) : avg.toFixed(2));
            },

            normalizeDurationRow(row) {
                const p = parseFloat(row.price) || 0;
                const o = parseFloat(row.offer_price) || 0;
                const eff = parseFloat(row.effective_price);
                const effective = ! Number.isNaN(eff) && eff > 0
                    ? eff
                    : (o > 0 && o < p ? o : p);
                const days = parseInt(row.days, 10) || 0;
                const ppd = days > 0 ? Math.round((effective / days) * 100) / 100 : (parseFloat(row.price_per_day) || 0);
                const hasOffer = o > 0 && o < p;

                return { ...row, effective_price: effective, price_per_day: ppd, list_price: p, has_offer: hasOffer };
            },

            async hydratePlanDurations() {
                try {
                    let list = Array.isArray(this.planDurations) ? [...this.planDurations] : [];
                    list = list.map((row) => this.normalizeDurationRow(row));
                    if (list.length === 0 && this.checkoutProgramId) {
                        try {
                            const res = await fetch('http://127.0.0.1:8083/api/plan/' + this.checkoutProgramId + '/durations');
                            const data = await res.json();
                            const raw = Array.isArray(data) ? data : [];
                            list = raw.map((row) => this.normalizeDurationRow(row));
                        } catch (e) {}
                    }
                    if (list.length === 0 && this.cartDurationFallback) {
                        list = [this.normalizeDurationRow(this.cartDurationFallback)];
                    }
                    this.planDurations = list;
                    this.planDurationPrices = {};
                    list.forEach((row) => {
                        const id = String(row.id);
                        const eff = parseFloat(row.effective_price) || 0;
                        this.planDurationPrices[id] = eff;
                    });
                    const idOk = (s) => s && list.some((r) => String(r.id) === String(s));
                    let sel = "5";
                    if (! idOk(sel)) {
                        let pick = this.cartDurationDaysHint > 0
                            ? list.find((r) => parseInt(r.days, 10) === this.cartDurationDaysHint)
                            : null;
                        if (! pick) {
                            pick = list.find((r) => r.is_default && Number(r.id) > 0) || list.find((r) => Number(r.id) > 0);
                        }
                        sel = pick ? String(pick.id) : (list[0] ? String(list[0].id) : '');
                    }
                    this.selectedPlanDurationId = sel;
                    if (sel !== '' && this.planDurationPrices[sel] != null) {
                        this.baseSubtotal = Math.round(this.planDurationPrices[sel] * 100) / 100;
                    }
                } finally {
                    this.durationsLoading = false;
                }
            },

            planDurationSummaryLabel() {
                const id = this.selectedPlanDurationId;
                const row = (this.planDurations || []).find((d) => String(d.id) === String(id));
                if (! row) {
                    return '';
                }
                let label = row.label;
                if (typeof label === 'object' && label !== null && ! Array.isArray(label)) {
                    label = label['ar'] || label['en'] || '';
                }
                const labelStr = String(label || '').trim();
                const daysNum = parseInt(row.days, 10) || 0;
                if (labelStr && daysNum > 0 && labelStr.includes(String(daysNum))) {
                    return labelStr;
                }
                if (! labelStr && daysNum > 0) {
                    return `${daysNum} ${"\u064a\u0648\u0645"}`;
                }
                if (labelStr && daysNum > 0) {
                    return labelStr + ` - ${daysNum} ${"\u064a\u0648\u0645"}`;
                }

                return labelStr;
            },

            composeBuildingNotes() {
                const p = [];
                const b = (this.deliveryBuilding || '').trim();
                const f = (this.deliveryFloor || '').trim();
                const d = (this.deliveryDoor || '').trim();
                if (b) {
                    p.push("\u0627\u0644\u0645\u0628\u0646\u0649" + ': ' + b);
                }
                if (f) {
                    p.push("\u0627\u0644\u0637\u0627\u0628\u0642" + ': ' + f);
                }
                if (d) {
                    p.push("\u0627\u0644\u0628\u0627\u0628" + ': ' + d);
                }
                this.buildingNotes = p.join(', ');
                if (this.addressConfirmedForSync) {
                    clearTimeout(this._syncExtTimer);
                    this._syncExtTimer = setTimeout(() => this.syncExternalAddress(), 1200);
                }
            },

            async syncExternalAddress() {
                if (this.deliveryType !== 'home') {
                    return;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return;
                }
                if (! this.fullPhone966()) {
                    this.syncAddressError = "\u0623\u062f\u062e\u0644 \u0631\u0642\u0645 \u062c\u0648\u0627\u0644 \u0635\u0627\u0644\u062d (\u0628\u0639\u062f +966) \u0642\u0628\u0644 \u062d\u0641\u0638 \u0645\u0648\u0642\u0639 \u0627\u0644\u062e\u0631\u064a\u0637\u0629.";

                    return;
                }
                const fd = new FormData(form);
                try {
                    const res = await fetch('http://127.0.0.1:8083/checkout/sync-address', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    const ok = res.ok && (data.success === true || data.skipped === true);
                    if (ok) {
                        this.syncAddressError = data.already_saved
                            ? (data.message || "\u0647\u0630\u0627 \u0627\u0644\u0645\u0648\u0642\u0639 \u0645\u062d\u0641\u0648\u0638 \u0645\u0633\u0628\u0642\u0627\u064b \u0641\u064a \u0639\u0646\u0627\u0648\u064a\u0646\u0643.")
                            : '';
                        if (data.data && data.data.id) {
                            this.selectedAddressId = String(data.data.id);
                            await this.refreshCustomerFromServer();
                            const fresh = this.savedAddresses.find(a => String(a.id) === String(data.data.id));
                            if (fresh) {
                                this.applySavedAddress(fresh);
                            } else {
                                this.applySavedAddress(data.data);
                            }
                        } else {
                            await this.refreshCustomerFromServer();
                        }
                        if (this.addressConfirmedForSync || this.selectedAddressId) {
                            this.scheduleMoyasarRefresh();
                        }
                    } else {
                        const errs = data && data.errors && typeof data.errors === 'object'
                            ? Object.values(data.errors).flat().filter(Boolean)
                            : [];
                        this.syncAddressError = errs[0] || data.message || "\u062a\u0639\u0630\u0631 \u062d\u0641\u0638 \u0647\u0630\u0627 \u0627\u0644\u0645\u0648\u0642\u0639 \u0641\u064a \u062d\u0633\u0627\u0628\u0643. \u062a\u0623\u0643\u062f \u0645\u0646 \u0627\u0644\u0639\u0646\u0648\u0627\u0646 \u0648\u0631\u0642\u0645 \u0627\u0644\u062c\u0648\u0627\u0644 \u062b\u0645 \u0623\u0639\u062f \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629.";
                    }
                } catch (e) {
                    this.syncAddressError = "\u062a\u0639\u0630\u0631 \u062d\u0641\u0638 \u0647\u0630\u0627 \u0627\u0644\u0645\u0648\u0642\u0639 \u0641\u064a \u062d\u0633\u0627\u0628\u0643. \u062a\u0623\u0643\u062f \u0645\u0646 \u0627\u0644\u0639\u0646\u0648\u0627\u0646 \u0648\u0631\u0642\u0645 \u0627\u0644\u062c\u0648\u0627\u0644 \u062b\u0645 \u0623\u0639\u062f \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629.";
                }
            },

            handleAddressFromMap(event) {
                const d = event.detail || {};
                this.syncAddressError = '';
                if (d.description) {
                    this.addressStreet = d.description;
                }
                this.deliveryBuilding = d.building_num != null && d.building_num !== '' ? String(d.building_num) : '';
                this.deliveryFloor = d.floor != null && d.floor !== '' ? String(d.floor) : '';
                this.deliveryDoor = d.door != null && d.door !== '' ? String(d.door) : '';
                if (d.building_notes) {
                    this.buildingNotes = d.building_notes;
                } else {
                    this.composeBuildingNotes();
                }
                const fromSaved = d.id != null && String(d.id).trim() !== '';
                if (! fromSaved) {
                    this.addressConfirmedForSync = false;
                }
            },

            handleMapAddressDraft(event) {
                const d = event.detail || {};
                if (d.description) {
                    this.addressStreet = d.description;
                }
            },

            savedAddressDistrict(addr) {
                if (! addr || ! addr.district) {
                    return '';
                }
                const d = addr.district;
                if (typeof d.name === 'string') {
                    return d.name;
                }
                if (d.name && typeof d.name === 'object') {
                    return d.name['ar'] || d.name['en'] || '';
                }

                return '';
            },

            startAddingAddress() {
                this.addingNewAddress = !this.addingNewAddress;
                this.newAddressError = '';
                if (this.addingNewAddress) {
                    this.selectedAddressId = null;
                    this.addressStreet = '';
                    this.deliveryBuilding = '';
                    this.deliveryFloor = '';
                    this.deliveryDoor = '';
                    this.buildingNotes = '';
                    this.deliveryType = 'home';
                    if (! (this.addressPhoneLocal || '').trim()) {
                        this.addressPhoneLocal = this.phoneLocal || '';
                    }
                    setTimeout(() => window.dispatchEvent(new CustomEvent('checkout-home-map-refresh')), 200);
                }
            },

            async saveNewAddress() {
                this.newAddressError = '';
                const form = this.$refs.checkoutForm;
                if (! form) return;
                const fd = new FormData(form);
                // Only send the fields the sync-address endpoint needs.
                const payload = new FormData();
                const keep = ['delivery_lat', 'delivery_lng', 'delivery_district_id', 'delivery_description',
                              'delivery_kind', 'delivery_title', 'delivery_pickup_type', 'building', 'zone_id'];
                keep.forEach(k => { if (fd.has(k)) payload.append(k, fd.get(k)); });
                // The sync-address endpoint still expects the field named `delivery_type`.
                // On the form it is `delivery_kind` to avoid colliding with the home/pickup radio.
                if (payload.has('delivery_kind')) {
                    payload.append('delivery_type', payload.get('delivery_kind'));
                    payload.delete('delivery_kind');
                }
                const phoneForAddress = this.addressPhone966() || this.fullPhone966();
                if (! phoneForAddress) {
                    this.newAddressError = "\u064a\u064f\u0631\u062c\u0649 \u0625\u062f\u062e\u0627\u0644 \u0631\u0642\u0645 \u0647\u0627\u062a\u0641 \u0644\u0647\u0630\u0627 \u0627\u0644\u0639\u0646\u0648\u0627\u0646.";
                    return;
                }
                payload.set('phone', phoneForAddress);
                if (! payload.get('delivery_lat') || ! payload.get('delivery_lng')) {
                    this.newAddressError = "\u064a\u064f\u0631\u062c\u0649 \u062a\u062d\u062f\u064a\u062f \u0627\u0644\u0645\u0648\u0642\u0639 \u0639\u0644\u0649 \u0627\u0644\u062e\u0631\u064a\u0637\u0629 \u0623\u0648\u0644\u0627\u064b.";
                    return;
                }
                if (! payload.get('delivery_district_id')) {
                    this.newAddressError = "\u064a\u064f\u0631\u062c\u0649 \u062a\u0623\u0643\u064a\u062f \u0627\u0644\u062d\u064a \u0639\u0644\u0649 \u0627\u0644\u062e\u0631\u064a\u0637\u0629.";
                    return;
                }
                if (! payload.get('zone_id')) {
                    this.newAddressError = "\u064a\u064f\u0631\u062c\u0649 \u0627\u062e\u062a\u064a\u0627\u0631 \u0627\u0644\u0645\u062f\u064a\u0646\u0629 \u0623\u0648\u0644\u0627\u064b.";
                    return;
                }
                this.savingNewAddress = true;
                try {
                    const res = await fetch('http://127.0.0.1:8083/checkout/sync-address', {
                        method: 'POST',
                        body: payload,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (! res.ok || ! data.success) {
                        const errs = data && data.errors && typeof data.errors === 'object'
                            ? Object.values(data.errors).flat().filter(Boolean)
                            : [];
                        this.newAddressError = errs[0] || data.message || "\u062a\u0639\u0630\u0651\u0631 \u062d\u0641\u0638 \u0627\u0644\u0639\u0646\u0648\u0627\u0646 \u0639\u0644\u0649 \u0627\u0644\u062e\u0627\u062f\u0645. \u064a\u0645\u0643\u0646\u0643 \u0645\u062a\u0627\u0628\u0639\u0629 \u0627\u0644\u0637\u0644\u0628.";
                        this.syncAddressError = this.newAddressError;
                        return;
                    }
                    this.syncAddressError = '';
                    await this.refreshCustomerFromServer();
                    if (data.data && data.data.id) {
                        let fresh = this.savedAddresses.find(a => String(a.id) === String(data.data.id));
                        if (!fresh) {
                            fresh = {
                                id: data.data.id,
                                latitude: payload.get('delivery_lat'),
                                longitude: payload.get('delivery_lng'),
                                city_id: payload.get('zone_id'),
                                line1: payload.get('delivery_description') || this.addressStreet || '',
                                description: payload.get('delivery_description') || this.addressStreet || '',
                                district_id: payload.get('delivery_district_id'),
                            };
                            this.savedAddresses = [fresh, ...this.savedAddresses];
                        }
                        this.applySavedAddress(fresh);
                    }
                    this.addingNewAddress = false;
                } catch (e) {
                    this.newAddressError = "\u062d\u062f\u062b \u062e\u0637\u0623. \u064a\u0631\u062c\u0649 \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062e\u0631\u0649.";
                } finally {
                    this.savingNewAddress = false;
                }
            },

            applySavedAddress(addr) {
                if (! addr || this.deliveryType !== 'home') {
                    return;
                }
                this.syncAddressError = '';
                this.selectedAddressId = addr.id ?? null;
                this.addingNewAddress = false;
                this.newAddressError = '';
                this.addressConfirmedForSync = true;
                this.moyasarError = '';
                const districtId = addr.district?.id ?? addr.district_id;
                // Resolve zone/city id across every known field shape the external
                // API might return, then fall back to matching the district against
                // the locally known zones list. Without a valid zone the server
                // can't compute the correct delivery fee and the Moyasar session
                // fails silently ? so we *must* have one populated.
                let cityId = addr.city?.id
                    ?? addr.city_id
                    ?? addr.zone_id
                    ?? addr.zone?.id
                    ?? addr.district?.city_id
                    ?? addr.district?.zone_id
                    ?? addr.district?.city?.id
                    ?? addr.district?.zone?.id
                    ?? '';
                if (! cityId && districtId && Array.isArray(this.zones)) {
                    const match = this.zones.find((z) => {
                        const districtList = z.districts || z.district_ids || [];
                        return districtList.some((d) => {
                            const id = typeof d === 'object' ? (d.id ?? d.district_id) : d;
                            return String(id) === String(districtId);
                        });
                    });
                    if (match) {
                        cityId = match.id;
                    }
                }
                // Last-resort fallback: infer zone from address text/title
                // when API does not return city/zone IDs in saved addresses.
                if (! cityId && Array.isArray(this.zones) && this.zones.length > 0) {
                    const text = String(
                        addr.description
                        || addr.line1
                        || addr.title
                        || addr.address
                        || ''
                    ).toLowerCase();
                    if (text) {
                        const matchByName = this.zones.find((z) => {
                            let zoneName = z?.name ?? '';
                            if (zoneName && typeof zoneName === 'object') {
                                zoneName = zoneName['ar'] || zoneName.en || Object.values(zoneName)[0] || '';
                            }
                            zoneName = String(zoneName || '').toLowerCase();
                            return zoneName && text.includes(zoneName);
                        });
                        if (matchByName) {
                            cityId = matchByName.id;
                        }
                    }
                }
                if (cityId) {
                    this.selectedZoneId = String(cityId);
                }
                let pickup = 'hand_it_to_me';
                const pt = addr.pickupType;
                if (pt && typeof pt === 'object') {
                    const id = String(pt.id ?? '').toLowerCase();
                    const tx = String(pt.text ?? '').toLowerCase();
                    if (id.includes('leave') || tx.includes('leave') || tx.includes('door')) {
                        pickup = 'leave_at_door';
                    }
                }
                window.dispatchEvent(new CustomEvent('gmp-external-address-apply', {
                    detail: {
                        latitude: addr.latitude,
                        longitude: addr.longitude,
                        description: addr.description || '',
                        district_id: districtId,
                        type: addr.type || 'residential',
                        title: addr.title || '',
                        pickup_type: pickup,
                    },
                }));
                window.dispatchEvent(new CustomEvent('address-selected', {
                    detail: {
                        id: addr.id ?? null,
                        latitude: addr.latitude,
                        longitude: addr.longitude,
                        city_id: cityId || null,
                        line1: addr.line1 || addr.description || '',
                        description: addr.description || '',
                        district_id: districtId || null,
                        building_num: addr.building_num ?? this.deliveryBuilding,
                        floor: addr.floor ?? this.deliveryFloor,
                        door: addr.door ?? this.deliveryDoor,
                    },
                }));
                if (addr.description) {
                    this.addressStreet = addr.description;
                }
                this.$nextTick(() => this.scheduleMoyasarRefresh());
            },

            selectSavedAddress(addr) {
                this.applySavedAddress(addr);
                this.$nextTick(() => {
                    this.$refs.paymentCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },

            async refreshCustomerFromServer() {
                try {
                    const res = await fetch('http://127.0.0.1:8083/checkout/customer-state', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const d = await res.json().catch(() => ({}));
                    if (! d.success) {
                        return;
                    }
                    this.savedAddresses = Array.isArray(d.addresses) ? d.addresses : [];
                    if (d.profile && d.profile.name && ! (this.customerName || '').trim()) {
                        this.customerName = String(d.profile.name);
                    }
                    // If already verified (page reload), determine name field visibility
                    if (this.phoneVerified) {
                        const hasName = !!(d.profile && d.profile.name);
                        const isNewUser = !!(d.is_continue);
                        this.isContinueUser = isNewUser;
                        this.showNameField = isNewUser || !hasName;
                        // Keep selection manual: user confirms address with "اختيار العنوان" button.
                    }
                } catch (e) {}
            },

            branchLabel(branch) {
                if (!branch) return '';
                if (typeof branch.name === 'object' && branch.name !== null) {
                    return branch.name['ar'] || branch.name['en'] || '';
                }
                return branch.name || '';
            },

            filterBranches() {
                const q = (this.branchSearch || '').trim().toLowerCase();
                if (!q) return this.branches;
                return this.branches.filter((b) => {
                    const name = this.branchLabel(b).toLowerCase();
                    const addr = (b.address || '').toLowerCase();
                    const phone = (b.phone || '').toLowerCase();
                    return name.includes(q) || addr.includes(q) || phone.includes(q);
                });
            },

            selectedBranchObj() {
                if (!this.selectedBranchId) return null;
                return this.branches.find((b) => String(b.id) === String(this.selectedBranchId)) || null;
            },

            openBranchPicker() {
                this.pickupPhase = 'list';
                this.branchSearch = '';
            },

            selectBranch(id) {
                this.selectedBranchId = String(id);
                this.pickupPhase = 'done';
                this.moyasarError = '';
                this.scheduleMoyasarRefresh();
            },

            editBranchSelection() {
                this.pickupPhase = 'list';
                this.branchSearch = '';
            },

            syncPickupPhase() {
                if (this.deliveryType !== 'pickup') return;
                if (this.selectedBranchId) {
                    this.pickupPhase = 'done';
                } else {
                    this.pickupPhase = 'cta';
                }
            },

            selectedDurationValue() {
                if (this.isPlanCheckout) {
                    return this.selectedPlanDurationId ? String(this.selectedPlanDurationId) : '';
                }
                return this.duration ? String(this.duration) : '';
            },

            hasStartDate() {
                if (! this.isPlanCheckout) {
                    return true;
                }
                const localValue = String(this.startDate || '').trim();
                if (localValue.length > 0) {
                    return true;
                }
                const inputValue = String(document.getElementById('start_date_input')?.value || '').trim();
                if (inputValue.length > 0) {
                    this.startDate = inputValue;
                    return true;
                }
                return false;
            },

            /** Home delivery: map pin confirmed + city + district (no saved-address id required). */
            inlineHomeAddressReady() {
                if (this.deliveryType !== 'home') {
                    return false;
                }
                if (! this.addressConfirmedForSync) {
                    return false;
                }
                if (this.syncAddressError) {
                    return false;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return false;
                }
                const lat = String(this.inlineMapLat || (form.querySelector('input[name="delivery_lat"]')?.value ?? '')).trim();
                const lng = String(this.inlineMapLng || (form.querySelector('input[name="delivery_lng"]')?.value ?? '')).trim();
                const district = String(this.inlineMapDistrictId || (form.querySelector('input[name="delivery_district_id"]')?.value ?? '')).trim();
                const zone = String(this.selectedZoneId || form.querySelector('select[name="zone_id"]')?.value || '').trim();

                return lat !== '' && lng !== '' && district !== '' && zone !== '';
            },

            inlineAddressHasRequiredFields() {
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return false;
                }
                const lat = String(form.querySelector('input[name="delivery_lat"]')?.value ?? '').trim();
                const lng = String(form.querySelector('input[name="delivery_lng"]')?.value ?? '').trim();
                const dist = String(form.querySelector('input[name="delivery_district_id"]')?.value ?? '').trim();
                const zone = String(this.selectedZoneId || form.querySelector('select[name="zone_id"]')?.value || '').trim();
                const street = String(this.addressStreet || '').trim();

                return lat !== '' && lng !== '' && dist !== '' && zone !== '' && street !== '';
            },

            async confirmInlineAddress() {
                if (! this.inlineAddressHasRequiredFields()) {
                    this.syncAddressError = "\u064a\u0631\u062c\u0649 \u0625\u0643\u0645\u0627\u0644 \u0627\u0644\u0645\u0648\u0642\u0639 \u0639\u0644\u0649 \u0627\u0644\u062e\u0631\u064a\u0637\u0629 \u0648\u0627\u0644\u062d\u064a \u0648\u0627\u0644\u0645\u062f\u064a\u0646\u0629 \u0648\u0639\u0646\u0648\u0627\u0646 \u0627\u0644\u0634\u0627\u0631\u0639 \u0642\u0628\u0644 \u0627\u0644\u062a\u0623\u0643\u064a\u062f.";

                    return;
                }
                if (! this.fullPhone966()) {
                    this.syncAddressError = "\u0623\u062f\u062e\u0644 \u0631\u0642\u0645 \u062c\u0648\u0627\u0644 \u0635\u0627\u0644\u062d (\u0628\u0639\u062f +966) \u0642\u0628\u0644 \u062d\u0641\u0638 \u0645\u0648\u0642\u0639 \u0627\u0644\u062e\u0631\u064a\u0637\u0629.";

                    return;
                }
                window.dispatchEvent(new CustomEvent('checkout-confirm-inline-address'));
                await this.$nextTick();
                if (! this.inlineAddressHasRequiredFields()) {
                    this.syncAddressError = "\u064a\u0631\u062c\u0649 \u0625\u0643\u0645\u0627\u0644 \u0627\u0644\u0645\u0648\u0642\u0639 \u0639\u0644\u0649 \u0627\u0644\u062e\u0631\u064a\u0637\u0629 \u0648\u0627\u0644\u062d\u064a \u0648\u0627\u0644\u0645\u062f\u064a\u0646\u0629 \u0648\u0639\u0646\u0648\u0627\u0646 \u0627\u0644\u0634\u0627\u0631\u0639 \u0642\u0628\u0644 \u0627\u0644\u062a\u0623\u0643\u064a\u062f.";

                    return;
                }
                await this.syncExternalAddress();
                if (! this.syncAddressError) {
                    this.addressConfirmedForSync = true;
                    this.scheduleMoyasarRefresh();
                }
            },

            deliveryReady() {
                if (this.deliveryType === 'pickup') {
                    return !!this.selectedBranchId;
                }
                if (this.selectedAddressId) {
                    return true;
                }

                return this.inlineHomeAddressReady();
            },

            canProceedToPayment() {
                const hasSelectedPlan = this.hasCartItems;
                const hasSelectedDuration = this.isPlanCheckout
                    ? (this.selectedDurationValue() !== '' || Number(this.cartDurationDaysHint || 0) > 0)
                    : this.selectedDurationValue() !== '';

                const homeBlockedBySync = this.deliveryType === 'home'
                    && this.syncAddressError
                    && ! this.selectedAddressId
                    && ! this.inlineHomeAddressReady();

                return this.deliveryReady()
                    && hasSelectedPlan
                    && hasSelectedDuration
                    && this.hasStartDate()
                    && ! homeBlockedBySync;
            },

            paymentBlockerMessage() {
                if (this.deliveryType === 'pickup') {
                    return "\u0627\u062e\u062a\u0631 \u0627\u0644\u0645\u062f\u0629 \u0648\u062a\u0627\u0631\u064a\u062e \u0627\u0644\u0628\u062f\u0627\u064a\u0629 \u0648\u0627\u0644\u0641\u0631\u0639 \u062d\u062a\u0649 \u064a\u062a\u0637\u0627\u0628\u0642 \u0627\u0644\u0645\u0628\u0644\u063a \u0642\u0628\u0644 \u0627\u0644\u062f\u0641\u0639";
                }
                if (this.syncAddressError && ! this.selectedAddressId && ! this.inlineHomeAddressReady()) {
                    return "\u0635\u062d\u062d \u062e\u0637\u0623 \u062d\u0641\u0638 \u0627\u0644\u0639\u0646\u0648\u0627\u0646 \u0623\u062f\u0646\u0627\u0647\u060c \u0623\u0648 \u0627\u062e\u062a\u0631 \u0639\u0646\u0648\u0627\u0646\u0627\u064b \u0645\u062d\u0641\u0648\u0638\u0627\u064b\u060c \u062b\u0645 \u0623\u0643\u0645\u0644 \u0627\u0644\u062f\u0641\u0639.";
                }
                return "\u0627\u062e\u062a\u0631 \u0627\u0644\u0645\u062f\u0629\u060c \u0627\u0644\u0645\u062f\u064a\u0646\u0629\u060c \u0648\u0627\u0644\u0639\u0646\u0648\u0627\u0646 \u0639\u0644\u0649 \u0627\u0644\u062e\u0631\u064a\u0637\u0629 \u062d\u062a\u0649 \u064a\u062a\u0637\u0627\u0628\u0642 \u0627\u0644\u0645\u0628\u0644\u063a \u0642\u0628\u0644 \u0627\u0644\u062f\u0641\u0639";
            },

            // Computed: subscription line total is fixed; meals use duration multiplier
            subtotal() {
                if (this.isPlanCheckout) {
                    return Math.round(this.baseSubtotal * 100) / 100;
                }
                const multiplier = this.durationMultipliers[this.duration] || 1;
                return Math.round(this.baseSubtotal * multiplier * 100) / 100;
            },

            // Computed: subtotal including VAT (same as subtotal ? price already includes VAT)
            subtotalInclVat() {
                return this.subtotal();
            },

            // Computed: delivery fee based on zone selection
            deliveryFee() {
                if (this.deliveryType !== 'home') return 0;
                if (this.selectedZoneId && this.zones.length > 0) {
                    const zone = this.zones.find(z => String(z.id) === String(this.selectedZoneId));
                    if (zone) {
                        const hasPlan = true;
                        return hasPlan
                            ? parseFloat(zone.subscription_delivery_price || 0)
                            : parseFloat(zone.order_delivery_price || 0);
                    }
                }
                return this.deliveryFeeAmount;
            },

            // Zone change handler
            onZoneChange() {
                // Recalculate when zone changes
            },

            // Computed: VAT extracted from VAT-inclusive price (for display only)
            // Formula: VAT = inclPrice - (inclPrice / (1 + vatRate))
            vatAmount() {
                const inclTotal = this.subtotal() + this.deliveryFee() - this.discount;
                return Math.round((inclTotal - (inclTotal / (1 + this.vatRate))) * 100) / 100;
            },

            // Computed: grand total (price already includes VAT, just add delivery and subtract discount)
            total() {
                return Math.round((this.subtotal() + this.deliveryFee() - this.discount) * 100) / 100;
            },

            // AJAX coupon validation
            async applyCoupon() {
                if (!this.couponCode.trim()) return;

                this.couponLoading = true;
                this.couponMessage = '';

                try {
                    const response = await fetch('http://127.0.0.1:8083/checkout/apply-coupon', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            code: this.couponCode.trim(),
                            subtotal: this.subtotal(),
                            identifier: this.fullPhone966() || '',
                            program_id: this.selectedPlanId || 0,
                            subscription_plan_id: parseInt(this.selectedSubscriptionPlanId || 0, 10) || 0,
                            plan_duration_id: parseInt(this.selectedPlanDurationId || 0, 10) || 0,
                            plan_calory_id: parseInt(this.selectedPlanCaloryId || 0, 10) || 0,
                        }),
                    });

                    const data = await response.json().catch(() => ({}));
                    let couponMsg = data.message ? String(data.message) : '';
                    if (data.errors && typeof data.errors === 'object') {
                        const flat = Object.values(data.errors).flat().filter(Boolean);
                        if (flat.length > 0) {
                            couponMsg = String(flat[0]);
                        }
                    }
                    this.couponMessage = couponMsg;

                    if (response.ok && data.valid) {
                        this.discount = data.discount;
                        this.couponApplied = true;
                    } else {
                        this.discount = 0;
                        this.couponApplied = false;
                    }
                } catch (error) {
                    this.couponMessage = "\u062d\u062f\u062b \u062e\u0637\u0623. \u064a\u0631\u062c\u0649 \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062e\u0631\u0649.";
                    this.discount = 0;
                    this.couponApplied = false;
                }

                this.couponLoading = false;
            },

            // Remove applied coupon
            removeCoupon() {
                this.discount = 0;
                this.couponApplied = false;
                this.couponCode = '';
                this.couponMessage = '';
            },

            // Re-validate coupon when duration changes (subtotal changes)
            async revalidateCoupon() {
                if (this.couponApplied && this.couponCode.trim()) {
                    await this.applyCoupon();
                }
            },

            // Open OTP modal and send code
            async openOtpModal() {
                if (!this.fullPhone966()) return;
                if (true) {
                    window.dispatchEvent(new CustomEvent('open-checkout-auth', {
                        detail: { phone: this.fullPhone966() },
                    }));
                    return;
                }
                this.otpMessage = '';
                this.otpDigits = ['', '', '', ''];
                this.otpModalOpen = true;
                if (!this.otpSent) {
                    await this.sendOtp();
                } else {
                    this.$nextTick(() => document.getElementById('otp-input-0')?.focus());
                }
            },

            // Send OTP
            async sendOtp() {
                if (!this.fullPhone966()) return;

                this.otpLoading = true;
                this.otpMessage = '';
                this.otpDigits = ['', '', '', ''];

                try {
                    const { headers, csrf } = this.buildCsrfHeaders();
                    const response = await fetch('http://127.0.0.1:8083/otp/send', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers,
                        body: JSON.stringify({ phone: this.fullPhone966(), _token: csrf }),
                    });

                    const data = await response.json();
                    this.otpMessage = data.otp
                        ? data.message + ' (Code: ' + data.otp + ')'
                        : data.message;

                    if (data.success) {
                        this.otpSent = true;
                        this.otpMessageType = 'success';
                        this.startCooldown();
                        this.$nextTick(() => document.getElementById('otp-input-0')?.focus());
                    } else {
                        this.otpMessageType = 'error';
                    }
                } catch (error) {
                    this.otpMessage = "\u062d\u062f\u062b \u062e\u0637\u0623. \u064a\u0631\u062c\u0649 \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062e\u0631\u0649.";
                    this.otpMessageType = 'error';
                }

                this.otpLoading = false;
            },

            // Verify OTP
            async verifyOtp() {
                const code = this.otpDigits.join('');
                if (code.length < 4) return;

                this.otpLoading = true;
                this.otpMessage = '';

                try {
                    const { headers, csrf } = this.buildCsrfHeaders();
                    const response = await fetch('http://127.0.0.1:8083/otp/verify', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers,
                        body: JSON.stringify({
                            phone: this.fullPhone966(),
                            otp: code,
                            device_id: this.deviceId,
                            _token: csrf,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.phoneVerified = true;
                        this.syncAddressError = '';
                        this.otpMessageType = 'success';
                        this.otpMessage = data.message;
                        this.savedAddresses = Array.isArray(data.addresses) ? data.addresses : [];
                        this.isContinueUser = !!data.is_continue;

                        if (data.profile && data.profile.name) {
                            this.customerName = String(data.profile.name);
                        }

                        // Name field: show for new users; hide when returning user already has name.
                        const isNewUser = !!data.is_continue;
                        if (!isNewUser && data.profile && data.profile.name) {
                            this.showNameField = false;
                        } else {
                            this.showNameField = true;
                        }

                        // Keep selection manual: user confirms address with "اختيار العنوان" button.
                        if (isNewUser) {
                            this.$nextTick(() => this.$refs.checkoutUserCard?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
                        }

                        setTimeout(() => { this.otpModalOpen = false; }, 800);
                        this.$nextTick(() => this.scheduleMoyasarRefresh());
                    } else {
                        this.otpMessageType = 'error';
                        this.otpMessage = data.message;
                        this.otpDigits = ['', '', '', ''];
                        this.$nextTick(() => document.getElementById('otp-input-0')?.focus());
                    }
                } catch (error) {
                    this.otpMessage = "\u062d\u062f\u062b \u062e\u0637\u0623. \u064a\u0631\u062c\u0649 \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062e\u0631\u0649.";
                    this.otpMessageType = 'error';
                }

                this.otpLoading = false;
            },

            // Handle single digit input ? auto-focus next
            handleOtpInput(event, index) {
                const val = event.target.value.replace(/\D/g, '');
                const digit = val.charAt(0) || '';
                // Force new array reference for Alpine reactivity
                const newDigits = [...this.otpDigits];
                newDigits[index] = digit;
                this.otpDigits = newDigits;
                event.target.value = digit;

                if (digit && index < 3) {
                    this.$nextTick(() => document.getElementById('otp-input-' + (index + 1))?.focus());
                }
                // Auto-submit when all 4 filled
                if (this.otpDigits.join('').length === 4) {
                    this.$nextTick(() => this.verifyOtp());
                }
            },

            // Handle backspace ? go to previous input
            handleOtpBackspace(event, index) {
                if (!this.otpDigits[index] && index > 0) {
                    this.$nextTick(() => document.getElementById('otp-input-' + (index - 1))?.focus());
                }
            },

            // Handle paste ? fill all digits
            handleOtpPaste(event) {
                event.preventDefault();
                const paste = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').substring(0, 4);
                const newDigits = ['', '', '', ''];
                for (let i = 0; i < 4; i++) {
                    newDigits[i] = paste.charAt(i) || '';
                }
                this.otpDigits = newDigits;
                const lastIndex = Math.min(paste.length, 4) - 1;
                if (lastIndex >= 0) {
                    this.$nextTick(() => document.getElementById('otp-input-' + lastIndex)?.focus());
                }
                if (paste.length === 4) {
                    this.$nextTick(() => this.verifyOtp());
                }
            },

            // Cooldown timer for resend
            startCooldown() {
                this.otpCooldown = 60;
                const timer = setInterval(() => {
                    this.otpCooldown--;
                    if (this.otpCooldown <= 0) clearInterval(timer);
                }, 1000);
            },

            // Form submission ? require phone verification
            submitForm(event) {
                if (!this.phoneVerified) {
                    // Open OTP modal so user can verify
                    this.openOtpModal();
                    return;
                }
                if (!this.canProceedToPayment()) {
                    this.moyasarError = "\u0627\u062e\u062a\u0631 \u0627\u0644\u0645\u062f\u062f\u0629\u060c \u0627\u0644\u0645\u062f\u064a\u0646\u0629\u060c \u0648\u0627\u0644\u0639\u0646\u0648\u0627\u0646 \u0639\u0644\u0649 \u0627\u0644\u062e\u0631\u064a\u0637\u0629 \u062d\u062a\u0649 \u064a\u062a\u0637\u0627\u0628\u0642 \u0627\u0644\u0645\u0628\u0644\u063a \u0642\u0628\u0644 \u0627\u0644\u062f\u0641\u0639.";
                    return;
                }
                event.target.submit();
            },

            scheduleMoyasarRefresh() {
                clearTimeout(this._moyasarTimer);
                this._moyasarTimer = setTimeout(() => {
                    const el = document.getElementById('moyasar-form-checkout');
                    if (! this.canProceedToPayment()) {
                        this.moyasarError = '';
                        this._moyasarFingerprint = '';
                        if (el) {
                            el.innerHTML = '';
                        }

                        return;
                    }
                    if (! this.phoneVerified) {
                        this.moyasarError = '';
                        this._moyasarFingerprint = '';
                        if (el) {
                            el.innerHTML = '';
                        }

                        return;
                    }
                    this.bootstrapMoyasar();
                }, 800);
            },

            buildMoyasarFingerprint(fd) {
                const keys = [
                    'phone', 'start_date', 'plan_duration_id', 'delivery_type',
                    'zone_id', 'selected_address_id', 'branch_id', 'coupon', 'promocode_name',
                ];

                return keys.map((k) => k + '=' + String(fd.get(k) || '')).join('&');
            },

            moyasarWidgetMounted() {
                const el = document.getElementById('moyasar-form-checkout');

                return !!(el && (el.querySelector('.mysr-form') || el.querySelector('form') || el.querySelector('iframe')));
            },

            async bootstrapMoyasarPreview() {
                if (this.phoneVerified) {
                    return;
                }
                const hasSdk = await this.waitForMoyasar();
                if (! hasSdk) {
                    this.moyasarError = "\u062a\u0639\u0630\u0631 \u062a\u062d\u0645\u064a\u0644 \u0646\u0645\u0648\u0630\u062c \u0627\u0644\u062f\u0641\u0639. \u062d\u062f\u0651\u062b \u0627\u0644\u0635\u0641\u062d\u0629 \u0623\u0648 \u062a\u062d\u0642\u0642 \u0645\u0646 \u0627\u0644\u0627\u062a\u0635\u0627\u0644.";

                    return;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return;
                }
                this.moyasarError = '';
                const fd = new FormData(form);
                fd.append('preview_only', '1');
                try {
                    const res = await fetch('http://127.0.0.1:8083/checkout/moyasar-session', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (this.phoneVerified) {
                        return;
                    }
                    if (! res.ok || ! data.success) {
                        this.moyasarError = data.message || "\u0627\u062e\u062a\u0631 \u0627\u0644\u0645\u062f\u062f\u0629\u060c \u0627\u0644\u0645\u062f\u064a\u0646\u0629\u060c \u0648\u0627\u0644\u0639\u0646\u0648\u0627\u0646 \u0639\u0644\u0649 \u0627\u0644\u062e\u0631\u064a\u0637\u0629 \u062d\u062a\u0649 \u064a\u062a\u0637\u0627\u0628\u0642 \u0627\u0644\u0645\u0628\u0644\u063a \u0642\u0628\u0644 \u0627\u0644\u062f\u0641\u0639.";
                        const el = document.getElementById('moyasar-form-checkout');
                        if (el) {
                            el.innerHTML = '';
                        }

                        return;
                    }
                    this.initMoyasarWidget(data);
                } catch (e) {
                    this.moyasarError = "\u062d\u062f\u062b \u062e\u0637\u0623. \u064a\u0631\u062c\u0649 \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062e\u0631\u0649.";
                }
            },

            waitForMoyasar(maxMs = 8000) {
                return new Promise((resolve) => {
                    if (typeof Moyasar !== 'undefined') {
                        resolve(true);

                        return;
                    }
                    const start = Date.now();
                    const tick = () => {
                        if (typeof Moyasar !== 'undefined') {
                            resolve(true);

                            return;
                        }
                        if (Date.now() - start >= maxMs) {
                            resolve(false);

                            return;
                        }
                        setTimeout(tick, 100);
                    };
                    tick();
                });
            },

            applyMinimumStartDate(minDate, options = {}) {
                const silent = options.silent === true;
                const normalized = String(minDate || '').trim();
                if (! normalized) {
                    return;
                }
                const input = document.getElementById('start_date_input');
                if (! input) {
                    return;
                }
                const picker = input._flatpickr;
                if (picker) {
                    picker.set('minDate', normalized);
                    if (! this.startDate || this.startDate < normalized) {
                        picker.setDate(normalized, true);
                    }
                } else {
                    input.value = normalized;
                }
                this.startDate = normalized;
                if (! silent) {
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            },

            async bootstrapMoyasar() {
                if (! this.phoneVerified) {
                    return;
                }
                const hasSdk = await this.waitForMoyasar();
                if (! hasSdk) {
                    this.moyasarError = "\u062a\u0639\u0630\u0631 \u062a\u062d\u0645\u064a\u0644 \u0646\u0645\u0648\u0630\u062c \u0627\u0644\u062f\u0641\u0639. \u062d\u062f\u0651\u062b \u0627\u0644\u0635\u0641\u062d\u0629 \u0623\u0648 \u062a\u062d\u0642\u0642 \u0645\u0646 \u0627\u0644\u0627\u062a\u0635\u0627\u0644.";

                    return;
                }
                const form = this.$refs.checkoutForm;
                if (! form) {
                    return;
                }
                const fd = new FormData(form);
                fd.set('selected_plan_id', String(this.selectedPlanId || ''));
                fd.set('selected_duration', this.selectedDurationValue());
                const fingerprint = this.buildMoyasarFingerprint(fd);
                if (fingerprint === this._moyasarFingerprint && this.moyasarWidgetMounted()) {
                    return;
                }
                const requestId = ++this._moyasarRequestId;
                this.moyasarError = '';
                try {
                    const res = await fetch('http://127.0.0.1:8083/checkout/moyasar-session', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });
                    if (requestId !== this._moyasarRequestId) {
                        return;
                    }
                    const data = await res.json().catch(() => ({}));
                    if (! this.phoneVerified) {
                        return;
                    }
                    if (! res.ok || ! data.success) {
                        if (data.errors && data.errors.start_date && data.errors.start_date[0]) {
                            this.moyasarError = data.errors.start_date[0];
                        } else {
                            this.moyasarError = data.message || "\u0627\u062e\u062a\u0631 \u0627\u0644\u0645\u062f\u062f\u0629\u060c \u0627\u0644\u0645\u062f\u064a\u0646\u0629\u060c \u0648\u0627\u0644\u0639\u0646\u0648\u0627\u0646 \u0639\u0644\u0649 \u0627\u0644\u062e\u0631\u064a\u0637\u0629 \u062d\u062a\u0649 \u064a\u062a\u0637\u0627\u0628\u0642 \u0627\u0644\u0645\u0628\u0644\u063a \u0642\u0628\u0644 \u0627\u0644\u062f\u0641\u0639.";
                        }
                        if (data.min_start_date && data.min_start_date !== this.lastAppliedMinStartDate) {
                            this.lastAppliedMinStartDate = data.min_start_date;
                            this.applyMinimumStartDate(data.min_start_date, { silent: true });
                            this._moyasarFingerprint = '';
                            await this.$nextTick();

                            return this.bootstrapMoyasar();
                        }
                        this._moyasarFingerprint = '';
                        const el = document.getElementById('moyasar-form-checkout');
                        if (el) {
                            el.innerHTML = '';
                        }

                        return;
                    }
                    if (data.adjusted_start_date) {
                        this.applyMinimumStartDate(data.adjusted_start_date, { silent: true });
                    }
                    this._moyasarFingerprint = fingerprint;
                    this.initMoyasarWidget(data);
                } catch (e) {
                    if (requestId === this._moyasarRequestId) {
                        this.moyasarError = "\u062d\u062f\u062b \u062e\u0637\u0623. \u064a\u0631\u062c\u0649 \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062e\u0631\u0649.";
                    }
                }
            },

            initMoyasarWidget(data) {
                const el = document.getElementById('moyasar-form-checkout');
                if (! el || typeof Moyasar === 'undefined') {
                    return;
                }
                const publishableKey = String(
                    data.publishable_key || ""                ).trim();
                if (! publishableKey || ! /^pk_(test|live)_[a-zA-Z0-9]+$/.test(publishableKey)) {
                    this.moyasarError = "\u0627\u0644\u062f\u0641\u0639 \u063a\u064a\u0631 \u0645\u0647\u064a\u0623 \u0639\u0644\u0649 \u0627\u0644\u0645\u0648\u0642\u0639. \u062a\u0648\u0627\u0635\u0644 \u0645\u0639 \u0627\u0644\u062f\u0639\u0645 \u0623\u0648 \u062d\u0627\u0648\u0644 \u0644\u0627\u062d\u0642\u0627\u064b.";

                    return;
                }
                el.innerHTML = '';
                let cb = (data.callback_url || '').trim();
                if (data.order_number) {
                    const sep = cb.includes('?') ? '&' : '?';
                    cb = cb + sep + 'order=' + encodeURIComponent(data.order_number);
                }
                Moyasar.init({
                    element: '#moyasar-form-checkout',
                    amount: data.amount_halalas,
                    currency: data.currency || 'SAR',
                    description: data.description || '',
                    publishable_api_key: publishableKey,
                    callback_url: cb,
                    methods: ['creditcard', 'applepay', 'stcpay'],
                    metadata: data.metadata || {},
                    supported_networks: ['visa', 'mastercard', 'mada'],
                    apple_pay: {
                        country: 'SA',
                        label: 'Diet Watchers',
                        validate_merchant_url: 'https://api.moyasar.com/v1/applepay/initiate',
                    },
                    language: 'ar',
                });
            },

            // Watch for duration changes to re-validate coupon
            async init() {
                window.addEventListener('checkout-auth-success', async (event) => {
                    const detail = event.detail || {};
                    this.phoneVerified = true;
                    this.syncAddressError = '';
                    if (detail.phone && typeof window.dwSaudiPhoneDigits === 'function') {
                        this.phoneLocal = window.dwSaudiPhoneDigits(String(detail.phone));
                    }
                    this.savedAddresses = Array.isArray(detail.addresses) ? detail.addresses : [];
                    await this.refreshCustomerFromServer();
                    this.isContinueUser = !!detail.isContinue;
                    if (detail.profile && detail.profile.name) {
                        this.customerName = String(detail.profile.name);
                    }
                    this.showNameField = this.isContinueUser || ! (this.customerName || '').trim();
                    this.$nextTick(() => this.scheduleMoyasarRefresh());
                });

                if (this.isPlanCheckout) {
                    try {
                        await this.hydratePlanDurations();
                    } catch (e) {
                        this.durationsLoading = false;
                    }
                } else {
                    this.durationsLoading = false;
                }
                this.$watch('selectedPlanDurationId', (id) => {
                    if (! this.isPlanCheckout || id === undefined || id === null) {
                        return;
                    }
                    const p = this.planDurationPrices[String(id)];
                    if (p != null) {
                        this.baseSubtotal = Math.round(p * 100) / 100;
                        this.revalidateCoupon();
                    }
                    this.scheduleMoyasarRefresh();
                });
                this.$watch('duration', () => this.revalidateCoupon());
                this.$watch('duration', () => this.scheduleMoyasarRefresh());
                this.$watch('selectedZoneId', () => this.scheduleMoyasarRefresh());
                this.$watch('selectedAddressId', () => this.scheduleMoyasarRefresh());
                this.$watch('selectedBranchId', () => this.scheduleMoyasarRefresh());
                this.$watch('deliveryType', (v) => {
                    if (v === 'pickup') {
                        this.syncPickupPhase();
                    }
                    if (v === 'home') {
                        setTimeout(() => window.dispatchEvent(new CustomEvent('checkout-home-map-refresh')), 300);
                    }
                    this.scheduleMoyasarRefresh();
                });
                this.$watch('couponApplied', () => this.scheduleMoyasarRefresh());
                if (this.deliveryType === 'home') {
                    setTimeout(() => window.dispatchEvent(new CustomEvent('checkout-home-map-refresh')), 500);
                }

                fetch('http://127.0.0.1:8083/api/branches')
                    .then(r => r.json())
                    .then(data => {
                        this.branches = data;
                        this.branchesLoading = false;
                        if (this.deliveryType === 'pickup' && !this.selectedBranchId && this.branches.length === 1) {
                            this.selectBranch(this.branches[0].id);
                        }
                        this.syncPickupPhase();
                    })
                    .catch(() => { this.branches = []; this.branchesLoading = false; });

                if (this.phoneVerified) {
                    await this.refreshCustomerFromServer();
                }
                const startDateInput = document.getElementById('start_date_input');
                if (startDateInput) {
                    this.startDate = String(startDateInput.value || this.startDate || '');
                    startDateInput.addEventListener('change', () => {
                        this.startDate = String(startDateInput.value || '');
                        this.scheduleMoyasarRefresh();
                    });
                }
                this.scheduleMoyasarRefresh();
            }
        }
    }

    window.checkoutPage = checkoutPage;