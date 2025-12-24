<?php

declare(strict_types=1);

namespace App\Domain\Template\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class RequiredVariables
{
    /** @var array<string, true> */
    private array $keys;

    public function __construct(array $keys)
    {
        $map = [];

        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw InvariantViolation::because('Required variable key must be a string.');
            }

            $key = mb_trim($key);

            if ($key === '') {
                throw InvariantViolation::because('Required variable key cannot be empty.');
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_\.]{0,127}$/', $key)) {
                throw InvariantViolation::because("Invalid variable key: {$key}");
            }

            $map[$key] = true;
        }

        $this->keys = $map;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function all(): array
    {
        return array_keys($this->keys);
    }

    public function contains(string $key): bool
    {
        return isset($this->keys[$key]);
    }
}
