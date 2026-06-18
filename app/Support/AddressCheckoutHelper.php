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

    public static function districtId(?array $address): int
    {
        if (! is_array($address)) {
            return 0;
        }

        return (int) (
            $address['district_id']
            ?? $address['district']['id']
            ?? 0
        );
    }

    /**
     * RegionDuration rows attached to the address district (subscription delivery slots).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function districtDurations(?array $address): array
    {
        if (! is_array($address)) {
            return [];
        }

        $raw = data_get($address, 'district.durations', []);
        if ($raw instanceof \stdClass) {
            $raw = json_decode(json_encode($raw), true);
        }
        if (! is_array($raw)) {
            return [];
        }
        if (! array_is_list($raw)) {
            $raw = array_values(array_filter($raw, static fn ($row): bool => is_array($row)));
        }

        return array_values(array_filter($raw, static fn ($row): bool => is_array($row) && (int) ($row['id'] ?? 0) > 0));
    }

    public static function firstRegionDurationId(?array $address): int
    {
        foreach (self::districtDurations($address) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{id: int, duration: string, durationText: string, time: string, label: string}>
     */
    public static function normalizeRegionDurations(array $rows): array
    {
        $locale = app()->getLocale();
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $duration = trim((string) ($row['duration'] ?? ''));
            $durationText = trim((string) ($row['durationText'] ?? $row['duration_text'] ?? ''));
            $time = trim((string) ($row['time'] ?? ''));
            $normalized[] = [
                'id' => $id,
                'duration' => $duration,
                'durationText' => $durationText,
                'time' => $time,
                'label' => self::regionDurationLabel($row, $locale),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function regionDurationLabel(array $row, string $locale = 'en'): string
    {
        $time = trim((string) ($row['time'] ?? ''));
        $durationText = trim((string) ($row['durationText'] ?? $row['duration_text'] ?? ''));
        if ($time !== '' && $durationText !== '') {
            return $durationText.' — '.$time;
        }
        if ($time !== '') {
            return $time;
        }
        if ($durationText !== '') {
            return $durationText;
        }

        $duration = trim((string) ($row['duration'] ?? ''));
        if ($duration !== '') {
            return $duration;
        }

        $id = (int) ($row['id'] ?? 0);

        return $id > 0 ? (string) $id : '';
    }

    /**
     * @param  array<int, array{id: int, duration: string, durationText: string, time: string, label: string}>  $slots
     */
    public static function isValidRegionDurationId(int $regionDurationId, array $slots): bool
    {
        if ($regionDurationId <= 0) {
            return false;
        }

        foreach ($slots as $slot) {
            if ((int) ($slot['id'] ?? 0) === $regionDurationId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $allAddresses
     */
    public static function enrichAddressDistrictDurations(array $address, array $allAddresses): array
    {
        if (self::districtDurations($address) !== []) {
            return $address;
        }

        $districtId = self::districtId($address);
        if ($districtId <= 0) {
            return $address;
        }

        foreach ($allAddresses as $peer) {
            if (! is_array($peer)) {
                continue;
            }
            if (self::districtId($peer) !== $districtId) {
                continue;
            }
            $durations = self::districtDurations($peer);
            if ($durations === []) {
                continue;
            }
            $district = is_array($address['district'] ?? null) ? $address['district'] : ['id' => $districtId];
            $district['durations'] = $durations;
            $address['district'] = $district;

            return $address;
        }

        return $address;
    }

    /**
     * @param  array<int, array<string, mixed>>  $allAddresses
     */
    public static function isDeliverableForSubscription(array $address, array $allAddresses = []): bool
    {
        $enriched = $allAddresses !== [] ? self::enrichAddressDistrictDurations($address, $allAddresses) : $address;

        return self::firstRegionDurationId($enriched) > 0;
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public static function isCantModify(array $address): bool
    {
        return filter_var(
            $address['cant_modify'] ?? $address['cantModify'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function markDeliverability(array $rows): array
    {
        return array_values(array_map(static function (array $row) use ($rows): array {
            $row['is_deliverable'] = self::isDeliverableForSubscription($row, $rows);
            $row['cant_modify'] = self::isCantModify($row);

            return $row;
        }, array_filter($rows, 'is_array')));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function findById(array $rows, int $addressId): ?array
    {
        if ($addressId <= 0) {
            return null;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ((int) ($row['id'] ?? 0) === $addressId) {
                return $row;
            }
        }

        return null;
    }
}
