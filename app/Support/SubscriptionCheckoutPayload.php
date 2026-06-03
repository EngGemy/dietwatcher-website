<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Payment;
use App\Services\ExternalDataService;

/**
 * Build external API subscription payloads from checkout cart / payment rows.
 */
final class SubscriptionCheckoutPayload
{
    /**
     * @param  array<string, mixed>  $cart
     * @return array{program_id: int, line_key: string|null, line: array<string, mixed>|null}
     */
    public static function firstPlanLine(array $cart): array
    {
        foreach ($cart as $key => $item) {
            if (! is_array($item)) {
                continue;
            }
            if (! empty($item['options']['duration_days'])) {
                return [
                    'program_id' => (int) ($item['id'] ?? 0),
                    'line_key' => is_string($key) ? $key : null,
                    'line' => $item,
                ];
            }
        }

        return ['program_id' => 0, 'line_key' => null, 'line' => null];
    }

    public static function isSubscriptionCart(array $cart): bool
    {
        return self::firstPlanLine($cart)['line'] !== null;
    }

    /**
     * @param  array<string, mixed>  $validated  Checkout form fields
     * @param  array<string, mixed>  $cart
     * @return array<string, string>
     */
    public static function buildFormPayload(
        array $validated,
        array $cart,
        ExternalDataService $external,
        ?int $planDurationIdOverride = null,
    ): array {
        $plan = self::firstPlanLine($cart);
        $line = $plan['line'];
        if ($line === null) {
            return [];
        }

        $programId = $plan['program_id'];
        $options = is_array($line['options'] ?? null) ? $line['options'] : [];
        $subscriptionPlanId = (int) ($options['subscription_plan_id'] ?? 0);
        $planId = $subscriptionPlanId > 0 ? $subscriptionPlanId : $programId;

        $requestedDurationId = $planDurationIdOverride !== null && $planDurationIdOverride > 0
            ? $planDurationIdOverride
            : (int) ($validated['plan_duration_id'] ?? 0);
        if ($requestedDurationId <= 0 && ! empty($options['duration_id'])) {
            $requestedDurationId = (int) $options['duration_id'];
        }
        $cartDurationDays = (int) ($options['duration_days'] ?? 0);
        $durationId = self::resolvePlanDurationId(
            $programId,
            $requestedDurationId,
            $cartDurationDays,
            $external,
        );
        if ($durationId <= 0) {
            return [];
        }

        $caloryId = (int) ($options['calorie_id'] ?? $options['plan_calory_id'] ?? 0);
        if ($caloryId <= 0) {
            $caloryId = self::resolvePlanCaloryId(
                $programId,
                $planId,
                (string) ($options['calories'] ?? ''),
                $external
            );
        }

        $deliveryType = (string) ($validated['delivery_type'] ?? 'home');
        $receiving = $deliveryType === 'pickup' ? 'pickup' : 'delivery';

        $startDate = self::normalizeStartDate((string) ($validated['start_date'] ?? ''));

        $withWeekend = self::resolveWithWeekend($cart, $external, $validated);
        $withPickup = $receiving === 'pickup' ? '1' : '0';

        $payload = [
            'program_id' => (string) $programId,
            'plan_id' => (string) $planId,
            'plan_duration_id' => (string) max(0, $durationId),
            'plan_calory_id' => (string) max(0, $caloryId),
            'receiving' => $receiving,
            'with_pickup' => $withPickup,
            'with_support' => '0',
            'with_weekend' => $withWeekend,
            'with_service' => '0',
            'payment_option' => 'credit_card',
            'useWallet' => (string) ($validated['useWallet'] ?? $validated['use_wallet'] ?? '0'),
        ];

        $zoneId = (int) ($validated['zone_id'] ?? 0);
        if ($zoneId > 0) {
            $payload['zone_id'] = (string) $zoneId;
        }

        $promo = (string) ($validated['promocode_name'] ?? $validated['coupon'] ?? '');
        if ($promo !== '') {
            $payload['promocode_name'] = $promo;
        }

        $note = (string) ($validated['note'] ?? '');
        if ($note !== '') {
            $payload['note'] = $note;
        }

        if ($receiving === 'delivery') {
            $addressId = (string) ($validated['selected_address_id'] ?? $validated['address_id'] ?? '');
            if ($addressId !== '' && $addressId !== '0') {
                $payload['address_id'] = $addressId;
            }
        } else {
            $branchId = (string) ($validated['branch_id'] ?? '');
            if ($branchId !== '' && $branchId !== '0') {
                $payload['branch_id'] = $branchId;
            }
        }

        $mealType = trim((string) ($options['mealType'] ?? ''));
        if ($mealType !== '' && $subscriptionPlanId <= 0) {
            $payload['meal_type'] = $mealType;
        }

        if ($startDate !== '') {
            $payload['start_date'] = $startDate;
            $payload['date'] = $startDate;
        }

        return array_filter($payload, static fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Resolve with_weekend from plan week_end_status (API) or validated input.
     *
     * @param  array<string, mixed>  $cart
     * @param  array<string, mixed>|null  $validated
     */
    public static function resolveWithWeekend(array $cart, ExternalDataService $external, ?array $validated = null): string
    {
        if (is_array($validated) && array_key_exists('with_weekend', $validated)) {
            return filter_var($validated['with_weekend'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        $plan = self::firstPlanLine($cart);
        if ($plan['line'] === null) {
            return '0';
        }

        $programId = $plan['program_id'];
        $options = is_array($plan['line']['options'] ?? null) ? $plan['line']['options'] : [];
        $subscriptionPlanId = (int) ($options['subscription_plan_id'] ?? 0);
        $planId = $subscriptionPlanId > 0 ? $subscriptionPlanId : $programId;

        $weekEndStatus = self::planWeekEndStatus($external, $programId, $planId);
        if ($weekEndStatus === 'with_week_end') {
            return '1';
        }
        if ($weekEndStatus === 'without_week_end') {
            return '0';
        }

        return '0';
    }

    /**
     * Minimal payload for POST /subscriptions/calculate (schedule / min start date).
     *
     * @param  array<string, mixed>  $cart
     * @param  array<string, mixed>|null  $validated
     * @return array<string, string>
     */
    public static function buildMinimalSchedulePayload(
        array $cart,
        ExternalDataService $external,
        ?array $validated = null,
    ): array {
        $validated = is_array($validated) ? $validated : [];
        $validated['delivery_type'] = $validated['delivery_type'] ?? 'home';
        $validated['start_date'] = '';

        $payload = self::buildFormPayload($validated, $cart, $external);
        unset($payload['payment_option'], $payload['start_date'], $payload['date'], $payload['promocode_name']);

        return $payload;
    }

    /**
     * Read authoritative min/default start date from calculate response data.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function extractApiMinStartDate(?array $data): string
    {
        if (! is_array($data)) {
            return '';
        }

        foreach ([
            'first_available_date_for_subscription',
            'min_start_date',
            'first_available_date',
            'minimum_start_date',
            'available_from',
        ] as $key) {
            $normalized = self::normalizeStartDate((string) ($data[$key] ?? ''));
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    protected static function planWeekEndStatus(ExternalDataService $external, int $programId, int $planId): ?string
    {
        if ($programId <= 0) {
            return null;
        }

        $program = $external->getProgram($programId);
        if ($program === null) {
            return null;
        }

        $plans = $program->subscription_plans ?? [];
        if (! is_array($plans)) {
            $plans = json_decode(json_encode($plans), true) ?? [];
        }

        foreach ($plans as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ($planId > 0 && (int) ($row['id'] ?? 0) !== $planId) {
                continue;
            }
            $status = (string) ($row['week_end_status'] ?? '');
            if ($status !== '') {
                return $status;
            }
        }

        return null;
    }

    public static function normalizeStartDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Normalize a date string from API validation messages (handles d-m-Y in errors).
     */
    public static function reconcileApiMinimumStartDate(string $candidate): string
    {
        return self::normalizeStartDate($candidate);
    }

    /**
     * Normalize API minimum date; reject unreasonably far future values from bad regex matches.
     */
    public static function sanitizeApiMinimumStartDate(string $candidate): string
    {
        $normalized = self::normalizeStartDate($candidate);
        if ($normalized === '') {
            return '';
        }

        try {
            if (\Illuminate\Support\Carbon::parse($normalized)->gt(now()->addYears(3))) {
                return '';
            }
        } catch (\Throwable) {
            return '';
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $apiResult
     */
    public static function extractMinStartDateFromApiResult(array $apiResult): string
    {
        $data = is_array($apiResult['data'] ?? null) ? $apiResult['data'] : [];
        $fromData = self::extractApiMinStartDate($data);
        if ($fromData !== '') {
            return $fromData;
        }

        $raw = is_array($apiResult['raw'] ?? null) ? $apiResult['raw'] : [];
        $rawData = is_array($raw['data'] ?? null) ? $raw['data'] : [];
        $fromRawData = self::extractApiMinStartDate($rawData);
        if ($fromRawData !== '') {
            return $fromRawData;
        }

        foreach (['min_start_date', 'minimum_start_date', 'available_from', 'first_available_date_for_subscription'] as $key) {
            $value = $raw[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $sanitized = self::sanitizeApiMinimumStartDate($value);
                if ($sanitized !== '') {
                    return $sanitized;
                }
            }
        }

        $errors = $raw['errors'] ?? null;
        if (is_array($errors)) {
            foreach (['date', 'start_date'] as $field) {
                $messages = $errors[$field] ?? null;
                if (is_string($messages)) {
                    $parsed = self::parseMinimumDateFromValidationMessage($messages);
                    if ($parsed !== '') {
                        return $parsed;
                    }
                }
                if (is_array($messages)) {
                    foreach ($messages as $msg) {
                        if (! is_string($msg)) {
                            continue;
                        }
                        $parsed = self::parseMinimumDateFromValidationMessage($msg);
                        if ($parsed !== '') {
                            return $parsed;
                        }
                    }
                }
            }
        }

        return self::sanitizeApiMinimumStartDate(
            self::parseMinimumDateFromValidationMessage((string) ($apiResult['message'] ?? ''))
        );
    }

    public static function parseMinimumDateFromValidationMessage(string $message): string
    {
        if (preg_match('#(20\d{2}-\d{2}-\d{2})#', $message, $matches)) {
            return self::sanitizeApiMinimumStartDate($matches[1]);
        }

        if (preg_match('#(\d{2}-\d{2}-20\d{2})#', $message, $matches)) {
            try {
                return self::sanitizeApiMinimumStartDate(
                    \Illuminate\Support\Carbon::createFromFormat('d-m-Y', $matches[1])->format('Y-m-d')
                );
            } catch (\Throwable) {
                return '';
            }
        }

        return '';
    }

    public static function isStartDateValidationMessage(string $message): bool
    {
        $message = trim($message);
        if ($message === '') {
            return false;
        }

        if (self::parseMinimumDateFromValidationMessage($message) !== '') {
            return true;
        }

        return (bool) preg_match(
            '#(start_date|start date|تاريخ|التاريخ|date\b)#iu',
            $message
        );
    }

    public static function formatMinimumStartDateLabel(string $ymd): string
    {
        $ymd = self::normalizeStartDate($ymd);
        if ($ymd === '') {
            return '';
        }

        return \Illuminate\Support\Carbon::parse($ymd)
            ->locale(app()->getLocale())
            ->translatedFormat('d M Y');
    }

    /**
     * API weekday index: Sat=1, Sun=2, Mon=3, Tue=4, Wed=5, Thu=6, Fri=7.
     *
     * @return array<int, int>
     */
    public static function defaultDeliveryWeekdays(bool $withWeekend): array
    {
        return $withWeekend ? [1, 2, 3, 4, 5, 6, 7] : [2, 3, 4, 5, 6];
    }

    /**
     * @param  array<string, mixed>|null  $address
     * @param  array<string, mixed>|null  $daysData
     * @return array<int, int>
     */
    public static function extractDeliveryDays(?array $address, ?array $daysData, bool $withWeekend): array
    {
        $days = [];

        if (is_array($address['days'] ?? null)) {
            foreach ($address['days'] as $entry) {
                if (is_numeric($entry)) {
                    $days[] = (int) $entry;
                }
            }
        }

        if ($days === [] && is_array($daysData)) {
            $selected = $daysData['selected'] ?? null;
            if (is_array($selected)) {
                foreach ($selected as $entry) {
                    if (is_numeric($entry)) {
                        $days[] = (int) $entry;
                    }
                }
            }

            $list = $daysData['days'] ?? null;
            if (is_array($list)) {
                foreach ($list as $entry) {
                    if (is_numeric($entry)) {
                        $days[] = (int) $entry;
                    } elseif (is_array($entry)) {
                        $day = (int) ($entry['day'] ?? $entry['id'] ?? $entry['value'] ?? 0);
                        if ($day > 0) {
                            $days[] = $day;
                        }
                    }
                }
            }
            for ($i = 0; $i < 7; $i++) {
                $value = $daysData["days[{$i}]"] ?? null;
                if (is_numeric($value)) {
                    $days[] = (int) $value;
                }
            }
        }

        $days = array_values(array_unique(array_filter($days, static fn (int $d): bool => $d >= 1 && $d <= 7)));

        return $days !== [] ? $days : self::defaultDeliveryWeekdays($withWeekend);
    }

    /**
     * @param  array<string, mixed>|null  $address
     * @param  array<string, mixed>|null  $daysData
     * @param  array<int, array<string, mixed>>  $durationRows
     */
    public static function resolveRegionDurationId(
        ?array $address,
        ?array $daysData,
        int $zoneId,
        int $planDurationId,
        array $durationRows,
    ): int {
        $districtDurations = is_array($address) ? data_get($address, 'district.durations', []) : [];
        if (is_array($districtDurations)) {
            foreach ($districtDurations as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0) {
                    return $id;
                }
            }
        }

        $helperId = \App\Support\AddressCheckoutHelper::firstRegionDurationId($address);
        if ($helperId > 0) {
            return $helperId;
        }

        foreach (['region_duration_id', 'regionDurationId'] as $key) {
            $fromDays = is_array($daysData) ? data_get($daysData, $key) : null;
            if (is_numeric($fromDays) && (int) $fromDays > 0) {
                return (int) $fromDays;
            }
            $fromAddress = is_array($address) ? data_get($address, $key) : null;
            if (is_numeric($fromAddress) && (int) $fromAddress > 0) {
                return (int) $fromAddress;
            }
        }

        foreach ([$daysData, $address] as $source) {
            if (! is_array($source)) {
                continue;
            }
            $nested = $source['region_duration'] ?? $source['regionDuration'] ?? null;
            if (is_array($nested)) {
                $id = (int) ($nested['id'] ?? $nested['region_duration_id'] ?? 0);
                if ($id > 0) {
                    return $id;
                }
            }
        }

        foreach ($durationRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ($planDurationId > 0 && (int) ($row['id'] ?? 0) !== $planDurationId) {
                continue;
            }
            $direct = (int) ($row['region_duration_id'] ?? 0);
            if ($direct > 0) {
                return $direct;
            }
            $regions = $row['region_durations'] ?? $row['regionDurations'] ?? [];
            if (! is_array($regions)) {
                continue;
            }
            foreach ($regions as $regionRow) {
                if (! is_array($regionRow)) {
                    continue;
                }
                $regionZoneId = (int) (
                    $regionRow['zone_id']
                    ?? $regionRow['city_id']
                    ?? $regionRow['region_id']
                    ?? 0
                );
                if ($zoneId > 0 && $regionZoneId > 0 && $regionZoneId !== $zoneId) {
                    continue;
                }
                $id = (int) ($regionRow['id'] ?? $regionRow['region_duration_id'] ?? 0);
                if ($id > 0) {
                    return $id;
                }
            }
            if ($planDurationId > 0 && (int) ($row['id'] ?? 0) === $planDurationId) {
                foreach ($regions as $regionRow) {
                    if (! is_array($regionRow)) {
                        continue;
                    }
                    $id = (int) ($regionRow['id'] ?? $regionRow['region_duration_id'] ?? 0);
                    if ($id > 0) {
                        return $id;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Merge API doc delivery address matrix into a subscription payload.
     *
     * @param  array<string, string>  $payload
     * @param  array<string, mixed>|null  $address
     * @param  array<string, mixed>|null  $daysData
     * @param  array<int, array<string, mixed>>  $durationRows
     * @return array<string, string>
     */
    public static function appendDeliveryAddressFields(
        array $payload,
        int $addressId,
        ?array $address,
        ?array $daysData,
        array $durationRows,
    ): array {
        if ($addressId <= 0) {
            return $payload;
        }

        $zoneId = (int) ($payload['zone_id'] ?? $address['city_id'] ?? $address['city']['id'] ?? $address['zone_id'] ?? 0);
        $planDurationId = (int) ($payload['plan_duration_id'] ?? 0);
        $withWeekend = filter_var($payload['with_weekend'] ?? '0', FILTER_VALIDATE_BOOLEAN);
        $regionDurationId = self::resolveRegionDurationId(
            $address,
            $daysData,
            $zoneId,
            $planDurationId,
            $durationRows,
        );
        $days = self::extractDeliveryDays($address, $daysData, $withWeekend);

        $payload['addresses[0][address_id]'] = (string) $addressId;
        $companyId = (string) ($address['company_id'] ?? $address['company']['id'] ?? '');
        if ($companyId !== '' && $companyId !== '0') {
            $payload['addresses[0][company_id]'] = $companyId;
        }
        if ($regionDurationId > 0) {
            $payload['addresses[0][region_duration_id]'] = (string) $regionDurationId;
        }
        foreach ($days as $index => $day) {
            $payload["addresses[0][days][{$index}]"] = (string) $day;
        }

        return $payload;
    }

    public static function startDateBeforeMinimumMessage(string $minDateYmd): string
    {
        return __('checkout.start_date_before_minimum', [
            'date' => self::formatMinimumStartDateLabel($minDateYmd),
        ]);
    }

    public static function resolveStartDateErrorMessage(string $message, ?string $minStartDateYmd = null): string
    {
        $message = trim($message);
        $min = self::normalizeStartDate((string) ($minStartDateYmd));
        if ($min === '') {
            $min = self::parseMinimumDateFromValidationMessage($message);
        }

        $generic = trim(__('account.request_failed'));
        $isGeneric = $message === '' || $message === $generic;
        $hasDateHint = self::parseMinimumDateFromValidationMessage($message) !== '';

        if ($min !== '') {
            $min = self::sanitizeApiMinimumStartDate($min);
        }

        if ($min !== '' && ($isGeneric || $hasDateHint)) {
            return self::startDateBeforeMinimumMessage($min);
        }

        return $message !== '' ? $message : $generic;
    }

    /**
     * @param  array<string, string>  $payload
     * @return array<string, string>
     */
    public static function clampPayloadStartDate(array $payload, ExternalDataService $external): array
    {
        $current = self::normalizeStartDate((string) ($payload['date'] ?? $payload['start_date'] ?? ''));
        if ($current !== '') {
            $payload['date'] = $current;
            $payload['start_date'] = $current;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $checkoutPayload
     * @param  array<int, array<string, mixed>>  $cartItems
     * @return array<string, string>
     */
    public static function buildFromPayment(Payment $payment, ExternalDataService $external): array
    {
        $stored = is_array($payment->checkout_payload['subscription_api'] ?? null)
            ? $payment->checkout_payload['subscription_api']
            : [];

        if ($stored !== []) {
            $out = [];
            foreach ($stored as $k => $v) {
                if ($v === null || $v === '') {
                    continue;
                }
                $out[(string) $k] = (string) $v;
            }
            $out['payment_option'] = $out['payment_option'] ?? 'credit_card';
            $out['useWallet'] = $out['useWallet'] ?? (string) ($payment->checkout_payload['use_wallet'] ?? '0');
            if (! isset($out['start_date']) && $payment->start_date) {
                $out['start_date'] = (string) $payment->start_date;
            }

            return $out;
        }

        $cart = is_array($payment->cart_items) ? $payment->cart_items : [];
        $validated = [
            'delivery_type' => $payment->delivery_type ?? ($payment->checkout_payload['delivery_type'] ?? 'home'),
            'branch_id' => $payment->checkout_payload['branch_id'] ?? null,
            'selected_address_id' => $payment->checkout_payload['selected_address_id'] ?? null,
            'start_date' => $payment->start_date,
            'coupon' => $payment->coupon,
            'note' => $payment->checkout_payload['note'] ?? '',
            'use_wallet' => $payment->checkout_payload['use_wallet'] ?? '0',
        ];

        $durationId = (int) ($payment->checkout_payload['plan_duration_id'] ?? 0);

        return self::buildFormPayload($validated, $cart, $external, $durationId > 0 ? $durationId : null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $durations
     */
    private static function resolvePlanDurationIdFromList(array $durations, int $requestedId, int $cartDurationDays): int
    {
        if ($durations === []) {
            return 0;
        }

        foreach ($durations as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0 && $requestedId > 0 && $id === $requestedId) {
                return $requestedId;
            }
        }

        if ($cartDurationDays > 0) {
            foreach ($durations as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ((int) ($row['days'] ?? 0) === $cartDurationDays) {
                    $matchId = (int) ($row['id'] ?? 0);
                    if ($matchId > 0) {
                        return $matchId;
                    }
                }
            }
        }

        $pick = collect($durations)->first(fn ($d) => is_array($d) && ($d['is_default'] ?? false))
            ?? collect($durations)->first(fn ($d) => is_array($d) && (int) ($d['id'] ?? 0) > 0);

        return is_array($pick) ? (int) ($pick['id'] ?? 0) : 0;
    }

    /**
     * Map checkout duration selection to an id returned by GET /programs/{id}/durations.
     * Stale cart or nested-plan ids (e.g. from meal-plan profile only) must not be sent to POST /subscriptions.
     */
    public static function resolvePlanDurationId(
        int $programId,
        int $requestedId,
        int $cartDurationDays,
        ExternalDataService $external,
    ): int {
        if ($programId <= 0) {
            return 0;
        }

        $authoritative = $external->getAuthoritativePlanDurations($programId);
        $resolved = self::resolvePlanDurationIdFromList($authoritative, $requestedId, $cartDurationDays);
        if ($resolved > 0) {
            return $resolved;
        }

        $checkout = $external->getCheckoutPlanDurations($programId);
        if ($checkout === [] || $checkout === $authoritative) {
            return 0;
        }

        return self::resolvePlanDurationIdFromList($checkout, $requestedId, $cartDurationDays);
    }

    public static function resolvePlanCaloryId(
        int $programId,
        int $planId,
        string $caloriesRange,
        ExternalDataService $external,
    ): int {
        if ($programId <= 0) {
            return 0;
        }

        $program = $external->getProgram($programId);
        if ($program !== null && ! empty($program->subscription_plans)) {
            $plans = is_array($program->subscription_plans)
                ? $program->subscription_plans
                : json_decode(json_encode($program->subscription_plans), true);

            foreach ($plans ?? [] as $sp) {
                if (! is_array($sp)) {
                    continue;
                }
                if ($planId > 0 && (int) ($sp['id'] ?? 0) !== $planId) {
                    continue;
                }
                $id = self::matchCalorieRow($sp['calories'] ?? [], $caloriesRange);
                if ($id > 0) {
                    return $id;
                }
            }
        }

        $apiCalories = $external->getPlanCalories($planId > 0 ? $planId : $programId);
        $id = self::matchCalorieRow($apiCalories, $caloriesRange);
        if ($id > 0) {
            return $id;
        }

        $default = collect($apiCalories)->firstWhere('is_default', true) ?? ($apiCalories[0] ?? null);

        return (int) ($default['id'] ?? 0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private static function matchCalorieRow(array $rows, string $caloriesRange): int
    {
        $needle = self::normalizeCalorieRange($caloriesRange);
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            $amount = self::normalizeCalorieRange((string) ($row['amount'] ?? $row['range'] ?? ''));
            $min = (int) ($row['min_amount'] ?? 0);
            $max = (int) ($row['max_amount'] ?? 0);
            $rangeFromMinMax = ($min > 0 || $max > 0) ? self::normalizeCalorieRange("{$min}-{$max}") : '';

            if ($needle !== '' && ($amount === $needle || $rangeFromMinMax === $needle)) {
                return $id;
            }
        }

        return 0;
    }

    private static function normalizeCalorieRange(string $value): string
    {
        $value = trim(str_replace(['–', ' '], ['-', ''], $value));

        return $value;
    }
}
