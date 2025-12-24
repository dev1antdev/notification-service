<?php

declare(strict_types=1);

namespace App\Domain\Notification\Event;

use App\Domain\Notification\ValueObject\NotificationId;
use App\Domain\Shared\Event\AbstractDomainEvent;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Time\Instant;

final readonly class NotificationExpired extends AbstractDomainEvent
{
    public function __construct(
        string $eventId,
        Instant $occurredAt,
        ?CorrelationId $correlationId,
        private NotificationId $notificationId,
    ) {
        parent::__construct($eventId, $occurredAt, $correlationId);
    }

    public static function eventName(): string
    {
        return 'notification.expired';
    }

    public function notificationId(): NotificationId
    {
        return $this->notificationId;
    }

    public function payload(): array
    {
        return [
            'notificationId' => $this->notificationId->toString(),
        ];
    }
}
