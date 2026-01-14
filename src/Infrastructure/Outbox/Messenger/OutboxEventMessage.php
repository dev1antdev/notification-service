<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox\Messenger;

final readonly class OutboxEventMessage
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public array $payload,
        public string $occurredAt,
    ) {
    }
}
