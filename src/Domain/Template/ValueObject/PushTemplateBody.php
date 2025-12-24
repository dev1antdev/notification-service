<?php

declare(strict_types=1);

namespace App\Domain\Template\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class PushTemplateBody
{
    public function __construct(
        private ?string $title,
        private ?string $body,
        private array $data = [],
    ) {
        $hasTitle = $this->title !== null && mb_trim($this->title) !== '';
        $hasBody = $this->body !== null && mb_trim($this->body) !== '';
        $hasData = $this->data !== [];

        if (!$hasTitle && !$hasBody && !$hasData) {
            throw InvariantViolation::because('Push template must have title or body or data.');
        }

        foreach ($this->data as $key => $value) {
            if (!is_string($key) || mb_trim($key) === '') {
                throw InvariantViolation::because('Push template data keys must be non-empty strings.');
            }

            if (!is_string($value)) {
                throw InvariantViolation::because('Push data values must be strings.');
            }

            if (mb_strlen($key) > 64) {
                throw InvariantViolation::because('Push data key too long.');
            }

            if (mb_strlen($value) > 5000) {
                throw InvariantViolation::because('Push data value too long.');
            }
        }
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ];
    }
}
