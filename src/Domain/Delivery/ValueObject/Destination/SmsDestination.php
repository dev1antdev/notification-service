<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject\Destination;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\Channel;

final readonly class SmsDestination implements DestinationInterface
{
    public function __construct(
        private string $phoneNumber,
    ) {
        $phoneNumber = mb_trim($phoneNumber);

        if ($phoneNumber === '' || !preg_match('/^\+?[1-9]\d{6,19}$/', $phoneNumber)) {
            throw InvariantViolation::because('Phone number is invalid.');
        }
    }

    public function phoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function channel(): Channel
    {
        return Channel::SMS;
    }

    public function toSafeArray(): array
    {
        $digits = preg_replace('/\D+/', '', $this->phoneNumber) ?? '';
        $last3 = mb_strlen($digits) >= 3 ? mb_substr($digits, -3) : $digits;

        return [
            'type' => 'sms',
            'value' => '***' . $last3,
        ];
    }
}
