<?php

declare(strict_types=1);

namespace App\Application\Ports\Template;

use App\Domain\Notification\ValueObject\TemplateRef;
use App\Domain\Notification\ValueObject\Variables;
use App\Domain\Template\ValueObject\RenderedMessage;

interface TemplateRenderer
{
    public function render(TemplateRef $ref, Variables $variables): RenderedMessage;
}
