<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Notification;

final readonly class SendNowRequest
{
    /**
     * @param string[] $channels
     * @param array<string, array<string, mixed>> $addresses
     * @param string[] $tags
     */
    public function __construct(
        public string $correlationId,
        public Recipient $recipient,
        public array $channels,
        public array $addresses,
        public Content $content,
        public Options $options,
        public array $tags = [],
    ) {}
}
