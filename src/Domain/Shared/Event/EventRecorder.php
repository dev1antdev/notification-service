<?php

declare(strict_types=1);

namespace App\Domain\Shared\Event;

final class EventRecorder
{
    /**
     * @var DomainEventInterface[]
     */
    private array $events = [];
}
