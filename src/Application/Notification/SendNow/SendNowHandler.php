<?php

declare(strict_types=1);

namespace App\Application\Notification\SendNow;

use App\Application\Common\Mapper\AddressFactory;
use App\Application\Common\Mapper\ContentFactory;
use App\Application\Common\Outbox\OutboxPublisher;
use App\Application\Common\Transaction\UnitOfWork;
use App\Application\Ports\Persistence\IdempotencyStore;
use App\Domain\Delivery\Entity\Delivery;
use App\Domain\Delivery\Policy\RoutingPolicy;
use App\Domain\Delivery\Repository\DeliveryRepository;
use App\Domain\Delivery\ValueObject\DeliveryId;
use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Repository\NotificationRepository;
use App\Domain\Notification\ValueObject\NotificationId;
use App\Domain\Shared\Notification\Channel;
use App\Domain\Shared\Time\ClockInterface;

final readonly class SendNowHandler
{
    public function __construct(
        private UnitOfWork $uow,
        private ClockInterface $clock,
        private IdempotencyStore $idempotencyStore,
        private AddressFactory $addressFactory,
        private ContentFactory $contentFactory,
        private RoutingPolicy $routingPolicy,
        private NotificationRepository $notificationRepository,
        private DeliveryRepository $deliveryRepository,
        private OutboxPublisher $outbox,
    ) {
    }

    public function __invoke(SendNowCommand $command): SendNowResult
    {
        if ($command->idempotencyKey) {
            $cached = $this->idempotencyStore->get($command->idempotencyKey);

            if ($cached) {
                return new SendNowResult(
                    notificationId: (string) $cached['notificationId'],
                    deliveryIds: $cached['deliveryIds'] ?? [],
                );
            }
        }

        $result = $this->uow->transactional(function () use ($command): SendNowResult {
            $now = $this->clock->now();

            $notification = Notification::request(
                id: NotificationId::new(),
                recipient: $command->recipient,
                channels: $command->channels,
                content: $command->content,
                correlationId: $command->correlationId,
                createdAt: $now,
                idempotencyKey: $command->idempotencyKey,
                tags: $command->tags,
            );

            if ($command->persistNotification) {
                $this->notificationRepository->save($notification);
            }

            $deliveryIds = [];

            /**
             * @var Channel $channel
             */
            foreach ($command->channels->all() as $channel) {
                $customPayload = $command->addresses[$channel->name()] ?? null;
                $address = $this->addressFactory->fromRecipient($channel, $command->recipient, $customPayload);
                $provider = $this->routingPolicy->chooseProvider($channel, $address);
                $content = $this->contentFactory->build($channel, $command->content);

                $delivery = Delivery::create(
                    id: DeliveryId::new(),
                    notificationId: $notification->id(),
                    channel: $channel,
                    provider: $provider,
                    address: $address,
                    content: $content,
                    correlationId: $command->correlationId,
                    now: $now,
                );

                $this->deliveryRepository->save($delivery);

                $deliveryIds[] = (string) $delivery->id();

                $this->outbox->enqueue(...$delivery->pullDomainEvents());
            }

            $this->outbox->enqueue(...$notification->pullDomainEvents());

            $response = new SendNowResult(
                (string) $notification->id(),
                $deliveryIds,
            );

            if ($command->idempotencyKey) {
                $this->idempotencyStore->put($command->idempotencyKey, $response->toArray());
            }

            return $response;
        });

        if ($command->dispatchSynchronously) {
            // TODO: Dispatch here handler to send notification synchronously (in foreach)
        }

        return $result;
    }
}
