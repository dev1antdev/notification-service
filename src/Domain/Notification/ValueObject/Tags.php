<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class Tags
{
    /**
     * @var string[]
     */
    private array $tags;

    public function __construct(array $tags)
    {
        $normalized = [];

        foreach (array_values($tags) as $key => $tag) {
            if (!is_string($tag) || mb_trim($tag) === '') {
                throw InvariantViolation::because("Tag value for '{$key}' must be a non-empty string.");
            }

            $value = mb_trim($tag);

            if (mb_strlen($value) > 256) {
                throw InvariantViolation::because("Tag value too long for key: {$key}");
            }

            $normalized[$key] = $value;
        }

        $this->tags = $normalized;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function toArray(): array
    {
        return $this->tags;
    }

    public function get(int $key, ?string $default = null): ?string
    {
        return $this->tags[$key] ?? $default;
    }

    public function has(int $key): bool
    {
        return array_key_exists($key, $this->tags);
    }
}
