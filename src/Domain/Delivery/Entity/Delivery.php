<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Entity;

use App\Domain\Delivery\Enum\AttemptStatus;
use App\Domain\Delivery\Enum\DeliveryStatus;
use App\Domain\Delivery\Event\DeliveryAttemptFailed;
use App\Domain\Delivery\Event\DeliveryAttemptSucceeded;
use App\Domain\Delivery\Event\DeliveryAttemptStarted;
use App\Domain\Delivery\Event\DeliveryCancelled;
use App\Domain\Delivery\Event\DeliveryCreated;
use App\Domain\Delivery\Event\DeliveryDeadLettered;
use App\Domain\Delivery\Event\DeliveryDispatchStarted;
use App\Domain\Delivery\Event\DeliveryFailed;
use App\Domain\Delivery\Event\DeliveryRetryScheduled;
use App\Domain\Delivery\Event\DeliverySucceeded;
use App\Domain\Delivery\ValueObject\Address\Address;
use App\Domain\Delivery\ValueObject\AttemptId;
use App\Domain\Delivery\ValueObject\Content\DeliveryContent;
use App\Domain\Delivery\ValueObject\DeliveryId;
use App\Domain\Delivery\ValueObject\ErrorInfo;
use App\Domain\Delivery\ValueObject\ProviderMessageId;
use App\Domain\Delivery\ValueObject\ProviderName;
use App\Domain\Delivery\ValueObject\RetryPlan;
use App\Domain\Shared\Entity\AggregateRoot;
use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Identity\AbstractId;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\Time\Instant;
use Symfony\Component\Uid\Uuid;

final class Delivery extends AggregateRoot
{
    private DeliveryStatus $status;
    private ?ErrorInfo $lastError = null;
    private ?RetryPlan $retryPlan = null;
    private ?Instant $nextRetryAt = null;
    private ?Instant $deadLetteredAt = null;
    private ?ProviderMessageId $providerMessageId = null;
    private int $attemptCount = 0;
    private int $version = 0;

    private function __construct(
        private readonly DeliveryId $id,
        private readonly AbstractId $notificationId,
        private readonly Channel $channel,
        private ProviderName $provider,
        private readonly Address $address,
        private readonly DeliveryContent $content,
        private readonly CorrelationId $correlationId,
        private readonly Instant $createdAt,
        private Instant $updatedAt,
    ) {
        parent::__construct();

        if (!$address->channel()->equals($channel)) {
            throw InvariantViolation::because('Address channel does not match channel of delivery.');
        }

        if (!$content->channel()->equals($channel)) {
            throw InvariantViolation::because('Content channel does not match channel of delivery.');
        }

        $this->status = DeliveryStatus::PENDING;
    }

