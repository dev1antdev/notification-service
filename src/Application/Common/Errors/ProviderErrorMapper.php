<?php

declare(strict_types=1);

namespace App\Application\Common\Errors;

use App\Domain\Delivery\ValueObject\ErrorInfo;

interface ProviderErrorMapper
{
    public function map(\Throwable $error): ErrorInfo;
}
