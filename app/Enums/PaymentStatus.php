<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::AUTHORIZED => 'Authorized',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::EXPIRED => 'Expired',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::PAID;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::FAILED, self::REFUNDED, self::EXPIRED]);
    }
}
