<template x-teleport="body">
    <div
        x-data="checkoutAuthModalMock()"
        x-init="init()"
        x-show="isOpen"
        x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="otp-overlay"
            style="display:none;"
            @keydown.escape.window="if (!isBusy()) closeModal()"
        >
            <div class="otp-overlay__backdrop" @click="if (!isBusy()) closeModal()"></div>

            <div
                class="otp-modal"
                x-show="isOpen"
                x-transition:enter="transition ease-out duration-300 delay-75"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                @click.stop
            >
                <button type="button" class="otp-modal__close" @click="closeModal()" :disabled="isBusy()">
                    <span class="sr-only">{{ __('checkout.auth.close') }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 class="otp-modal__title">{{ __('checkout.auth.modal.title') }}</h3>

                <div class="mb-4 flex items-center justify-center gap-2">
                    <template x-for="item in visibleSteps()" :key="item.key">
                        <span
                            class="inline-flex min-w-7 items-center justify-center rounded-full border px-2 py-0.5 text-xs font-semibold"
                            :class="isStepDone(item.key) ? 'border-blue text-blue' : 'border-gray-300 text-gray-500'"
                            x-text="item.label"
                        ></span>
                    </template>
                </div>

                <template x-if="step === 'otp_1' || step === 'sending_otp_1' || step === 'verifying_1'">
                    <div>
                        <p class="otp-modal__subtitle" x-text="otpSubtitle()"></p>
                        <p class="otp-modal__phone" dir="ltr" x-text="mobile"></p>

                        <div class="otp-modal__digits" dir="ltr">
                            <template x-for="idx in otpIndices" :key="'otp1-' + idx">
                                <input
                                    type="text"
                                    maxlength="1"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    autocomplete="one-time-code"
                                    class="otp-digit"
                                    placeholder="&middot;"
                                    :value="otp1[idx]"
                                    @input="setOtpDigit('otp1', idx, $event)"
                                    @keydown.backspace="handleBackspace('otp1', idx, $event)"
                                    :disabled="isBusy()"
                                    :class="{ 'otp-digit--filled': otp1[idx] }"
                                />
                            </template>
                        </div>

                        <div class="mt-4">
                            <button type="button" class="otp-modal__btn" @click="verifyOtp1()" :disabled="isBusy() || otp1.join('').length < otpLength">
                                {{ __('checkout.auth.modal.step.otp') }}
                            </button>
                            <p x-show="errors.otp" x-text="errors.otp" class="mt-2 text-sm text-red-600"></p>
                            <p x-show="errors.general" x-text="errors.general" class="mt-2 text-sm text-red-600"></p>
                        </div>

                        <p class="otp-modal__resend mt-4">
                            <button type="button" class="otp-modal__resend-btn" @click="sendOtp('otp_1')" :disabled="isBusy() || resendIn > 0">
                                <span x-text="resendLabel()"></span>
                            </button>
                        </p>
                    </div>
                </template>

                <template x-if="step === 'register' || step === 'registering'">
                    <div class="space-y-3 text-start">
                        <p class="otp-modal__subtitle">{{ __('checkout.auth.register.subtitle') }}</p>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('account.phone') }}</label>
                            <input type="text" class="form-control" :value="mobile" readonly dir="ltr" />
                            <button type="button" class="mt-2 text-sm font-semibold text-blue" @click="changeNumber()">
                                {{ __('checkout.auth.changeNumber') }}
                            </button>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('checkout.auth.register.name.label') }}</label>
                            <input type="text" class="form-control" :class="errors.name ? 'border-red-500' : ''" :placeholder="'{{ __('checkout.auth.register.name.placeholder') }}'" x-model="registerForm.name" :disabled="isBusy()" />
                            <p x-show="errors.name" class="mt-1 text-xs text-red-600" x-text="fieldError('name')"></p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('checkout.auth.register.email.label') }}</label>
                            <input type="email" class="form-control" :class="errors.email ? 'border-red-500' : ''" :placeholder="'{{ __('checkout.auth.register.email.placeholder') }}'" x-model="registerForm.email" :disabled="isBusy()" dir="ltr" />
                            <p x-show="errors.email" class="mt-1 text-xs text-red-600" x-text="fieldError('email')"></p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('checkout.auth.register.gender.label') }}</label>
                            <div class="choice-group choice-group--two">
                                <div class="choice-group__item">
                                    <input type="radio" id="auth-gender-male" name="auth_register_gender" class="choice-group__input" value="male" x-model="registerForm.gender" :disabled="isBusy()" />
                                    <label for="auth-gender-male" class="choice-group__label">
                                        <span class="choice-group__title">{{ __('checkout.auth.register.gender.male') }}</span>
                                    </label>
                                </div>
                                <div class="choice-group__item">
                                    <input type="radio" id="auth-gender-female" name="auth_register_gender" class="choice-group__input" value="female" x-model="registerForm.gender" :disabled="isBusy()" />
                                    <label for="auth-gender-female" class="choice-group__label">
                                        <span class="choice-group__title">{{ __('checkout.auth.register.gender.female') }}</span>
                                    </label>
                                </div>
                            </div>
                            <p x-show="errors.gender" class="mt-1 text-xs text-red-600" x-text="fieldError('gender')"></p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('checkout.auth.register.avatar.label') }}</label>
                            <input type="file" class="form-control" accept="image/*" @change="setAvatar($event)" :disabled="isBusy()" />
                            <p class="mt-2 text-xs text-gray-500" x-show="registerForm.avatarName" x-text="registerForm.avatarName"></p>
                        </div>

                        <div>
                            <button type="button" class="otp-modal__btn" @click="submitRegister()" :disabled="isBusy()">
                                {{ __('checkout.auth.register.submit') }}
                            </button>
                            <p x-show="errors.general" x-text="errors.general" class="mt-2 text-sm text-red-600"></p>
                        </div>
                    </div>
                </template>

                <template x-if="step === 'otp_2' || step === 'sending_otp_2' || step === 'verifying_2'">
                    <div>
                        <p class="otp-modal__subtitle" x-text="otpSubtitle()"></p>
                        <p class="otp-modal__phone" dir="ltr" x-text="mobile"></p>

                        <div class="otp-modal__digits" dir="ltr">
                            <template x-for="idx in otpIndices" :key="'otp2-' + idx">
                                <input
                                    type="text"
                                    maxlength="1"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    autocomplete="one-time-code"
                                    class="otp-digit"
                                    placeholder="&middot;"
                                    :value="otp2[idx]"
                                    @input="setOtpDigit('otp2', idx, $event)"
                                    @keydown.backspace="handleBackspace('otp2', idx, $event)"
                                    :disabled="isBusy()"
                                    :class="{ 'otp-digit--filled': otp2[idx] }"
                                />
                            </template>
                        </div>

                        <div class="mt-4">
                            <button type="button" class="otp-modal__btn" @click="verifyOtp2()" :disabled="isBusy() || otp2.join('').length < otpLength">
                                {{ __('checkout.auth.modal.step.confirm') }}
                            </button>
                            <p x-show="errors.otp" x-text="errors.otp" class="mt-2 text-sm text-red-600"></p>
                            <p x-show="errors.general" x-text="errors.general" class="mt-2 text-sm text-red-600"></p>
                        </div>

                        <p class="otp-modal__resend mt-4">
                            <button type="button" class="otp-modal__resend-btn" @click="sendOtp('otp_2')" :disabled="isBusy() || resendIn > 0">
                                <span x-text="resendLabel()"></span>
                            </button>
                        </p>
                    </div>
                </template>
            </div>
    </div>
