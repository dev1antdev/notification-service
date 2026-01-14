<?php

declare(strict_types=1);

namespace App\Infrastructure\Template;

final readonly class TemplateMaterial
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
