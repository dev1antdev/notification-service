<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Idempotency;

use App\Application\Ports\Persistence\IdempotencyStore;
use App\Domain\Shared\Identity\IdempotencyKey;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use JsonException;

final readonly class DbalIdempotencyStore implements IdempotencyStore
{
    public function __construct(
        private Connection $connection,
        private string $scope = 'notifications',
        private int $ttlSeconds = 86400,
    ) {}

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function get(IdempotencyKey $key): ?array
    {
        $sql = '
            SELECT
                `response`,
                `expires_at`
            FROM
                `idempotency_keys`
            WHERE
                `key` = :key
                AND `scope` = :scope
        ';

        $row = $this->connection->fetchAssociative($sql, [
            'key' => $key->value(),
            'scope' => $this->scope,
        ]);

        if (!$row) {
            return null;
        }

        if ($row['expires_at'] !== null && strtotime($row['expires_at']) < time()) {
            // TODO: remove expired row here or create a cron to remove old rows

            return null;
        }

        return json_decode($row['response'], true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws DateMalformedStringException
     * @throws JsonException
     * @throws Exception
     */
    public function put(IdempotencyKey $key, array $result): void
    {
        $expiresAt = new DateTimeImmutable()->modify('+' . $this->ttlSeconds . ' seconds');
        $payload = json_encode($result, JSON_THROW_ON_ERROR);

        try {
            $this->connection->insert('idempotency_keys', [
                'key' => $key->value(),
                'scope' => $this->scope,
                'response' => $payload,
                'created_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);
        } catch (UniqueConstraintViolationException) {
        }
    }
}
