<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Subscription status helpers aligned with the mobile API lifecycle.
 */
final class SubscriptionLifecycle
{
    /** @var list<string> */
    private const ACTIVE = ['active', 'running', 'started', 'ongoing', 'current'];

    /** @var list<string> */
    private const PAUSED = ['paused', 'pausing', 'hold', 'on_hold'];

    /** @var list<string> */
    private const TERMINAL = [
        'cancelled', 'canceled', 'ended', 'expired', 'terminated', 'stopped',
        'completed', 'closed', 'inactive',
    ];

    public static function normalize(string $status): string
    {
        return strtolower(trim(str_replace([' ', '-'], '_', $status)));
    }

    public static function isActive(string $status): bool
    {
        return in_array(self::normalize($status), self::ACTIVE, true);
    }

    public static function isPaused(string $status): bool
    {
        return in_array(self::normalize($status), self::PAUSED, true);
    }

    public static function isTerminal(string $status): bool
    {
        return in_array(self::normalize($status), self::TERMINAL, true);
    }

    public static function canPause(string $status): bool
    {
        return self::isActive($status);
    }

    public static function canResume(string $status): bool
    {
        return self::isPaused($status);
    }

    public static function canCancel(string $status): bool
    {
        $n = self::normalize($status);

        return self::isActive($status) || self::isPaused($status) || $n === 'pending';
    }

    public static function canSkipOrRestoreDay(string $status): bool
    {
        return self::isActive($status) || self::isPaused($status);
    }

    public static function canReplaceMeal(string $status): bool
    {
        return self::isActive($status);
    }

    public static function isDaySkipped(string $dayStatus): bool
    {
        return in_array(self::normalize($dayStatus), ['skipped', 'skip', 'paused_day'], true);
    }

    public static function isFutureOrToday(string $date): bool
    {
        try {
            return \Carbon\Carbon::parse($date)->startOfDay()->gte(now()->startOfDay());
        } catch (\Throwable) {
            return false;
        }
    }
}
