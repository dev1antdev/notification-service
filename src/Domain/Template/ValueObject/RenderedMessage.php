<?php

declare(strict_types=1);

namespace App\Domain\Template\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Notification\BuiltInChannel;
use App\Domain\Shared\Notification\Channel;

final readonly class RenderedMessage
{
    private function __construct(
        private Channel $channel,
        private ?string $subject,
        private ?string $text,
        private ?string $html,
        private ?string $pushTitle,
        private ?string $pushBody,
        private array $pushData,
    ) {}

    public static function email(string $subject, ?string $text, ?string $html): self
    {
        $subject = mb_trim($subject);

        if ($subject === '') {
            throw InvariantViolation::because('Rendered email subject cannot be empty.');
        }

        $hasText = $text !== null && mb_trim($text) !== '';
        $hasHtml = $html !== null && mb_trim($html) !== '';

        if (!$hasText && !$hasHtml) {
            throw InvariantViolation::because('Rendered email must have text or html.');
        }

        return new self(
            Channel::builtIn(BuiltInChannel::EMAIL),
            $subject,
            $text,
            $html,
            null,
            null,
            [],
        );
    }

    public static function sms(string $text): self
    {
        $text = mb_trim($text);

        if ($text === '') {
            throw InvariantViolation::because('Rendered sms text cannot be empty.');
        }

        return new self(Channel::builtIn(BuiltInChannel::SMS), null, $text, null, null, null, []);
    }

    public static function push(?string $title, ?string $body, array $data = []): self
    {
        $hasTitle = $title !== null && mb_trim($title) !== '';
        $hasBody = $body !== null && mb_trim($body) !== '';
        $hasData = $data !== [];

        if (!$hasTitle && !$hasBody && !$hasData) {
            throw InvariantViolation::because('Rendered push notification must have title, body or data.');
        }

        return new self(Channel::builtIn(BuiltInChannel::PUSH), null, null, null, $title, $body, $data);
    }

    public function channel(): Channel
    {
        return $this->channel;
    }

    public function subject(): ?string
    {
        return $this->subject;
    }

    public function text(): ?string
    {
        return $this->text;
    }

    public function html(): ?string
    {
        return $this->html;
    }

    public function pushTitle(): ?string
    {
        return $this->pushTitle;
    }

    public function pushBody(): ?string
    {
        return $this->pushBody;
    }

    public function pushData(): array
    {
        return $this->pushData;
    }

    public function toArray(): array
    {
        return [
            'channel' => $this->channel,
            'subject' => $this->subject,
            'text' => $this->text,
            'html' => $this->html,
            'pushTitle' => $this->pushTitle,
            'pushBody' => $this->pushBody,
            'pushData' => $this->pushData,
        ];
    }
}
