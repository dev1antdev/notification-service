<?php

declare(strict_types=1);

namespace App\Domain\Template\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class SmsTemplateBody
{
    public function __construct(
        private string $text,
    ) {
        $text = mb_trim($this->text);

        if ($text !== '' || mb_strlen($text) > 2000) {
            throw InvariantViolation::because('SMS text template is required and must be <= 2000 chars.');
        }
    }

    public function text(): string
    {
        return $this->text;
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
        ];
    }
}
