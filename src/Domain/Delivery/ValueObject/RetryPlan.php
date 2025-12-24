<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Time\Instant;

final readonly class RetryPlan
{
    public function __construct(
        private Instant $nextRetryAt,
        private int $attemptNumber,
        private int $maxAttempts,
    ) {
        if ($attemptNumber < 1) {
            throw InvariantViolation::because('Retry plan attempts number must be >= 1.');
        }

        if ($maxAttempts < 1) {
            throw InvariantViolation::because('Retry plan max attempts number must be >= 1.');
        }

        if ($attemptNumber > $maxAttempts) {
            throw InvariantViolation::because('Retry plan attempt number cannot be greater than max attempts number.');
        }
    }

    public function nextRetryAt(): Instant
    {
        return $this->nextRetryAt;
    }

    public function attemptNumber(): int
    {
        return $this->attemptNumber;
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function isLastAttempt(): bool
    {
        return $this->attemptNumber() >= $this->maxAttempts();
    }

    public function toArray(): array
    {
        return [
            'nextRetryAt' => $this->nextRetryAt,
            'attemptNumber' => $this->attemptNumber,
            'maxAttempts' => $this->maxAttempts,
        ];
    }
}
