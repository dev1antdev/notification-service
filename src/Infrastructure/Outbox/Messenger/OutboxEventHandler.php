<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox\Messenger;

use App\Application\Delivery\Dispatch\DispatchDeliveryCommand;
use App\Domain\Delivery\Event\DeliveryAttemptFailed;
use App\Domain\Delivery\Event\DeliveryAttemptStarted;
use App\Domain\Delivery\Event\DeliveryAttemptSucceeded;
use App\Domain\Delivery\Event\DeliveryCreated;
use App\Infrastructure\Projection\DeliveryAttemptProjector;
use Doctrine\DBAL\Exception;
use JsonException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class OutboxEventHandler
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private DeliveryAttemptProjector $attempts,
    ) {}

    /**
     * @throws JsonException
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function __invoke(OutboxEventMessage $message): void
    {
        switch ($message->eventType) {
            case DeliveryCreated::eventName():
                $deliveryId = $message->payload['deliveryId'] ?? '';

                if ($deliveryId !== '') {
                    $this->commandBus->dispatch(new DispatchDeliveryCommand($deliveryId));
                }

                break;
            case DeliveryAttemptStarted::eventName():
            case DeliveryAttemptSucceeded::eventName():
            case DeliveryAttemptFailed::eventName():
                $this->attempts->apply($message);
                break;
            default:
                break;
        }
    }
}
