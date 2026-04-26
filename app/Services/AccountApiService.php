<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
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

    protected function deviceId(): string
    {
        return 'web-account-' . substr(hash('sha256', session()->getId()), 0, 40);
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

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $body['data'] ?? $body['response'] ?? $body,
            'message' => (string) ($body['message'] ?? ''),
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
        try {
            $params = array_filter([
                'status' => $status,
                'device_id' => $this->deviceId(),
            ], fn ($v) => $v !== null && $v !== '');

            return $this->decode(
                $this->authed()->get($this->url('orders'), $params)
            );
        } catch (\Throwable $e) {
            Log::warning('AccountApiService::listOrders failed', ['error' => $e->getMessage()]);

            return $this->empty();
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

    // ─── Wallet ───────────────────────────────────────────────────────

    public function getWallet(string $type = 'all', ?string $dateFrom = null, ?string $dateTo = null, int $page = 1): array
    {
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
