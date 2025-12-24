<?php

declare(strict_types=1);

namespace App\Domain\Notification\Entity;

use App\Domain\Notification\Enum\NotificationStatus;
use App\Domain\Notification\Event\NotificationCancelled;
use App\Domain\Notification\Event\NotificationExpired;
use App\Domain\Notification\Event\NotificationRequested;
use App\Domain\Notification\Event\NotificationScheduled;
use App\Domain\Notification\ValueObject\ChannelSet;
use App\Domain\Notification\ValueObject\InlineContent;
use App\Domain\Notification\ValueObject\NotificationContent;
use App\Domain\Notification\ValueObject\NotificationId;
use App\Domain\Notification\ValueObject\Recipient;
use App\Domain\Notification\ValueObject\Schedule;
use App\Domain\Notification\ValueObject\Tags;
use App\Domain\Notification\ValueObject\TemplateContent;
use App\Domain\Shared\Entity\AggregateRoot;
use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Identity\IdempotencyKey;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\Time\Instant;
use Symfony\Component\Uid\Uuid;

final class Notification extends AggregateRoot
{
    private NotificationStatus $status;

    private function __construct(
        private readonly NotificationId $id,
        private readonly Recipient $recipient,
        private readonly ChannelSet $channels,
        private readonly NotificationContent $content,
        private readonly CorrelationId $correlationId,
        private readonly ?IdempotencyKey $idempotencyKey,
        private ?Schedule $schedule,
        private readonly Tags $tags,
        private readonly Instant $createdAt,
    ) {
        parent::__construct();

        $this->recipient->assertSupports($this->channels);
        $this->assertContentCompatibleWithChannels($this->channels, $this->content);

        $this->status = $this->schedule ? NotificationStatus::SCHEDULED : NotificationStatus::REQUESTED;

        $this->record(
            new NotificationRequested(
                eventId: Uuid::v4()->toString(),
                occurredAt: $this->createdAt,
                correlationId: $this->correlationId,
                notificationId: $this->id,
            )
        );

        if ($this->schedule !== null) {
            $this->record(
                new NotificationScheduled(
                    eventId: Uuid::v4()->toString(),
                    occurredAt: $this->createdAt,
                    correlationId: $this->correlationId,
                    notificationId: $this->id,
                    sendAt: $this->schedule->sendAt(),
                    expiresAt: $this->schedule->expiresAt(),
                )
            );
        }
    }

    public static function request(
        NotificationId $id,
        Recipient $recipient,
        ChannelSet $channels,
        NotificationContent $content,
        CorrelationId $correlationId,
        Instant $createdAt,
        ?IdempotencyKey $idempotencyKey = null,
        ?Schedule $schedule = null,
        ?Tags $tags = null,
    ): self {
        if ($schedule !== null) {
            if ($schedule->sendAt()->isBefore($createdAt)) {
                throw InvariantViolation::because('Cannot schedule notification in the past.');
            }

            if ($schedule->isExpiredAt($createdAt)) {
                throw InvariantViolation::because('Cannot schedule already expired notification.');
            }
        }

        return new self(
            id: $id,
            recipient: $recipient,
            channels: $channels,
            content: $content,
            correlationId: $correlationId,
            idempotencyKey: $idempotencyKey,
            schedule: $schedule,
            tags: $tags,
            createdAt: $createdAt,
        );
    }