</template>

@push('scripts')
<script>
if (typeof window.checkoutAuthModalMock === 'undefined') {
    window.checkoutAuthModalMock = function () {
        return {
            isOpen: false,
            step: 'idle',
            mobile: '',
            otpLength: 4,
            otp1: ['', '', '', ''],
            otp2: ['', '', '', ''],
            errors: {},
            needsRegistrationFlow: false,
            get otpIndices() {
                return Array.from({ length: this.otpLength }, (_, i) => i);
            },
            resendIn: 0,
            registerForm: {
                name: '',
                email: '',
                gender: '',
                avatar: null,
                avatarName: '',
            },
            timer: null,
            i18nResend: @json(__('checkout.auth.otp.resend')),
            i18nResendIn: @json(__('checkout.auth.otp.resendIn', ['seconds' => ':seconds'])),
            i18nOtpSubtitle: @json(__('checkout.auth.otp.subtitle', ['mobile' => ':mobile'])),
            i18n: {
                'errors.network': @json(__('checkout.auth.errors.network')),
                'errors.mobileTaken': @json(__('checkout.auth.errors.mobileTaken')),
                'errors.emailTaken': @json(__('checkout.auth.errors.emailTaken')),
                'errors.serverError': @json(__('checkout.auth.errors.serverError')),
                'otp.invalid': @json(__('checkout.auth.otp.invalid')),
                'errors.required': @json(__('validation.required')),
            },
            init() {
                this.mobile = this.getPhoneFromCheckout();
                window.addEventListener('open-checkout-auth', (event) => this.openModal(event.detail?.phone || ''));
            },
            getPhoneFromCheckout() {
                const el = document.querySelector('input[name="phone"]');
                return el && el.value ? String(el.value) : '';
            },
            otpSubtitle() {
                return this.i18nOtpSubtitle.replace(':mobile', this.mobile || ':mobile');
            },
            resendLabel() {
                if (this.resendIn > 0) {
                    return this.i18nResendIn.replace(':seconds', this.resendIn);
                }
                return this.i18nResend;
            },
            isBusy() {
                return ['sending_otp_1', 'verifying_1', 'registering', 'sending_otp_2', 'verifying_2'].includes(this.step);
            },
            visibleSteps() {
                const stepOtp = { key: 'otp_1', label: @json(__('checkout.auth.modal.step.otp')) };
                if (! this.needsRegistrationFlow && ! ['register', 'registering', 'sending_otp_2', 'otp_2', 'verifying_2'].includes(this.step)) {
                    return [stepOtp];
                }
                return [
                    stepOtp,
                    { key: 'register', label: @json(__('checkout.auth.modal.step.register')) },
                    { key: 'otp_2', label: @json(__('checkout.auth.modal.step.confirm')) },
                ];
            },
            isStepDone(key) {
                if (key === 'otp_1') return ['otp_1', 'verifying_1', 'register', 'registering', 'sending_otp_2', 'otp_2', 'verifying_2', 'authenticated'].includes(this.step);
                if (key === 'register') return ['register', 'registering', 'sending_otp_2', 'otp_2', 'verifying_2', 'authenticated'].includes(this.step);
                if (key === 'otp_2') return ['otp_2', 'verifying_2', 'authenticated'].includes(this.step);
                return false;
            },
            openModal(forcedPhone = '') {
                this.isOpen = true;
                this.step = 'sending_otp_1';
                this.errors = {};
                this.needsRegistrationFlow = false;
                this.mobile = forcedPhone ? String(forcedPhone).trim() : this.getPhoneFromCheckout();
                this.otp1 = ['', '', '', ''];
                this.otp2 = ['', '', '', ''];
                this.sendOtp('otp_1');
            },
            closeModal() {
                this.isOpen = false;
                this.step = 'idle';
                this.stopResend();
            },
            changeNumber() {
                this.needsRegistrationFlow = false;
                this.errors = {};
                this.otp1 = ['', '', '', ''];
                this.otp2 = ['', '', '', ''];
                this.step = 'otp_1';
            },
            csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '';
            },
            buildHeaders() {
                return {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'Accept': 'application/json',
                };
            },
            deviceId() {
                try {
                    let v = localStorage.getItem('dw_checkout_device_id');
                    if (! v && typeof crypto !== 'undefined' && crypto.randomUUID) {
                        v = 'web-' + crypto.randomUUID();
                        localStorage.setItem('dw_checkout_device_id', v);
                    }
                    return v || 'web-checkout-device';
                } catch (e) {
                    return 'web-checkout-device';
                }
            },
            t(key) {
                return this.i18n?.[key] || key;
            },
            fieldError(field) {
                const val = this.errors[field];
                if (! val) return '';
                if (Array.isArray(val)) {
                    const first = String(val[0] || '');
                    return first === 'required' ? this.t('errors.required') : first;
                }
                return String(val);
            },
            onAuthenticated(data) {
                window.dispatchEvent(new CustomEvent('checkout-auth-success', {
                    detail: {
                        profile: data.profile || {},
                        addresses: data.addresses || [],
                        isContinue: !!data.is_continue,
                    },
                }));
            },
            setAvatar(event) {
                const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                this.registerForm.avatar = file;
                this.registerForm.avatarName = file ? file.name : '';
            },
            setOtpDigit(which, idx, event) {
                const value = String(event.target.value || '').replace(/[^0-9]/g, '').slice(-1);
                this[which][idx] = value;
                event.target.value = value;
                if (value && idx < this.otpLength - 1) {
                    const next = event.target.parentElement.querySelectorAll('input')[idx + 1];
                    if (next) next.focus();
                }
            },
            handleBackspace(which, idx, event) {
                if (this[which][idx]) {
                    this[which][idx] = '';
                    event.target.value = '';
                    return;
                }
                if (idx > 0) {
                    const prev = event.target.parentElement.querySelectorAll('input')[idx - 1];
                    if (prev) prev.focus();
                }
            },
            startResend(seconds) {
                this.stopResend();
                this.resendIn = seconds;
                this.timer = setInterval(() => {
                    if (this.resendIn > 0) this.resendIn -= 1;
                }, 1000);
            },
            stopResend() {
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },
            async sendOtp(stepKey) {
                const sendingState = stepKey === 'otp_1' ? 'sending_otp_1' : 'sending_otp_2';
                const otpState = stepKey;
                this.step = sendingState;
                this.errors = {};
                try {
                    const res = await fetch('{{ route('otp.send') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: this.buildHeaders(),
                        body: JSON.stringify({ phone: this.mobile, _token: this.csrfToken() }),
                    });
                    const data = await res.json();
                    if (data.success || data.ok) {
                        this.startResend(60);
                        this.step = otpState;
                        this[stepKey === 'otp_1' ? 'otp1' : 'otp2'] = ['', '', '', ''];
                    } else {
                        this.errors.general = data.message || this.t('errors.network');
                        this.step = otpState;
                    }
                } catch (e) {
                    this.errors.general = this.t('errors.network');
                    this.step = otpState;
                }
            },
            async verifyOtp1() {
                this.step = 'verifying_1';
                this.errors = {};
                try {
                    const res = await fetch('{{ route('otp.verify') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: this.buildHeaders(),
                        body: JSON.stringify({
                            phone: this.mobile,
                            otp: this.otp1.join(''),
                            device_id: this.deviceId(),
                            _token: this.csrfToken(),
                        }),
                    });
                    const data = await res.json();
                    if (data.ok && data.needs_registration === true) {
                        this.needsRegistrationFlow = true;
                        this.step = 'register';
                    } else if (data.ok && !data.needs_registration) {
                        this.step = 'authenticated';
                        this.onAuthenticated(data);
                        this.closeModal();
                    } else {
                        this.errors.otp = data.message || this.t('otp.invalid');
                        this.otp1 = ['', '', '', ''];
                        this.step = 'otp_1';
                    }
                } catch (e) {
                    this.errors.general = this.t('errors.network');
                    this.step = 'otp_1';
                }
            },
            async submitRegister() {
                this.errors = {};
                if (! String(this.registerForm.name || '').trim()) this.errors.name = ['required'];
                if (! String(this.registerForm.email || '').trim()) this.errors.email = ['required'];
                if (! this.registerForm.gender) this.errors.gender = ['required'];
                if (Object.keys(this.errors).length > 0) return;

                this.step = 'registering';
                try {
                    const fd = new FormData();
                    fd.append('name', String(this.registerForm.name || '').trim());
                    fd.append('email', String(this.registerForm.email || '').trim());
                    fd.append('gender', this.registerForm.gender);
                    if (this.registerForm.avatar) {
                        fd.append('avatar', this.registerForm.avatar);
                    }
                    fd.append('_token', this.csrfToken());

                    const res = await fetch('{{ route('otp.register') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });
                    const data = await res.json();
                    const inferRegisterError = (payload) => {
                        const out = { type: '', message: '', fieldErrors: {} };
                        const p = payload && typeof payload === 'object' ? payload : {};
                        const errors = p.errors && typeof p.errors === 'object' ? p.errors : {};
                        const errCode = String(p.error || '').toLowerCase();
                        const message = String(p.message || '');
                        const msgLower = message.toLowerCase();

                        // 1) Explicit backend error code
                        if (errCode === 'mobile_taken') out.type = 'mobile_taken';
                        if (errCode === 'email_taken') out.type = 'email_taken';
                        if (errCode === 'validation_error') out.type = 'validation_error';

                        // 2) Structured field errors
                        if (!out.type) {
                            if (errors.mobile || errors.phone) out.type = 'mobile_taken';
                            else if (errors.email) out.type = 'email_taken';
                            else if (Object.keys(errors).length > 0) out.type = 'validation_error';
                        }

                        // 3) Message text fallback (EN/AR)
                        if (!out.type) {
                            const hasMobileWord = msgLower.includes('mobile') || msgLower.includes('phone') || message.includes('الجوال') || message.includes('رقم');
                            const hasEmailWord = msgLower.includes('email') || message.includes('البريد');
                            if (hasMobileWord) out.type = 'mobile_taken';
                            else if (hasEmailWord) out.type = 'email_taken';
                        }

                        out.message = message;
                        out.fieldErrors = errors;
                        return out;
                    };
                    if (data.ok) {
                        this.startResend(60);
                        this.otp2 = ['', '', '', ''];
                        this.step = 'otp_2';
                    } else {
                        const inferred = inferRegisterError(data);
                        if (inferred.type === 'mobile_taken') {
                            this.errors.general = this.t('errors.mobileTaken');
                        } else if (inferred.type === 'email_taken') {
                            this.errors.email = [this.t('errors.emailTaken')];
                        } else if (inferred.type === 'validation_error') {
                            this.errors = inferred.fieldErrors || {};
                            if (Object.keys(this.errors).length === 0) {
                                this.errors.general = inferred.message || this.t('errors.required');
                            }
                        } else {
                            this.errors.general = inferred.message || this.t('errors.serverError');
                        }
                        this.step = 'register';
                    }
                } catch (e) {
                    this.errors.general = this.t('errors.network');
                    this.step = 'register';
                }
            },
            async verifyOtp2() {
                this.step = 'verifying_2';
                this.errors = {};
                try {
                    const res = await fetch('{{ route('otp.verify') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: this.buildHeaders(),
                        body: JSON.stringify({
                            phone: this.mobile,
                            otp: this.otp2.join(''),
                            device_id: this.deviceId(),
                            _token: this.csrfToken(),
                        }),
                    });
                    const data = await res.json();
                    if (data.ok && ! data.needs_registration) {
                        this.step = 'authenticated';
                        this.onAuthenticated(data);
                        this.closeModal();
                    } else {
                        this.errors.otp = data.message || this.t('otp.invalid');
                        this.otp2 = ['', '', '', ''];
                        this.step = 'otp_2';
                    }
                } catch (e) {
                    this.errors.general = this.t('errors.network');
                    this.step = 'otp_2';
                }
            },
        };
    };
}
</script>
@endpush
