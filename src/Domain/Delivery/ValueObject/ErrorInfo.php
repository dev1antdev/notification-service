<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class ErrorInfo
{
    public function __construct(
        private string $code,
        private string $message,
        private bool $isTransient,
        private ?string $providerCode = null,
    ) {
        if ($this->code === '' || $this->message === '') {
            throw InvariantViolation::because('Error info cannot be empty.');
        }

        if (mb_strlen($this->code) > 64) {
            throw InvariantViolation::because('Error code too long.');
        }

        if (mb_strlen($this->message) > 2000) {
            throw InvariantViolation::because('Error message too long.');
        }

        if ($this->providerCode !== null && mb_strlen($this->providerCode) > 128) {
            throw InvariantViolation::because('Error provider code too long.');
        }
    }

    public static function transient(string $code, string $message, ?string $providerCode = null): self
    {
        return new self(
            code: mb_trim($code),
            message: mb_trim($message),
            isTransient: true,
            providerCode: $providerCode ? mb_trim($providerCode) : null,
        );
    }

    public static function permanent(string $code, string $message, ?string $providerCode = null): self
    {
        return new self(
            code: mb_trim($code),
            message: mb_trim($message),
            isTransient: false,
            providerCode: $providerCode ? mb_trim($providerCode) : null,
        );
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function isTransient(): bool
    {
        return $this->isTransient;
    }

    public function providerCode(): ?string
    {
        return $this->providerCode;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'isTransient' => $this->isTransient,
            'providerCode' => $this->providerCode,
        ];
    }
}
