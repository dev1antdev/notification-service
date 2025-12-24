<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class ProviderName
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = mb_trim($value);

        if ($value === '' || mb_strlen($value) > 64) {
            throw InvariantViolation::because('Provider name is invalid.');
        }

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_\-\.]{1,63}$/', $value)) {
            throw InvariantViolation::because('ProviderName has invalid characters.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
