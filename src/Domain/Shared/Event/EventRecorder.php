<?php

declare(strict_types=1);

namespace App\Domain\Shared\Event;

final class EventRecorder
{
    /**
     * @var DomainEventInterface[]
     */
    private array $events = [];

    public function record(DomainEventInterface $event): void
    {
        $this->events[] = $event;
    }

    public function pull(): array
    {
        $out = $this->events;

        $this->events = [];

        return $out;
    }

    public function peek(): array
    {
        return $this->events;
    }

    public function clear(): void
    {
        $this->events = [];
    }
}
