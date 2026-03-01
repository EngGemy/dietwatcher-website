<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private ?PendingRequest $request = null;
    private string $provider;

    public function __construct()
    {
        $this->provider = config('sms.default');
        $this->request = Http::baseUrl(config("sms.{$this->provider}.url"))
            ->withQueryParameters([
                'user' => config("sms.{$this->provider}.user"),
                'pwd' => config("sms.{$this->provider}.password"),
                'senderid' => config("sms.{$this->provider}.sender_id"),
                'CountryCode' => 966,
                'priority' => 'High',
            ]);
    }

    public static function create(): self
    {
        return new self;
    }

    public function send(string $mobile, string $message): ?string
    {
        $normalized = $this->normalizePhone($mobile);

        $response = $this->request->get('', [
            'mobileno' => $normalized,
            'msgtext' => $message,
        ]);

        $body = trim($response->body());

        Log::info('SMS API response', [
            'provider' => $this->provider,
            'phone' => $normalized,
            'status' => $response->status(),
            'response' => $body,
        ]);

        // Detect provider errors in response body
        if (str_contains(strtolower($body), 'not allowed')
            || str_contains(strtolower($body), 'error')
            || str_contains(strtolower($body), 'invalid')
            || str_contains(strtolower($body), 'fail')) {
            throw new \RuntimeException("SMS provider error: {$body}");
        }

        return $body;
    }

    /**
     * Normalize phone number: strip country code prefix, keep local number.
     * Input examples: +966532472930, 00966532472930, 0532472930, 532472930
     * Output: 532472930
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);
        $phone = preg_replace('/^(\+966|00966|966)/', '', $phone);
        $phone = ltrim($phone, '0');

        return $phone;
    }
}
