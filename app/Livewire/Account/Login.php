<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Services\ApiAuthService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Phone + OTP login for /account. Uses the external API directly
 * when EXTERNAL_API_URL is configured.
 */
#[Layout('layouts.account-bare')]
#[Title('تسجيل الدخول')]
class Login extends Component
{
    public string $phone = '';

    public string $code = '';

    public bool $otpSent = false;

    public int $resendCooldown = 0;

    public string $error = '';

    public string $status = '';

    public function sendOtp(ApiAuthService $apiAuth): void
    {
        $this->error = '';
        $this->status = '';

        $this->validate([
            'phone' => ['required', 'string', 'regex:/^\+?\d{8,15}$/'],
        ], [
            'phone.regex' => __('account.phone_invalid'),
        ]);

        $cooldownKey = 'otp_sent_at_' . md5($this->phone);
        $last = (int) session($cooldownKey, 0);
        if ($last && (time() - $last) < 60) {
            $this->resendCooldown = 60 - (time() - $last);
            $this->error = __('otp.wait_seconds', ['seconds' => $this->resendCooldown]);

            return;
        }

        $result = $apiAuth->sendOtp($this->phone);

        if (! ($result['_http_ok'] ?? false)) {
            $this->error = (string) ($result['message'] ?? __('otp.send_failed'));

            return;
        }

        session([
            $cooldownKey => time(),
            'account_login_phone' => $this->phone,
        ]);

        $this->otpSent = true;
        $this->resendCooldown = 60;
        $this->status = __('otp.code_sent');

        // In non-production the API echoes the OTP — prefill for easier testing.
        if (! app()->isProduction() && isset($result['otp'])) {
            $this->code = (string) $result['otp'];
        }
    }

    public function verifyOtp(ApiAuthService $apiAuth): void
    {
        $this->error = '';
        $this->status = '';

        $this->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'string', 'size:4'],
        ]);

        $deviceId = (string) session('account_device_id', '');
        if ($deviceId === '') {
            $deviceId = 'web-account-' . Str::uuid()->toString();
            session(['account_device_id' => $deviceId]);
        }

        $result = $apiAuth->verifyOtp($this->phone, $this->code, $deviceId);

        if (! ($result['_http_ok'] ?? false)) {
            $this->error = (string) ($result['message'] ?? __('otp.invalid_code'));

            return;
        }

        $payload = $result['data'] ?? $result;
        $token = (string) ($payload['token'] ?? $payload['access_token'] ?? $result['token'] ?? '');
        $profile = $payload['profile'] ?? $result['profile'] ?? [];
        $isContinue = filter_var($payload['is_continue'] ?? $result['is_continue'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($token === '') {
            Log::warning('Account login: OTP verified but empty token', ['phone' => $this->phone]);
            $this->error = __('otp.invalid_code');

            return;
        }

        session([
            'external_api_token' => $token,
            'external_api_profile' => is_array($profile) ? $profile : [],
            'external_login_is_continue' => $isContinue,
            'phone_verified' => $this->phone,
        ]);

        session()->forget('account_login_phone');

        $intended = (string) session()->pull('account.intended_url', '');
        $target = $intended !== '' ? $intended : route('account.dashboard');

        $this->redirect($target, navigate: false);
    }

    public function resetPhone(): void
    {
        $this->otpSent = false;
        $this->code = '';
        $this->error = '';
        $this->status = '';
    }

    public function render()
    {
        return view('livewire.account.login');
    }
}
