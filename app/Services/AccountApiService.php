<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\MoyasarPaymentService;
use App\Support\AddressCheckoutHelper;
use App\Support\SubscriptionCheckoutPayload;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Customer dashboard endpoints on the external Diet Watchers API.
 *
 * Thin HTTP wrapper — all calls use the logged-in customer's token
 * from session (`external_api_token`). Failures are logged and return
 * a predictable shape so Livewire components can render safely.
 */
class AccountApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.external_api.url', ''),
            '/'
        );
    }

    // ─── HTTP primitives ──────────────────────────────────────────────

    protected function http(): PendingRequest
    {
        return Http::withOptions(['timeout' => 15, 'connect_timeout' => 8])
            ->acceptJson()
            ->withHeaders([
                'Accept-Language' => app()->getLocale(),
            ]);
    }

    protected function authed(): PendingRequest
    {
        $token = (string) session('external_api_token', '');

        return $this->http()->withToken($token);
    }

    protected function authedWithToken(string $token): PendingRequest
    {
        $request = $this->http();

        return $token !== '' ? $request->withToken($token) : $request;
    }

    protected function deviceId(): string
    {
        return 'web-account-'.substr(hash('sha256', session()->getId()), 0, 40);
    }

    protected function hasToken(): bool
    {
        return (string) session('external_api_token', '') !== '';
    }

    protected function url(string $path): string
    {
        return $this->baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * Decode an API response into a predictable array, unwrapping `data`
     * when present and surfacing the HTTP status/message fields.
     *
     * @return array{ok: bool, status: int, data: mixed, message: string, raw: array}
     */
    protected function decode(Response $response): array
    {
        $body = $response->json();
        if (! is_array($body)) {
            $body = [];
        }

        $bodySuccess = $body['success'] ?? $body['ok'] ?? null;
        $bodyStatus = $body['status'] ?? null;
        $bodyMessage = (string) ($body['message'] ?? '');
        $httpOk = $response->successful();
        $logicalOk = $httpOk;
        if ($bodySuccess !== null) {
            $logicalOk = $logicalOk && filter_var($bodySuccess, FILTER_VALIDATE_BOOLEAN);
        }
        if (is_numeric($bodyStatus)) {
            $logicalOk = $logicalOk && ((int) $bodyStatus < 400);
        }

        $decoded = [
            'ok' => $logicalOk,
            'status' => $response->status(),
            'data' => $body['data'] ?? $body['response'] ?? $body,
            'message' => $bodyMessage,
            'raw' => $body,
        ];

        if (! $logicalOk && trim($bodyMessage) === '') {
            $decoded['message'] = $this->extractApiValidationMessage($decoded);
        }

        return $decoded;
    }

    protected function empty(string $message = ''): array
    {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'message' => $message ?: __('account.request_failed'),
            'raw' => [],
        ];
    }

    // ─── Profile ──────────────────────────────────────────────────────

    public function getProfile(): array
    {
        try {
            return $this->decode(
                $this->authed()->get($this->url('profile'), ['device_id' => $this->deviceId()])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::getProfile failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function updateProfile(array $payload): array
    {
        try {
            return $this->decode(
                $this->authed()->asForm()->post($this->url('profile'), $payload)
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::updateProfile failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    // ─── Subscriptions ────────────────────────────────────────────────

    /**
     * GET /subscriptions — list the customer's subscriptions. Optional filters.
     */
    public function listSubscriptions(?int $subscriptionId = null, ?string $date = null): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            $params = array_filter([
                'subscription_id' => $subscriptionId,
                'date' => $date,
                'device_id' => $this->deviceId(),
            ], fn ($v) => $v !== null && $v !== '');

            return $this->decode(
                $this->authed()->get($this->url('subscriptions'), $params)
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::listSubscriptions failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function showSubscription(int $subscriptionId, ?string $date = null): array
    {
        return $this->listSubscriptions($subscriptionId, $date);
    }

    public function startSubscription(string $date, ?string $token = null): array
    {
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '') {
            return $this->empty(__('account.login_required'));
        }

        try {
            return $this->decode(
                $this->authedWithToken($token)->asForm()->post($this->url('subscriptions/start'), ['date' => $date])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::startSubscription failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function updateSubscriptionStatus(
        string $status,
        ?string $pausedDate = null,
        ?string $reactivateDate = null,
        ?int $subscriptionId = null,
    ): array {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            $payload = array_filter([
                'status' => $status,
                'paused_date' => $pausedDate,
                'reactivate_date' => $reactivateDate,
                'subscription_id' => $subscriptionId,
            ], fn ($v) => $v !== null && $v !== '');

            return $this->decode(
                $this->authed()->asForm()->post($this->url('subscriptions/updateStatus'), $payload)
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::updateSubscriptionStatus failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function skipDay(int $dietId, string $dateFrom, string $dateTo, ?int $subscriptionId = null): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            $query = http_build_query(array_filter([
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'subscription_id' => $subscriptionId,
            ], fn ($v) => $v !== null && $v !== ''));

            return $this->decode(
                $this->authed()
                    ->asForm()
                    ->post($this->url('subscriptions/skipDay?'.$query), [
                        'diet_id' => (string) $dietId,
                    ])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::skipDay failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function restoreDay(int $dietId, string $date, ?int $subscriptionId = null): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            $query = http_build_query(array_filter([
                'date' => $date,
                'subscription_id' => $subscriptionId,
            ], fn ($v) => $v !== null && $v !== ''));

            return $this->decode(
                $this->authed()
                    ->asForm()
                    ->post($this->url('subscriptions/restoreDay?'.$query), [
                        'diet_id' => (string) $dietId,
                    ])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::restoreDay failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function cancelSubscriptionInfo(string $date, ?int $subscriptionId = null): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            return $this->decode(
                $this->authed()->get($this->url('subscriptions/cancel'), array_filter([
                    'date' => $date,
                    'subscription_id' => $subscriptionId,
                ], fn ($v) => $v !== null && $v !== ''))
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::cancelSubscriptionInfo failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function cancelSubscription(string $date, string $reason, ?int $subscriptionId = null): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            return $this->decode(
                $this->authed()->asForm()->post($this->url('subscriptions/cancel'), array_filter([
                    'date' => $date,
                    'cancel_reason' => $reason,
                    'subscription_id' => $subscriptionId,
                ], fn ($v) => $v !== null && $v !== ''))
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::cancelSubscription failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function listInvoices(?int $subscriptionId = null): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            $params = array_filter([
                'subscription_id' => $subscriptionId,
            ], fn ($v) => $v !== null && $v !== '');

            return $this->decode(
                $this->authed()->get($this->url('subscriptions/invoices/index'), $params)
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::listInvoices failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function subscriptionInvoicePdfUrl(int $invoiceId): string
    {
        return $this->url('subscriptions/invoices/'.$invoiceId.'/pdf');
    }

    /**
     * Resolve a downloadable invoice URL from an API invoice row.
     *
     * @param  array<string, mixed>  $invoice
     */
    public function resolveInvoiceDownloadUrl(array $invoice): ?string
    {
        foreach (['pdf_url', 'invoice_url', 'url', 'file', 'link', 'download_url'] as $key) {
            $url = trim((string) ($invoice[$key] ?? ''));
            if ($url !== '') {
                if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                    return $url;
                }
                if (str_starts_with($url, '/')) {
                    return rtrim(preg_replace('#/api/?$#i', '', $this->baseUrl), '/').$url;
                }
            }
        }

        $id = (int) ($invoice['id'] ?? $invoice['invoice_id'] ?? 0);

        return $id > 0 ? $this->subscriptionInvoicePdfUrl($id) : null;
    }

    public function fetchInvoicePdf(string $url): ?string
    {
        if (! $this->hasToken()) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        try {
            $response = $this->authed()
                ->withOptions(['timeout' => 30, 'connect_timeout' => 10])
                ->get($url);

            if (! $response->successful()) {
                Log::warning('AccountApiService::fetchInvoicePdf HTTP error', [
                    'status' => $response->status(),
                    'url' => $url,
                ]);

                return null;
            }

            $body = $response->body();
            if ($body === '' || strlen($body) < 64) {
                return null;
            }

            return $body;
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::fetchInvoicePdf failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getReplaceMealOptions(int $planMenuId, string $date, int $dietId, int $mealId, ?int $subscriptionId = null): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            return $this->decode(
                $this->authed()->get($this->url('subscriptions/replaceMeal'), array_filter([
                    'plan_menu_id' => $planMenuId,
                    'date' => $date,
                    'diet_id' => $dietId,
                    'meal_id' => $mealId,
                    'subscription_id' => $subscriptionId,
                ], fn ($v) => $v !== null && $v !== ''))
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::getReplaceMealOptions failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function replaceMeal(string $date, int $dietId, int $mealId, int $replacedDietId, ?int $subscriptionId = null): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            $query = http_build_query(array_filter([
                'date' => $date,
                'subscription_id' => $subscriptionId,
            ], fn ($v) => $v !== null && $v !== ''));

            return $this->decode(
                $this->authed()->asForm()->post($this->url('subscriptions/replaceMeal?'.$query), [
                    'diet_id' => (string) $dietId,
                    'meal_id' => (string) $mealId,
                    'replaced_diet_id' => (string) $replacedDietId,
                ])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::replaceMeal failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    /**
     * POST /subscriptions/calculate — authoritative subscription pricing (customer token).
     */
    public function calculateSubscription(array $payload, ?string $token = null): array
    {
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '') {
            return $this->empty(__('account.login_required'));
        }

        $payload = $this->prepareSubscriptionApiPayload($payload, $token);

        try {
            return $this->decode(
                $this->authedWithToken($token)->asForm()->post(
                    $this->subscriptionRequestUrl('subscriptions/calculate', $payload),
                    $payload
                )
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::calculateSubscription failed', ['error' => $e->getMessage()]);

            return $this->empty(__('account.request_failed'));
        }
    }

    /**
     * POST /subscriptions — create subscription for authenticated customer.
     */
    public function createSubscription(array $payload, ?string $token = null): array
    {
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '') {
            return $this->empty(__('account.login_required'));
        }

        $payload = $this->prepareSubscriptionApiPayload($payload, $token);

        try {
            $response = $this->authedWithToken($token)->asForm()->post(
                $this->subscriptionRequestUrl('subscriptions', $payload),
                $payload
            );
            $result = $this->decode($response);
            if (! ($result['ok'] ?? false)) {
                Log::warning('AccountApiService::createSubscription rejected', [
                    'http_status' => $response->status(),
                    'message' => $this->extractApiValidationMessage($result),
                    'body' => $response->json(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::createSubscription failed', ['error' => $e->getMessage()]);

            return $this->empty(__('account.request_failed'));
        }
    }

    /**
     * GET /addresses/{id}/days — delivery weekdays + region duration metadata.
     */
    public function getAddressDeliveryDays(int $addressId, ?string $token = null): array
    {
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '' || $addressId <= 0) {
            return $this->empty(__('account.login_required'));
        }

        try {
            return $this->decode(
                $this->authedWithToken($token)->get($this->url("addresses/{$addressId}/days"))
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::getAddressDeliveryDays failed', [
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            return $this->empty();
        }
    }

    /**
     * @param  array<string, string>  $payload
     * @return array<string, string>
     */
    protected function prepareSubscriptionApiPayload(array $payload, string $token): array
    {
        $enrichError = $this->enrichSubscriptionDeliveryPayload($payload, $token);
        if ($enrichError !== null) {
            Log::warning('AccountApiService subscription payload enrichment issue', [
                'message' => $enrichError,
                'receiving' => $payload['receiving'] ?? null,
            ]);
        }

        return $payload;
    }

    /**
     * @param  array<string, string>  $payload
     */
    protected function subscriptionRequestUrl(string $path, array $payload): string
    {
        $query = [
            'device_id' => $this->deviceId(),
        ];
        $date = SubscriptionCheckoutPayload::normalizeStartDate(
            (string) ($payload['date'] ?? $payload['start_date'] ?? '')
        );
        if ($date !== '') {
            $query['date'] = $date;
        }

        return $this->url($path.'?'.http_build_query($query));
    }

    /**
     * Align delivery subscriptions with POST /subscriptions/calculate address matrix.
     *
     * @param  array<string, string>  $payload
     */
    protected function enrichSubscriptionDeliveryPayload(array &$payload, string $token): ?string
    {
        $receiving = (string) ($payload['receiving'] ?? '');
        $payload['with_pickup'] = $receiving === 'pickup' ? '1' : '0';
        $payload['with_service'] = $payload['with_service'] ?? '0';

        if ($receiving !== 'delivery') {
            return null;
        }

        $addressId = (int) ($payload['address_id'] ?? $payload['selected_address_id'] ?? 0);
        if ($addressId <= 0) {
            return null;
        }

        $payload['address_id'] = (string) $addressId;

        $auth = app(ApiAuthService::class);
        $address = $auth->loadAddressForSubscription($token, $addressId);
        if (! is_array($address)) {
            return __('checkout.confirm_saved_address_before_payment');
        }

        $withWeekend = filter_var($payload['with_weekend'] ?? '0', FILTER_VALIDATE_BOOLEAN);
        $defaultDays = SubscriptionCheckoutPayload::defaultDeliveryWeekdays($withWeekend);
        $storedDays = $auth->resolveAddressDeliveryDays($token, $addressId, $address, $withWeekend);
        if ($storedDays === []) {
            $sync = $auth->syncAddressDeliveryDaysForCheckout($token, $addressId, $defaultDays, $withWeekend);
            if (! ($sync['ok'] ?? false)) {
                return (string) ($sync['message'] ?? __('checkout.address_max_active_delivery'));
            }

            $address = is_array($sync['address'] ?? null) ? $sync['address'] : $address;
            $storedDays = $auth->resolveAddressDeliveryDays($token, $addressId, $address, $withWeekend);
        }

        $address['days'] = $storedDays;

        $daysResult = $this->getAddressDeliveryDays($addressId, $token);
        $daysData = is_array($daysResult['data'] ?? null) ? $daysResult['data'] : null;

        $sessionOverride = (int) data_get(session('checkout_region_duration_by_address', []), (string) $addressId, 0);
        $requestOverride = (int) ($payload['region_duration_id'] ?? 0);
        $regionOverride = $requestOverride > 0 ? $requestOverride : $sessionOverride;
        if ($regionOverride > 0) {
            $slots = AddressCheckoutHelper::normalizeRegionDurations(AddressCheckoutHelper::districtDurations($address));
            if ($slots === []) {
                $slots = app(ApiAuthService::class)->findDistrictRegionDurations(
                    $token,
                    AddressCheckoutHelper::districtId($address),
                    $addressId,
                );
            }
            if ($slots === [] || AddressCheckoutHelper::isValidRegionDurationId($regionOverride, $slots)) {
                $payload['region_duration_id'] = (string) $regionOverride;
            }
        }

        $programId = (int) ($payload['program_id'] ?? 0);
        $durationRows = $programId > 0
            ? app(ExternalDataService::class)->getCheckoutPlanDurations($programId)
            : [];

        $payload = SubscriptionCheckoutPayload::appendDeliveryAddressFields(
            $payload,
            $addressId,
            $address,
            $daysData,
            $durationRows,
        );

        if (! isset($payload['addresses[0][region_duration_id]'])) {
            Log::warning('AccountApiService subscription payload missing region_duration_id', [
                'address_id' => $addressId,
                'district_id' => AddressCheckoutHelper::districtId($address),
                'district_durations' => AddressCheckoutHelper::districtDurations($address),
            ]);

            return __('checkout.address_region_duration_missing');
        }

        return null;
    }

    /**
     * Validate start date via POST /subscriptions/calculate — never mutates the user's chosen date.
     *
     * @param  array<string, string>  $payload
     * @return array{ok: bool, message: string, min_start_date: string|null, payload: array<string, string>}
     */
    protected function validateSubscriptionStartDateFromCalculate(array $payload, string $token): array
    {
        $userDate = SubscriptionCheckoutPayload::normalizeStartDate(
            (string) ($payload['date'] ?? $payload['start_date'] ?? '')
        );
        if ($userDate === '') {
            return [
                'ok' => false,
                'message' => __('checkout.start_date_required'),
                'min_start_date' => null,
                'payload' => $payload,
            ];
        }

        $calcPayload = $payload;
        unset($calcPayload['payment_option']);

        $calc = $this->calculateSubscription($calcPayload, $token);
        if (! ($calc['ok'] ?? false)) {
            $message = $this->extractApiValidationMessage($calc);
            $rawMin = SubscriptionCheckoutPayload::extractRawMinStartDateFromApiResult($calc);
            if ($rawMin === '') {
                $rawMin = SubscriptionCheckoutPayload::parseRawMinimumDateFromValidationMessage($message);
            }

            if ($rawMin !== '' && $userDate < $rawMin) {
                return $this->acceptClampedSubscriptionStartDate($payload, $rawMin, $token);
            }

            $minStartDate = SubscriptionCheckoutPayload::extractMinStartDateFromApiResult($calc);
            if ($minStartDate === '') {
                $minStartDate = SubscriptionCheckoutPayload::parseMinimumDateFromValidationMessage($message);
            }

            $dateRelated = SubscriptionCheckoutPayload::isStartDateValidationMessage($message)
                || $minStartDate !== '';

            return [
                'ok' => false,
                'message' => $dateRelated
                    ? SubscriptionCheckoutPayload::resolveStartDateErrorMessage($message, $minStartDate !== '' ? $minStartDate : $rawMin)
                    : ($message !== '' ? $message : __('account.request_failed')),
                'min_start_date' => $dateRelated ? (($rawMin !== '' ? $rawMin : $minStartDate) ?: null) : null,
                'payload' => $payload,
            ];
        }

        $data = is_array($calc['data'] ?? null) ? $calc['data'] : [];
        $apiMin = SubscriptionCheckoutPayload::extractRawApiMinStartDate($data);

        if ($apiMin === '') {
            $payload['date'] = $userDate;
            $payload['start_date'] = $userDate;

            return [
                'ok' => true,
                'message' => '',
                'min_start_date' => $userDate,
                'adjusted_start_date' => null,
                'payload' => $payload,
            ];
        }

        if ($userDate < $apiMin) {
            return $this->acceptClampedSubscriptionStartDate($payload, $apiMin, $token);
        }

        $payload['date'] = $userDate;
        $payload['start_date'] = $userDate;

        return [
            'ok' => true,
            'message' => '',
            'min_start_date' => $apiMin,
            'adjusted_start_date' => null,
            'payload' => $payload,
        ];
    }

    /**
     * Create (or reuse) a pending API subscription and return Moyasar bootstrap from PaymentResource.
     *
     * @param  array<string, string>  $payload
     * @return array{ok: bool, message: string, min_start_date: string|null, adjusted_start_date: string|null, subscription_id: int|null, bootstrap: array<string, mixed>|null}
     */
    public function bootstrapSubscriptionMoyasar(array $payload, ?string $token = null): array
    {
        $created = $this->createSubscriptionCheckout($payload, $token);
        if (! ($created['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($created['message'] ?? __('account.request_failed')),
                'min_start_date' => $created['min_start_date'] ?? null,
                'adjusted_start_date' => $created['adjusted_start_date'] ?? null,
                'subscription_id' => $created['subscription_id'] ?? null,
                'bootstrap' => null,
            ];
        }

        $createData = is_array($created['create_data'] ?? null) ? $created['create_data'] : [];
        $bootstrap = $this->extractMoyasarBootstrapFromCreateResponse($createData, $created['raw'] ?? null);
        if ($bootstrap === null) {
            return [
                'ok' => false,
                'message' => __('checkout.subscription_payment_unavailable'),
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => $created['subscription_id'] ?? null,
                'bootstrap' => null,
            ];
        }

        $subscriptionId = (int) ($bootstrap['subscription_id'] ?? $created['subscription_id'] ?? 0);
        $payloadHash = (string) ($created['payload_hash'] ?? '');

        session([
            'checkout_api_subscription_checkout' => [
                'payload_hash' => $payloadHash,
                'subscription_id' => $subscriptionId,
                'bootstrap' => $bootstrap,
            ],
        ]);

        return [
            'ok' => true,
            'message' => '',
            'min_start_date' => null,
            'adjusted_start_date' => $created['adjusted_start_date'] ?? null,
            'subscription_id' => $subscriptionId,
            'bootstrap' => $bootstrap,
        ];
    }

    /**
     * Create and activate a subscription when the calculated total is zero (100% discount).
     *
     * @param  array<string, string>  $payload
     * @return array{
     *     ok: bool,
     *     message: string,
     *     min_start_date: string|null,
     *     adjusted_start_date: string|null,
     *     subscription_id: int|null,
     *     redirect_url: string|null
     * }
     */
    public function confirmFreeSubscription(array $payload, ?string $token = null): array
    {
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '') {
            return [
                'ok' => false,
                'message' => __('account.login_required'),
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'redirect_url' => null,
            ];
        }

        $calcPayload = $payload;
        unset($calcPayload['payment_option']);
        $calc = $this->calculateSubscription($calcPayload, $token);
        if (! ($calc['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $this->extractApiValidationMessage($calc),
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'redirect_url' => null,
            ];
        }

        $totals = $this->parseSubscriptionCalculateTotals($calc['data'] ?? null);
        if ($totals === null || $totals['total'] > 0.001) {
            return [
                'ok' => false,
                'message' => __('checkout.free_subscription_requires_zero_total'),
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'redirect_url' => null,
            ];
        }

        $created = $this->createSubscriptionCheckout($payload, $token);
        if (! ($created['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($created['message'] ?? __('account.request_failed')),
                'min_start_date' => $created['min_start_date'] ?? null,
                'adjusted_start_date' => $created['adjusted_start_date'] ?? null,
                'subscription_id' => $created['subscription_id'] ?? null,
                'redirect_url' => null,
            ];
        }

        $createData = is_array($created['create_data'] ?? null) ? $created['create_data'] : [];
        $subscriptionId = (int) ($created['subscription_id'] ?? 0);
        if ($subscriptionId <= 0) {
            return [
                'ok' => false,
                'message' => __('checkout.free_subscription_activation_failed'),
                'min_start_date' => null,
                'adjusted_start_date' => $created['adjusted_start_date'] ?? null,
                'subscription_id' => null,
                'redirect_url' => null,
            ];
        }

        $payload = is_array($created['payload'] ?? null) ? $created['payload'] : $payload;
        $activated = $this->finalizeFreeSubscription($subscriptionId, $createData, $payload, $token);
        if (! ($activated['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($activated['message'] ?? __('checkout.free_subscription_activation_failed')),
                'min_start_date' => null,
                'adjusted_start_date' => $created['adjusted_start_date'] ?? null,
                'subscription_id' => $subscriptionId,
                'redirect_url' => null,
            ];
        }

        session()->forget('checkout_api_subscription_checkout');

        return [
            'ok' => true,
            'message' => '',
            'min_start_date' => null,
            'adjusted_start_date' => $created['adjusted_start_date'] ?? null,
            'subscription_id' => $subscriptionId,
            'redirect_url' => route('payment.subscription-confirm', ['subscription' => $subscriptionId]),
        ];
    }

    /**
     * Shared subscription create flow for paid (Moyasar) and free checkout.
     *
     * @param  array<string, string>  $payload
     * @return array{
     *     ok: bool,
     *     message: string,
     *     min_start_date: string|null,
     *     adjusted_start_date: string|null,
     *     subscription_id: int|null,
     *     create_data: array<string, mixed>|null,
     *     raw: array<string, mixed>|null,
     *     payload: array<string, string>|null,
     *     payload_hash: string|null
     * }
     */
    protected function createSubscriptionCheckout(array $payload, ?string $token = null): array
    {
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '') {
            return [
                'ok' => false,
                'message' => __('account.login_required'),
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'create_data' => null,
                'raw' => null,
                'payload' => null,
                'payload_hash' => null,
            ];
        }

        $addressError = $this->ensureSubscriptionDeliveryAddress($payload, $token);
        if ($addressError !== null) {
            return [
                'ok' => false,
                'message' => $addressError,
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'create_data' => null,
                'raw' => null,
                'payload' => null,
                'payload_hash' => null,
            ];
        }

        $enrichError = $this->enrichSubscriptionDeliveryPayload($payload, $token);
        if ($enrichError !== null) {
            return [
                'ok' => false,
                'message' => $enrichError,
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'create_data' => null,
                'raw' => null,
                'payload' => null,
                'payload_hash' => null,
            ];
        }

        $schedule = $this->validateSubscriptionStartDateFromCalculate($payload, $token);
        if (! ($schedule['ok'] ?? false)) {
            $message = (string) ($schedule['message'] ?? __('account.request_failed'));
            $minStartDate = (string) ($schedule['min_start_date'] ?? '');

            return [
                'ok' => false,
                'message' => $message,
                'min_start_date' => $minStartDate !== '' ? $minStartDate : null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'create_data' => null,
                'raw' => null,
                'payload' => null,
                'payload_hash' => null,
            ];
        }

        $payload = $schedule['payload'];
        $adjustedStartDate = (string) ($schedule['adjusted_start_date'] ?? '');
        $payloadHash = $this->hashSubscriptionPayload($payload);

        $cached = session('checkout_api_subscription_checkout');
        if (is_array($cached) && ($cached['payload_hash'] ?? '') === $payloadHash) {
            $subscriptionId = (int) ($cached['subscription_id'] ?? 0);
            $bootstrap = is_array($cached['bootstrap'] ?? null) ? $cached['bootstrap'] : null;
            if ($subscriptionId > 0 && $bootstrap !== null) {
                return [
                    'ok' => true,
                    'message' => '',
                    'min_start_date' => null,
                    'adjusted_start_date' => null,
                    'subscription_id' => $subscriptionId,
                    'create_data' => ['subscription' => ['id' => $subscriptionId]],
                    'raw' => null,
                    'payload' => $payload,
                    'payload_hash' => $payloadHash,
                ];
            }
        }

        $result = $this->createSubscription($payload, $token);

        if (! ($result['ok'] ?? false)) {
            $message = $this->extractApiValidationMessage($result);
            $rawMin = SubscriptionCheckoutPayload::extractRawMinStartDateFromApiResult($result);
            if ($rawMin === '') {
                $rawMin = SubscriptionCheckoutPayload::parseRawMinimumDateFromValidationMessage($message);
            }
            $currentDate = SubscriptionCheckoutPayload::normalizeStartDate(
                (string) ($payload['date'] ?? $payload['start_date'] ?? '')
            );

            if ($rawMin !== '' && $currentDate !== '' && $currentDate < $rawMin) {
                $payload['date'] = $rawMin;
                $payload['start_date'] = $rawMin;
                $adjustedStartDate = $rawMin;
                $result = $this->createSubscription($payload, $token);
            }
        }

        if (! ($result['ok'] ?? false)) {
            $message = $this->extractApiValidationMessage($result);
            $rawMin = SubscriptionCheckoutPayload::extractRawMinStartDateFromApiResult($result);
            if ($rawMin === '') {
                $rawMin = SubscriptionCheckoutPayload::parseRawMinimumDateFromValidationMessage($message);
            }
            $minStartDate = SubscriptionCheckoutPayload::parseMinimumDateFromValidationMessage($message);
            if ($minStartDate === '' && $rawMin !== '') {
                $minStartDate = $rawMin;
            }
            $dateRelated = SubscriptionCheckoutPayload::isStartDateValidationMessage($message)
                || $rawMin !== '';

            return [
                'ok' => false,
                'message' => $dateRelated
                    ? SubscriptionCheckoutPayload::resolveStartDateErrorMessage($message, $minStartDate !== '' ? $minStartDate : $rawMin)
                    : $message,
                'min_start_date' => $dateRelated ? (($rawMin !== '' ? $rawMin : $minStartDate) ?: null) : null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'create_data' => null,
                'raw' => is_array($result['raw'] ?? null) ? $result['raw'] : null,
                'payload' => $payload,
                'payload_hash' => $payloadHash,
            ];
        }

        $createData = is_array($result['data'] ?? null) ? $result['data'] : [];
        if ($createData === [] && is_array($result['raw']['data'] ?? null)) {
            $createData = $result['raw']['data'];
        }

        return [
            'ok' => true,
            'message' => '',
            'min_start_date' => null,
            'adjusted_start_date' => $adjustedStartDate !== '' ? $adjustedStartDate : null,
            'subscription_id' => $this->extractExternalSubscriptionId($createData),
            'create_data' => $createData,
            'raw' => is_array($result['raw'] ?? null) ? $result['raw'] : null,
            'payload' => $payload,
            'payload_hash' => $payloadHash,
        ];
    }

    /**
     * @param  array<string, mixed>  $createData
     * @param  array<string, string>  $payload
     * @return array{ok: bool, message: string}
     */
    protected function finalizeFreeSubscription(int $subscriptionId, array $createData, array $payload, string $token): array
    {
        $subRow = $this->extractSubscriptionRowFromCreate($createData);
        if ($subRow === []) {
            $show = $this->showSubscription($subscriptionId);
            if ($show['ok'] ?? false) {
                $subRow = $this->extractSubscriptionRowFromDecoded($show['data'] ?? null, $subscriptionId);
            }
        }

        if (! $this->isSubscriptionPaymentConfirmed($subRow)) {
            $payment = $this->extractPaymentResource($createData);
            $externalPaymentId = is_numeric($payment['id'] ?? $payment['payment_id'] ?? null)
                ? (int) ($payment['id'] ?? $payment['payment_id'])
                : (int) data_get($subRow, 'payment.id', 0);

            $notifyQuery = array_filter([
                'status' => 'paid',
                'subscription_id' => (string) $subscriptionId,
                'payment_id' => $externalPaymentId > 0 ? (string) $externalPaymentId : null,
            ], static fn ($value) => $value !== null && $value !== '');

            $notify = $this->notifySubscriptionMoyasarPayment($notifyQuery, $token);
            Log::info('AccountApiService::finalizeFreeSubscription notify', [
                'subscription_id' => $subscriptionId,
                'external_payment_id' => $externalPaymentId > 0 ? $externalPaymentId : null,
                'api_ok' => $notify['ok'] ?? false,
                'notify_succeeded' => $this->paymentNotifyResponseSucceeded($notify),
            ]);
        }

        $startDate = SubscriptionCheckoutPayload::normalizeStartDate(
            (string) ($payload['date'] ?? $payload['start_date'] ?? '')
        );
        if ($startDate !== '') {
            $start = $this->startSubscription($startDate, $token);
            if (! ($start['ok'] ?? false)) {
                Log::warning('AccountApiService::finalizeFreeSubscription start failed', [
                    'subscription_id' => $subscriptionId,
                    'start_date' => $startDate,
                    'message' => $start['message'] ?? '',
                ]);
            }
        }

        $show = $this->showSubscription($subscriptionId);
        if (! ($show['ok'] ?? false)) {
            return ['ok' => false, 'message' => __('checkout.free_subscription_activation_failed')];
        }

        $sub = $this->extractSubscriptionRowFromDecoded($show['data'] ?? null, $subscriptionId);
        if (! $this->isSubscriptionPaymentConfirmed($sub)) {
            return ['ok' => false, 'message' => __('checkout.free_subscription_activation_failed')];
        }

        return ['ok' => true, 'message' => ''];
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractSubscriptionRowFromCreate(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $sub = $data['subscription'] ?? $data;
        if (! is_array($sub)) {
            return [];
        }

        return $sub;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractSubscriptionRowFromDecoded(mixed $data, int $subscriptionId): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data['subscription']) && is_array($data['subscription'])) {
            return $data['subscription'];
        }

        if (isset($data['id']) && (int) $data['id'] === $subscriptionId) {
            return $data;
        }

        $rows = $data['subscriptions'] ?? $data['data'] ?? null;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row) && (int) ($row['id'] ?? 0) === $subscriptionId) {
                    return $row;
                }
            }
        }

        return [];
    }

    /**
     * @param  array<string, string>  $payload
     * @return array{ok: bool, message: string, min_start_date: string, adjusted_start_date: string, payload: array<string, string>}
     */
    protected function acceptClampedSubscriptionStartDate(array $payload, string $apiMin, string $token): array
    {
        Log::info('AccountApiService clamping subscription start date to API minimum', [
            'requested' => $payload['date'] ?? $payload['start_date'] ?? null,
            'api_min' => $apiMin,
        ]);

        $payload['date'] = $apiMin;
        $payload['start_date'] = $apiMin;

        $calcPayload = $payload;
        unset($calcPayload['payment_option']);
        $calc = $this->calculateSubscription($calcPayload, $token);
        if (! ($calc['ok'] ?? false)) {
            Log::warning('AccountApiService calculate after start-date clamp still rejected', [
                'api_min' => $apiMin,
                'message' => $this->extractApiValidationMessage($calc),
            ]);
        }

        return [
            'ok' => true,
            'message' => '',
            'min_start_date' => $apiMin,
            'adjusted_start_date' => $apiMin,
            'payload' => $payload,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function extractMoyasarBootstrapFromCreateResponse(mixed $data, mixed $raw = null): ?array
    {
        $payment = $this->extractPaymentResource($data);
        if ($payment === null && is_array($raw)) {
            $payment = $this->extractPaymentResource(is_array($raw['data'] ?? null) ? $raw['data'] : $raw);
        }

        if ($payment === null) {
            return null;
        }

        $amountRaw = $payment['amount'] ?? $payment['pay_amount'] ?? null;
        $amountHalalas = is_numeric($amountRaw) ? (int) $amountRaw : 0;
        if ($amountHalalas <= 0) {
            return null;
        }

        $metadata = is_array($payment['metadata'] ?? null) ? $payment['metadata'] : [];
        $metadata = array_map(static fn ($v) => (string) $v, $metadata);

        $publishableKey = trim((string) (
            $payment['publishable_api_key']
            ?? $payment['publishable_key']
            ?? ''
        ));

        if (! app(MoyasarPaymentService::class)->isValidPublishableKey($publishableKey)) {
            return null;
        }

        $subscriptionId = null;
        if (is_array($data)) {
            $subscriptionId = $this->extractExternalSubscriptionId($data);
        }

        $externalPaymentId = $payment['id'] ?? $payment['payment_id'] ?? null;

        return [
            'amount_halalas' => $amountHalalas,
            'publishable_key' => $publishableKey,
            'currency' => (string) ($payment['currency'] ?? 'SAR'),
            'description' => (string) ($payment['description'] ?? ''),
            'metadata' => $metadata,
            'subscription_id' => $subscriptionId,
            'external_payment_id' => is_numeric($externalPaymentId) ? (int) $externalPaymentId : null,
        ];
    }

    /**
     * Forward Moyasar redirect query params to the external API so it can mark the subscription paid.
     *
     * @param  array<string, scalar|null>  $query
     */
    public function forwardMoyasarPaymentCallback(array $query, ?string $token = null): array
    {
        return $this->notifySubscriptionMoyasarPayment($query, $token);
    }

    /**
     * Notify the external API that Moyasar payment succeeded (callback / confirm / verify).
     *
     * @param  array<string, scalar|null>  $query
     */
    public function notifySubscriptionMoyasarPayment(array $query, ?string $token = null): array
    {
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '') {
            return $this->empty(__('account.login_required'));
        }

        $query = array_filter(
            $query,
            static fn ($value) => $value !== null && $value !== '',
        );
        $query['device_id'] = $query['device_id'] ?? $this->deviceId();

        $last = $this->empty(__('account.request_failed'));
        $variants = $this->buildMoyasarNotifyQueryVariants($query);

        foreach (['payments/callback', 'payments/confirm', 'payments/verify'] as $path) {
            foreach ($variants as $variant) {
                foreach (['get', 'post'] as $method) {
                    try {
                        $request = $this->authedWithToken($token)->acceptJson();
                        $response = $method === 'post'
                            ? $request->asForm()->post($this->url($path), $variant)
                            : $request->get($this->url($path), $variant);
                        $result = $this->decode($response);
                        $last = $result;

                        Log::info('AccountApiService::notifySubscriptionMoyasarPayment attempt', [
                            'path' => $path,
                            'method' => strtoupper($method),
                            'ok' => $result['ok'] ?? false,
                            'status' => $result['status'] ?? null,
                            'message' => $result['message'] ?? '',
                            'query_keys' => array_keys($variant),
                        ]);

                        if ($this->paymentNotifyResponseSucceeded($result)) {
                            return $result;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('AccountApiService::notifySubscriptionMoyasarPayment failed', [
                            'path' => $path,
                            'method' => strtoupper($method),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        $moyasarId = trim((string) ($query['id'] ?? $query['moyasar_id'] ?? ''));
        if ($moyasarId !== '') {
            $webhookResult = $this->forwardVerifiedMoyasarWebhookToApi($moyasarId, $query);
            if ($this->paymentNotifyResponseSucceeded($webhookResult)) {
                return $webhookResult;
            }
            $last = $webhookResult['ok'] ?? false ? $webhookResult : $last;
        }

        return $last;
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<int, array<string, string>>
     */
    protected function buildMoyasarNotifyQueryVariants(array $query): array
    {
        $base = array_map(static fn ($value) => (string) $value, $query);
        $variants = [$base];

        $moyasarId = trim((string) ($base['id'] ?? ''));
        $paymentId = trim((string) ($base['payment_id'] ?? ''));
        $subscriptionId = trim((string) ($base['subscription_id'] ?? ''));

        if ($paymentId !== '' && $paymentId !== $moyasarId) {
            $variants[] = array_merge($base, ['id' => $paymentId]);
        }

        if ($moyasarId !== '') {
            $variants[] = array_merge($base, [
                'moyasar_id' => $moyasarId,
                'pay_id' => $moyasarId,
            ]);
        }

        if ($paymentId !== '' && $subscriptionId !== '') {
            $variants[] = [
                'payment_id' => $paymentId,
                'subscription_id' => $subscriptionId,
                'status' => (string) ($base['status'] ?? 'paid'),
                'device_id' => (string) ($base['device_id'] ?? $this->deviceId()),
            ];
        }

        return array_values(array_unique($variants, SORT_REGULAR));
    }

    /**
     * Mirror the mobile app Moyasar webhook so the external API marks the payment paid.
     *
     * @param  array<string, scalar|null>  $context
     */
    public function forwardVerifiedMoyasarWebhookToApi(string $moyasarId, array $context = []): array
    {
        $secretToken = trim((string) config('services.moyasar.webhook_secret_token', ''));
        if ($secretToken === '') {
            Log::warning('AccountApiService::forwardVerifiedMoyasarWebhookToApi missing MOYASAR_SECRET_TOKEN');

            return $this->empty('missing_webhook_secret');
        }

        $verified = app(MoyasarPaymentService::class)->verify($moyasarId);
        if (! is_array($verified) || strtolower((string) ($verified['status'] ?? '')) !== 'paid') {
            return $this->empty('moyasar_not_paid');
        }

        $metadata = is_array($verified['metadata'] ?? null) ? $verified['metadata'] : [];
        $paymentId = trim((string) (
            $context['payment_id']
            ?? $context['external_payment_id']
            ?? $metadata['payment_id']
            ?? ''
        ));
        if ($paymentId !== '') {
            $metadata['payment_id'] = $paymentId;
        }

        $payload = [
            'secret_token' => $secretToken,
            'data' => array_merge($verified, [
                'metadata' => $metadata,
            ]),
        ];

        try {
            $result = $this->decode(
                $this->http()->acceptJson()->post($this->url('webhook/payment'), $payload)
            );

            Log::info('AccountApiService::forwardVerifiedMoyasarWebhookToApi', [
                'moyasar_id' => $moyasarId,
                'payment_id' => $paymentId !== '' ? $paymentId : null,
                'ok' => $result['ok'] ?? false,
                'message' => $result['message'] ?? '',
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::forwardVerifiedMoyasarWebhookToApi failed', [
                'moyasar_id' => $moyasarId,
                'error' => $e->getMessage(),
            ]);

            return $this->empty($e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function paymentNotifyResponseSucceeded(array $result): bool
    {
        if ($result['ok'] ?? false) {
            return true;
        }

        $raw = is_array($result['raw'] ?? null) ? $result['raw'] : [];
        if (filter_var($raw['success'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        // Empty JSON body from webhook/payment means success in the mobile API.
        if (($result['status'] ?? 0) >= 200 && ($result['status'] ?? 0) < 300 && $raw === []) {
            return true;
        }

        $data = $result['data'] ?? null;
        if (is_array($data)) {
            $paymentStatus = strtolower((string) (
                $data['status']
                ?? data_get($data, 'payment.status', '')
            ));
            if (in_array($paymentStatus, ['paid', 'success', 'completed', 'confirmed'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build Moyasar notify query from subscription row + optional redirect params.
     *
     * @param  array<string, mixed>  $subscription
     * @param  array<string, mixed>  $extras
     * @return array<string, string>
     */
    public function buildMoyasarNotifyQuery(array $subscription, array $extras = []): array
    {
        $subscriptionId = (int) ($subscription['id'] ?? ($extras['subscription_id'] ?? 0));
        $externalPaymentId = (int) (
            $extras['external_payment_id']
            ?? $extras['payment_id']
            ?? data_get($subscription, 'payment.id', 0)
        );
        $moyasarId = trim((string) (
            $extras['moyasar_id']
            ?? $extras['id']
            ?? ''
        ));

        return array_filter([
            'id' => $moyasarId !== '' ? $moyasarId : null,
            'status' => (string) ($extras['status'] ?? 'paid'),
            'message' => isset($extras['message']) ? (string) $extras['message'] : null,
            'subscription_id' => $subscriptionId > 0 ? (string) $subscriptionId : null,
            'payment_id' => $externalPaymentId > 0 ? (string) $externalPaymentId : null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    public function isSubscriptionPaymentConfirmed(array $subscription): bool
    {
        $status = strtolower((string) ($subscription['status'] ?? ''));
        $paymentStatus = strtolower((string) (
            $subscription['payment_status']
            ?? data_get($subscription, 'payment.status', '')
        ));

        if (in_array($status, ['active', 'paid', 'confirmed', 'accepted'], true)) {
            return true;
        }

        if (in_array($paymentStatus, ['paid', 'success', 'completed', 'confirmed'], true)) {
            return true;
        }

        if (filter_var($subscription['is_paid'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return filter_var(data_get($subscription, 'payment.is_paid', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Retry forwarding a pending Moyasar callback while the confirm page polls the API.
     *
     * @param  array<string, mixed>  $pending
     */
    public function maybeForwardPendingMoyasarCallback(array $pending, ?string $token = null, ?array $subscription = null): array
    {
        $moyasarId = trim((string) ($pending['moyasar_id'] ?? ''));
        $externalPaymentId = (int) ($pending['external_payment_id'] ?? 0);
        if ($moyasarId === '' && $externalPaymentId <= 0 && is_array($subscription)) {
            $externalPaymentId = (int) data_get($subscription, 'payment.id', 0);
        }
        if ($moyasarId === '' && $externalPaymentId <= 0) {
            return $this->empty('missing_moyasar_id');
        }

        $lastForwardAt = (int) ($pending['last_forward_at'] ?? 0);
        if ($lastForwardAt > 0 && (time() - $lastForwardAt) < 3) {
            return $this->empty('forward_rate_limited');
        }

        $subscriptionId = (int) ($pending['subscription_id'] ?? 0);
        $query = $this->buildMoyasarNotifyQuery(
            is_array($subscription) ? $subscription : ['id' => $subscriptionId],
            $pending,
        );

        $result = $this->notifySubscriptionMoyasarPayment($query, $token);

        session([
            'pending_subscription_confirm' => array_merge($pending, [
                'last_forward_at' => time(),
                'last_forward_ok' => $this->paymentNotifyResponseSucceeded($result),
            ]),
        ]);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function extractApiValidationMessage(array $result): string
    {
        $raw = is_array($result['raw'] ?? null) ? $result['raw'] : [];
        $errors = $raw['errors'] ?? null;
        if (is_array($errors)) {
            foreach (['date', 'start_date'] as $field) {
                $fieldMessages = $errors[$field] ?? null;
                if (! is_array($fieldMessages)) {
                    if (is_string($fieldMessages) && trim($fieldMessages) !== '') {
                        return trim($fieldMessages);
                    }

                    continue;
                }
                foreach ($fieldMessages as $msg) {
                    if (is_string($msg) && trim($msg) !== '') {
                        return trim($msg);
                    }
                }
            }

            foreach ($errors as $messages) {
                if (is_array($messages)) {
                    foreach ($messages as $msg) {
                        if (is_string($msg) && trim($msg) !== '') {
                            return trim($msg);
                        }
                    }
                }
                if (is_string($messages) && trim($messages) !== '') {
                    return trim($messages);
                }
            }
        }

        foreach (['message', 'error', 'detail'] as $key) {
            $value = $raw[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $message = trim((string) ($result['message'] ?? ''));

        return $message !== '' ? $message : __('account.request_failed');
    }

    /**
     * @param  array<string, string>  $payload
     */
    protected function hashSubscriptionPayload(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractPaymentResource(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }

        if (isset($data['amount']) || isset($data['pay_amount']) || isset($data['publishable_api_key'])) {
            return $data;
        }

        if (isset($data['payment']) && is_array($data['payment'])) {
            return $data['payment'];
        }

        if (isset($data['subscription']['payment']) && is_array($data['subscription']['payment'])) {
            return $data['subscription']['payment'];
        }

        return null;
    }

    /**
     * @param  array<string, string>  $payload
     */
    protected function ensureSubscriptionDeliveryAddress(array &$payload, string $token): ?string
    {
        if (($payload['receiving'] ?? '') !== 'delivery') {
            return null;
        }

        $addressId = trim((string) ($payload['address_id'] ?? ''));
        if ($addressId === '' || $addressId === '0') {
            $addressId = trim((string) ($payload['selected_address_id'] ?? ''));
        }
        if ($addressId !== '' && $addressId !== '0') {
            $payload['address_id'] = $addressId;

            return null;
        }

        $addresses = app(ApiAuthService::class)->getAddresses($token, true, true);
        if ($addresses !== []) {
            $latest = collect($addresses)->sortByDesc(fn (array $row): int => (int) ($row['id'] ?? 0))->first();
            if (is_array($latest) && (int) ($latest['id'] ?? 0) > 0) {
                $payload['address_id'] = (string) $latest['id'];

                return null;
            }
        }

        return __('checkout.confirm_saved_address_before_payment');
    }

    /**
     * Parse /subscriptions/calculate data into SAR amounts for Moyasar.
     *
     * @return array{subtotal: float, delivery: float, discount: float, vat: float, total: float}|null
     */
    public function parseSubscriptionCalculateTotals(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }

        $source = $this->resolveCalculatePricingSource($data);

        $subtotal = $this->moneyAmount(
            $source['order_total'] ?? $source['subtotal'] ?? $source['price'] ?? $source['plan_price'] ?? 0
        );
        $delivery = $this->moneyAmount(
            $source['delievery'] ?? $source['delivery'] ?? $source['delivery_price'] ?? $source['delivery_fee'] ?? 0
        );
        $discount = $this->moneyAmount($source['discount'] ?? $source['discount_amount'] ?? 0);
        $total = $this->moneyAmount(
            $source['total'] ?? $source['after_discount'] ?? $source['grand_total'] ?? $source['amount'] ?? $source['final_total'] ?? null
        );

        if ($total < 0) {
            return null;
        }

        if ($total === 0.0 && $discount <= 0 && $subtotal <= 0) {
            return null;
        }
        $vat = $this->moneyAmount($source['vat'] ?? $source['tax'] ?? $source['vat_amount'] ?? 0);

        if ($vat <= 0 && $subtotal > 0) {
            $vat = max(0, $total - $subtotal - $delivery + $discount);
        }

        return [
            'subtotal' => round($subtotal > 0 ? $subtotal : max(0, $total - $delivery + $discount - $vat), 2),
            'delivery' => round($delivery, 2),
            'discount' => round($discount, 2),
            'vat' => round($vat, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * API calculate responses nest pricing under `checkout` (order_total, discount, total, delievery).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resolveCalculatePricingSource(array $data): array
    {
        $checkout = $data['checkout'] ?? null;

        if (! is_array($checkout)) {
            return $data;
        }

        return array_merge($data, $checkout);
    }

    protected function moneyAmount(mixed $value): float
    {
        if (is_array($value)) {
            $value = $value['amount'] ?? $value['value'] ?? 0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Sync a paid subscription payment to POST /subscriptions (+ optional start).
     *
     * @return array{ok: bool, message: string, external_subscription_id: int|null}
     */
    public function syncPaidPaymentToExternalSubscription(
        Payment $payment,
        ?string $token = null,
        ?ExternalDataService $externalData = null,
    ): array {
        if ($payment->status !== PaymentStatus::PAID) {
            return ['ok' => false, 'message' => 'payment_not_paid', 'external_subscription_id' => null];
        }

        if (! empty($payment->external_subscription_id)) {
            return [
                'ok' => true,
                'message' => 'already_synced',
                'external_subscription_id' => (int) $payment->external_subscription_id,
            ];
        }

        $externalData ??= app(ExternalDataService::class);
        $payload = SubscriptionCheckoutPayload::buildFromPayment($payment, $externalData);

        if (($payload['program_id'] ?? '') === '' || ($payload['plan_duration_id'] ?? '') === '' || ($payload['plan_duration_id'] ?? '') === '0') {
            return ['ok' => false, 'message' => 'missing_subscription_fields', 'external_subscription_id' => null];
        }

        if (($payload['plan_calory_id'] ?? '') === '' || ($payload['plan_calory_id'] ?? '') === '0') {
            return ['ok' => false, 'message' => 'missing_plan_calory_id', 'external_subscription_id' => null];
        }

        $result = $this->createSubscription($payload, $token);
        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($result['message'] ?? 'external_subscription_create_failed'),
                'external_subscription_id' => null,
            ];
        }

        $subscriptionId = $this->extractExternalSubscriptionId($result['data'] ?? []);

        $startDate = (string) ($payment->start_date ?: ($payload['start_date'] ?? ''));
        if ($subscriptionId !== null && $startDate !== '') {
            $start = $this->startSubscription($startDate, $token);
            if (! ($start['ok'] ?? false)) {
                Log::warning('AccountApiService::syncPaidPaymentToExternalSubscription start failed', [
                    'payment' => $payment->order_number,
                    'subscription_id' => $subscriptionId,
                    'message' => $start['message'] ?? '',
                ]);
            }
        }

        return [
            'ok' => true,
            'message' => '',
            'external_subscription_id' => $subscriptionId,
        ];
    }

    protected function extractExternalSubscriptionId(mixed $data): ?int
    {
        if (! is_array($data)) {
            return null;
        }

        $sub = $data['subscription'] ?? $data['data'] ?? $data;
        if (! is_array($sub)) {
            return null;
        }

        $id = $sub['id'] ?? $sub['subscription_id'] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    // ─── Orders ───────────────────────────────────────────────────────

    public function listOrders(string $status = 'active'): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            $params = array_filter([
                'status' => $status,
                'device_id' => $this->deviceId(),
            ], fn ($v) => $v !== null && $v !== '');

            $cacheKey = 'account_orders_'.md5((string) session('external_api_token', '').'|'.$status.'|'.app()->getLocale());
            $decoded = Cache::remember($cacheKey, now()->addSeconds(20), function () use ($params) {
                return $this->decode(
                    $this->authed()->get($this->url('orders'), $params)
                );
            });

            $apiRows = $this->extractRowsFromDecoded($decoded['data'] ?? null);
            $localRows = $this->localOrdersFallback($status);

            if ($apiRows === []) {
                if ($decoded['ok'] ?? false) {
                    $decoded['data'] = ['orders' => $localRows];

                    return $decoded;
                }

                return [
                    'ok' => true,
                    'status' => 200,
                    'data' => ['orders' => $localRows],
                    'message' => '',
                    'raw' => [],
                ];
            }

            if ($localRows !== []) {
                $apiOrderNumbers = collect($apiRows)
                    ->map(fn (array $r) => (string) ($r['order_number'] ?? $r['number'] ?? ''))
                    ->filter()
                    ->all();

                $mergedLocal = array_values(array_filter($localRows, function (array $row) use ($apiOrderNumbers): bool {
                    $num = (string) ($row['order_number'] ?? '');

                    return $num === '' || ! in_array($num, $apiOrderNumbers, true);
                }));

                if ($mergedLocal !== []) {
                    $decoded['data'] = ['orders' => array_values(array_merge($apiRows, $mergedLocal))];
                }
            }

            return $decoded;
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::listOrders failed', ['error' => $e->getMessage()]);

            return [
                'ok' => true,
                'status' => 200,
                'data' => ['orders' => $this->localOrdersFallback($status)],
                'message' => '',
                'raw' => [],
            ];
        }
    }

    public function clearOrdersCache(?string $token = null): void
    {
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '') {
            return;
        }
        foreach (['active', 'completed', 'cancelled', ''] as $status) {
            $cacheKey = 'account_orders_'.md5($token.'|'.$status.'|'.app()->getLocale());
            Cache::forget($cacheKey);
        }
    }

    public function showOrder(int $orderId): array
    {
        try {
            return $this->decode(
                $this->authed()->get($this->url('orders/'.$orderId))
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::showOrder failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractRowsFromDecoded(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        foreach (['orders', 'items', 'rows', 'data', 'response'] as $key) {
            $candidate = $data[$key] ?? null;
            if (! is_array($candidate)) {
                continue;
            }
            if (array_is_list($candidate)) {
                return array_values(array_filter($candidate, 'is_array'));
            }
            if (isset($candidate['data']) && is_array($candidate['data']) && array_is_list($candidate['data'])) {
                return array_values(array_filter($candidate['data'], 'is_array'));
            }
        }

        return [];
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return substr($digits, -9);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function localOrdersFallback(string $status): array
    {
        $verifiedPhone = (string) session('phone_verified', '');
        $phoneNorm = $this->normalizePhone($verifiedPhone);
        if ($phoneNorm === '') {
            return [];
        }

        $rows = Payment::query()
            ->when($phoneNorm !== '', function ($q) use ($phoneNorm) {
                $q->where('customer_phone_normalized', $phoneNorm);
            })
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->filter(function (Payment $payment) use ($phoneNorm): bool {
                if (($payment->customer_phone_normalized ?? '') !== '') {
                    return (string) $payment->customer_phone_normalized === $phoneNorm;
                }

                return $this->normalizePhone((string) $payment->customer_phone) === $phoneNorm;
            })
            ->values();

        $rows = $rows->filter(function (Payment $payment) use ($status): bool {
            $state = $payment->status->value;
            $normalized = strtolower(trim($status));

            if ($normalized === 'completed') {
                return in_array($state, [PaymentStatus::PAID->value, PaymentStatus::REFUNDED->value], true);
            }
            if ($normalized === 'cancelled') {
                return in_array($state, [PaymentStatus::FAILED->value, PaymentStatus::EXPIRED->value], true);
            }

            // active/default tab should still show purchased orders for the user.
            return in_array($state, [PaymentStatus::PAID->value, PaymentStatus::AUTHORIZED->value, PaymentStatus::PENDING->value], true);
        });

        return $rows->map(function (Payment $payment): array {
            $items = is_array($payment->cart_items) ? $payment->cart_items : [];

            return [
                'id' => $payment->external_order_id ? (int) $payment->external_order_id : null,
                'order_number' => $payment->order_number,
                'external_order_number' => $payment->external_order_number,
                'date' => optional($payment->created_at)->toDateString(),
                'created_at' => optional($payment->created_at)?->toIso8601String(),
                'delivery_date' => $payment->start_date,
                'status' => $payment->status->value,
                'amount' => $payment->amount_in_sar,
                'total' => $payment->amount_in_sar,
                'items' => array_values($items),
                'source' => 'web_payment',
            ];
        })->values()->all();
    }

    public function orderTrackings(int $orderId): array
    {
        try {
            return $this->decode(
                $this->authed()->get($this->url('orders/'.$orderId.'/orderTrackings'))
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::orderTrackings failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function orderInvoicePdfUrl(int $orderId): string
    {
        return $this->url('orders/'.$orderId.'/pdf');
    }

    /**
     * Create external order on API to keep web/app parity.
     */
    public function createOrder(array $payload, ?string $token = null): array
    {
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '') {
            return $this->empty(__('account.login_required'));
        }

        try {
            $response = $this->authedWithToken($token)->asForm()->post(
                $this->url('orders'),
                $payload
            );

            return $this->decode($response);
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::createOrder failed', ['error' => $e->getMessage()]);

            return $this->empty(__('account.request_failed'));
        }
    }

    /**
     * Sync a paid local payment to external /orders endpoint.
     *
     * @return array{ok: bool, message: string, external_order_id: int|null, external_order_number: string|null}
     */
    public function syncPaidPaymentToExternalOrder(Payment $payment, ?string $token = null): array
    {
        if ($payment->kind === PaymentKind::Subscription) {
            return [
                'ok' => false,
                'message' => 'wrong_payment_kind',
                'external_order_id' => null,
                'external_order_number' => null,
            ];
        }

        if ($payment->status !== PaymentStatus::PAID) {
            return ['ok' => false, 'message' => 'payment_not_paid', 'external_order_id' => null, 'external_order_number' => null];
        }

        if (! empty($payment->external_order_id) || ! empty($payment->external_order_number)) {
            return [
                'ok' => true,
                'message' => 'already_synced',
                'external_order_id' => $payment->external_order_id ? (int) $payment->external_order_id : null,
                'external_order_number' => $payment->external_order_number ?: null,
            ];
        }

        $checkoutPayload = is_array($payment->checkout_payload) ? $payment->checkout_payload : [];
        $deliveryDate = (string) ($payment->start_date ?: now()->toDateString());
        $branchId = (string) ($checkoutPayload['branch_id'] ?? config('services.external_api.default_order_branch_id', ''));
        $addressId = (string) ($checkoutPayload['selected_address_id'] ?? '');
        $note = (string) ($checkoutPayload['note'] ?? '');
        $useWallet = (string) ($checkoutPayload['use_wallet'] ?? '0');

        // External API expects branch_id; fallback to configured default when missing.
        if ($branchId === '') {
            return ['ok' => false, 'message' => 'missing_branch_id', 'external_order_id' => null, 'external_order_number' => null];
        }

        $payload = array_filter([
            'branch_id' => $branchId,
            'address_id' => $addressId !== '' ? $addressId : null,
            'note' => $note,
            'payment_option' => 'credit_card',
            'delivery_date' => $deliveryDate,
            'useWallet' => $useWallet,
        ], fn ($v) => $v !== null);

        $result = $this->createOrder($payload, $token);
        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($result['message'] ?? 'external_order_create_failed'),
                'external_order_id' => null,
                'external_order_number' => null,
            ];
        }

        [$externalOrderId, $externalOrderNumber] = $this->extractExternalOrderIdentifiers($result['data'] ?? []);

        return [
            'ok' => true,
            'message' => '',
            'external_order_id' => $externalOrderId,
            'external_order_number' => $externalOrderNumber,
        ];
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    protected function extractExternalOrderIdentifiers(mixed $data): array
    {
        if (! is_array($data)) {
            return [null, null];
        }

        $order = $data['order'] ?? $data['data'] ?? $data['response'] ?? $data;
        if (! is_array($order)) {
            return [null, null];
        }

        $id = $order['id'] ?? $order['order_id'] ?? null;
        $number = $order['order_number'] ?? $order['number'] ?? null;

        return [
            is_numeric($id) ? (int) $id : null,
            is_string($number) && $number !== '' ? $number : null,
        ];
    }

    // ─── Wallet ───────────────────────────────────────────────────────

    public function getWallet(string $type = 'all', ?string $dateFrom = null, ?string $dateTo = null, int $page = 1): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            $params = array_filter([
                'type' => $type,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'page' => $page,
                'device_id' => $this->deviceId(),
            ], fn ($v) => $v !== null && $v !== '');

            return $this->decode(
                $this->authed()->get($this->url('wallet'), $params)
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::getWallet failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    // ─── Notifications ────────────────────────────────────────────────

    public function notificationCount(): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            return $this->decode($this->authed()->get($this->url('notifications/count')));
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::notificationCount failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function unreadNotificationCount(): int
    {
        $result = $this->notificationCount();
        if (! ($result['ok'] ?? false)) {
            return 0;
        }
        $data = $result['data'] ?? [];
        if (! is_array($data)) {
            return is_numeric($data) ? (int) $data : 0;
        }

        return (int) ($data['count'] ?? $data['unread'] ?? $data['unread_count'] ?? $data['total'] ?? 0);
    }

    public function notifications(int $page = 1): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            return $this->decode(
                $this->authed()->get($this->url('notifications'), ['page' => $page])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::notifications failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function markNotificationsRead(): array
    {
        if (! $this->hasToken()) {
            return $this->empty(__('account.login_required'));
        }
        try {
            return $this->decode($this->authed()->get($this->url('notifications/read-all')));
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::markNotificationsRead failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }
}
