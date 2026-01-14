<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Delivery\Event\DeliveryAttemptFailed;
use App\Domain\Delivery\Event\DeliveryAttemptStarted;
use App\Domain\Delivery\Event\DeliveryAttemptSucceeded;
use App\Infrastructure\Outbox\Messenger\OutboxEventMessage;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;

final readonly class DeliveryAttemptProjector
{
    private const string STARTED_STATUS = 'started';
    private const string SUCCEEDED_STATUS = 'succeeded';
    private const string FAILED_STATUS = 'failed';

    public function __construct(private Connection $connection) {}

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function apply(OutboxEventMessage $message): void
    {
        $payload = $message->payload;
        $deliveryId = $payload['deliveryId'] ?? '';
        $attemptId = $payload['attemptId'] ?? '';

        if ($deliveryId === '' || $attemptId === '') {
            return;
        }

        switch ($message->eventType) {
            case DeliveryAttemptStarted::eventName():
                $this->processAttemptStarted(
                    $deliveryId,
                    $attemptId,
                    $payload,
                    $message->occurredAt,
                );
                break;
            case DeliveryAttemptSucceeded::eventName():
                $this->processAttemptSucceeded(
                    $deliveryId,
                    $attemptId,
                    $payload,
                    $message->occurredAt,
                );
                break;
            case DeliveryAttemptFailed::eventName():
                $this->processAttemptFailed(
                    $deliveryId,
                    $attemptId,
                    $payload,
                    $message->occurredAt,
                );
                break;
        }
    }

    /**
     * @throws Exception
     */
    private function processAttemptStarted(
        string $deliveryId,
        string $attemptId,
        array $payload,
        string $fallbackTime,
    ): void {
        $this->connection->executeStatement('
            INSERT INTO delivery_attempts (id, delivery_id, attempt_number, started_at, status)
            VALUES (:id, :deliveryId, :attemptNumber, :startedAt, :status)
            ON CONFLICT (id) DO NOTHING
        ', [
            'id' => $attemptId,
            'deliveryId' => $deliveryId,
            'attemptNumber' => $payload['attemptNumber'] ?? 0,
            'startedAt' => $payload['startedAt'] ?? $fallbackTime,
            'status' => self::STARTED_STATUS,
        ]);
    }

    /**
     * @throws Exception
     */
    private function processAttemptSucceeded(
        string $deliveryId,
        string $attemptId,
        array $payload,
        string $fallbackTime,
    ): void {
        $this->processAttemptStarted($deliveryId, $attemptId, $payload, $fallbackTime);

        $this->connection->executeStatement('
            UPDATE delivery_attempts
            SET
                finished_at = :finishedAt,
                status = :status,
                provider_message_id = :providerMessageId
            WHERE id = :id
        ', [
            'id' => $attemptId,
            'finishedAt' => $payload['finishedAt'] ?? $fallbackTime,
            'status' => self::SUCCEEDED_STATUS,
            'providerMessageId' => $payload['providerMessageId'] ?? null,
        ]);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    private function processAttemptFailed(
        string $deliveryId,
        string $attemptId,
        array $payload,
        string $fallbackTime,
    ): void {
        $this->processAttemptStarted($deliveryId, $attemptId, $payload, $fallbackTime);

        $this->connection->executeStatement('
            UPDATE delivery_attempts
            SET
                finished_at = :finishedAt,
                status = :status,
                error = :error::jsonb
            WHERE id = :id
        ', [
            'id' => $attemptId,
            'finishedAt' => $payload['finishedAt'] ?? $fallbackTime,
            'status' => self::FAILED_STATUS,
            'error' => json_encode($payload['error'] ?? [], JSON_THROW_ON_ERROR),
        ]);
    }
}
