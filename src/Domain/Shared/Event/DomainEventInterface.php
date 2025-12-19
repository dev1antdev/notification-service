<?php

declare(strict_types=1);

namespace App\Domain\Shared\Event;

interface DomainEventInterface
{
    public function eventId(): string;
    public function occurredAt(): Instant;
    public function correlationId(): ?CorrelationId;
}
