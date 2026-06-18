<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Single source for EXTERNAL_API_URL — used for HTTP clients, cache keys,
 * and session binding so a .env switch (dev → staging → production) does not
 * keep serving cached catalog data or tokens from the previous API host.
 */
final class ExternalApiConfig
{
    public static function baseUrl(): string
    {
        return rtrim((string) config('services.external_api.url', ''), '/');
    }

    /**
     * Short stable id for cache key namespaces (changes when .env URL changes).
     */
    public static function fingerprint(): string
    {
        $url = self::baseUrl();

        return $url === '' ? 'none' : substr(hash('sha256', $url), 0, 12);
    }

    public static function isConfigured(): bool
    {
        return self::baseUrl() !== '';
    }

    /**
     * Call whenever a customer API token is stored in session.
     */
    public static function bindSession(): void
    {
        session(['external_api_bound_url' => self::baseUrl()]);
    }

    public static function sessionMatchesConfig(): bool
    {
        $bound = rtrim((string) session('external_api_bound_url', ''), '/');
        if ($bound === '') {
            return true;
        }

        return $bound === self::baseUrl();
    }

    /**
     * Drop API auth when the configured base URL changed (e.g. dev → staging).
     */
    public static function clearMismatchedSession(): bool
    {
        if (self::sessionMatchesConfig()) {
            return false;
        }

        session()->forget([
            'external_api_token',
            'external_api_profile',
            'external_api_bound_url',
            'external_login_is_continue',
            'phone_verified',
            'customer_profile_hydrated',
            'customer_name',
        ]);

        return true;
    }

    /**
     * On first request after EXTERNAL_API_URL changes, flush app cache so catalog
     * pages pull fresh data without running artisan manually (config must be
     * reloaded via optimize:clear / config:cache on the server after .env edit).
     */
    public static function ensureCacheSyncedWithEnvironment(): void
    {
        $current = self::fingerprint();
        $path = storage_path('framework/external_api_fingerprint');
        $previous = is_file($path) ? trim((string) file_get_contents($path)) : null;

        if ($previous !== null && $previous !== $current) {
            Cache::flush();
            Log::info('External API URL changed — application cache cleared.', [
                'previous_fingerprint' => $previous,
                'current_fingerprint' => $current,
                'api_url' => self::baseUrl(),
            ]);
        }

        if ($previous !== $current) {
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            file_put_contents($path, $current);
        }
    }
}
