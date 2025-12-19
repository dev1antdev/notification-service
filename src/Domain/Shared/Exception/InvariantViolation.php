<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class InvariantViolation extends AbstractDomainException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
