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

        $payload = [
            'program_id' => (string) $programId,
            'plan_id' => (string) $planId,
            'plan_duration_id' => (string) max(0, $durationId),
            'plan_calory_id' => (string) max(0, $caloryId),
            'receiving' => $receiving,
            'with_support' => '0',
            'with_weekend' => '0',
            'start_date' => self::normalizeStartDate((string) ($validated['start_date'] ?? ''))
                ?: self::defaultCheckoutMinimumStartDate(),
            'date' => self::normalizeStartDate((string) ($validated['start_date'] ?? ''))
                ?: self::defaultCheckoutMinimumStartDate(),
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
        return now()->addHours(48)->format('Y-m-d');
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
     * Checkout minimum is always 48 hours — not the plan duration's catalog metadata dates.
     *
     * @param  array<string, mixed>|null  $durationRow
     */
    public static function minimumStartDateForDuration(?array $durationRow): string
    {
        return self::defaultCheckoutMinimumStartDate();
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
        if (preg_match('#(\d{4}-\d{2}-\d{2})#', $message, $matches)) {
            return $matches[1];
        }

        if (preg_match('#(\d{2}-\d{2}-\d{4})#', $message, $matches)) {
            try {
                return \Illuminate\Support\Carbon::createFromFormat('d-m-Y', $matches[1])->format('Y-m-d');
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

        if ($min !== '' && ($isGeneric || $hasDateHint)) {
            return self::startDateBeforeMinimumMessage($min);
        }

        return $message !== '' ? $message : $generic;
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

        $durations = $external->getAuthoritativePlanDurations($programId);
        if ($durations === []) {
            return 0;
        }

        $validIds = [];
        foreach ($durations as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $validIds[] = $id;
                if ($requestedId > 0 && $id === $requestedId) {
                    return $requestedId;
                }
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
