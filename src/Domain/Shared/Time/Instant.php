<?php

declare(strict_types=1);

namespace App\Domain\Shared\Time;

use App\Domain\Shared\Exception\InvariantViolation;
use DateTimeImmutable;
use DateTimeZone;

final class Instant
{
    private const string TZ_UTC = 'UTC';

    private function __construct(
        private readonly DateTimeImmutable $value,
    ) {}

    public static function now(): self
    {
        return new self(new DateTimeImmutable('now', new DateTimeZone(self::TZ_UTC)));
    }

    public static function fromDateTime(DateTimeImmutable $dt): self
    {
        $utc = $dt->setTimezone(new DateTimeZone(self::TZ_UTC));

        return new self($utc);
    }

    public static function fromString(string $value): self
    {
        try {
            $datetime = new DateTimeImmutable($value);
        } catch (\Throwable) {
            throw InvariantViolation::because("Invalid instant format: {$value}");
        }

        return self::fromDateTime($datetime);
    }

    public function toRfc3339(): string
    {
        return $this->value->format(\DateTimeInterface::RFC3339_EXTENDED);
    }

    public function toUnixMillis(): int
    {
        $seconds = (int) $this->value->format('U');
        $micros = (int) $this->value->format('u');

        return ($seconds * 1000) + intdiv($micros, 1000);
    }

    public function plusSeconds(int $seconds): self
    {
        return new self($this->value->modify(sprintf('+%d seconds', $seconds)));
    }

    public function isBefore(self $other): bool
    {
        return $this->value < $other->value;
    }

    public function isAfter(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function equals(self $other): bool
    {
        return $this->toUnixMillis() === $other->toUnixMillis();
    }

    public function dateTime(): DateTimeImmutable
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->toRfc3339();
    }
}
