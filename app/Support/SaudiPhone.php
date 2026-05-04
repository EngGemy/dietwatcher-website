<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Saudi mobile normalization (966 + 9 digits starting with 5).
 */
final class SaudiPhone
{
    /**
     * Last 9 significant digits (national mobile without leading 0), for comparisons.
     */
    public static function matchKey(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) ($phone ?? '')) ?? '';
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
     * International format without plus: 9665XXXXXXXX, or empty if invalid Saudi mobile.
     */
    public static function to966(?string $phone): string
    {
        $nine = self::matchKey($phone);
        if (strlen($nine) !== 9 || $nine[0] !== '5') {
            return '';
        }

        return '966'.$nine;
    }

    /**
     * E.164 format with plus, for external API calls only.
     * Returns '+966XXXXXXXXX' or empty string if invalid.
     */
    public static function toE164(?string $phone): string
    {
        $nine = self::matchKey($phone);
        if (strlen($nine) !== 9 || $nine[0] !== '5') {
            return '';
        }

        return '+966'.$nine;
    }

    /**
     * Nine digits for the checkout input (after +966 prefix).
     */
    public static function localDigitsForInput(?string $phone): string
    {
        return self::matchKey($phone);
    }
}
