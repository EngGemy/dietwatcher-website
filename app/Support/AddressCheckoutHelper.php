<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Saved-address helpers for checkout (dedupe, duplicate detection, API response unwrap).
 */
final class AddressCheckoutHelper
{
    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function dedupeRows(array $rows): array
    {
        $byKey = [];
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }
            $lat = round((float) ($row['latitude'] ?? 0), 5);
            $lng = round((float) ($row['longitude'] ?? 0), 5);
            $districtId = (int) ($row['district_id'] ?? $row['district']['id'] ?? 0);
            $desc = strtolower(trim(preg_replace('/\s+/', ' ', (string) ($row['description'] ?? $row['title'] ?? ''))));
            $key = $districtId.'|'.$lat.'|'.$lng.'|'.$desc;
            $prev = $byKey[$key] ?? null;
            if (! is_array($prev) || (int) ($row['id'] ?? 0) > (int) ($prev['id'] ?? 0)) {
                $byKey[$key] = $row;
            }
        }

        return array_values($byKey);
    }

    /**
     * @param  array<int, array<string, mixed>>  $existing
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>|null
     */
    public static function findDuplicate(array $existing, array $candidate): ?array
    {
        $cLat = round((float) ($candidate['latitude'] ?? 0), 5);
        $cLng = round((float) ($candidate['longitude'] ?? 0), 5);
        $cDist = (int) ($candidate['district_id'] ?? 0);
        $cDesc = strtolower(trim(preg_replace('/\s+/', ' ', (string) ($candidate['description'] ?? ''))));

        foreach ($existing as $row) {
            if (! is_array($row)) {
                continue;
            }
            $lat = round((float) ($row['latitude'] ?? 0), 5);
            $lng = round((float) ($row['longitude'] ?? 0), 5);
            $dist = (int) ($row['district_id'] ?? $row['district']['id'] ?? 0);
            $desc = strtolower(trim(preg_replace('/\s+/', ' ', (string) ($row['description'] ?? $row['title'] ?? ''))));

            if ($dist === $cDist && $lat === $cLat && $lng === $cLng && ($desc === $cDesc || $desc === '' || $cDesc === '')) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function unwrapStoredAddress(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }

        if (isset($data['id'])) {
            return $data;
        }

        if (isset($data['data']) && is_array($data['data'])) {
            return self::unwrapStoredAddress($data['data']);
        }

        if (isset($data['address']) && is_array($data['address'])) {
            return self::unwrapStoredAddress($data['address']);
        }

        return null;
    }
}
