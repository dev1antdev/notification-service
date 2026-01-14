<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Event;

use App\Domain\Delivery\ValueObject\AttemptId;
use App\Domain\Delivery\ValueObject\DeliveryId;
use App\Domain\Shared\Event\AbstractDomainEvent;
use App\Domain\Shared\Identity\AbstractId;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Time\Instant;

final readonly class DeliveryAttemptFailed extends AbstractDomainEvent
{
    public function __construct(
        string $eventId,
        Instant $occurredAt,
        ?CorrelationId $correlationId,
        private DeliveryId $deliveryId,
        private AbstractId $notificationId,
        private AttemptId $attemptId,
    ) {
        parent::__construct($eventId, $occurredAt, $correlationId);
    }

    public static function eventName(): string
    {
        return 'delivery.attempt_failed';
    }

    public function payload(): array
    {
        return [
            'deliveryId' => $this->deliveryId->toString(),
            'notificationId' => $this->notificationId->toString(),
            'attemptId' => $this->attemptId->toString(),
        ];
    }
}
