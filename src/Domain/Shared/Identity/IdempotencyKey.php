<?php

declare(strict_types=1);

namespace App\Domain\Shared\Identity;

use App\Domain\Shared\Exception\InvariantViolation;
use Symfony\Component\Uid\Uuid;

final class IdempotencyKey
{
    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw InvariantViolation::because('Idempotency key cannot be empty');
        }

        if (!Uuid::isValid($value)) {
            throw InvariantViolation::because('Invalid idempotency key format');
        }

        return new self($value);
    }
}
