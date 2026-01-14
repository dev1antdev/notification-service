<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Delivery;

use App\Domain\Delivery\Entity\Delivery;
use App\Domain\Delivery\Enum\DeliveryStatus;
use App\Domain\Delivery\ValueObject\Address\Address;
use App\Domain\Delivery\ValueObject\Address\CustomAddress;
use App\Domain\Delivery\ValueObject\Address\EmailAddress;
use App\Domain\Delivery\ValueObject\Address\PushAddress;
use App\Domain\Delivery\ValueObject\Address\SmsAddress;
use App\Domain\Delivery\ValueObject\Content\CustomContent;
use App\Domain\Delivery\ValueObject\Content\DeliveryContent;
use App\Domain\Delivery\ValueObject\Content\SnapshotContent;
use App\Domain\Delivery\ValueObject\Content\TemplateRefContent;
use App\Domain\Delivery\ValueObject\DeliveryId;
use App\Domain\Delivery\ValueObject\ErrorInfo;
use App\Domain\Delivery\ValueObject\ProviderMessageId;
use App\Domain\Delivery\ValueObject\ProviderName;
use App\Domain\Notification\ValueObject\NotificationId;
use App\Domain\Notification\ValueObject\TemplateRef;
use App\Domain\Notification\ValueObject\Variables;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\Notification\PushTarget;
use App\Domain\Shared\Time\Instant;
use App\Domain\Shared\ValueObject\JsonObject;
use JsonException;
use RuntimeException;

