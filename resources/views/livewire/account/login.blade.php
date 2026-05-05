<div class="acc-card acc-login" wire:poll.1s="">
    <style>
        .acc-login { padding: 1.5rem 1.1rem; }
        .acc-login__head { margin-bottom: 1.2rem; text-align: center; }
        .acc-login__icon {
            width: 56px; height: 56px; margin-inline: auto; margin-bottom: .7rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 16px;
            color: #fff;
            background: linear-gradient(135deg, #3B82F6, #1D4ED8);
            box-shadow: 0 14px 32px -14px rgba(37,99,235,.75);
        }
        .acc-login__icon svg { width: 28px; height: 28px; }
        .acc-login__title { margin: 0; font-size: 1.7rem; line-height: 1.2; font-weight: 800; color: #111827; }
        .acc-login__hint { margin-top: .35rem; font-size: .92rem; color: #6B7280; }
        .acc-login__form { display: flex; flex-direction: column; gap: .95rem; }
        .acc-login__row { display: flex; flex-direction: column; gap: .45rem; }
        .acc-login__actions { display:flex; align-items:center; justify-content:space-between; gap:.8rem; font-size:.88rem; flex-wrap: wrap; }
        .acc-login__footer { margin-top: 1.1rem; text-align:center; }
        @media (min-width: 640px) {
            .acc-login { padding: 2rem 2.25rem; }
        }
    </style>

    <div class="acc-login__head">
        <div class="acc-login__icon">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
        </div>
        <h1 class="acc-login__title">{{ __('account.login_title') }}</h1>
        <p class="acc-login__hint">
            @if(! $otpSent)
                {{ __('account.login_phone_hint') }}
            @else
                {{ __('account.login_otp_hint', ['phone' => $phone]) }}
            @endif
        </p>
    </div>

    @if($error)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            {{ $error }}
        </div>
    @elseif($status && $otpSent)
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ $status }}
        </div>
    @endif

    @if(! $otpSent)
        <form wire:submit.prevent="sendOtp" class="acc-login__form">
            <div class="acc-login__row">
                <label for="phone" class="acc-label">{{ __('account.phone_label') }}</label>
                <input type="tel" id="phone" class="acc-input" dir="ltr" autocomplete="tel" inputmode="tel"
                       placeholder="+9665xxxxxxxx"
                       wire:model.live.debounce.200ms="phone"
                       required />
                @error('phone') <p class="acc-err">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="acc-btn" wire:loading.attr="disabled" wire:target="sendOtp">
                <span wire:loading.remove wire:target="sendOtp">{{ __('account.send_otp') }}</span>
                <span wire:loading wire:target="sendOtp">{{ __('Saving...') }}</span>
            </button>
        </form>
    @else
        <form wire:submit.prevent="verifyOtp" class="acc-login__form">
            <div class="acc-login__row">
                <label for="code" class="acc-label">{{ __('account.otp_label') }}</label>
                <input type="text" id="code" class="acc-input acc-otp" dir="ltr" autocomplete="one-time-code"
                       inputmode="numeric" maxlength="4"
                       placeholder="••••"
                       wire:model.live="code"
                       required />
                @error('code') <p class="acc-err">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="acc-btn" wire:loading.attr="disabled" wire:target="verifyOtp">
                <span wire:loading.remove wire:target="verifyOtp">{{ __('account.verify_and_enter') }}</span>
                <span wire:loading wire:target="verifyOtp">{{ __('Verifying...') }}</span>
            </button>

            <div class="acc-login__actions">
                <button type="button" wire:click="resetPhone" class="acc-btn--ghost">
                    <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('account.change_phone') }}
                </button>
                <button type="button"
                        wire:click="sendOtp"
                        @disabled($resendCooldown > 0)
                        class="acc-btn--ghost {{ $resendCooldown > 0 ? 'opacity-40 cursor-not-allowed' : '' }}">
                    @if($resendCooldown > 0)
                        {{ __('otp.wait_seconds', ['seconds' => $resendCooldown]) }}
                    @else
                        {{ __('account.resend_otp') }}
                    @endif
                </button>
            </div>
        </form>

        {{-- Cooldown ticker --}}
        <div x-data="{ left: @entangle('resendCooldown') }"
             x-init="$nextTick(() => {
                const tick = () => {
                    if (left > 0) { left = left - 1; setTimeout(tick, 1000); }
                };
                tick();
             })"
             x-show="false">
        </div>
    @endif

    <p class="acc-meta-link acc-login__footer">
        {{ __('account.new_here') }}
        <a href="{{ route('meal-plans.index') }}">{{ __('account.start_plan') }}</a>
    </p>
</div>
