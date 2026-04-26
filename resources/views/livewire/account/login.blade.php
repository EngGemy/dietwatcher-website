<div class="acc-card px-6 py-8 md:px-10 md:py-10" wire:poll.1s="">
    <div class="mb-6 text-center">
        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-lg shadow-blue-500/30">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('account.login_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
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
        <form wire:submit.prevent="sendOtp" class="space-y-4">
            <div>
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
        <form wire:submit.prevent="verifyOtp" class="space-y-4">
            <div>
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

            <div class="flex items-center justify-between text-sm">
                <button type="button" wire:click="resetPhone" class="acc-btn--ghost">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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

    <p class="acc-meta-link mt-6 text-center">
        {{ __('account.new_here') }}
        <a href="{{ route('meal-plans.index') }}">{{ __('account.start_plan') }}</a>
    </p>
</div>
