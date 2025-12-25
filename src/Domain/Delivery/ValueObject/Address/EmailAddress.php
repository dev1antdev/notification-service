<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Address;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\BuiltInChannel;
use App\Domain\Shared\Notification\Channel;

final readonly class EmailAddress implements Address
{
    public function __construct(
        private string $to,
    ) {
        $to = mb_trim($to);

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw InvariantViolation::because('Email is invalid.');
        }
    }

    public function to(): string
    {
        return $this->to;
    }

    public function channel(): Channel
    {
        return Channel::builtIn(BuiltInChannel::EMAIL);
    }

    public function toSafeArray(): array
    {
        $parts = explode('@', $this->to, 2);
        $local = $parts[0] ?? '';
        $domain = $parts[1] ?? '';

        $maskedLocal = ($local === '') ? '' : mb_substr($local, 0, 1) . '***';

        return [
            'type' => 'email',
            'value' => $maskedLocal . '@' . $domain,
        ];
    }
}
