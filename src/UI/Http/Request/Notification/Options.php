<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Notification;

final readonly class Options
{
    public function __construct(
        public ?string $dispatch = null,
        public ?string $contentMode = null,
    ) {}
}
