<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

final readonly class TemplateContent implements NotificationContent
{
    public function __construct(
        private TemplateRef $templateRef,
        private Variables $variables,
    ) {}

    public function kind(): string
    {
        return 'template';
    }

    public function templateRef(): TemplateRef
    {
        return $this->templateRef;
    }

    public function variables(): Variables
    {
        return $this->variables;
    }
}
