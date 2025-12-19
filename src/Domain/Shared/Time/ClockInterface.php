<?php

declare(strict_types=1);

namespace App\Domain\Shared\Time;

interface ClockInterface
{
    public function now(): Instant;
}
