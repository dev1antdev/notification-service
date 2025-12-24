<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Enum;

enum DeliveryStatus: string
{
    case PENDING = 'pending';
    case DISPATCHING = 'dispatching';
    case SENT = 'sent';
    case FAILED = 'failed';
    case RETRYING = 'retrying';
    case CANCELLED = 'cancelled';
}
