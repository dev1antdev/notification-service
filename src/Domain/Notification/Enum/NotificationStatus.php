<?php

declare(strict_types=1);

namespace App\Domain\Notification\Enum;

enum NotificationStatus: string
{
    case REQUESTED = 'requested';
    case SCHEDULED = 'scheduled';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
}
