<?php

declare(strict_types=1);

namespace App\Domain\Shared\Notification;

use App\Domain\Shared\Exception\InvariantViolation;

enum Channel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';

    public function isEmail(): bool
    {
        return $this === self::EMAIL;
    }

    public function isSms(): bool
    {
        return $this === self::SMS;
    }

    public function isPush(): bool
    {
        return $this === self::PUSH;
    }

    public static function fromString(string $value): self
    {
        $value = mb_strtolower(trim($value));

        return match($value) {
            'email' => self::EMAIL,
            'sms' => self::SMS,
            'push' => self::PUSH,
            default => InvariantViolation::because('Unknown channel.'),
        };
    }
}
