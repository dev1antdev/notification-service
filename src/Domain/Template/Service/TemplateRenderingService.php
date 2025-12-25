<?php

declare(strict_types=1);

namespace App\Domain\Template\Service;

use App\Domain\Notification\ValueObject\TemplateRef;
use App\Domain\Notification\ValueObject\Variables;
use App\Domain\Template\ValueObject\RenderedMessage;

interface TemplateRenderingService
{
    public function render(TemplateRef $ref, Variables $variables): RenderedMessage;
}
