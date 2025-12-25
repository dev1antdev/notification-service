<?php

declare(strict_types=1);

namespace App\Application\Notification\SendNow;

final readonly class SendNowResult
{
    public function __construct(
        public string $notificationId,
        public array $deliveryIds,
    ) {}

    public function toArray(): array
    {
        return [
            'notificationId' => $this->notificationId,
            'deliveryIds' => $this->deliveryIds,
        ];
    }
}
