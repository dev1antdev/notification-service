<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Notification;

final readonly class PushTarget
{
    public function __construct(
        public ?string $userId = null,
        public ?string $deviceToken = null,
    ) {}
}
