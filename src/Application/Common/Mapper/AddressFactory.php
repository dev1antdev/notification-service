<?php

declare(strict_types=1);

namespace App\Application\Common\Mapper;

use App\Domain\Delivery\ValueObject\Address\Address;
use App\Domain\Delivery\ValueObject\Address\CustomAddress;
use App\Domain\Delivery\ValueObject\Address\EmailAddress;
use App\Domain\Delivery\ValueObject\Address\PushAddress;
use App\Domain\Delivery\ValueObject\Address\SmsAddress;
use App\Domain\Notification\ValueObject\Recipient;
use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\BuiltInChannel;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\ValueObject\JsonObject;

final readonly class AddressFactory
{
    public function fromRecipient(Channel $channel, Recipient $recipient, ?JsonObject $customPayload = null): Address
    {
        if ($channel->isBuiltIn()) {
            return match ($channel->getBuiltIn()) {
                BuiltInChannel::EMAIL => $this->email($recipient),
                BuiltInChannel::SMS => $this->sms($recipient),
                BuiltInChannel::PUSH => $this->push($recipient),
                default => throw InvariantViolation::because('Built-in channel not supported.'),
            };
        }

        if ($customPayload === null) {
            throw InvariantViolation::because('Custom channel requires custom address payload.');
        }

        return new CustomAddress($channel, $customPayload);
    }

    private function email(Recipient $recipient): EmailAddress
    {
        if ($recipient->email() === null) {
            throw InvariantViolation::because('Recipient email missing.');
        }

        return new EmailAddress($recipient->email());
    }

    private function sms(Recipient $recipient): SmsAddress
    {
        if ($recipient->phoneNumber() === null) {
            throw InvariantViolation::because('Recipient phone number missing.');
        }

        return new SmsAddress($recipient->phoneNumber());
    }

    private function push(Recipient $recipient): PushAddress
    {
        if ($recipient->pushTarget() === null) {
            throw InvariantViolation::because('Recipient push target missing.');
        }

        return new PushAddress($recipient->pushTarget());
    }
}
