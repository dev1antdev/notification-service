<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class JsonObject
{
    /** @var array array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (!is_string($key) || mb_trim($key) === '') {
                throw InvariantViolation::because('Json object keys must be non-empty strings.');
            }

            if (!$this->isJsonSafe($value)) {
                throw InvariantViolation::because("Json object contains non-JSON-safe value for key '{$key}'");
            }
        }

        $this->data = $data;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    private function isJsonSafe(mixed $value): bool
    {
        if ($value === null || is_scalar($value)) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $kk => $vv) {
                if (!(is_int($kk) || is_string($kk))) {
                    return false;
                }

                if (!$this->isJsonSafe($vv)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
