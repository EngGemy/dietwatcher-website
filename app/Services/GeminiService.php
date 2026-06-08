<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class GeminiService
{
    private const MODEL = 'gemini-2.0-flash';

    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    private const TIMEOUT_SECONDS = 20;

    private const RATE_LIMIT_PER_MINUTE = 12;

    public function isConfigured(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function generate(string $systemPrompt, array $messages, bool $jsonMode = false, float $temperature = 0.35): ?string
    {
        $apiKey = (string) config('services.gemini.key', '');
        if ($apiKey === '') {
            Log::info('GeminiService::generate skipped — GEMINI_API_KEY not set');

            return null;
        }

        if (! $this->acquireRateLimit()) {
            Log::warning('GeminiService::generate rate limited', [
                'key' => $this->rateLimitKey(),
            ]);

            return null;
        }

        $contents = $this->formatContents($messages);
        if ($contents === []) {
            return null;
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => $contents,
            'generationConfig' => array_filter([
                'temperature' => max(0.0, min(1.0, $temperature)),
                'maxOutputTokens' => 4096,
                'responseMimeType' => $jsonMode ? 'application/json' : null,
            ]),
        ];

        $url = sprintf(self::ENDPOINT, self::MODEL).'?key='.urlencode($apiKey);

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(8)
                ->acceptJson()
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('GeminiService::generate HTTP error', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);

                return null;
            }

            $text = $this->extractText($response->json());
            if ($text === null || trim($text) === '') {
                Log::warning('GeminiService::generate empty response');

                return null;
            }

            return trim($text);
        } catch (\Throwable $e) {
            Log::error('GeminiService::generate failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<int, array{role: string, parts: array<int, array{text: string}>}>
     */
    private function formatContents(array $messages): array
    {
        $contents = [];

        foreach ($messages as $message) {
            $content = trim((string) ($message['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $role = strtolower((string) ($message['role'] ?? 'user'));
            $contents[] = [
                'role' => $role === 'assistant' || $role === 'model' ? 'model' : 'user',
                'parts' => [
                    ['text' => $content],
                ],
            ];
        }

        return $contents;
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function extractText(?array $json): ?string
    {
        if (! is_array($json)) {
            return null;
        }

        $candidates = $json['candidates'] ?? [];
        if (! is_array($candidates) || $candidates === []) {
            return null;
        }

        $parts = $candidates[0]['content']['parts'] ?? [];
        if (! is_array($parts)) {
            return null;
        }

        $chunks = [];
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $chunks[] = $part['text'];
            }
        }

        return $chunks === [] ? null : implode("\n", $chunks);
    }

    private function acquireRateLimit(): bool
    {
        $key = $this->rateLimitKey();

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_PER_MINUTE)) {
            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    private function rateLimitKey(): string
    {
        $userId = auth()->id();
        $suffix = $userId !== null ? 'user:'.$userId : 'ip:'.request()->ip();

        return 'gemini:'.$suffix;
    }
}
