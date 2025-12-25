<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Content;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\ValueObject\JsonObject;

final readonly class CustomContent implements DeliveryContent
{
    public function __construct(
        private Channel $channel,
        private JsonObject $payload
    ) {
        if ($payload->toArray() === []) {
            throw InvariantViolation::because('Custom content payload cannot be empty.');
        }
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function payload(): JsonObject
    {
        return $this->payload;
    }

    public function toSafeArray(): array
    {
        return [
            'type' => 'custom',
            'channel' => $this->channel->name(),
            'keys' => array_slice(array_keys($this->payload->toArray()), 0, 50),
        ];
    }
}
