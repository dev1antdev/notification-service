<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\Channel;

final readonly class Recipient
{
    public function __construct(
        private ?string $email,
        private ?string $phoneNumber,
        private ?PushTarget $pushTarget,
        private ?string $locale = null,
        private ?string $timeZone = null,
    ) {
        $this->validate();
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function pushTaget(): ?PushTarget
    {
        return $this->pushTarget;
    }

    public function locale(): ?string
    {
        return $this->locale;
    }

    public function timeZone(): ?string
    {
        return $this->timeZone;
    }

    public function assertSupports(ChannelSet $channels): void
    {
        /** @var Channel $channel */
        foreach ($channels->all() as $channel) {
            if ($channel->isEmail() && $this->email === null) {
                throw InvariantViolation::because('Recipient email is required for email channel.');
            }

            if ($channel->isSms() && $this->phoneNumber === null) {
                throw InvariantViolation::because('Recipient phone number is required for SMS channel.');
            }

            if ($channel->isPush() && $this->pushTarget === null) {
                throw InvariantViolation::because('Recipient push target is required for push channel.');
            }
        }
    }

    private function validate(): void
    {
        if ($this->email !== null) {
            $email = trim($this->email);

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw InvariantViolation::because('Invalid recipient email.');
            }
        }

        if ($this->phoneNumber !== null) {
            $phoneNumber = trim($this->phoneNumber);

            if ($phoneNumber === '' || !preg_match('/^\+?[1-9]\d{6,19}$/', $phoneNumber)) {
                throw InvariantViolation::because('Invalid recipient phone number.');
            }
        }

        if ($this->locale !== null && !preg_match('/^[a-z]{2,3}([-_][A-Z]{2})?$/', $this->locale)) {
            throw InvariantViolation::because('Invalid recipient locale.');
        }

        if ($this->timeZone !== null) {
            try {
                new \DateTimeZone($this->timeZone);
            } catch (\DateInvalidTimeZoneException) {
                throw InvariantViolation::because('Invalid recipient time zone.');
            }
        }
    }
}
