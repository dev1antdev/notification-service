<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Policy;

use App\Domain\Delivery\ValueObject\Address\Address;
use App\Domain\Delivery\ValueObject\ProviderName;
use App\Domain\Shared\Notification\BuiltInChannel;

interface RoutingPolicy
{
    public function chooseProvider(BuiltInChannel $channel, Address $address): ProviderName;
}
