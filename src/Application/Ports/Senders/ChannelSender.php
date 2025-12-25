<?php

declare(strict_types=1);

namespace App\Application\Ports\Senders;

use App\Domain\Delivery\ValueObject\Address\Address;
use App\Domain\Delivery\ValueObject\Content\DeliveryContent;
use App\Domain\Delivery\ValueObject\ProviderMessageId;
use App\Domain\Shared\Notification\Channel;

interface ChannelSender
{
    public function channel(): Channel;

    public function send(Address $address, DeliveryContent $content): ProviderMessageId;
}
