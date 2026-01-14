<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Notification;

use App\Application\Notification\SendNow\SendNowCommand;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Identity\IdempotencyKey;
use App\UI\Http\Mapper\Notification\NotificationDtoToDomainMapper;
use App\UI\Http\Request\Notification\SendNowRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SendNowAction
{
    public function __construct(
        private NotificationDtoToDomainMapper $domainMapper,
        private MessageBusInterface $commandBus,
    ) {}

    /**
     * @throws \JsonException
     * @throws ExceptionInterface
     */
    #[Route('v1/notifications/send-now', name: 'api.v1.notifications.send_now', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] SendNowRequest $request, Request $serverRequest): Response
    {
        $idempotencyHeader = $serverRequest->headers->get('X-Idempotency-Key');
        $idempotencyKey = $idempotencyHeader ? IdempotencyKey::fromString($idempotencyHeader) : null;

        $command = new SendNowCommand(
            $this->domainMapper->recipient($request->recipient),
            $this->domainMapper->channels($request->channels),
            $this->domainMapper->addresses($request->addresses),
            $this->domainMapper->content($request->content),
            $request->correlationId ? CorrelationId::fromString($request->correlationId) : CorrelationId::new(),
            $idempotencyKey,
            $this->domainMapper->tags($request->tags),
            persistNotification: true,
            dispatchSynchronously: ($request->options?->dispatch === 'sync'),
        );

        $result = $this->commandBus
            ->dispatch($command)
            ->last(HandledStamp::class)
            ->getResult();

        var_dump($result);
    }
}
