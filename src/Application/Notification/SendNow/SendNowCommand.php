<?php

declare(strict_types=1);

namespace App\Application\Notification\SendNow;

use App\Domain\Notification\ValueObject\ChannelSet;
use App\Domain\Notification\ValueObject\NotificationContent;
use App\Domain\Notification\ValueObject\Recipient;
use App\Domain\Notification\ValueObject\Tags;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Identity\IdempotencyKey;

final readonly class SendNowCommand
{
    public function __construct(
        public Recipient $recipient,
        public ChannelSet $channels,
        public NotificationContent $content,
        public CorrelationId $correlationId,
        public ?IdempotencyKey $idempotencyKey = null,
        public ?Tags $tags = null,
        public bool $persistNotification = true,
        public bool $dispatchSynchronously = false,
    ) {}
}
