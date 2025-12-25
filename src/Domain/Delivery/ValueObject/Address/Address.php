<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Address;

use App\Domain\Shared\Notification\Channel;

interface Address
{
    public function channel(): Channel;

    public function toSafeArray(): array;
}
