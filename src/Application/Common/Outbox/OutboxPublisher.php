<?php

declare(strict_types=1);

namespace App\Application\Common\Outbox;

use App\Domain\Shared\Event\DomainEventInterface;

interface OutboxPublisher
{
    public function enqueue(DomainEventInterface ...$events): void;
}