    public static function rehydrate(
        DeliveryId $id,
        AbstractId $notificationId,
        Channel $channel,
        ProviderName $provider,
        Address $address,
        DeliveryContent $content,
        CorrelationId $correlationId,
        Instant $createdAt,
        Instant $updatedAt,
        DeliveryStatus $status,
        ?ErrorInfo $lastError,
        ?Instant $nextRetryAt,
        ?Instant $deadLetteredAt,
        ?ProviderMessageId $providerMessageId,
        int $version,
    ): self {
        $self = new self(
            id: $id,
            notificationId: $notificationId,
            channel: $channel,
            provider: $provider,
            address: $address,
            content: $content,
            correlationId: $correlationId,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $self->status = $status;
        $self->lastError = $lastError;
        $self->nextRetryAt = $nextRetryAt;
        $self->deadLetteredAt = $deadLetteredAt;
        $self->providerMessageId = $providerMessageId;
        $self->version = $version;

        return $self;
    }

    public static function create(
        DeliveryId $id,
        AbstractId $notificationId,
        Channel $channel,
        ProviderName $provider,
        Address $address,
        DeliveryContent $content,
        CorrelationId $correlationId,
        Instant $now,
    ): self {
        $delivery = new self(
            $id,
            $notificationId,
            $channel,
            $provider,
            $address,
            $content,
            $correlationId,
            $now,
            $now,
        );

        $delivery->record(
            new DeliveryCreated(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $now,
                correlationId: $correlationId,
                deliveryId: $id,
                notificationId: $notificationId,
            ),
        );

        return $delivery;
    }

    public function startDispatch(Instant $now): void
    {
        $this->assertNotFinal();

        if (!in_array($this->status, [DeliveryStatus::PENDING, DeliveryStatus::RETRYING], true)) {
            throw InvariantViolation::because('Cannot start dispatch from current status: ' . $this->status->value);
        }

        $this->status = DeliveryStatus::DISPATCHING;
        $this->updatedAt = $now;

        $this->record(
            new DeliveryDispatchStarted(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                deliveryId: $this->id,
                notificationId: $this->notificationId,
                channel: $this->channel,
                provider: $this->provider,
            )
        );
    }

    public function beginAttempt(Instant $now): AttemptId
    {
        if ($this->status !== DeliveryStatus::DISPATCHING) {
            throw InvariantViolation::because('Can begin attempt only while DISPATCHING');
        }

        $this->updatedAt = $now;
        $this->attemptCount++;

        $attemptId = AttemptId::new();

        $this->record(
            new DeliveryAttemptStarted(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                deliveryId: $this->id,
                notificationId: $this->notificationId,
                attemptId: $attemptId,
            ),
        );

        return $attemptId;
    }

    public function attemptSucceeded(AttemptId $attemptId, ProviderMessageId $providerMessageId, Instant $now): void
    {
        $this->status = DeliveryStatus::SENT;
        $this->providerMessageId = $providerMessageId;
        $this->lastError = null;
        $this->retryPlan = null;
        $this->nextRetryAt = null;
        $this->updatedAt = $now;

        $this->record(
            new DeliveryAttemptSucceeded(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                deliveryId: $this->id,
                notificationId: $this->notificationId,
                channel: $this->channel,
                attemptId: $attemptId,
                provider: $this->provider,
                providerMessageId: $providerMessageId,
                error: null,
            )
        );

        $this->record(
            new DeliverySucceeded(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                deliveryId: $this->id,
                notificationId: $this->notificationId,
                channel: $this->channel,
                provider: $this->provider,
            )
        );
    }

    public function attemptFailed(AttemptId $attemptId, ErrorInfo $error, Instant $now, ?RetryPlan $retryPlan): void
    {
        $this->lastError = $error;
        $this->retryPlan = $retryPlan;
        $this->updatedAt = $now;

        $this->record(
            new DeliveryAttemptFailed(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                deliveryId: $this->id,
                notificationId: $this->notificationId,
                attemptId: $attemptId,
            )
        );

        if ($retryPlan !== null) {
            $this->status = DeliveryStatus::RETRYING;
            $this->nextRetryAt = $retryPlan->nextRetryAt();

            $this->record(
                new DeliveryFailed(
                    eventId: Uuid::v4()->toRfc4122(),
                    occurredAt: $now,
                    correlationId: $this->correlationId,
                    deliveryId: $this->id,
                    notificationId: $this->notificationId,
                    channel: $this->channel,
                    provider: $this->provider,
                    error: $error,
                    retryPlan: $retryPlan,
                )
            );

            $this->record(
                new DeliveryRetryScheduled(
                    eventId: Uuid::v4()->toRfc4122(),
                    occurredAt: $now,
                    correlationId: $this->correlationId,
                    deliveryId: $this->id,
                    notificationId: $this->notificationId,
                    channel: $this->channel,
                    provider: $this->provider,
                    retryPlan: $retryPlan,
                )
            );
        } else {
            $this->status = DeliveryStatus::FAILED;
            $this->nextRetryAt = null;
            $this->deadLetteredAt = $now;

            $this->record(
                new DeliveryFailed(
                    eventId: Uuid::v4()->toRfc4122(),
                    occurredAt: $now,
                    correlationId: $this->correlationId,
                    deliveryId: $this->id,
                    notificationId: $this->notificationId,
                    channel: $this->channel,
                    provider: $this->provider,
                    error: $error,
                    retryPlan: null,
                )
            );

            $this->record(
                new DeliveryDeadLettered(
                    eventId: Uuid::v4()->toRfc4122(),
                    occurredAt: $now,
                    correlationId: $this->correlationId,
                    deliveryId: $this->id,
                    notificationId: $this->notificationId,
                    channel: $this->channel,
                    provider: $this->provider,
                    error: $error,
                )
            );
        }
    }

    public function changeProvider(ProviderName $provider): void
    {
        $this->assertNotFinal();

        if ($this->status === DeliveryStatus::DISPATCHING) {
            throw InvariantViolation::because('Cannot change provider while delivery is dispatching.');
        }

        $this->provider = $provider;
    }

    public function cancel(Instant $now): void
    {
        $this->assertNotFinal();

        $this->status = DeliveryStatus::CANCELLED;

        $this->record(
            new DeliveryCancelled(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                deliveryId: $this->id,
                notificationId: $this->notificationId,
            )
        );
    }

    public function isRetryDue(Instant $now): bool
    {
        if ($this->status !== DeliveryStatus::RETRYING || $this->retryPlan === null) {
            return false;
        }

        return !$this->retryPlan->nextRetryAt()->isAfter($now);
    }

    public function attemptCount(): int
    {
        return $this->attemptCount;
    }

    public function id(): DeliveryId
    {
        return $this->id;
    }

    public function notificationId(): AbstractId
    {
        return $this->notificationId;
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function provider(): ProviderName
    {
        return $this->provider;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function content(): DeliveryContent
    {
        return $this->content;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function status(): DeliveryStatus
    {
        return $this->status;
    }

    public function createdAt(): Instant
    {
        return $this->createdAt;
    }

    public function lastError(): ?ErrorInfo
    {
        return $this->lastError;
    }

    public function retryPlan(): ?RetryPlan
    {
        return $this->retryPlan;
    }

    public function nextRetryAt(): ?Instant
    {
        return $this->nextRetryAt;
    }

    public function deadLetteredAt(): ?Instant
    {
        return $this->deadLetteredAt;
    }

    public function updatedAt(): Instant
    {
        return $this->updatedAt;
    }

    public function providerMessageId(): ?ProviderMessageId
    {
        return $this->providerMessageId;
    }

    public function version(): int
    {
        return $this->version;
    }

    private function assertNotFinal(): void
    {
        if (in_array($this->status, [DeliveryStatus::SENT, DeliveryStatus::FAILED, DeliveryStatus::CANCELLED], true)) {
            throw InvariantViolation::because('Delivery cannot be modified after it has been sent, failed, or cancelled.');
        }
    }
}