final readonly class DeliveryDbMapper
{
    /**
     * @throws JsonException
     */
    public function toRow(Delivery $delivery): array
    {
        $address = $this->addressToArray($delivery->address());
        $content = $this->contentToArray($delivery->content());

        return [
            'id' => $delivery->id()->toString(),
            'notification_id' => $delivery->notificationId()->toString(),
            'correlation_id' => $delivery->correlationId()->toString(),
            'channel' => $delivery->channel()->name(),
            'provider' => $delivery->provider()->value(),
            'address_type' => $address['type'],
            'address' => json_encode($address['payload'], JSON_THROW_ON_ERROR),
            'content_type' => $content['type'],
            'content' => json_encode($content['payload'], JSON_THROW_ON_ERROR),
            'status' => $delivery->status()->value,
            'attempt_count' => $delivery->attemptCount(),
            'next_retry_at' => $delivery->nextRetryAt()?->toRfc3339(),
            'dead_lettered_at' => $delivery->deadLetteredAt()?->toRfc3339(),
            'provider_message_id' => $delivery->providerMessageId()?->value(),
            'last_error' => $delivery->lastError() ? json_encode($delivery->lastError()->toArray(), JSON_THROW_ON_ERROR) : null,
            'created_at' => $delivery->createdAt()->toRfc3339(),
            'updated_at' => $delivery->updatedAt()->toRfc3339(),
            'version' => $delivery->version(),
        ];
    }

    /**
     * @throws JsonException
     */
    public function fromRow(array $row): Delivery
    {
        $id = DeliveryId::fromString($row['id']);
        $notificationId = NotificationId::fromString($row['notification_id']);
        $correlationId = CorrelationId::fromString($row['correlation_id']);
        $channel = Channel::fromString($row['channel']);
        $provider = ProviderName::fromString($row['provider']);
        $addressPayload = json_decode($row['address'], true, 512, JSON_THROW_ON_ERROR);
        $contentPayload = json_decode($row['content'], true, 512, JSON_THROW_ON_ERROR);

        $address = $this->addressFromArray($row['address_type'], $channel, $addressPayload);
        $content = $this->contentFromArray($row['content_type'], $channel, $contentPayload);

        return Delivery::rehydrate(
            id: $id,
            notificationId: $notificationId,
            channel: $channel,
            provider: $provider,
            address: $address,
            content: $content,
            correlationId: $correlationId,
            createdAt: Instant::fromString($row['created_at']),
            updatedAt: Instant::fromString($row['updated_at']),
            status: DeliveryStatus::from($row['status']),
            lastError: $row['last_error'] ? $this->lastErrorFromArray(json_decode($row['last_error'], true, 512, JSON_THROW_ON_ERROR)) : null,
            nextRetryAt: $row['next_retry_at'] ? Instant::fromString($row['next_retry_at']) : null,
            deadLetteredAt: $row['dead_lettered_at'] ? Instant::fromString($row['dead_lettered_at']) : null,
            providerMessageId: $row['provider_message_id'] ? ProviderMessageId::fromString($row['provider_message_id']) : null,
            version: (int) $row['version'],
        );
    }

    private function addressToArray(Address $address): array
    {
        return match (true) {
            $address instanceof EmailAddress => ['type' => 'email', 'payload' => ['to' => $address->to()]],
            $address instanceof SmsAddress => ['type' => 'sms', 'payload' => ['to' => $address->to()]],
            $address instanceof PushAddress => ['type' => 'push', 'payload' => ['userId' => $address->target()->userId(), 'deviceToken' => $address->target()->deviceToken()]],
            $address instanceof CustomAddress => ['type' => 'custom', 'payload' => ['channel' => $address->channel()->name(), 'payload' => $address->payload()->toArray()]],
            default => throw new RuntimeException('Unsupported address type.'),
        };
    }

    private function addressFromArray(string $type, Channel $channel, array $address): Address
    {
        return match ($type) {
            'email' => new EmailAddress($address['to'] ?? ''),
            'sms' => new SmsAddress($address['to'] ?? ''),
            'push' => new PushAddress(new PushTarget(
                deviceToken: $address['deviceToken'] ?? null,
                userId: $address['userId'] ?? null,
            )),
            'custom' => new CustomAddress(
                Channel::fromString($address['channel'] ?? $channel->name()),
                new JsonObject(is_array($address['payload'] ?? null) ? $address['payload'] : []),
            ),
            default => throw new RuntimeException('Unsupported address type.'),
        };
    }

    private function contentToArray(DeliveryContent $content): array
    {
        return match (true) {
            $content instanceof SnapshotContent => ['type' => 'snapshot', 'payload' => $content->payload()->toArray()],
            $content instanceof TemplateRefContent => ['type' => 'template_ref', 'payload' => [
                'templateRef' => [
                    'templateId' => $content->templateRef()->templateId(),
                    'version' => $content->templateRef()->version(),
                    'locale' => $content->templateRef()->locale(),
                ],
                'variables' => $content->variables()->toArray(),
            ]],
            $content instanceof CustomContent => ['type' => 'custom', 'payload' => $content->payload()->toArray()],
            default => throw new RuntimeException('Unsupported content type.'),
        };
    }

    private function contentFromArray(string $type, Channel $channel, array $content): DeliveryContent
    {
        return match ($type) {
            'snapshot' => new SnapshotContent($channel, new JsonObject($content)),
            'template_ref' => new TemplateRefContent(
                $channel,
                new TemplateRef(
                    templateId: $content['templateRef']['templateId'] ?? '',
                    version: (int)($content['templateRef']['version'] ?? 1),
                    locale: $content['templateRef']['locale'] ?? 'en-US',
                ),
                new Variables(is_array($content['variables']) ? $content['variables'] : []),
            ),
            'custom' => new CustomContent($channel, new JsonObject($content)),
            default => new RuntimeException('Unsupported content type.'),
        };
    }

    private function lastErrorFromArray(array $lastError): ErrorInfo
    {
        return new ErrorInfo(
            code: $lastError['code'] ?? '',
            message: $lastError['message'] ?? '',
            isTransient: (bool) ($lastError['isTransient'] ?? false),
            providerCode: $lastError['providerCode'] ?? null,
        );
    }
}
