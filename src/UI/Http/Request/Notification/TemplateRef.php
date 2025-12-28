<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Notification;

final readonly class TemplateRef
{
    private const string DEFAULT_LOCALE = 'en-US';
    private const int DEFAULT_VERSION = 1;

    public function __construct(
        public string $templateId,
        public int $version = self::DEFAULT_VERSION,
        public string $locale = self::DEFAULT_LOCALE,
    ) {}
}
