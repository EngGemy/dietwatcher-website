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
     * Resolve delivery zone / city id from every known address payload shape.
     */
    public static function resolveZoneId(?array $address, int $fallback = 0): int
    {
        if (! is_array($address)) {
            return $fallback > 0 ? $fallback : 0;
        }

        foreach ([
            $address['zone_id'] ?? null,
            $address['city_id'] ?? null,
            data_get($address, 'zone.id'),
            data_get($address, 'city.id'),
            data_get($address, 'district.zone_id'),
            data_get($address, 'district.city_id'),
            data_get($address, 'district.zone.id'),
            data_get($address, 'district.city.id'),
            data_get($address, 'district.city.zone_id'),
        ] as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return $fallback > 0 ? $fallback : 0;
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

        return self::resolveDeliveryTimeSlots($enriched) !== [];
    }

    /**
     * Collect delivery time slots from every known address/days payload shape.
     *
     * @return array<int, array{id: int, duration: string, durationText: string, time: string, label: string}>
     */
    public static function resolveDeliveryTimeSlots(?array $address, ?array $daysData = null): array
    {
        if (is_array($address)) {
            $fromDistrict = self::normalizeRegionDurations(self::districtDurations($address));
            if ($fromDistrict !== []) {
                return $fromDistrict;
            }

            foreach (['region_durations', 'regionDurations'] as $key) {
                $raw = $address[$key] ?? null;
                if (! is_array($raw) || $raw === []) {
                    continue;
                }
                $list = array_is_list($raw) ? $raw : array_values(array_filter($raw, 'is_array'));
                $normalized = self::normalizeRegionDurations($list);
                if ($normalized !== []) {
                    return $normalized;
                }
            }

            foreach (['region_duration', 'regionDuration'] as $key) {
                $nested = $address[$key] ?? null;
                if (! is_array($nested)) {
                    continue;
                }
                $normalized = self::normalizeRegionDurations([$nested]);
                if ($normalized !== []) {
                    return $normalized;
                }
            }

            $regionId = (int) ($address['region_duration_id'] ?? $address['regionDurationId'] ?? 0);
            if ($regionId > 0) {
                return self::normalizeRegionDurations([['id' => $regionId]]);
            }
        }

        return self::deliveryTimeSlotsFromDaysData($daysData);
    }

    /**
     * @return array<int, array{id: int, duration: string, durationText: string, time: string, label: string}>
     */
    public static function deliveryTimeSlotsFromDaysData(?array $daysData): array
    {
        if (! is_array($daysData)) {
            return [];
        }

        $districtRaw = data_get($daysData, 'district.durations');
        if (is_array($districtRaw) && $districtRaw !== []) {
            $list = array_is_list($districtRaw) ? $districtRaw : array_values(array_filter($districtRaw, 'is_array'));
            $normalized = self::normalizeRegionDurations($list);
            if ($normalized !== []) {
                return $normalized;
            }
        }

        foreach (['region_durations', 'regionDurations'] as $key) {
            $raw = data_get($daysData, $key);
            if (! is_array($raw) || $raw === []) {
                continue;
            }
            $list = array_is_list($raw) ? $raw : array_values(array_filter($raw, 'is_array'));
            $normalized = self::normalizeRegionDurations($list);
            if ($normalized !== []) {
                return $normalized;
            }
        }

        foreach (['region_duration', 'regionDuration'] as $key) {
            $nested = data_get($daysData, $key);
            if (! is_array($nested)) {
                continue;
            }
            $normalized = self::normalizeRegionDurations([$nested]);
            if ($normalized !== []) {
                return $normalized;
            }
        }

        $regionId = (int) data_get($daysData, 'region_duration_id', data_get($daysData, 'regionDurationId', 0));
        if ($regionId > 0) {
            return self::normalizeRegionDurations([['id' => $regionId]]);
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<int, string>
     */
    public static function addressDistrictSearchTexts(?array $address): array
    {
        if (! is_array($address)) {
            return [];
        }

        $texts = [];
        $seen = [];
        $push = static function (mixed $value) use (&$texts, &$seen): void {
            if ($value === null) {
                return;
            }
            if (is_array($value)) {
                foreach ($value as $part) {
                    if (is_string($part) && trim($part) !== '') {
                        $push($part);
                    }
                }

                return;
            }
            $text = trim((string) $value);
            if ($text === '' || isset($seen[$text])) {
                return;
            }
            $seen[$text] = true;
            $texts[] = $text;
        };

        $push(self::districtDisplayName($address));
        $push($address['district_name'] ?? null);
        $push(data_get($address, 'district.district_identifier'));
        $push($address['description'] ?? null);
        $push($address['title'] ?? null);
        $push($address['address'] ?? null);
        $push($address['full_address'] ?? null);

        foreach (preg_split('/[,،]/u', (string) ($address['description'] ?? '')) ?: [] as $part) {
            $push(trim($part));
        }

        return $texts;
    }

    /**
     * Match Google / saved-address text fragments to GET /districts rows.
     *
     * @param  array<int, string>  $texts
     * @param  array<int, array<string, mixed>>  $districtRows
     */
    public static function matchDistrictIdFromTexts(array $texts, array $districtRows): int
    {
        $normalizedNeedles = array_values(array_unique(array_filter(array_map(
            static fn (string $text): string => self::normalizeDistrictMatchText($text),
            $texts,
        ))));

        if ($normalizedNeedles === []) {
            return 0;
        }

        foreach ($districtRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $candidateId = (int) ($row['id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }

            $candidates = array_map(
                static fn (string $name): string => self::normalizeDistrictMatchText($name),
                self::districtNameCandidates($row),
            );
            $candidates = array_values(array_filter(array_unique($candidates)));
            if ($candidates === []) {
                continue;
            }

            foreach ($normalizedNeedles as $needle) {
                if ($needle === '') {
                    continue;
                }
                foreach ($candidates as $candidate) {
                    if ($candidate === '') {
                        continue;
                    }
                    if ($needle === $candidate || str_contains($needle, $candidate) || str_contains($candidate, $needle)) {
                        return $candidateId;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $districtRows
     */
    public static function resolveDistrictId(?array $address, array $districtRows = []): int
    {
        $id = self::districtId($address);
        if ($id > 0) {
            return $id;
        }

        if (! is_array($address) || $districtRows === []) {
            return 0;
        }

        return self::matchDistrictIdFromTexts(self::addressDistrictSearchTexts($address), $districtRows);
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public static function districtDisplayName(array $address): string
    {
        $district = $address['district'] ?? null;
        if (is_array($district)) {
            $name = $district['name'] ?? '';
            if (is_array($name)) {
                return (string) ($name[app()->getLocale()] ?? $name['ar'] ?? $name['en'] ?? '');
            }

            return (string) $name;
        }

        return (string) ($address['district_name'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    public static function districtNameCandidates(array $row): array
    {
        $out = [];
        foreach (['name', 'district_identifier', 'identifier', 'slug', 'name_ar', 'name_en', 'title'] as $key) {
            $value = $row[$key] ?? null;
            if (is_array($value)) {
                foreach ($value as $part) {
                    if (is_string($part) && trim($part) !== '') {
                        $out[] = trim($part);
                    }
                }

                continue;
            }
            if (is_string($value) && trim($value) !== '') {
                $out[] = trim($value);
            }
        }

        return array_values(array_unique($out));
    }

    public static function normalizeDistrictMatchText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $value) ?? $value;
        $value = preg_replace('/\b(district|riyadh|saudi|arabia)\b/i', '', $value) ?? $value;
        $value = str_replace(['حي', 'الرياض', 'السعودية'], '', $value);
        $value = preg_replace('/[^a-z\x{0600}-\x{06ff}0-9]+/u', '', $value) ?? $value;

        return trim($value);
    }

    /**
     * @return array<int, string>
     */
    public static function geocodeDistrictSearchTexts(?array $result): array
    {
        if (! is_array($result)) {
            return [];
        }

        $texts = [];
        if (! empty($result['formatted_address'])) {
            $texts[] = (string) $result['formatted_address'];
            foreach (preg_split('/[,،]/u', (string) $result['formatted_address']) ?: [] as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $texts[] = $part;
                }
            }
        }

        foreach ($result['address_components'] ?? [] as $component) {
            if (! is_array($component)) {
                continue;
            }
            $types = is_array($component['types'] ?? null) ? $component['types'] : [];
            $wanted = ['sublocality_level_1', 'sublocality_level_2', 'sublocality', 'neighborhood', 'administrative_area_level_2', 'locality'];
            if (! array_intersect($wanted, $types)) {
                continue;
            }
            foreach (['long_name', 'short_name'] as $key) {
                $name = trim((string) ($component[$key] ?? ''));
                if ($name !== '') {
                    $texts[] = $name;
                }
            }
        }

        return array_values(array_unique($texts));
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
