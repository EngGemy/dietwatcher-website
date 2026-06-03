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

        $durations = $external->getCheckoutPlanDurations($programId);
        $durationRow = null;
        foreach ($durations as $row) {
            if (is_array($row) && (int) ($row['id'] ?? 0) === $durationId) {
                $durationRow = $row;
                break;
            }
        }
        $minStart = self::minimumStartDateForDuration($durationRow);
        $startDate = self::normalizeStartDate((string) ($validated['start_date'] ?? ''));
        if ($startDate === '' || $startDate < $minStart) {
            $startDate = $minStart;
        }

        $payload = [
            'program_id' => (string) $programId,
            'plan_id' => (string) $planId,
            'plan_duration_id' => (string) max(0, $durationId),
            'plan_calory_id' => (string) max(0, $caloryId),
            'receiving' => $receiving,
            'with_support' => '0',
            'with_weekend' => '0',
            'start_date' => $startDate,
            'date' => $startDate,
            'payment_option' => 'credit_card',
            'useWallet' => (string) ($validated['useWallet'] ?? $validated['use_wallet'] ?? '0'),
        ];

        $promo = (string) ($validated['promocode_name'] ?? $validated['coupon'] ?? '');
        if ($promo !== '') {
            $payload['promocode_name'] = $promo;
        }

        $note = (string) ($validated['note'] ?? '');
        if ($note !== '') {
            $payload['note'] = $note;
        }

        if ($receiving === 'delivery') {
            $addressId = (string) ($validated['selected_address_id'] ?? '');
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

        return array_filter($payload, static fn ($v) => $v !== null && $v !== '');
    }

    public static function defaultCheckoutMinimumStartDate(): string
    {
        $now = now(config('app.timezone', 'Asia/Riyadh'));
        $noonCutoff = $now->copy()->startOfDay()->addHours(12);
        $daysToAdd = $now->lt($noonCutoff) ? 2 : 3;

        return $now->copy()->startOfDay()->addDays($daysToAdd)->format('Y-m-d');
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
     * Earliest subscription start: noon cutoff (+2 days before 12:00, +3 days after).
     *
     * @param  array<string, mixed>|null  $durationRow
     */
    public static function minimumStartDateForDuration(?array $durationRow): string
    {
        return self::defaultCheckoutMinimumStartDate();
    }

    /**
     * Reject parsed API minimums that are unreasonably far in the future (bad regex matches).
     */
    public static function sanitizeApiMinimumStartDate(string $candidate): string
    {
        $normalized = self::normalizeStartDate($candidate);
        if ($normalized === '') {
            return '';
        }

        try {
            $date = \Illuminate\Support\Carbon::parse($normalized);
            $floor = \Illuminate\Support\Carbon::parse(self::defaultCheckoutMinimumStartDate());
            if ($date->lt($floor)) {
                return $floor->format('Y-m-d');
            }
            if ($date->gt(now()->addYears(3))) {
                return '';
            }

            return $normalized;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @param  array<string, mixed>  $apiResult
     */
    public static function extractMinStartDateFromApiResult(array $apiResult): string
    {
        $raw = is_array($apiResult['raw'] ?? null) ? $apiResult['raw'] : [];
        foreach (['min_start_date', 'minimum_start_date', 'available_from'] as $key) {
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

    /**
     * @param  array<int, array<string, mixed>>  $durations
     */
    public static function minimumStartDateForDurationId(array $durations, ?int $durationId): string
    {
        if ($durationId !== null && $durationId > 0) {
            foreach ($durations as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ((int) ($row['id'] ?? 0) === $durationId) {
                    return self::minimumStartDateForDuration($row);
                }
            }
        }

        return self::minimumStartDateForDuration(null);
    }

    public static function parseMinimumDateFromValidationMessage(string $message): string
    {
        if (preg_match('#\b(20\d{2}-\d{2}-\d{2})\b#', $message, $matches)) {
            return self::sanitizeApiMinimumStartDate($matches[1]);
        }

        if (preg_match('#\b(\d{2}-\d{2}-20\d{2})\b#', $message, $matches)) {
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

    public static function formatMinimumStartDateLabel(string $ymd): string
    {
        return \Illuminate\Support\Carbon::parse($ymd)
            ->locale(app()->getLocale())
            ->translatedFormat('d M Y');
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
        $programId = (int) ($payload['program_id'] ?? 0);
        $durationId = (int) ($payload['plan_duration_id'] ?? 0);
        if ($programId <= 0) {
            return $payload;
        }

        $durationRow = null;
        foreach ($external->getCheckoutPlanDurations($programId) as $row) {
            if (is_array($row) && (int) ($row['id'] ?? 0) === $durationId) {
                $durationRow = $row;
                break;
            }
        }

        $min = self::minimumStartDateForDuration($durationRow);
        $current = self::normalizeStartDate((string) ($payload['date'] ?? $payload['start_date'] ?? ''));
        if ($current === '' || $current < $min) {
            $payload['date'] = $min;
            $payload['start_date'] = $min;
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
