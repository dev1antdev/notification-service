<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Repository;

use App\Domain\Delivery\Entity\Delivery;
use App\Domain\Delivery\ValueObject\DeliveryId;

interface DeliveryRepository
{
    public function get(DeliveryId $id): Delivery;

    public function save(Delivery $delivery): void;
}
