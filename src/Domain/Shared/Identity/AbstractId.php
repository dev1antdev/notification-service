<?php

declare(strict_types=1);

namespace App\Domain\Shared\Identity;

use App\Domain\Shared\Exception\InvariantViolation;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractId
{
    private function __construct(
        private string $value,
    ) {}

    public static function new(): static
    {
        return new static(Uuid::v7()->toRfc4122());
    }

    public static function fromString(string $value): static
    {
        $value = trim($value);

        if ($value === '') {
            throw InvariantViolation::because('Id cannot be empty');
        }

        if (!Uuid::isValid($value)) {
            throw InvariantViolation::because('Invalid id format');
        }

        return new static($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
