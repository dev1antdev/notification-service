<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Application\Common\Outbox\OutboxPublisher;
use App\Domain\Shared\Event\DomainEventInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;

final readonly class DbalOutboxPublisher implements OutboxPublisher
{
    public function __construct(private Connection $connection) {}

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function enqueue(DomainEventInterface ...$events): void
    {
        foreach ($events as $event) {
            $this->connection->insert('outbox_events', [
                'event_id' => $event->eventId(),
                'correlation_id' => $event->correlationId()?->toString(),
                'occurred_at' => $event->occurredAt()->toRfc3339(),
                'event_type' => $event::eventName(),
                'payload' => json_encode($event->payload(), JSON_THROW_ON_ERROR),
                'available_at' => $event->occurredAt()->toRfc3339(),
            ]);
        }
    }
}
