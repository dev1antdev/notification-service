<?php

declare(strict_types=1);

namespace App\Infrastructure\Template;

use App\Domain\Notification\ValueObject\TemplateRef;

interface TemplateSource
{
    public function get(TemplateRef $ref): TemplateMaterial;
}
