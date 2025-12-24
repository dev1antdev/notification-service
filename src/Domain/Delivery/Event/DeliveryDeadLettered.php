<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Event;

use App\Domain\Delivery\ValueObject\DeliveryId;
use App\Domain\Delivery\ValueObject\ErrorInfo;
use App\Domain\Delivery\ValueObject\ProviderName;
use App\Domain\Shared\Event\AbstractDomainEvent;
use App\Domain\Shared\Identity\AbstractId;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\Time\Instant;

final readonly class DeliveryDeadLettered extends AbstractDomainEvent
{
    public function __construct(
        string $eventId,
        Instant $occurredAt,
        ?CorrelationId $correlationId,
        private DeliveryId $deliveryId,
        private AbstractId $notificationId,
        private Channel $channel,
        private ProviderName $provider,
        private ErrorInfo $error,
    ) {
        parent::__construct($eventId, $occurredAt, $correlationId);
    }

    public static function eventName(): string
    {
        return 'delivery.dead_lettered';
    }

    public function payload(): array
    {
        return [
            'deliveryId' => $this->deliveryId->toString(),
            'notificationId' => $this->notificationId->toString(),
            'channel' => $this->channel->value,
            'provider' => $this->provider->value(),
            'error' => $this->error->toArray(),
        ];
    }
}
