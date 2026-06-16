<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Client\Response;

class GeminiService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    private const TIMEOUT_SECONDS = 20;

    private const RATE_LIMIT_PER_MINUTE = 12;

    private const MAX_RETRIES = 3;

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
            Log::error('GeminiService::generate aborted: GEMINI_API_KEY not configured');

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
            Log::warning('GeminiService::generate aborted: no valid message contents after formatting');

            return null;
        }

        $configuredModel = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $models = array_values(array_unique(array_filter([
            $configuredModel,
            'gemini-2.5-flash-lite',
            'gemini-flash-latest',
        ], static fn (string $model): bool => trim($model) !== '')));

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => max(0.0, min(1.0, $temperature)),
                'maxOutputTokens' => $jsonMode ? 8192 : 4096,
            ],
        ];

        if ($jsonMode) {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
            $payload['generationConfig']['thinkingConfig'] = [
                'thinkingBudget' => (int) config('services.gemini.thinking_budget', 0),
            ];
        }

        $attemptTrail = [];

        foreach ($models as $model) {
            $result = $this->requestWithRetries($model, $apiKey, $payload);
            $attemptTrail[] = $result;

            if ($result['ok'] === true && is_string($result['text'])) {
                return trim($result['text']);
            }

            $reason = (string) ($result['reason'] ?? 'unknown');
            if (! in_array($reason, ['http_429', 'http_503', 'empty_parts', 'empty_text', 'finish_max_tokens'], true)) {
                Log::warning('GeminiService::generate stopping fallback chain due to non-retryable failure', [
                    'model' => $model,
                    'reason' => $reason,
                ]);
                break;
            }
        }

        Log::error('GeminiService::generate failed on all model attempts', [
            'attempts' => $attemptTrail,
        ]);

        return null;
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, text?: string, reason: string, model: string, status?: int, finish_reason?: string|null}
     */
    private function requestWithRetries(string $model, string $apiKey, array $payload): array
    {
        $url = sprintf(self::ENDPOINT, $model).'?key='.urlencode($apiKey);

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            Log::info('GeminiService::generate model attempt started', [
                'model' => $model,
                'attempt' => $attempt,
                'max_retries' => self::MAX_RETRIES,
            ]);

            try {
                $response = Http::timeout(self::TIMEOUT_SECONDS)
                    ->connectTimeout(8)
                    ->acceptJson()
                    ->post($url, $payload);
            } catch (\Throwable $e) {
                Log::error('GeminiService::generate request exception', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'ok' => false,
                    'reason' => 'exception',
                    'model' => $model,
                ];
            }

            if ($response->status() === 429 || $response->status() === 503) {
                $delayMs = $this->resolveRetryDelayMs($response, $attempt);
                Log::warning('GeminiService::generate retryable HTTP status', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'retry_delay_ms' => $delayMs,
                    'body' => mb_substr((string) $response->body(), 0, 800),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    $this->sleepMs($delayMs);
                    continue;
                }

                return [
                    'ok' => false,
                    'reason' => $response->status() === 429 ? 'http_429' : 'http_503',
                    'status' => $response->status(),
                    'model' => $model,
                ];
            }

            if (! $response->successful()) {
                Log::error('GeminiService::generate non-retryable HTTP error', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 800),
                ]);

                return [
                    'ok' => false,
                    'reason' => 'http_error',
                    'status' => $response->status(),
                    'model' => $model,
                ];
            }

            $json = $response->json();
            $candidate = (is_array($json['candidates'] ?? null) && isset($json['candidates'][0]) && is_array($json['candidates'][0]))
                ? $json['candidates'][0]
                : [];
            $finishReason = isset($candidate['finishReason']) && is_string($candidate['finishReason'])
                ? $candidate['finishReason']
                : null;

            if ($finishReason !== null && strtoupper($finishReason) === 'MAX_TOKENS') {
                Log::warning('GeminiService::generate candidate stopped by MAX_TOKENS', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'finish_reason' => $finishReason,
                    'body' => mb_substr((string) $response->body(), 0, 800),
                ]);

                return [
                    'ok' => false,
                    'reason' => 'finish_max_tokens',
                    'model' => $model,
                    'finish_reason' => $finishReason,
                    'status' => $response->status(),
                ];
            }

            if ($finishReason !== null && strtoupper($finishReason) !== 'STOP') {
                Log::warning('GeminiService::generate candidate finishReason is not STOP', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'finish_reason' => $finishReason,
                    'body' => mb_substr((string) $response->body(), 0, 800),
                ]);
            }

            $parts = $candidate['content']['parts'] ?? [];
            if (! is_array($parts) || $parts === []) {
                Log::warning('GeminiService::generate candidate has empty parts', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'finish_reason' => $finishReason,
                    'body' => mb_substr((string) $response->body(), 0, 800),
                ]);

                return [
                    'ok' => false,
                    'reason' => 'empty_parts',
                    'model' => $model,
                    'finish_reason' => $finishReason,
                    'status' => $response->status(),
                ];
            }

            $text = $this->extractText(is_array($json) ? $json : null);
            if ($text === null || trim($text) === '') {
                Log::warning('GeminiService::generate extracted empty text', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'finish_reason' => $finishReason,
                    'body' => mb_substr((string) $response->body(), 0, 800),
                ]);

                return [
                    'ok' => false,
                    'reason' => 'empty_text',
                    'model' => $model,
                    'finish_reason' => $finishReason,
                    'status' => $response->status(),
                ];
            }

            return [
                'ok' => true,
                'text' => trim($text),
                'reason' => 'ok',
                'model' => $model,
                'finish_reason' => $finishReason,
                'status' => $response->status(),
            ];
        }

        return [
            'ok' => false,
            'reason' => 'retries_exhausted',
            'model' => $model,
        ];
    }

    private function resolveRetryDelayMs(Response $response, int $attempt): int
    {
        $json = $response->json();
        $details = $json['error']['details'] ?? [];
        if (is_array($details)) {
            foreach ($details as $detail) {
                if (! is_array($detail)) {
                    continue;
                }

                $rawDelay = $detail['retryDelay'] ?? null;
                if (! is_string($rawDelay) || $rawDelay === '') {
                    continue;
                }

                if (preg_match('/^(\d+)(?:\.(\d+))?s$/', $rawDelay, $matches) === 1) {
                    $seconds = (int) $matches[1];
                    $fraction = isset($matches[2]) ? (float) ('0.'.$matches[2]) : 0.0;

                    return (int) (($seconds + $fraction) * 1000);
                }
            }
        }

        return (int) (1000 * (2 ** max(0, $attempt - 1)));
    }

    private function sleepMs(int $delayMs): void
    {
        if ($delayMs <= 0 || app()->environment('testing')) {
            return;
        }

        usleep($delayMs * 1000);
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
