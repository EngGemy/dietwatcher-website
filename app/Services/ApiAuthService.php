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
            if (! $response->successful()) {
                Log::warning('ApiAuthService::getAddresses non-success HTTP', [
                    'status' => $response->status(),
                ]);

                return [];
            }

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
                    return is_array($addr) && self::isAddressRowActive($addr);
                }));
            } else {
                $rows = array_values(array_filter($rows, static function ($addr): bool {
                    return is_array($addr) && ! self::isAddressRowDeleted($addr);
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
     * @param  array<string, mixed>  $addr
     */
    public static function isAddressRowDeleted(array $addr): bool
    {
        if (isset($addr['deleted_at']) && $addr['deleted_at'] !== null && $addr['deleted_at'] !== '') {
            return true;
        }

        return filter_var($addr['deleted'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<string, mixed>  $addr
     */
    public static function isAddressRowActive(array $addr): bool
    {
        if (self::isAddressRowDeleted($addr)) {
            return false;
        }

        if (array_key_exists('is_active', $addr) || array_key_exists('isActive', $addr)) {
            return filter_var($addr['is_active'] ?? $addr['isActive'], FILTER_VALIDATE_BOOLEAN);
        }

        return true;
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
            if (! is_array($json)) {
                $json = [];
            }
            $json['_http_ok'] = $response->successful();
            $json['status'] = $response->status();
            $json['message'] = $this->extractApiMessage($json, $response->status());

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
     * Deactivate delivery on an address so another can be activated (API max: 2 active).
     */
    public function deactivateAddress(string $token, int $addressId): array
    {
        if ($addressId <= 0) {
            return ['success' => false, 'message' => __('address.save_failed'), '_http_ok' => false];
        }

        try {
            $response = $this->httpWithToken($token)->asForm()->post(
                $this->url("addresses/{$addressId}"),
                ['is_active' => '0']
            );
            $json = $response->json() ?? [];
            if (! is_array($json)) {
                $json = [];
            }
            $json['_http_ok'] = $response->successful();
            $json['message'] = $this->extractApiMessage($json, $response->status());

            return $json;
        } catch (\Exception $e) {
            Log::warning('ApiAuthService::deactivateAddress failed', [
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => __('address.save_failed'), '_http_ok' => false];
        }
    }

    /**
     * Free a delivery slot before activating $addressId (external API allows 2 active max).
     */
    public function prepareAddressForDeliveryActivation(string $token, int $addressId): ?string
    {
        if ($addressId <= 0) {
            return __('checkout.confirm_saved_address_before_payment');
        }

        $rows = $this->getAddresses($token, true, false);
        foreach ($rows as $row) {
            $otherId = (int) ($row['id'] ?? 0);
            if ($otherId <= 0 || $otherId === $addressId) {
                continue;
            }
            if (self::isAddressRowActive($row)) {
                $this->deactivateAddress($token, $otherId);
            }
        }

        return null;
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
            $json = $this->normalizeAddressMutationResponse($response);

            if ($this->addressDeleteVerified($token, $id, $json)) {
                $json['success'] = true;

                return $json;
            }

            foreach ($this->addressDeleteFallbackAttempts($id) as $attempt) {
                try {
                    $fallbackResponse = $attempt($token);
                    $fallbackJson = $this->normalizeAddressMutationResponse($fallbackResponse);
                    if ($this->addressDeleteVerified($token, $id, $fallbackJson)) {
                        $fallbackJson['success'] = true;

                        return $fallbackJson;
                    }
                } catch (\Throwable $fallbackError) {
                    Log::debug('ApiAuthService::deleteAddress fallback failed', [
                        'id' => $id,
                        'error' => $fallbackError->getMessage(),
                    ]);
                }
            }

            return [
                'success' => false,
                'message' => $this->extractApiMessage($json, (int) ($json['status'] ?? $response->status()))
                    ?: __('address.delete_failed'),
                '_http_ok' => (bool) ($json['_http_ok'] ?? false),
                'status' => (int) ($json['status'] ?? $response->status()),
            ];
        } catch (\Exception $e) {
            Log::error('ApiAuthService::deleteAddress failed', ['id' => $id, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => __('address.delete_failed'), '_http_ok' => false];
        }
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function extractApiMessage(array $json, int $status = 0): string
    {
        foreach (['message', 'error', 'detail'] as $key) {
            $value = $json[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        if ($status >= 400) {
            return __('address.save_failed');
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAddressMutationResponse(\Illuminate\Http\Client\Response $response): array
    {
        $json = $response->json() ?? [];
        if (! is_array($json)) {
            $json = [];
        }
        $json['_http_ok'] = $response->successful();
        $json['status'] = $response->status();

        return $json;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function addressDeleteVerified(string $token, int $id, array $json): bool
    {
        if (isset($json['success']) && ! filter_var($json['success'], FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if (! filter_var($json['_http_ok'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        try {
            $response = $this->httpWithToken($token)->get($this->url('addresses'));
            if (! $response->successful()) {
                return false;
            }
        } catch (\Throwable $e) {
            Log::warning('ApiAuthService::addressDeleteVerified list fetch failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $remaining = $this->findAddressById($token, $id, false);
        if ($remaining === null) {
            return true;
        }

        return ! self::isAddressRowActive($remaining);
    }

    /**
     * @return array<int, callable(string): \Illuminate\Http\Client\Response>
     */
    private function addressDeleteFallbackAttempts(int $id): array
    {
        return [
            fn (string $token) => $this->httpWithToken($token)->asForm()->post($this->url("addresses/{$id}"), ['_method' => 'DELETE']),
            fn (string $token) => $this->httpWithToken($token)->asForm()->post($this->url("addresses/{$id}"), [
                'is_active' => '0',
                'isActive' => '0',
            ]),
        ];
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
