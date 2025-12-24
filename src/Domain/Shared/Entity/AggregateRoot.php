<?php

declare(strict_types=1);

namespace App\Domain\Shared\Entity;

use App\Domain\Shared\Event\DomainEventInterface;
use App\Domain\Shared\Event\EventRecorder;

abstract class AggregateRoot
{
    private EventRecorder $recorder;

    protected function __construct()
    {
        $this->recorder = new EventRecorder();
    }

    final protected function record(DomainEventInterface $event): void
    {
        $this->recorder->record($event);
    }

    final public function pullDomainEvents(): array
    {
        return $this->recorder->pull();
    }

    final public function peekDomainEvents(): array
    {
        return $this->recorder->peek();
    }
}
