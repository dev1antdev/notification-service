<?php

declare(strict_types=1);

namespace App\UI\Http\Exception;

final readonly class ValidationError
{
    public function __construct(
        public string $field,
        public string $code,
        public string $message,
    ) {}
}
