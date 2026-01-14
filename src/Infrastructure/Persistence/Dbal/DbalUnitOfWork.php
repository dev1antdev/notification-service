<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dbal;

use App\Application\Common\Transaction\UnitOfWork;
use Doctrine\DBAL\Connection;
use Throwable;

final readonly class DbalUnitOfWork implements UnitOfWork
{
    public function __construct(private Connection $connection){}

    /**
     * @throws Throwable
     */
    public function transactional(callable $fn): mixed
    {
        return $this->connection->transactional($fn);
    }
}
