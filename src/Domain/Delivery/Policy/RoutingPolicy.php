<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Policy;

use App\Domain\Delivery\ValueObject\Destination\DestinationInterface;
use App\Domain\Delivery\ValueObject\ProviderName;
use App\Domain\Shared\Notification\Channel;

interface RoutingPolicy
{
    public function chooseProvider(Channel $channel, DestinationInterface $recipient): ProviderName;
}
