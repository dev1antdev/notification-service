<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class Tags
{
    /**
     * @var array<string, string>
     */
    private array $tags;

    public function __construct(array $tags)
    {
        $normalized = [];

        foreach ($tags as $k => $v) {
            if (!is_string($k) || mb_trim($k) === '') {
                throw InvariantViolation::because('Tag key must be a non-empty string.');
            }

            if (!is_string($v) || mb_trim($v) === '') {
                throw InvariantViolation::because("Tag value for '{$k}' must be a non-empty string.");
            }

            $key = mb_trim($k);
            $value = mb_trim($v);

            if (!preg_match('/^[A-Za-z0-9:_\-.]{1,64}$/', $key)) {
                throw InvariantViolation::because("Invalid tag key: {$key}");
            }

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

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->tags[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->tags);
    }
}
