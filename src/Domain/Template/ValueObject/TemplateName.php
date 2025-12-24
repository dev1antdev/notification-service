<?php

declare(strict_types=1);

namespace App\Domain\Template\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class TemplateName
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = mb_trim($value);

        if ($value === '' || mb_strlen($value) > 128) {
            throw InvariantViolation::because('Template name is too long.');
        }

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 _\-\.\:]{1,127}$/', $value)) {
            throw InvariantViolation::because('Template name has invalid characters.');
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
