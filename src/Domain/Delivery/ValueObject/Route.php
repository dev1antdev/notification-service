<?php

declare(strict_types=1);

namespace App\Domain\Delivery\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\ValueObject\JsonObject;

final readonly class Route
{
    public function __construct(
        private ProviderName $provider,
        private ?JsonObject $options = null,
    ) {
        if ((string) $provider === '') {
            throw InvariantViolation::because('Route provider cannot be empty.');
        }
    }

    public function provider(): ProviderName
    {
        return $this->provider;
    }

    public function options(): ?JsonObject
    {
        return $this->options;
    }
}
