<?php

declare(strict_types=1);

namespace App\Domain\Shared\Event;

use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Time\Instant;

abstract readonly class AbstractDomainEvent implements DomainEventInterface
{
    public function __construct(
        private string $eventId,
        private Instant $occurredAt,
        private ?CorrelationId $correlationId,
    ) {}

    final public function eventId(): string
    {
        return $this->eventId;
    }

    final public function occurredAt(): Instant
    {
        return $this->occurredAt;
    }

    final public function correlationId(): ?CorrelationId
    {
        return $this->correlationId;
    }
}
