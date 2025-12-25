<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Address;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\BuiltInChannel;
use App\Domain\Shared\Notification\Channel;

final readonly class SmsAddress implements Address
{
    public function __construct(
        private string $to,
    ) {
        $to = mb_trim($to);

        if ($to === '' || !preg_match('/^\+?[1-9]\d{6,19}$/', $to)) {
            throw InvariantViolation::because('Phone number is invalid.');
        }
    }

    public function to(): string
    {
        return $this->to;
    }

    public function channel(): Channel
    {
        return Channel::builtIn(BuiltInChannel::SMS);
    }

    public function toSafeArray(): array
    {
        $digits = preg_replace('/\D+/', '', $this->to) ?? '';
        $last3 = mb_strlen($digits) >= 3 ? mb_substr($digits, -3) : $digits;

        return [
            'type' => 'sms',
            'value' => '***' . $last3,
        ];
    }
}
