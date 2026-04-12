<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Proxies all customer-auth and address requests to the mobile app's
 * external API (same base URL as ExternalDataService).
 */
class ApiAuthService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.external_api.url', 'https://diet-watchers-stage-fbofszkn.on-forge.com/api'),
            '/'
        );
    }

    // ─── HTTP helpers ─────────────────────────────────────────────────

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withOptions(['timeout' => 15, 'connect_timeout' => 8])
            ->acceptJson()
            ->withHeaders(['Accept-Language' => app()->getLocale()]);
    }

    protected function httpWithToken(string $token): \Illuminate\Http\Client\PendingRequest
    {
        return $this->http()->withToken($token);
    }

    protected function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    // ─── OTP / Login ──────────────────────────────────────────────────

    /**
     * Step 1: request an OTP be sent to the given phone via SMS.
     * POST /login/ordinary/reset
     */
    public function sendOtp(string $phone): array
    {
        try {
            $response = $this->http()->asForm()->post($this->url('login/ordinary/reset'), [
                'mobile' => $phone,
            ]);
            $json = $response->json() ?? [];
            $json['_http_ok'] = $response->successful();

            return $json;
        } catch (\Exception $e) {
            Log::error('ApiAuthService::sendOtp failed', ['phone' => $phone, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => __('auth.otp_send_failed'), '_http_ok' => false];
        }
    }

    /**
     * Step 2: verify the OTP.
     * POST /login/ordinary/verify
     * Returns { is_continue (bool), token (string|''), profile (object|null) }
     *   is_continue = false → existing customer, token is ready
     *   is_continue = true  → new phone, must register
     */
    public function verifyOtp(string $phone, string $code, ?string $deviceId = null): array
    {
        try {
            $deviceId ??= 'web-checkout-' . substr(hash('sha256', session()->getId()), 0, 40);

            $response = $this->http()->asForm()->post($this->url('login/ordinary/verify'), [
                'mobile' => $phone,
                'code' => $code,
                'device_id' => $deviceId,
            ]);
            $json = $response->json() ?? [];
            $json['_http_ok'] = $response->successful();

            return $json;
        } catch (\Exception $e) {
            Log::error('ApiAuthService::verifyOtp failed', ['phone' => $phone, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => __('auth.otp_verify_failed'), '_http_ok' => false];
        }
    }

    // ─── Registration ─────────────────────────────────────────────────

    /**
     * Full mobile registration.
     * POST /register/mobile
     * Required: name, mobile, gender, brithdate, weight, weight_unit, height, height_unit, customer_target_id
     */
    public function registerMobile(array $data): array
    {
        try {
            $response = $this->http()->post($this->url('register/mobile'), $data);
            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('ApiAuthService::registerMobile failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('auth.register_failed')];
        }
    }

    /**
     * Simple registration (name, email?, mobile?, gender).
     * POST /register/simple-register
     */
    public function simpleRegister(array $data): array
    {
        try {
            $response = $this->http()->post($this->url('register/simple-register'), $data);
            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('ApiAuthService::simpleRegister failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('auth.register_failed')];
        }
    }

    // ─── Profile ──────────────────────────────────────────────────────

    /**
     * GET /profile  (requires Sanctum token)
     */
    public function getProfile(string $token): array
    {
        try {
            $response = $this->httpWithToken($token)->get($this->url('profile'));
            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('ApiAuthService::getProfile failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── Addresses ────────────────────────────────────────────────────

    /**
     * GET /addresses  (requires Sanctum token)
     */
    public function getAddresses(string $token): array
    {
        try {
            $response = $this->httpWithToken($token)->get($this->url('addresses'));
            $body = $response->json();
            return $body['data'] ?? $body ?? [];
        } catch (\Exception $e) {
            Log::error('ApiAuthService::getAddresses failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * POST /addresses  (requires Sanctum token)
     * Fields: latitude, longitude, description, type (home|work|other), district_id, title?
     */
    public function storeAddress(string $token, array $data): array
    {
        try {
            $response = $this->httpWithToken($token)->asForm()->post($this->url('addresses'), $data);
            $json = $response->json() ?? [];
            $json['_http_ok'] = $response->successful();

            return $json;
        } catch (\Exception $e) {
            Log::error('ApiAuthService::storeAddress failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => __('address.save_failed'), '_http_ok' => false];
        }
    }

    /**
     * DELETE /addresses/{id}  (requires Sanctum token)
     */
    public function deleteAddress(string $token, int $id): array
    {
        try {
            $response = $this->httpWithToken($token)->delete($this->url("addresses/{$id}"));
            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('ApiAuthService::deleteAddress failed', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('address.delete_failed')];
        }
    }

    // ─── Districts ────────────────────────────────────────────────────

    /**
     * GET /districts  — public, no token needed.
     * Used to populate the district dropdown in the address picker.
     */
    public function getDistricts(): array
    {
        try {
            $response = $this->http()->get($this->url('districts'));
            $body = $response->json();
            return $body['data'] ?? $body ?? [];
        } catch (\Exception $e) {
            Log::error('ApiAuthService::getDistricts failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── Logout ───────────────────────────────────────────────────────

    /**
     * POST /logout  (requires Sanctum token)
     */
    public function logout(string $token): void
    {
        try {
            $this->httpWithToken($token)->post($this->url('logout'));
        } catch (\Exception $e) {
            Log::warning('ApiAuthService::logout failed', ['error' => $e->getMessage()]);
        }
    }
}
