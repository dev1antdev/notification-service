<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Repository;

interface DeliveryFinder
{
    /**
     * @return list<string> deliveryId strings
     */
    public function findDispatchable(int $limit): array;

    /**
     * @return list<string> deliveryId strings
     */
    public function findRetryDue(\DateTimeImmutable $now, int $limit): array;
}
