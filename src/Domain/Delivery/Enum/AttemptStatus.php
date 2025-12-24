<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Enum;

enum AttemptStatus: string
{
    case STARTED = 'started';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}
