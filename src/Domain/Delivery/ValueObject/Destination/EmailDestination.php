<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Destination;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\Channel;

final readonly class EmailDestination implements DestinationInterface
{
    public function __construct(
        private string $email,
    ) {
        $email = mb_trim($email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw InvariantViolation::because('Email is invalid.');
        }
    }

    public function email(): string
    {
        return $this->email;
    }

    public function channel(): Channel
    {
        return Channel::EMAIL;
    }

    public function toSafeArray(): array
    {
        $parts = explode('@', $this->email, 2);
        $local = $parts[0] ?? '';
        $domain = $parts[1] ?? '';

        $maskedLocal = ($local === '') ? '' : mb_substr($local, 0, 1) . '***';

        return [
            'type' => 'email',
            'value' => $maskedLocal . '@' . $domain,
        ];
    }
}
