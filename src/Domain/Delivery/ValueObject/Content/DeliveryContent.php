<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Content;

use App\Domain\Shared\Notification\Channel;

interface DeliveryContent
{
    public function channel(): Channel;

    public function toSafeArray(): array;
}
