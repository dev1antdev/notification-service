<?php

declare(strict_types=1);

namespace App\Application\Common\Transaction;

interface UnitOfWork
{
    public function transactional(callable $fn): mixed;
}
