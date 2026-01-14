<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Delivery;

use App\Domain\Delivery\Entity\Delivery;
use App\Domain\Delivery\Repository\DeliveryRepository;
use App\Domain\Delivery\ValueObject\DeliveryId;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use RuntimeException;

final readonly class DbalDeliveryRepository implements DeliveryRepository
{
    public function __construct(
        private Connection $connection,
        private DeliveryDbMapper $mapper,
    ) {
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function get(DeliveryId $id): Delivery
    {
        $row = $this->connection->fetchAssociative('
            SELECT
                id,
                notification_id,
                correlation_id,
                channel,
                provider,
                address_type,
                address,
                content_type,
                content,
                status,
                attempt_count,
                next_retry_at,
                dead_lettered_at,
                provider_message_id,
                last_error,
                created_at,
                updated_at,
                version
            FROM deliveries
            WHERE id = :id
        ', ['id' => $id->toString()]);

        if (!$row) {
            throw new RuntimeException('Delivery not found.'); // TODO: change this exception to NotFoundException
        }

        return $this->mapper->fromRow($row);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function save(Delivery $delivery): void
    {
        $row = $this->mapper->toRow($delivery);

        $sql = '
            INSERT INTO deliveries (
                id, notification_id, channel, provider, address_type, address, content_type, content, correlation_id,
                status, attempt_count, next_retry_at, dead_lettered_at, provider_message_id, last_error, created_at,
                updated_at, version
            ) VALUES (
                :id, :notification_id, :channel, :provider, :address_type, :address, :content_type, :content,
                :correlation_id, :status, :attempt_count, :next_retry_at, :dead_lettered_at, :provider_message_id,
                :last_error, :created_at, :updated_at, :version
            ) ON CONFLICT (id) DO UPDATE SET
                provider = EXCLUDED.provider,
                address_type = EXCLUDED.address_type,
                address = EXCLUDED.address,
                content_type = EXCLUDED.content_type,
                content = EXCLUDED.content,
                status = EXCLUDED.status,
                attempt_count = EXCLUDED.attempt_count,
                next_retry_at = EXCLUDED.next_retry_at,
                dead_lettered_at = EXCLUDED.dead_lettered_at,
                provider_message_id = EXCLUDED.provider_message_id,
                last_error = EXCLUDED.last_error,
                updated_at = EXCLUDED.updated_at,
                version = deliveries.version + 1
        ';

        $this->connection->executeStatement($sql, $row);
    }
}
