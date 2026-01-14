<?php

declare(strict_types=1);

namespace App\Domain\Shared\Event;

use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Time\Instant;

interface DomainEventInterface
{
    public function eventId(): string;
    public function occurredAt(): Instant;
    public function correlationId(): ?CorrelationId;

    public function payload(): array;

    public static function eventName(): string;
}
