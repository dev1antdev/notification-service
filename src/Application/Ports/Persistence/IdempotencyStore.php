<?php

declare(strict_types=1);

namespace App\Application\Ports\Persistence;

use App\Domain\Shared\Identity\IdempotencyKey;

interface IdempotencyStore
{
    public function get(IdempotencyKey $key): ?array;

    public function put(IdempotencyKey $key, array $result): void;
}
