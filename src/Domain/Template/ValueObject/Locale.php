<?php

declare(strict_types=1);

namespace App\Domain\Template\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class Locale
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = mb_trim($value);

        if ($value === '' || !preg_match('/^[a-z]{2,3}([-_][A-Z]{2})?$/', $value)) {
            throw InvariantViolation::because('Invalid locale format.');
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
