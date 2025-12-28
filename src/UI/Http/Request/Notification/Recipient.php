<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Notification;

final readonly class Recipient
{
    public function __construct(
        public ?string $email = null,
        public ?string $phone = null,
        public ?PushTarget $pushTarget = null,
    )
    {
    }
}
