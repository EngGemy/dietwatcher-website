<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentKind: string
{
    case Order = 'order';
    case Subscription = 'subscription';
}
