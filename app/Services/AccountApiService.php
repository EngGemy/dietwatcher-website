<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
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

    public function startSubscription(string $date): array
    {
        try {
            return $this->decode(
                $this->authed()->asForm()->post($this->url('subscriptions/start'), ['date' => $date])
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
