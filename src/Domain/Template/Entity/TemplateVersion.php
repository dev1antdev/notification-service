<?php

declare(strict_types=1);

namespace App\Domain\Template\Entity;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\BuiltInChannel;
use App\Domain\Template\ValueObject\EmailTemplateBody;
use App\Domain\Template\ValueObject\Locale;
use App\Domain\Template\ValueObject\PushTemplateBody;
use App\Domain\Template\ValueObject\RequiredVariables;
use App\Domain\Template\ValueObject\SmsTemplateBody;
use App\Domain\Template\ValueObject\TemplateVersionId;

final readonly class TemplateVersion
{
    private function __construct(
        private TemplateVersionId $id,
        private BuiltInChannel $channel,
        private Locale $locale,
        private RequiredVariables $requiredVariables,
        private ?EmailTemplateBody $emailBody,
        private ?SmsTemplateBody $smsBody,
        private ?PushTemplateBody $pushBody,
        private int $versionNumber,
    ) {
        $this->assertBodyMatchesChannel();
    }

    public static function email(
        TemplateVersionId $id,
        Locale $locale,
        RequiredVariables $requiredVariables,
        EmailTemplateBody $body,
        int $versionNumber,
    ): self {
        return new self(
            $id,
            BuiltInChannel::EMAIL,
            $locale,
            $requiredVariables,
            $body,
            null,
            null,
            $versionNumber,
        );
    }

    public static function sms(
        TemplateVersionId $id,
        Locale $locale,
        RequiredVariables $requiredVariables,
        SmsTemplateBody $body,
        int $versionNumber,
    ): self {
        return new self(
            $id,
            BuiltInChannel::SMS,
            $locale,
            $requiredVariables,
            null,
            $body,
            null,
            $versionNumber,
        );
    }

    public static function push(
        TemplateVersionId $id,
        Locale $locale,
        RequiredVariables $requiredVariables,
        PushTemplateBody $body,
        int $versionNumber,
    ): self {
        return new self(
            $id,
            BuiltInChannel::PUSH,
            $locale,
            $requiredVariables,
            null,
            null,
            $body,
            $versionNumber,
        );
    }

    public function id(): TemplateVersionId
    {
        return $this->id;
    }

    public function channel(): BuiltInChannel
    {
        return $this->channel;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function requiredVariables(): RequiredVariables
    {
        return $this->requiredVariables;
    }

    public function versionNumber(): int
    {
        return $this->versionNumber;
    }

    public function emailBody(): ?EmailTemplateBody
    {
        return $this->emailBody;
    }

    public function smsBody(): ?SmsTemplateBody
    {
        return $this->smsBody;
    }

    public function pushBody(): ?PushTemplateBody
    {
        return $this->pushBody;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'channel' => $this->channel->value,
            'locale' => $this->locale->value(),
            'requiredVariables' => $this->requiredVariables()->all(),
            'versionNumber' => $this->versionNumber,
            'emailBody' => $this->emailBody?->toArray(),
            'smsBody' => $this->smsBody?->toArray(),
            'pushBody' => $this->pushBody?->toArray(),
        ];
    }

    private function assertBodyMatchesChannel(): void
    {
        if ($this->versionNumber < 1) {
            throw InvariantViolation::because('Template version number must be greater than 0.');
        }

        $ok = match($this->channel) {
            BuiltInChannel::EMAIL => $this->emailBody !== null && $this->smsBody === null && $this->pushBody === null,
            BuiltInChannel::SMS => $this->emailBody === null && $this->smsBody !== null && $this->pushBody !== null,
            BuiltInChannel::PUSH => $this->emailBody === null && $this->smsBody === null && $this->pushBody !== null,
        };

        if (!$ok) {
            throw InvariantViolation::because('Template body does not match channel.');
        }
    }
}
