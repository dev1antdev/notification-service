<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

interface NotificationContent
{
    public function kind(): string;
}