    public function scheduleFor(Instant $sendAt, Instant $now, ?Instant $expiresAt = null): void
    {
        $this->assertNotFinal();

        if ($sendAt->isBefore($now)) {
            throw InvariantViolation::because('Cannot schedule notification in the past.');
        }

        $this->schedule = new Schedule($sendAt, $expiresAt);
        $this->status = NotificationStatus::SCHEDULED;

        $this->record(
            new NotificationScheduled(
                eventId: Uuid::v4()->toString(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                notificationId: $this->id,
                sendAt: $sendAt,
                expiresAt: $expiresAt,
            )
        );
    }

    public function cancel(string $reason, Instant $now): void
    {
        $this->assertNotFinal();

        $reason = mb_trim($reason);

        if ($reason === '') {
            throw InvariantViolation::because('Cancel reason cannot be empty.');
        }

        $this->status = NotificationStatus::CANCELLED;

        $this->record(
            new NotificationCancelled(
                eventId: Uuid::v4()->toString(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                notificationId: $this->id,
                reason: $reason,
            )
        );
    }

    public function markExpired(Instant $now): void
    {
        $this->assertNotFinal();

        $this->status = NotificationStatus::EXPIRED;

        $this->record(
            new NotificationExpired(
                eventId: Uuid::v4()->toString(),
                occurredAt: $now,
                correlationId: $this->correlationId,
                notificationId: $this->id,
            )
        );
    }

    public function id(): NotificationId
    {
        return $this->id;
    }

    public function recipient(): Recipient
    {
        return $this->recipient;
    }

    public function channels(): ChannelSet
    {
        return $this->channels;
    }

    public function content(): NotificationContent
    {
        return $this->content;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function idempotencyKey(): ?IdempotencyKey
    {
        return $this->idempotencyKey;
    }

    public function schedule(): ?Schedule
    {
        return $this->schedule;
    }

    public function status(): NotificationStatus
    {
        return $this->status;
    }

    public function createdAt(): Instant
    {
        return $this->createdAt;
    }

    public function tags(): ?Tags
    {
        return $this->tags;
    }

    public function isDue(Instant $now): bool
    {
        if ($this->status === NotificationStatus::CANCELLED || $this->status === NotificationStatus::EXPIRED) {
            return false;
        }

        if ($this->schedule === null) {
            return true;
        }

        if ($this->schedule->isExpiredAt($now)) {
            return false;
        }

        return !$this->schedule->sendAt()->isAfter($now);
    }

    private function assertNotFinal(): void
    {
        if (in_array($this->status, [NotificationStatus::CANCELLED, NotificationStatus::EXPIRED], true)) {
            throw InvariantViolation::because('Notification is final and cannot be modified');
        }
    }

    private function assertContentCompatibleWithChannels(ChannelSet $channels, NotificationContent $content): void
    {
        if ($content instanceof TemplateContent) {
            return;
        }

        if (!$content instanceof InlineContent) {
            throw InvariantViolation::because('Notification content must be either inline or template.');
        }

        /** @var Channel $channel */
        foreach ($channels->all() as $channel) {
            if ($channel->isEmail()) {
                $this->validateEmailContent($content);
            }

            if ($channel->isSms()) {
                $this->validateSmsContent($content);
            }

            if ($channel->isPush()) {
                $this->validatePushContent($content);
            }
        }
    }

    private function validateEmailContent(InlineContent $content): void
    {
        if ($content->subject() === null || mb_trim($content->subject()) === '') {
            throw InvariantViolation::because('Email notification content must contain subject.');
        }

        $hasBody = ($content->html() !== null && mb_trim($content->html()) !== '')
            || ($content->text() !== null && mb_trim($content->text()) !== '');

        if (!$hasBody) {
            throw InvariantViolation::because('Email notification content must contain body.');
        }
    }

    private function validateSmsContent(InlineContent $content): void
    {
        if ($content->text() === null || mb_trim($content->text()) === '') {
            throw InvariantViolation::because('SMS notification content must contain text.');
        }
    }

    private function validatePushContent(InlineContent $content): void
    {
        $hasPush = ($content->pushTitle() !== null && mb_trim($content->pushTitle()) !== '')
            || ($content->pushBody() !== null && mb_trim($content->pushBody()) !== '')
            || ($content->pushData() !== []);

        if (!$hasPush) {
            throw InvariantViolation::because('Push notification content must contain push data.');
        }
    }
}
