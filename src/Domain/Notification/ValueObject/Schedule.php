<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Time\Instant;

final readonly class Schedule
{
    public function __construct(
        private Instant $sendAt,
        private ?Instant $expiresAt = null,
    ) {
        if (
            $this->expiresAt !== null
            && !$this->sendAt->isBefore($this->expiresAt)
            && !$this->sendAt->equals($this->expiresAt)
        ) {
            throw InvariantViolation::because('Schedule expiresAt must be after or equal to sendAt.');
        }
    }

    public function sendAt(): Instant
    {
        return $this->sendAt;
    }

    public function expiresAt(): ?Instant
    {
        return $this->expiresAt;
    }

    public function isExpiredAt(Instant $now): bool
    {
        return $this->expiresAt !== null && $now->isAfter($this->expiresAt);
    }
}
