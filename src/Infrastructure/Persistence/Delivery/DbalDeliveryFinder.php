<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Delivery;

use App\Domain\Delivery\Enum\DeliveryStatus;
use App\Domain\Delivery\Repository\DeliveryFinder;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class DbalDeliveryFinder implements DeliveryFinder
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    public function findDispatchable(int $limit): array
    {
        return $this->connection->fetchFirstColumn('
            SELECT id
            FROM deliveries
            WHERE status = :status
            ORDER BY created_at
            LIMIT :limit
        ', ['status' => DeliveryStatus::PENDING->value, 'limit' => $limit]);
    }

    /**
     * @throws Exception
     */
    public function findRetryDue(DateTimeImmutable $now, int $limit): array
    {
        return $this->connection->fetchFirstColumn('
            SELECT id
            FROM deliveries
            WHERE status = :status
            AND next_retry_at IS NOT NULL
            AND next_retry_at <= :now
            ORDER BY next_retry_at
            LIMIT :limit
        ', ['status' => DeliveryStatus::RETRYING->value, 'now' => $now->format('Y-m-d H:i:sP'), 'limit' => $limit]);
    }
}
