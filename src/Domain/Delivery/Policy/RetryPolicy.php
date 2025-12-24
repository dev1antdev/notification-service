<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Policy;

use App\Domain\Delivery\ValueObject\ErrorInfo;
use App\Domain\Delivery\ValueObject\RetryPlan;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\Time\Instant;

interface RetryPolicy
{
    public function planRetry(Channel $channel, int $attemptNumber, ErrorInfo $error, Instant $now): ?RetryPlan;
}
