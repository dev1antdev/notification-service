<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Policy;

use App\Domain\Delivery\ValueObject\ProviderName;
use App\Domain\Notification\ValueObject\Recipient;
use App\Domain\Shared\Notification\Channel;

interface RoutingPolicy
{
    public function chooseProvider(Channel $channel, Recipient $recipient): ProviderName;
}
