<?php

declare(strict_types=1);

namespace App\Domain\Template\Repository;

use App\Domain\Template\Entity\Template;
use App\Domain\Template\ValueObject\TemplateId;

interface TemplateRepository
{
    public function get(TemplateId $id): Template;

    public function save(Template $template): void;
}
