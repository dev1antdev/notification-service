<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class ProviderMessageId
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = mb_trim($value);

        if ($value === '' || mb_strlen($value) > 256) {
            throw InvariantViolation::because('Provider message ID is invalid.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
