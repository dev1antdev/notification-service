<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Destination;

use App\Domain\Shared\Notification\Channel;

interface DestinationInterface
{
    public function channel(): Channel;

    public function toSafeArray(): array;
}
