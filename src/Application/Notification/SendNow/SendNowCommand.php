<?php

declare(strict_types=1);

namespace App\Application\Notification\SendNow;

use App\Domain\Notification\ValueObject\ChannelSet;
use App\Domain\Notification\ValueObject\NotificationContent;
use App\Domain\Notification\ValueObject\Recipient;
use App\Domain\Notification\ValueObject\Tags;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Identity\IdempotencyKey;
use App\Domain\Shared\ValueObject\JsonObject;

final readonly class SendNowCommand
{
    /**
     * @param array<string, JsonObject> $addresses
     */
    public function __construct(
        public Recipient $recipient,
        public ChannelSet $channels,
        public array $addresses,
        public NotificationContent $content,
        public CorrelationId $correlationId,
        public ?IdempotencyKey $idempotencyKey = null,
        public ?Tags $tags = null,
        public bool $persistNotification = true,
        public bool $dispatchSynchronously = false,
    ) {}
}
