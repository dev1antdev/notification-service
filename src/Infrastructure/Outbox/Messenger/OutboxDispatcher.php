<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox\Messenger;

use App\Infrastructure\Outbox\DbalOutboxStore;
use Doctrine\DBAL\Connection;
use Random\RandomException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class OutboxDispatcher
{
    public function __construct(
        private Connection $connection,
        private DbalOutboxStore $store,
        private MessageBusInterface $bus,
    ) {}

    /**
     * @throws \Throwable
     * @throws RandomException
     */
    public function dispatchBatch(int $limit): int
    {
        $now = new \DateTimeImmutable();
        $token = bin2hex(random_bytes(16));

        return $this->connection->transactional(function () use ($limit, $now, $token): int {
            $rows = $this->store->lockBatch($token, $limit, $now);

            $count = 0;

            foreach ($rows as $row) {
                $rowId = (int) $row['id'];

                try {
                    $payload = json_decode((string)$row['payload'], true, 512, JSON_THROW_ON_ERROR);

                    $message = new OutboxEventMessage(
                        eventId: $row['event_id'],
                        eventType: $row['event_type'],
                        payload: is_array($payload) ? $payload : [],
                        occurredAt: $row['occurred_at'],
                    );

                    $this->bus->dispatch($message);

                    $this->store->markPublished($row, $token, $now);
                    $count++;
                } catch (\Throwable $exception) {
                    $attempts = ((int)$row['attempts']) + 1;
                    $backoff = $this->computeBackoffSeconds($attempts);

                    $this->store->markFailed($rowId, $token, $now, $exception->getMessage(), $attempts, $backoff);
                }
            }

            return $count;
        });
    }

    private function computeBackoffSeconds(int $attempts): int
    {
        $base = 2 ** min(10, $attempts);

        return min(900, $base);
    }
}
