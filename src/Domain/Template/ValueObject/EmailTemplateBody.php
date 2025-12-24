<?php

declare(strict_types=1);

namespace App\Domain\Template\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class EmailTemplateBody
{
    public function __construct(
        private string $subject,
        private ?string $html,
        private ?string $text,
    ) {
        $subject = mb_trim($this->subject);

        if ($subject === '' || mb_strlen($subject) > 500) {
            throw InvariantViolation::because('Email subject template is required and must be <= 500 characters.');
        }

        $hasHtml = $this->html !== null && mb_trim($this->html) !== '';
        $hasText = $this->text !== null && mb_trim($this->text) !== '';

        if (!$hasHtml && !$hasText) {
            throw InvariantViolation::because('Email template must have either HTML or text content.');
        }
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function html(): ?string
    {
        return $this->html;
    }

    public function text(): ?string
    {
        return $this->text;
    }

    public function toArray(): array
    {
        return [
            'subject' => $this->subject,
            'html' => $this->html,
            'text' => $this->text,
        ];
    }
}
