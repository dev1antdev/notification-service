<?php

declare(strict_types=1);

namespace App\Domain\Template\Enum;

enum TemplateStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}
