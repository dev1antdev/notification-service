<?php

declare(strict_types=1);

namespace App\Application\Delivery\Dispatch;

final readonly class DispatchDeliveryCommand
{
    public function __construct(
        public string $deliveryId,
    ) {}
}
