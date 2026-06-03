<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\ApiAuthService;
use App\Services\ExternalDataService;
use App\Services\Payment\MoyasarPaymentService;
use App\Support\SubscriptionCheckoutPayload;
use Illuminate\Http\Client\PendingRequest;
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
        return $this->http()->withToken($token);
    }

    protected function deviceId(): string
    {
        return 'web-account-' . substr(hash('sha256', session()->getId()), 0, 40);
    }

    protected function hasToken(): bool
    {
        return (string) session('external_api_token', '') !== '';
    }

    protected function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Decode an API response into a predictable array, unwrapping `data`
     * when present and surfacing the HTTP status/message fields.
     *
     * @return array{ok: bool, status: int, data: mixed, message: string, raw: array}
     */
    protected function decode(\Illuminate\Http\Client\Response $response): array
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

        return [
            'ok' => $logicalOk,
            'status' => $response->status(),
            'data' => $body['data'] ?? $body['response'] ?? $body,
            'message' => $bodyMessage,
            'raw' => $body,
        ];
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

    public function updateSubscriptionStatus(string $status, ?string $pausedDate = null, ?string $reactivateDate = null): array
    {
        try {
            $payload = array_filter([
                'status' => $status,
                'paused_date' => $pausedDate,
                'reactivate_date' => $reactivateDate,
            ], fn ($v) => $v !== null && $v !== '');

            return $this->decode(
                $this->authed()->asForm()->post($this->url('subscriptions/updateStatus'), $payload)
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::updateSubscriptionStatus failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function skipDay(int $dietId, string $dateFrom, string $dateTo): array
    {
        try {
            return $this->decode(
                $this->authed()
                    ->asForm()
                    ->post($this->url('subscriptions/skipDay?date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo)), [
                        'diet_id' => (string) $dietId,
                    ])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::skipDay failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function restoreDay(int $dietId, string $date): array
    {
        try {
            return $this->decode(
                $this->authed()
                    ->asForm()
                    ->post($this->url('subscriptions/restoreDay?date=' . urlencode($date)), [
                        'diet_id' => (string) $dietId,
                    ])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::restoreDay failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function cancelSubscriptionInfo(string $date): array
    {
        try {
            return $this->decode(
                $this->authed()->get($this->url('subscriptions/cancel'), ['date' => $date])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::cancelSubscriptionInfo failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function cancelSubscription(string $date, string $reason): array
    {
        try {
            return $this->decode(
                $this->authed()->asForm()->post($this->url('subscriptions/cancel'), [
                    'date' => $date,
                    'cancel_reason' => $reason,
                ])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::cancelSubscription failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function listInvoices(): array
    {
        try {
            return $this->decode(
                $this->authed()->get($this->url('subscriptions/invoices/index'))
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::listInvoices failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function getReplaceMealOptions(int $planMenuId, string $date, int $dietId, int $mealId): array
    {
        try {
            return $this->decode(
                $this->authed()->get($this->url('subscriptions/replaceMeal'), [
                    'plan_menu_id' => $planMenuId,
                    'date' => $date,
                    'diet_id' => $dietId,
                    'meal_id' => $mealId,
                ])
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::getReplaceMealOptions failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function replaceMeal(string $date, int $dietId, int $mealId, int $replacedDietId): array
    {
        try {
            return $this->decode(
                $this->authed()->asForm()->post($this->url('subscriptions/replaceMeal?date=' . urlencode($date)), [
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
        $payload = SubscriptionCheckoutPayload::clampPayloadStartDate(
            $payload,
            app(ExternalDataService::class),
        );

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

        $addresses = app(ApiAuthService::class)->getAddresses($token, true, false);
        $address = collect($addresses)->first(
            fn (array $row): bool => (int) ($row['id'] ?? 0) === $addressId
        );
        if (! is_array($address)) {
            $address = null;
        }

        $daysResult = $this->getAddressDeliveryDays($addressId, $token);
        $daysData = is_array($daysResult['data'] ?? null) ? $daysResult['data'] : null;

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
            return __('checkout.subscription_delivery_config_missing');
        }

        return null;
    }

    /**
     * Use POST /subscriptions/calculate (same as mobile app) for authoritative start date.
     *
     * @param  array<string, string>  $payload
     * @return array{ok: bool, message: string, min_start_date: string|null, adjusted_start_date: string|null, payload: array<string, string>}
     */
    protected function syncSubscriptionStartDateFromCalculate(array $payload, string $token): array
    {
        $calcPayload = $payload;
        unset($calcPayload['payment_option']);

        $calc = $this->calculateSubscription($calcPayload, $token);
        if (! ($calc['ok'] ?? false)) {
            $message = $this->extractApiValidationMessage($calc);
            $minStartDate = SubscriptionCheckoutPayload::extractMinStartDateFromApiResult($calc);
            if ($minStartDate === '') {
                $minStartDate = SubscriptionCheckoutPayload::parseMinimumDateFromValidationMessage($message);
            }
            if ($minStartDate !== '') {
                $minStartDate = SubscriptionCheckoutPayload::reconcileApiMinimumStartDate($minStartDate);
            }

            return [
                'ok' => false,
                'message' => $message,
                'min_start_date' => $minStartDate !== '' ? $minStartDate : null,
                'adjusted_start_date' => null,
                'payload' => $payload,
            ];
        }

        $data = is_array($calc['data'] ?? null) ? $calc['data'] : [];
        $apiMin = SubscriptionCheckoutPayload::normalizeStartDate(
            (string) ($data['min_start_date'] ?? $data['first_available_date_for_subscription'] ?? '')
        );
        if ($apiMin === '') {
            $apiMin = SubscriptionCheckoutPayload::normalizeStartDate(
                (string) ($data['first_available_date'] ?? '')
            );
        }

        $current = SubscriptionCheckoutPayload::normalizeStartDate(
            (string) ($payload['date'] ?? $payload['start_date'] ?? '')
        );
        $adjusted = null;
        if ($apiMin !== '' && ($current === '' || $current < $apiMin)) {
            $payload['date'] = $apiMin;
            $payload['start_date'] = $apiMin;
            $adjusted = $apiMin;
        }

        return [
            'ok' => true,
            'message' => '',
            'min_start_date' => $apiMin !== '' ? $apiMin : null,
            'adjusted_start_date' => $adjusted,
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
        $token = (string) ($token ?: session('external_api_token', ''));
        if ($token === '') {
            return [
                'ok' => false,
                'message' => __('account.login_required'),
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'bootstrap' => null,
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
                'bootstrap' => null,
            ];
        }

        $payload = SubscriptionCheckoutPayload::clampPayloadStartDate(
            $payload,
            app(ExternalDataService::class),
        );

        $enrichError = $this->enrichSubscriptionDeliveryPayload($payload, $token);
        if (($payload['receiving'] ?? '') === 'delivery' && ! isset($payload['addresses[0][region_duration_id]'])) {
            return [
                'ok' => false,
                'message' => $enrichError ?: __('checkout.subscription_delivery_config_missing'),
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'bootstrap' => null,
            ];
        }

        $adjustedStartDate = null;
        $minStartDate = null;

        $schedule = $this->syncSubscriptionStartDateFromCalculate($payload, $token);
        if (! ($schedule['ok'] ?? false)) {
            $message = (string) ($schedule['message'] ?? __('account.request_failed'));
            $minStartDate = (string) ($schedule['min_start_date'] ?? '');
            $dateRelated = SubscriptionCheckoutPayload::isStartDateValidationMessage($message)
                || $minStartDate !== '';

            return [
                'ok' => false,
                'message' => $dateRelated
                    ? SubscriptionCheckoutPayload::resolveStartDateErrorMessage($message, $minStartDate)
                    : $message,
                'min_start_date' => $dateRelated ? ($minStartDate !== '' ? $minStartDate : null) : null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'bootstrap' => null,
            ];
        }

        $payload = $schedule['payload'];
        if (! empty($schedule['adjusted_start_date'])) {
            $adjustedStartDate = (string) $schedule['adjusted_start_date'];
        }
        if (! empty($schedule['min_start_date'])) {
            $minStartDate = (string) $schedule['min_start_date'];
        }

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
                    'bootstrap' => $bootstrap,
                ];
            }
        }

        $result = $this->createSubscription($payload, $token);
        $minStartDate = null;
        $dateRetryCount = 0;

        while (! ($result['ok'] ?? false) && $dateRetryCount < 3) {
            $message = $this->extractApiValidationMessage($result);
            if (! SubscriptionCheckoutPayload::isStartDateValidationMessage($message)) {
                break;
            }

            $parsedMin = SubscriptionCheckoutPayload::extractMinStartDateFromApiResult($result);
            if ($parsedMin === '') {
                $parsedMin = SubscriptionCheckoutPayload::parseMinimumDateFromValidationMessage($message);
            }
            if ($parsedMin === '') {
                break;
            }

            $currentDate = SubscriptionCheckoutPayload::normalizeStartDate(
                (string) ($payload['date'] ?? $payload['start_date'] ?? '')
            );
            $nextDate = SubscriptionCheckoutPayload::nextStartDateAfterApiRejection($currentDate, $parsedMin);
            $parsedMin = SubscriptionCheckoutPayload::reconcileApiMinimumStartDate($parsedMin);

            if ($currentDate !== '' && $nextDate === $currentDate) {
                $minStartDate = $parsedMin;
                break;
            }

            session()->forget('checkout_api_subscription_checkout');
            $payload['date'] = $nextDate;
            $payload['start_date'] = $nextDate;
            $minStartDate = SubscriptionCheckoutPayload::reconcileApiMinimumStartDate($nextDate);
            $adjustedStartDate = $nextDate;
            $payloadHash = $this->hashSubscriptionPayload($payload);
            $dateRetryCount++;
            $result = $this->createSubscription($payload, $token);
        }

        if (! ($result['ok'] ?? false)) {
            $message = $this->extractApiValidationMessage($result);
            if ($minStartDate === null) {
                $minStartDate = SubscriptionCheckoutPayload::extractMinStartDateFromApiResult($result);
            }
            if ($minStartDate === null || $minStartDate === '') {
                $minStartDate = SubscriptionCheckoutPayload::parseMinimumDateFromValidationMessage($message);
            }
            if ($minStartDate !== null && $minStartDate !== '') {
                $minStartDate = SubscriptionCheckoutPayload::reconcileApiMinimumStartDate($minStartDate);
            }
            $dateRelated = SubscriptionCheckoutPayload::isStartDateValidationMessage($message)
                || $dateRetryCount > 0;

            return [
                'ok' => false,
                'message' => $dateRelated
                    ? SubscriptionCheckoutPayload::resolveStartDateErrorMessage($message, $minStartDate)
                    : $message,
                'min_start_date' => $dateRelated ? $minStartDate : null,
                'adjusted_start_date' => null,
                'subscription_id' => null,
                'bootstrap' => null,
            ];
        }

        $createData = is_array($result['data'] ?? null) ? $result['data'] : [];
        if ($createData === [] && is_array($result['raw']['data'] ?? null)) {
            $createData = $result['raw']['data'];
        }

        $bootstrap = $this->extractMoyasarBootstrapFromCreateResponse($createData, $result['raw'] ?? null);
        if ($bootstrap === null) {
            return [
                'ok' => false,
                'message' => __('checkout.subscription_payment_unavailable'),
                'min_start_date' => null,
                'adjusted_start_date' => null,
                'subscription_id' => $this->extractExternalSubscriptionId($createData),
                'bootstrap' => null,
            ];
        }

        $subscriptionId = (int) ($bootstrap['subscription_id'] ?? $this->extractExternalSubscriptionId($createData) ?? 0);

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
            'adjusted_start_date' => $adjustedStartDate,
            'subscription_id' => $subscriptionId,
            'bootstrap' => $bootstrap,
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

        $publishableKey = app(MoyasarPaymentService::class)->resolvePublishableKey(
            (string) (
                $payment['publishable_api_key']
                ?? $payment['publishable_key']
                ?? ''
            )
        );

        if ($publishableKey === '') {
            return null;
        }

        $subscriptionId = null;
        if (is_array($data)) {
            $subscriptionId = $this->extractExternalSubscriptionId($data);
        }

        return [
            'amount_halalas' => $amountHalalas,
            'publishable_key' => $publishableKey,
            'currency' => (string) ($payment['currency'] ?? 'SAR'),
            'description' => (string) ($payment['description'] ?? ''),
            'metadata' => $metadata,
            'subscription_id' => $subscriptionId,
        ];
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

        $addresses = app(ApiAuthService::class)->getAddresses($token, true, false);
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

        $total = $this->moneyAmount($data['total'] ?? $data['grand_total'] ?? $data['amount'] ?? $data['final_total'] ?? null);
        if ($total <= 0) {
            return null;
        }

        $subtotal = $this->moneyAmount($data['subtotal'] ?? $data['price'] ?? $data['plan_price'] ?? 0);
        $delivery = $this->moneyAmount($data['delivery'] ?? $data['delivery_price'] ?? $data['delivery_fee'] ?? 0);
        $discount = $this->moneyAmount($data['discount'] ?? $data['discount_amount'] ?? 0);
        $vat = $this->moneyAmount($data['vat'] ?? $data['tax'] ?? $data['vat_amount'] ?? 0);

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
                $this->authed()->get($this->url('orders/' . $orderId))
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
                $this->authed()->get($this->url('orders/' . $orderId . '/orderTrackings'))
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::orderTrackings failed', ['error' => $e->getMessage()]);

            return $this->empty();
        }
    }

    public function orderInvoicePdfUrl(int $orderId): string
    {
        return $this->url('orders/' . $orderId . '/pdf');
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
        try {
            return $this->decode($this->authed()->get($this->url('notifications/count')));
        } catch (\Throwable $e) {
            return $this->empty();
        }
    }

    public function notifications(int $page = 1): array
    {
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
        try {
            return $this->decode($this->authed()->get($this->url('notifications/read-all')));
        } catch (\Throwable $e) {
            return $this->empty();
        }
    }
}
