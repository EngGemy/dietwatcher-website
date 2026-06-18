<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\AddressCheckoutHelper;
use App\Support\ExternalApiConfig;
use App\Support\SaudiPhone;
use App\Support\SubscriptionCheckoutPayload;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
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
        $this->baseUrl = ExternalApiConfig::baseUrl();
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

        return false;
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
     * GET /addresses/{id}/days — weekday delivery slots (same as mobile app).
     */
    public function getAddressDeliveryDays(string $token, int $addressId): array
    {
        if ($addressId <= 0) {
            return ['success' => false, 'message' => __('address.save_failed'), '_http_ok' => false];
        }

        try {
            $response = $this->httpWithToken($token)->get($this->url("addresses/{$addressId}/days"));
            $json = $response->json() ?? [];
            if (! is_array($json)) {
                $json = [];
            }
            $json['_http_ok'] = $response->successful();
            $json['message'] = $this->extractApiMessage($json, $response->status());

            return $json;
        } catch (\Exception $e) {
            Log::warning('ApiAuthService::getAddressDeliveryDays failed', [
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => __('address.save_failed'), '_http_ok' => false];
        }
    }

    /**
     * @param  array<string, mixed>|null  $address
     * @return array<int, int>
     */
    public function resolveAddressDeliveryDays(string $token, int $addressId, ?array $address = null, bool $withWeekend = true): array
    {
        $address ??= $this->findAddressById($token, $addressId, false);
        if (is_array($address) && is_array($address['days'] ?? null) && $address['days'] !== []) {
            return SubscriptionCheckoutPayload::extractDeliveryDays($address, null, $withWeekend);
        }

        $daysResult = $this->getAddressDeliveryDays($token, $addressId);
        $daysData = is_array($daysResult['data'] ?? null) ? $daysResult['data'] : null;

        return SubscriptionCheckoutPayload::extractDeliveryDays($address, $daysData, $withWeekend);
    }

    /**
     * Stored delivery days only — no checkout defaults when nothing is saved.
     *
     * @param  array<string, mixed>|null  $address
     * @return array<int, int>
     */
    public function resolveAddressDeliveryDaysForDisplay(string $token, int $addressId, ?array $address = null): array
    {
        $address ??= $this->findAddressById($token, $addressId, false);
        if (is_array($address) && is_array($address['days'] ?? null) && $address['days'] !== []) {
            return SubscriptionCheckoutPayload::extractDeliveryDaysOnly($address, null);
        }

        $daysResult = $this->getAddressDeliveryDays($token, $addressId);
        $daysData = is_array($daysResult['data'] ?? null) ? $daysResult['data'] : null;

        return SubscriptionCheckoutPayload::extractDeliveryDaysOnly($address, $daysData);
    }

    /**
     * Same rule as AddressDayUpdateAction: reject when more than one other address is active.
     */
    public function countOtherActiveAddresses(string $token, int $addressId): int
    {
        $rows = $this->getAddresses($token, true, false);
        $count = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $otherId = (int) ($row['id'] ?? 0);
            if ($otherId <= 0 || $otherId === $addressId) {
                continue;
            }
            if (self::isAddressRowActive($row)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Activate delivery days via POST /addresses/{id}/days (mirrors mobile API flow).
     *
     * @param  array<int, int>  $defaultDays
     * @return array{ok: bool, message: string, address: array<string, mixed>|null, already_active: bool}
     */
    public function syncAddressDeliveryDaysForCheckout(
        string $token,
        int $addressId,
        array $defaultDays,
        bool $withWeekend = true,
    ): array {
        if ($addressId <= 0) {
            return [
                'ok' => false,
                'message' => __('checkout.confirm_saved_address_before_payment'),
                'address' => null,
                'already_active' => false,
            ];
        }

        $address = $this->findAddressById($token, $addressId, false);
        if (! is_array($address)) {
            return [
                'ok' => false,
                'message' => __('checkout.confirm_saved_address_before_payment'),
                'address' => null,
                'already_active' => false,
            ];
        }

        $days = $this->resolveAddressDeliveryDays($token, $addressId, $address, $withWeekend);
        $isActive = self::isAddressRowActive($address);

        if ($isActive && $days !== []) {
            return [
                'ok' => true,
                'message' => '',
                'address' => $address,
                'already_active' => true,
            ];
        }

        if ($this->countOtherActiveAddresses($token, $addressId) > 1) {
            return [
                'ok' => false,
                'message' => (string) __('checkout.address_max_active_delivery'),
                'address' => null,
                'already_active' => false,
            ];
        }

        $daysToSync = $days !== [] ? $days : $defaultDays;
        $sync = $this->updateAddressDeliveryDays($token, $addressId, $daysToSync);
        if (! ($sync['_http_ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $this->extractApiMessage($sync, (int) ($sync['status'] ?? 422))
                    ?: __('address.save_failed'),
                'address' => null,
                'already_active' => false,
            ];
        }

        $refreshed = $this->findAddressById($token, $addressId, false);

        return [
            'ok' => true,
            'message' => '',
            'address' => is_array($refreshed) ? $refreshed : $address,
            'already_active' => false,
        ];
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
     * Delivery time slots (region durations) for a district from saved addresses or a probe address.
     *
     * @return array<int, array{id: int, duration: string, durationText: string, time: string, label: string}>
     */
    public function findDistrictRegionDurations(string $token, int $districtId, ?int $probeAddressId = null): array
    {
        if ($districtId <= 0) {
            return [];
        }

        $rows = $this->getAddresses($token, true, false);
        if (! is_array($rows)) {
            $rows = [];
        }

        $districtRows = $this->getDistricts();
        if (! is_array($districtRows)) {
            $districtRows = [];
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rowDistrictId = AddressCheckoutHelper::resolveDistrictId($row, $districtRows);
            if ($rowDistrictId !== $districtId) {
                continue;
            }
            $enriched = AddressCheckoutHelper::enrichAddressDistrictDurations($row, $rows);
            $slots = AddressCheckoutHelper::resolveDeliveryTimeSlots($enriched);
            if ($slots !== []) {
                return $slots;
            }
        }

        if ($probeAddressId === null || $probeAddressId <= 0) {
            return [];
        }

        $address = AddressCheckoutHelper::findById($rows, $probeAddressId);
        if (! is_array($address)) {
            $address = $this->findAddressById($token, $probeAddressId, false);
        }
        if (! is_array($address)) {
            return [];
        }

        $address = AddressCheckoutHelper::enrichAddressDistrictDurations($address, $rows);
        $resolvedDistrictId = AddressCheckoutHelper::resolveDistrictId($address, $districtRows);
        if ($resolvedDistrictId > 0) {
            $districtId = $resolvedDistrictId;
        }

        $slots = AddressCheckoutHelper::resolveDeliveryTimeSlots($address);
        if ($slots !== []) {
            return $slots;
        }

        $daysResult = $this->getAddressDeliveryDays($token, $probeAddressId);
        $daysData = is_array($daysResult['data'] ?? null) ? $daysResult['data'] : null;
        $slots = AddressCheckoutHelper::resolveDeliveryTimeSlots($address, $daysData);
        if ($slots !== []) {
            return $slots;
        }

        if (! AddressCheckoutHelper::isCantModify($address)) {
            $refreshed = $this->refreshAddressCard($token, $probeAddressId, $address);
            if (is_array($refreshed)) {
                $refreshed = AddressCheckoutHelper::enrichAddressDistrictDurations($refreshed, $rows);
                $slots = AddressCheckoutHelper::resolveDeliveryTimeSlots($refreshed);
                if ($slots !== []) {
                    return $slots;
                }
            }
        }

        foreach ($rows as $peer) {
            if (! is_array($peer)) {
                continue;
            }
            if (AddressCheckoutHelper::resolveDistrictId($peer, $districtRows) !== $districtId) {
                continue;
            }
            $peerId = (int) ($peer['id'] ?? 0);
            if ($peerId <= 0 || $peerId === $probeAddressId) {
                continue;
            }
            $peerDays = $this->getAddressDeliveryDays($token, $peerId);
            $peerData = is_array($peerDays['data'] ?? null) ? $peerDays['data'] : null;
            $slots = AddressCheckoutHelper::resolveDeliveryTimeSlots($peer, $peerData);
            if ($slots !== []) {
                return $slots;
            }
        }

        return [];
    }

    /**
     * Load address with district delivery windows (region durations) for subscription checkout.
     *
     * @return array<string, mixed>|null
     */
    public function loadAddressForSubscription(string $token, int $addressId): ?array
    {
        if ($addressId <= 0) {
            return null;
        }

        $rows = $this->getAddresses($token, true, false);
        $address = AddressCheckoutHelper::findById($rows, $addressId);
        if (! is_array($address)) {
            return null;
        }

        $address = AddressCheckoutHelper::enrichAddressDistrictDurations($address, $rows);
        $slots = AddressCheckoutHelper::resolveDeliveryTimeSlots($address);
        if ($slots !== []) {
            return $address;
        }

        $daysResult = $this->getAddressDeliveryDays($token, $addressId);
        $daysData = is_array($daysResult['data'] ?? null) ? $daysResult['data'] : null;
        $slots = AddressCheckoutHelper::resolveDeliveryTimeSlots($address, $daysData);
        if ($slots !== []) {
            return $address;
        }

        if (! AddressCheckoutHelper::isCantModify($address)) {
            $refreshed = $this->refreshAddressCard($token, $addressId, $address);
            if (is_array($refreshed)) {
                $address = AddressCheckoutHelper::enrichAddressDistrictDurations($refreshed, $rows);
                if (AddressCheckoutHelper::resolveDeliveryTimeSlots($address) !== []) {
                    return $address;
                }
            }
        }

        return $address;
    }

    /**
     * POST /addresses/{id} — re-fetch full address card (includes district.durations).
     *
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>|null
     */
    public function refreshAddressCard(string $token, int $addressId, array $address): ?array
    {
        $districtId = AddressCheckoutHelper::districtId($address);
        if ($districtId <= 0) {
            return null;
        }

        $pickupType = 'hand_it_to_me';
        $pickup = $address['pickupType'] ?? $address['pickup_type'] ?? null;
        if (is_array($pickup) && ! empty($pickup['id'])) {
            $pickupType = (string) $pickup['id'];
        } elseif (is_string($pickup) && $pickup !== '') {
            $pickupType = $pickup;
        }

        $payload = [
            'title' => (string) ($address['title'] ?? 'Home'),
            'latitude' => (string) ($address['latitude'] ?? ''),
            'longitude' => (string) ($address['longitude'] ?? ''),
            'description' => (string) ($address['description'] ?? ''),
            'type' => (string) ($address['type'] ?? 'residential'),
            'district_id' => (string) $districtId,
            'pickup_type' => $pickupType,
        ];

        try {
            $response = $this->httpWithToken($token)->asForm()->post(
                $this->url("addresses/{$addressId}"),
                $payload
            );
            if (! $response->successful()) {
                return null;
            }
            $json = $response->json() ?? [];

            return AddressCheckoutHelper::unwrapStoredAddress($json['data'] ?? $json);
        } catch (\Exception $e) {
            Log::warning('ApiAuthService::refreshAddressCard failed', [
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * DELETE /addresses/{id}  (requires Sanctum token)
     */
    public function deleteAddress(string $token, int $id): array
    {
        try {
            $response = $this->httpWithToken($token)->delete($this->url("addresses/{$id}"));
            $json = $this->normalizeAddressMutationResponse($response);
            $json['message'] = $this->extractApiMessage($json, $response->status());
            if (! array_key_exists('success', $json)) {
                $json['success'] = $response->successful();
            } else {
                $json['success'] = filter_var($json['success'], FILTER_VALIDATE_BOOLEAN);
            }

            return $json;
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
    private function normalizeAddressMutationResponse(Response $response): array
    {
        $json = $response->json() ?? [];
        if (! is_array($json)) {
            $json = [];
        }
        $json['_http_ok'] = $response->successful();
        $json['status'] = $response->status();

        return $json;
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
