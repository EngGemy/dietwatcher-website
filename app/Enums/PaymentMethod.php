<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case CREDIT_CARD = 'creditcard';
    case APPLE_PAY = 'applepay';
    case STC_PAY = 'stcpay';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Credit/Debit Card',
            self::APPLE_PAY => 'Apple Pay',
            self::STC_PAY => 'STC Pay',
        };
    }

    /**
     * Moyasar source types that map to this payment method.
     */
    public function moyasarSource(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'creditcard',
            self::APPLE_PAY => 'applepay',
            self::STC_PAY => 'stcpay',
        };
    }
}
