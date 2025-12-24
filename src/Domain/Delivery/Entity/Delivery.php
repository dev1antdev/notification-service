<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Entity;

use App\Domain\Delivery\Enum\AttemptStatus;
use App\Domain\Delivery\Enum\DeliveryStatus;
use App\Domain\Delivery\Event\DeliveryAttempted;
use App\Domain\Delivery\Event\DeliveryDeadLettered;
use App\Domain\Delivery\Event\DeliveryDispatchStarted;
use App\Domain\Delivery\Event\DeliveryFailed;
use App\Domain\Delivery\Event\DeliveryRetryScheduled;
use App\Domain\Delivery\Event\DeliverySucceeded;
use App\Domain\Delivery\ValueObject\AttemptId;
use App\Domain\Delivery\ValueObject\DeliveryId;
use App\Domain\Delivery\ValueObject\Destination\DestinationInterface;
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

    /** @var DeliveryAttempt[] */
    private array $attempts = [];

    private function __construct(
        private readonly DeliveryId $id,
        private readonly AbstractId $notificationId,
        private readonly Channel $channel,
        private ProviderName $provider,
        private DestinationInterface $destination,
        private readonly CorrelationId $correlationId,
        private readonly Instant $createdAt,
    ) {
        parent::__construct();

        $this->status = DeliveryStatus::PENDING;
    }

    public static function create(
        DeliveryId $id,
        AbstractId $notificationId,
        Channel $channel,
        ProviderName $provider,
        DestinationInterface $destination,
        CorrelationId $correlationId,
        Instant $now,
    ): self {
        return new self(
            $id,
            $notificationId,
            $channel,
            $provider,
            $destination,
            $correlationId,
            $now,
        );
    }

    public function startDispatch(Instant $now): void
    {
        $this->assertNotFinal();

        if (!in_array($this->status, [DeliveryStatus::PENDING, DeliveryStatus::RETRYING], true)) {
            throw InvariantViolation::because('Cannot start dispatch from current status: ' . $this->status->value);
        }

        $this->status = DeliveryStatus::DISPATCHING;

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

        $attemptId = AttemptId::new();
        $attempt = DeliveryAttempt::start($attemptId, $this->provider, $now);

        $this->attempts[] = $attempt;

        // TODO: add DeliveryAttemptStarted event

        return $attemptId;
    }

    public function attemptSucceeded(AttemptId $attemptId, ProviderMessageId $providerMessageId, Instant $now): void
    {
        $attempt = $this->findAttempt($attemptId);
        $attempt->succeed($providerMessageId, $now);

        $this->status = DeliveryStatus::SENT;
        $this->lastError = null;
        $this->retryPlan = null;

        $this->record(
            new DeliveryAttempted(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                deliveryId: $this->id,
                notificationId: $this->notificationId,
                channel: $this->channel,
                attemptId: $attemptId,
                provider: $this->provider,
                status: AttemptStatus::SUCCEEDED,
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
        $attempt = $this->findAttempt($attemptId);
        $attempt->fail($error, $now);

        $this->lastError = $error;
        $this->retryPlan = $retryPlan;

        $this->record(
            new DeliveryAttempted(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                deliveryId: $this->id,
                notificationId: $this->notificationId,
                channel: $this->channel,
                attemptId: $attemptId,
                provider: $this->provider,
                status: AttemptStatus::FAILED,
                providerMessageId: null,
                error: $error,
            )
        );

        if ($retryPlan !== null) {
            $this->status = DeliveryStatus::RETRYING;

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

        // TODO: add DeliveryCancelled event
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
        return count($this->attempts);
    }

    public function id(): DeliveryId
    {
        return $this->id;
    }

    public function notificationId(): NotificationId
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

    public function attempts(): array
    {
        return $this->attempts;
    }

    private function assertNotFinal(): void
    {
        if (in_array($this->status, [DeliveryStatus::SENT, DeliveryStatus::FAILED, DeliveryStatus::CANCELLED], true)) {
            throw InvariantViolation::because('Delivery cannot be modified after it has been sent, failed, or cancelled.');
        }
    }

    private function findAttempt(AttemptId $attemptId): DeliveryAttempt
    {
        foreach ($this->attempts as $attempt) {
            if ($attempt->id()->equals($attemptId)) {
                return $attempt;
            }
        }

        throw InvariantViolation::because('Attempt not found.');
    }
}
