<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Notification;

use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Enum\NotificationStatus;
use App\Domain\Notification\ValueObject\ChannelSet;
use App\Domain\Notification\ValueObject\InlineContent;
use App\Domain\Notification\ValueObject\NotificationContent;
use App\Domain\Notification\ValueObject\NotificationId;
use App\Domain\Notification\ValueObject\Recipient;
use App\Domain\Notification\ValueObject\Schedule;
use App\Domain\Notification\ValueObject\Tags;
use App\Domain\Notification\ValueObject\TemplateContent;
use App\Domain\Notification\ValueObject\TemplateRef;
use App\Domain\Notification\ValueObject\Variables;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Identity\IdempotencyKey;
use App\Domain\Shared\Notification\PushTarget;
use App\Domain\Shared\Time\Instant;
use JsonException;
use RuntimeException;

final readonly class NotificationDbMapper
{
    /**
     * @throws JsonException
     */
    public function toRow(Notification $notification): array
    {
        return [
            'id' => $notification->id()->toString(),
            'correlation_id' => $notification->correlationId()->toString(),
            'idempotency_key' => $notification->idempotencyKey()?->value(),
            'recipient' => json_encode($this->recipientToArray($notification->recipient()), JSON_THROW_ON_ERROR),
            'channels' => json_encode($notification->channels()->toStrings(), JSON_THROW_ON_ERROR),
            'content' => json_encode($this->contentToArray($notification->content()), JSON_THROW_ON_ERROR),
            'schedule' => $notification->schedule() ? json_encode($this->scheduleToArray($notification->schedule()), JSON_THROW_ON_ERROR) : null,
            'tags' => $notification->tags() ? json_encode($notification->tags()->toArray(), JSON_THROW_ON_ERROR) : null,
            'status' => $notification->status()->value,
            'created_at' => $notification->createdAt()->toRfc3339(),
            'updated_at' => $notification->updatedAt()->toRfc3339(),
        ];
    }

    /**
     * @throws JsonException
     */
    public function fromRow(array $row): Notification
    {
        $id = NotificationId::fromString($row['id']);
        $correlationId = CorrelationId::fromString($row['correlation_id']);
        $idempotencyKey = $row['idempotency_key'] ? IdempotencyKey::fromString($row['idempotency_key']) : null;
        $recipient = $this->recipientFromArray(json_decode($row['recipient'], true, 512, JSON_THROW_ON_ERROR));
        $channels = ChannelSet::fromStrings(json_decode($row['channels'], true, 512, JSON_THROW_ON_ERROR));
        $content = $this->contentFromArray(json_decode($row['content'], true, 512, JSON_THROW_ON_ERROR));
        $schedule = $row['schedule'] ? $this->scheduleFromArray(json_decode($row['schedule'], true, 512, JSON_THROW_ON_ERROR)) : null;

        return Notification::rehydrate(
            id: $id,
            recipient: $recipient,
            channels: $channels,
            content: $content,
            correlationId: $correlationId,
            idempotencyKey: $idempotencyKey,
            schedule: $schedule,
            tags: $row['tags'] ? new Tags(json_decode($row['tags'], true, 512, JSON_THROW_ON_ERROR)) : Tags::empty(),
            status: NotificationStatus::from($row['status']),
            createdAt: Instant::fromString($row['created_at']),
            updatedAt: Instant::fromString($row['updated_at']),
        );
    }

    private function recipientToArray(Recipient $recipient): array
    {
        return [
            'email' => $recipient->email(),
            'phone' => $recipient->phoneNumber(),
            'pushTarget' => $recipient->pushTarget()
                ? ['userId' => $recipient->pushTarget()->userId(), 'deviceToken' => $recipient->pushTarget()->deviceToken()]
                : null,
        ];
    }

    private function recipientFromArray(array $recipient): Recipient
    {
        $pushTarget = null;

        if (isset($recipient['pushTarget']) && is_array($recipient['pushTarget'])) {
            $pushTarget = new PushTarget(
                deviceToken: $recipient['pushTarget']['deviceToken'] ?? null,
                userId: $recipient['pushTarget']['userId'] ?? null,
            );
        }

        return new Recipient(
            email: $recipient['email'] ?? null,
            phoneNumber: $recipient['phone'] ?? null,
            pushTarget: $pushTarget,
        );
    }

    private function contentToArray(NotificationContent $content): array
    {
        if ($content instanceof InlineContent) {
            return [
                'type' => 'inline',
                'subject' => $content->subject(),
                'text' => $content->text(),
                'html' => $content->html(),
                'pushTitle' => $content->pushTitle(),
                'pushBody' => $content->pushBody(),
                'pushData' => $content->pushData(),
            ];
        }

        if ($content instanceof TemplateContent) {
            return [
                'type' => 'template',
                'templateRef' => [
                    'templateId' => $content->templateRef()->templateId(),
                    'version' => $content->templateRef()->version(),
                    'locale' => $content->templateRef()->locale(),
                ],
                'variables' => $content->variables()->toArray(),
            ];
        }

        throw new RuntimeException('Unknown NotificationContent.');
    }

    private function contentFromArray(array $content): NotificationContent
    {
        return match ($content['type'] ?? null) {
            'inline' => new InlineContent(
                subject: $content['subject'] ?? null,
                text: $content['text'] ?? null,
                html: $content['html'] ?? null,
                pushTitle: $content['pushTitle'] ?? null,
                pushBody: $content['pushBody'] ?? null,
                pushData: is_array($content['pushData'] ?? null) ? $content['pushData'] : [],
            ),
            'template' => new TemplateContent(
                templateRef: new TemplateRef(
                    templateId: $content['templateRef']['templateId'] ?? '',
                    version: (int)($content['templateRef']['version'] ?? 1),
                    locale: $content['templateRef']['locale'] ?? 'en-US',
                ),
                variables: new Variables(is_array($content['variables'] ?? null) ? $content['variables'] : []),
            ),
            default => throw new RuntimeException('Unknown NotificationContent.'),
        };
    }

    private function scheduleToArray(Schedule $schedule): array
    {
        return [
            'sendAt' => $schedule->sendAt()->toRfc3339(),
            'expiresAt' => $schedule->expiresAt()?->toRfc3339(),
        ];
    }

    private function scheduleFromArray(array $schedule): Schedule
    {
        return new Schedule(
            sendAt: Instant::fromString($schedule['sendAt']),
            expiresAt: $schedule['expiresAt'] ? Instant::fromString($schedule['expiresAt']) : null,
        );
    }
}
