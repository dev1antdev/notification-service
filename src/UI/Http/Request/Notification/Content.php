<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Notification;

final readonly class Content
{
    /**
     * @param array<string, Payload> $payloads
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $variables
     * @param array<string, mixed> $overrides
     */
    public function __construct(
        public string $type,
        public ?array $payloads = null,
        public ?array $defaults = null,
        public ?TemplateRef $templateRef = null,
        public ?array $variables = null,
        public ?array $overrides = null,
    ) {}
}
