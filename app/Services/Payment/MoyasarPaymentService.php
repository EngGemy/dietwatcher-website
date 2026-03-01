<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarPaymentService
{
    private string $secretKey;
    private string $publishableKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->secretKey = config('services.moyasar.secret_key', '');
        $this->publishableKey = config('services.moyasar.publishable_key', '');
        $this->apiUrl = config('services.moyasar.api_url', 'https://api.moyasar.com/v1');
    }

    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }

    /**
     * Verify a payment with Moyasar API.
     */
    public function verify(string $moyasarPaymentId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get("{$this->apiUrl}/payments/{$moyasarPaymentId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Moyasar verification failed', [
                'payment_id' => $moyasarPaymentId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Moyasar verification exception', [
                'payment_id' => $moyasarPaymentId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Update payment record with Moyasar response data.
     */
    public function updatePaymentFromResponse(Payment $payment, array $response): Payment
    {
        $moyasarStatus = $response['status'] ?? 'failed';

        $sourceType = $response['source']['type'] ?? null;
        $paymentMethod = $sourceType ? PaymentMethod::tryFrom($sourceType) : null;

        $payment->update([
            'moyasar_id' => $response['id'] ?? $payment->moyasar_id,
            'status' => $this->mapStatus($moyasarStatus),
            'payment_method' => $paymentMethod,
            'description' => $response['description'] ?? null,
            'source_type' => $response['source']['type'] ?? null,
            'card_type' => $response['source']['company'] ?? null,
            'masked_pan' => $response['source']['number'] ?? null,
            'message' => $response['message'] ?? null,
            'raw_response' => $response,
        ]);

        return $payment->fresh();
    }

    /**
     * Map Moyasar status string to our PaymentStatus enum.
     */
    private function mapStatus(string $moyasarStatus): PaymentStatus
    {
        return match ($moyasarStatus) {
            'paid' => PaymentStatus::PAID,
            'authorized' => PaymentStatus::AUTHORIZED,
            'failed' => PaymentStatus::FAILED,
            'refunded' => PaymentStatus::REFUNDED,
            default => PaymentStatus::FAILED,
        };
    }

    /**
     * Parse Moyasar error message into user-friendly Arabic message.
     */
    public function parseErrorMessage(?string $message): string
    {
        $source = strtolower($message ?? '');

        if (str_contains($source, 'authentication declined') || str_contains($source, 'card authentication declined')) {
            return __('payment.errors.auth_declined');
        }
        if (str_contains($source, 'authentication attempt was rejected')) {
            return __('payment.errors.rejected_by_bank');
        }
        if (str_contains($source, 'authentication cancelled')) {
            return __('payment.errors.cancelled');
        }
        if (str_contains($source, 'authentication is unavailable')) {
            return __('payment.errors.unavailable');
        }
        if (str_contains($source, 'service error')) {
            return __('payment.errors.service_error');
        }

        return __('payment.errors.generic');
    }
}
