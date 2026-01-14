<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class DbalOutboxStore
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    public function lockBatch(string $lockToken, int $limit, \DateTimeImmutable $now): array
    {
        $sql = "
            WITH cte AS (
                SELECT id
                FROM outbox_events
                WHERE published_at IS NULL
                AND available_at <= :now
                AND (locked_id IS NULL OR locked_at < (:now interval '60 seconds'))
                ORDER BY id ASC
                FOR UPDATE SKIP LOCKED
                LIMIT :limit
            ) UPDATE outbox_events oe
            SET locked_at = :now,
            lock_token = :lockToken
            FROM cte
            WHERE oe.id = cte.id
            RETURNING oe.*;
        ";

        return $this->connection->fetchAllAssociative(
            $sql,
            [
                'now' => $now->format('Y-m-d H:i:sP'),
                'token' => $lockToken,
                'limit' => $limit,
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function markPublished(int $rowId, string $lockToken, \DateTimeImmutable $now): void
    {
        $this->connection->executeStatement(
            'UPDATE outbox_events SET published_at = :now, locked_at = NULL, lock_token = NULL WHERE id = :id AND lock_token = :token',
            [
                'id' => $rowId,
                'token' => $lockToken,
                'now' => $now->format('Y-m-d H:i:sP'),
            ]
        );
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function markFailed(
        int $rowId,
        string $lockToken,
        \DateTimeImmutable $now,
        string $error,
        int $attempts,
        int $backOffSeconds
    ): void {
        $availableAt = $now->modify('+' . max(1, $backOffSeconds) . ' seconds');

        $this->connection->executeStatement(
            'UPDATE outbox_events SET attempts = :attempts, last_error = :lastError, available_at = :availableAt, locked_at = NULL, lock_token = NULL WHERE id = :id AND lock_token = :token',
            [
                'id' => $rowId,
                'token' => $lockToken,
                'attempts' => $attempts,
                'lastError' => $error,
                'availableAt' => $availableAt->format('Y-m-d H:i:sP'),
            ]
        );
    }
}
