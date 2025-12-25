<?php

declare(strict_types=1);

namespace App\Application\Ports\Senders;

use App\Domain\Shared\Notification\Channel;

interface SenderRegistry
{
    public function get(Channel $channel): ChannelSender;
}
