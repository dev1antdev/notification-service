<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Notification;

use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Repository\NotificationRepository;
use App\Domain\Notification\ValueObject\NotificationId;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use RuntimeException;

final readonly class DbalNotificationRepository implements NotificationRepository
{
    public function __construct(
        private Connection $connection,
        private NotificationDbMapper $mapper,
    ) {}

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function get(NotificationId $id): Notification
    {
        $sql = '
            SELECT
                id,
                correlation_id,
                idempotency_key,
                recipient,
                channels,
                content,
                schedule,
                tags,
                status,
                created_at,
                updated_at
            FROM notifications
            WHERE id = :id
        ';

        $row = $this->connection->fetchAssociative($sql, ['id' => $id->toString()]);

        return $row ? $this->mapper->fromRow($row) : throw new RuntimeException('Notification not found'); // TODO: NotificationNotFoundException from NotFoundException + map to 404 status code (HttpNotFoundException)
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function save(Notification $notification): void
    {
        $row = $this->mapper->toRow($notification);

        $this->connection->executeStatement('
            INSERT INTO notifications (id, correlation_id, idempotency_key, recipient, channels, content, schedule, tags, status, created_at, updated_at)
            VALUES (:id, :correlation_id, :idempotency_key, :recipient, :channels, :content, :schedule, :tags, :status, :created_at, :updated_at)
            ON CONFLICT (id) DO UPDATE SET
                correlation_id = EXCLUDED.correlation_id,
                idempotency_key = EXCLUDED.idempotency_key,
                recipient = EXCLUDED.recipient,
                channels = EXCLUDED.channels,
                content = EXCLUDED.content,
                schedule = EXCLUDED.schedule,
                tags = EXCLUDED.tags,
                status = EXCLUDED.status,
                updated_at = EXCLUDED.updated_at
        ', $row);
    }
}
