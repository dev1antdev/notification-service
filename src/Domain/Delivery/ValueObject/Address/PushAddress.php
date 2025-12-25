<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Address;

use App\Domain\Shared\Notification\BuiltInChannel;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\Notification\PushTarget;

final readonly class PushAddress implements Address
{
    public function __construct(
        private PushTarget $target,
    ) {}

    public function target(): PushTarget
    {
        return $this->target;
    }

    public function channel(): Channel
    {
        return Channel::builtIn(BuiltInChannel::PUSH);
    }

    public function toSafeArray(): array
    {
        return [
            'type' => 'push',
            'userId' => $this->target->userId(),
            'hasDeviceToken' => $this->target->hasDeviceToken(),
        ];
    }
}
