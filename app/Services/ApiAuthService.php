<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\AddressCheckoutHelper;
use App\Support\SaudiPhone;
use Illuminate\Http\Client\PendingRequest;
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

    protected function http(): PendingRequest
    {
        return Http::withOptions(['timeout' => 15, 'connect_timeout' => 8])
            ->acceptJson()
            ->withHeaders(['Accept-Language' => app()->getLocale()]);
    }

    protected function httpWithToken(string $token): PendingRequest
    {
        return $this->http()->withToken($token);
    }

    protected function url(string $path): string
    {
        return $this->baseUrl.'/'.ltrim($path, '/');
    }

    // ─── OTP / Login ──────────────────────────────────────────────────

    /**
     * Step 1: request an OTP be sent to the given phone via SMS.
     * POST /login/ordinary/reset
     */
    public function sendOtp(string $phone): array
    {
        try {
            $mobile = SaudiPhone::forExternalApiMobile($phone);
            $response = $this->http()->asForm()->post($this->url('login/ordinary/reset'), [
                'mobile' => SaudiPhone::toE164($phone),
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
            $deviceId ??= 'web-checkout-'.substr(hash('sha256', session()->getId()), 0, 40);

            $response = $this->http()->asForm()->post($this->url('login/ordinary/verify'), [
                'mobile' => SaudiPhone::toE164($phone),
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
            foreach (['mobile', 'phone'] as $key) {
                if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
                    $data[$key] = SaudiPhone::forExternalApiMobile($data[$key]);
                }
            }
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
            $rawMobile = (string) ($data['mobile'] ?? '');
            $rawPhone = (string) ($data['phone'] ?? '');
            $e164 = SaudiPhone::toE164($rawMobile !== '' ? $rawMobile : $rawPhone);
            if ($e164 !== '') {
                $data['mobile'] = $e164;
                $data['phone'] = $e164;
            }
            $response = $this->http()->post($this->url('register/simple-register'), $data);
            $json = $response->json() ?? [];
            $json['_http_ok'] = $response->successful();

            return $json;
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
    public function getAddresses(string $token, bool $raw = false, bool $activeOnly = true): array
    {
        try {
            $params = [];
            if ($activeOnly) {
                // External API supports isActive query filtering.
                $params['isActive'] = '1';
            }
            $response = $this->httpWithToken($token)->get($this->url('addresses'), $params);
            $body = $response->json();

            $rows = [];
            if (is_array($body)) {
                if (isset($body['data']) && is_array($body['data'])) {
                    // Handles: { data: [...] } and { data: { data: [...] } }
                    if (array_is_list($body['data'])) {
                        $rows = $body['data'];
                    } elseif (isset($body['data']['data']) && is_array($body['data']['data'])) {
                        $rows = $body['data']['data'];
                    } elseif (isset($body['data']['items']) && is_array($body['data']['items'])) {
                        $rows = $body['data']['items'];
                    } else {
                        $rows = array_values(array_filter($body['data'], 'is_array'));
                    }
                } elseif (isset($body['items']) && is_array($body['items'])) {
                    $rows = $body['items'];
                } elseif (array_is_list($body)) {
                    $rows = $body;
                }
            }

            if ($activeOnly) {
                $rows = array_values(array_filter($rows, static function ($addr): bool {
                    if (! is_array($addr)) {
                        return false;
                    }

                    return (bool) ($addr['is_active'] ?? $addr['isActive'] ?? true) === true;
                }));
            }

            $rows = array_values(array_filter($rows, 'is_array'));
            $rows = AddressCheckoutHelper::dedupeRows($rows);

            return $rows;
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
     * POST /addresses/{id}/days — assign weekday delivery slots (Sat=1 … Fri=7).
     *
     * @param  array<int, int>  $days
     */
    public function updateAddressDeliveryDays(string $token, int $addressId, array $days): array
    {
        if ($addressId <= 0 || $days === []) {
            return ['success' => false, 'message' => __('address.save_failed'), '_http_ok' => false];
        }

        $payload = [];
        foreach (array_values($days) as $index => $day) {
            $payload["days[{$index}]"] = (string) $day;
        }

        try {
            $response = $this->httpWithToken($token)->asForm()->post(
                $this->url("addresses/{$addressId}/days"),
                $payload
            );
            $json = $response->json() ?? [];
            $json['_http_ok'] = $response->successful();

            return $json;
        } catch (\Exception $e) {
            Log::error('ApiAuthService::updateAddressDeliveryDays failed', [
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => __('address.save_failed'), '_http_ok' => false];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAddressById(string $token, int $addressId, bool $activeOnly = false): ?array
    {
        if ($addressId <= 0) {
            return null;
        }

        $rows = $this->getAddresses($token, true, $activeOnly);
        $address = AddressCheckoutHelper::findById($rows, $addressId);
        if (! is_array($address)) {
            return null;
        }

        return AddressCheckoutHelper::enrichAddressDistrictDurations($address, $rows);
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
