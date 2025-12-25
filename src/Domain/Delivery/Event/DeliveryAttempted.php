<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Event;

use App\Domain\Delivery\Enum\AttemptStatus;
use App\Domain\Delivery\ValueObject\AttemptId;
use App\Domain\Delivery\ValueObject\DeliveryId;
use App\Domain\Delivery\ValueObject\ErrorInfo;
use App\Domain\Delivery\ValueObject\ProviderMessageId;
use App\Domain\Delivery\ValueObject\ProviderName;
use App\Domain\Shared\Event\AbstractDomainEvent;
use App\Domain\Shared\Identity\AbstractId;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\Time\Instant;

final readonly class DeliveryAttempted extends AbstractDomainEvent
{
    public function __construct(
        string $eventId,
        Instant $occurredAt,
        ?CorrelationId $correlationId,
        private DeliveryId $deliveryId,
        private AbstractId $notificationId,
        private Channel $channel,
        private AttemptId $attemptId,
        private ProviderName $provider,
        private AttemptStatus $status,
        private ?ProviderMessageId $providerMessageId,
        private ?ErrorInfo $error,
    ) {
        parent::__construct($eventId, $occurredAt, $correlationId);
    }

    public static function eventName(): string
    {
        return 'delivery.attempted';
    }

    public function payload(): array
    {
        return [
            'deliveryId' => $this->deliveryId->toString(),
            'notificationId' => $this->notificationId->toString(),
            'channel' => $this->channel->name(),
            'attemptId' => $this->attemptId->toString(),
            'provider' => $this->provider->value(),
            'status' => $this->status->value,
            'providerMessageId' => $this->providerMessageId?->value(),
            'error' => $this->error?->toArray(),
        ];
    }
}
