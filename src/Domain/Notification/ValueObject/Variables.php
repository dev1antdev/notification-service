<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class Variables
{
    /**
     * @var array<string, scalar|array|null>
     */
    private array $values;

    public function __construct(array $values)
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw InvariantViolation::because('Variables keys must be non-empty string.');
            }

            if (!$this->isAllowedValue($value)) {
                throw InvariantViolation::because("Variables value for '{$key}' contains unsupported type.");
            }

            $normalized[$key] = $this->normalizeValue($value);
        }

        $this->values = $normalized;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    private function isAllowedValue(mixed $value): bool
    {
        if ($value === null || is_scalar($value)) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (!(is_int($k) || is_string($k))) {
                    return false;
                }

                if (!($v === null || is_scalar($v) || is_array($v))) {
                    return false;
                }

                if (is_array($v)  && !$this->isAllowedValue($v)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function normalizeValue(mixed $value): mixed
    {
        return $value;
    }
}
