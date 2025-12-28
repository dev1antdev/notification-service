<?php

declare(strict_types=1);

namespace App\Domain\Shared\Notification;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class Channel
{
    private function __construct(
        private ?BuiltInChannel $builtIn,
        private ?string $custom,
    ) {}

    public static function builtIn(BuiltInChannel $builtIn): self
    {
        return new self($builtIn, null);
    }

    public static function custom(string $name): self
    {
        $name = mb_strtolower(mb_trim($name));

        if ($name === '' || mb_strlen($name) > 64) {
            throw InvariantViolation::because('Custom channel name is invalid.');
        }

        if (!preg_match('/^[a-z][a-z0-9_\-\.]{0,63}$/', $name)) {
            throw InvariantViolation::because('Custom channel name contains invalid characters.');
        }

        return new self(null, $name);
    }

    public static function fromString(string $value): self
    {
        $value = mb_strtolower(mb_trim($value));

        foreach (BuiltInChannel::cases() as $case) {
            if ($case->value === $value) {
                return self::builtIn($case);
            }
        }

        return self::custom($value);
    }

    public function isBuiltIn(): bool
    {
        return $this->builtIn !== null;
    }

    public function name(): string
    {
        return $this->builtIn?->value ?? $this->custom;
    }

    public function getBuiltIn(): ?BuiltInChannel
    {
        return $this->builtIn;
    }

    public function equals(self $other): bool
    {
        return $this->name() === $other->name();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
