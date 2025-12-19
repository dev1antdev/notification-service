<?php

declare(strict_types=1);

namespace App\Domain\Shared\Time;

final class SystemClock implements ClockInterface
{

    public function now(): Instant
    {
        return Instant::now();
    }
}
