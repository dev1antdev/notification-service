<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Notification;

use App\UI\Http\Exception\BadRequestHttpException;
use App\UI\Http\Exception\ValidationError;
use JsonException;

final readonly class Content
{
    /**
     * @param array<string, Payload> $payloads
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $variables
     * @param array<string, mixed> $overrides
     * @throws JsonException
     */
    public function __construct(
        public string $type,
        public ?array $payloads = null,
        public ?array $defaults = null,
        public ?TemplateRef $templateRef = null,
        public ?array $variables = null,
        public ?array $overrides = null,
    ) {
        if ($this->type === 'inline' && empty($payloads)) {
            throw BadRequestHttpException::fromErrors([
                new ValidationError('content.payloads', 'empty', 'At least one payload must be provided for inline content.'),
            ]);
        }

        if ($this->type === 'template' && $templateRef === null) {
            throw BadRequestHttpException::fromErrors([
                new ValidationError('content.templateRef', 'empty', 'Template reference must be provided for template content.'),
            ]);
        }
    }
}
