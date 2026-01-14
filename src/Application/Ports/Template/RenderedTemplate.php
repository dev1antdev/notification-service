<?php

declare(strict_types=1);

namespace App\Application\Ports\Template;

final readonly class RenderedTemplate
{
    public function __construct(
        public ?string $subject,
        public ?string $text,
        public ?string $html,
        public ?string $pushTitle,
        public ?string $pushBody,
        public array $pushData = [],
    ) {}
}
