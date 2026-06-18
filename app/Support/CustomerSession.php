<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\AccountApiService;

/**
 * Reads the logged-in customer identity from the web session
 * (external API token flow).
 */
final class CustomerSession
{
    public static function isLoggedIn(): bool
    {
        return (bool) (
            session('external_api_token')
            && session('phone_verified')
            && ExternalApiConfig::sessionMatchesConfig()
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function profile(): array
    {
        return (array) session('external_api_profile', []);
    }

    public static function displayName(?string $fallback = null): string
    {
        $name = self::nameFromProfile(self::profile());

        if ($name === '' && self::isLoggedIn() && ! session('customer_profile_hydrated')) {
            self::hydrateProfileFromApi();
            $name = self::nameFromProfile(self::profile());
        }

        if ($name !== '') {
            return $name;
        }

        if ($fallback !== null) {
            return $fallback;
        }

        return (string) __('account.customer');
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private static function nameFromProfile(array $profile): string
    {
        return trim((string) (
            $profile['name']
            ?? $profile['full_name']
            ?? session('customer_name', '')
        ));
    }

    private static function hydrateProfileFromApi(): void
    {
        session(['customer_profile_hydrated' => true]);

        try {
            $result = app(AccountApiService::class)->getProfile();
        } catch (\Throwable) {
            return;
        }

        if (! ($result['ok'] ?? false)) {
            return;
        }

        $payload = $result['data'] ?? null;
        $profile = [];

        if (is_array($payload)) {
            if (array_is_list($payload)) {
                $profile = is_array($payload[0] ?? null) ? $payload[0] : [];
            } elseif (is_array($payload['profile'] ?? null)) {
                $profile = $payload['profile'];
            } elseif (is_array($payload['customer'] ?? null)) {
                $profile = $payload['customer'];
            } elseif (is_array($payload['data'] ?? null) && ! array_is_list($payload['data'])) {
                $profile = $payload['data'];
            } else {
                $profile = $payload;
            }
        }

        if ($profile === []) {
            return;
        }

        session([
            'external_api_profile' => array_merge(self::profile(), $profile),
        ]);
    }
}
