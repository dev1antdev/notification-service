<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Content;

use App\Domain\Notification\ValueObject\TemplateRef;
use App\Domain\Notification\ValueObject\Variables;
use App\Domain\Shared\Notification\Channel;

final readonly class TemplateRefContent implements DeliveryContent
{
    public function __construct(
        private Channel $channel,
        private TemplateRef $templateRef,
        private Variables $variables,
    ) {}

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function templateRef(): TemplateRef
    {
        return $this->templateRef;
    }

    public function variables(): Variables
    {
        return $this->variables;
    }

    public function toSafeArray(): array
    {
        return [
            'type' => 'template_ref',
            'channel' => $this->channel->name(),
            'templateId' => $this->templateRef->templateId(),
            'version' => $this->templateRef->version(),
        ];
    }
}
