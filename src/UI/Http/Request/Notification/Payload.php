<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Notification;

final readonly class Payload
{
    public function __construct(
        public ?string $format = null,
        public ?string $subject = null,
        public ?string $body = null,
        public ?array $data = null,
        public ?array $headers = [],
        public ?array $attachments = [],
    ) {}
}
