<?php

declare(strict_types=1);

namespace App\Application\Delivery\Dispatch;

use App\Application\Common\Errors\ProviderErrorMapper;
use App\Application\Common\Mapper\ContentFactory;
use App\Application\Common\Outbox\OutboxPublisher;
use App\Application\Common\Transaction\UnitOfWork;
use App\Application\Ports\Senders\SenderRegistry;
use App\Application\Ports\Template\TemplateRenderer;
use App\Domain\Delivery\Enum\DeliveryStatus;
use App\Domain\Delivery\Policy\RetryPolicy;
use App\Domain\Delivery\Repository\DeliveryRepository;
use App\Domain\Delivery\ValueObject\Content\SnapshotContent;
use App\Domain\Delivery\ValueObject\Content\TemplateRefContent;
use App\Domain\Delivery\ValueObject\DeliveryId;
use App\Domain\Shared\Time\ClockInterface;
use App\Domain\Shared\ValueObject\JsonObject;
use Throwable;

final readonly class DispatchDeliveryHandler
{
    public function __construct(
        private UnitOfWork $uow,
        private ClockInterface $clock,
        private DeliveryRepository $deliveryRepository,
        private RetryPolicy $retryPolicy,
        private ProviderErrorMapper $errorMapper,
        private OutboxPublisher $outbox,
        private SenderRegistry $senderRegistry,
        private TemplateRenderer $templateRenderer,
        private ContentFactory $contentFactory,
    ) {}

    public function __invoke(DispatchDeliveryCommand $command): void
    {
        $this->uow->transactional(function () use ($command): void {
            $now = $this->clock->now();

            $delivery = $this->deliveryRepository->get(DeliveryId::fromString($command->deliveryId));

            if (in_array($delivery->status(), [DeliveryStatus::SENT, DeliveryStatus::FAILED, DeliveryStatus::CANCELLED], true)) {
                return;
            }

            if ($delivery->status() === DeliveryStatus::PENDING && !$delivery->isRetryDue($now)) {
                return;
            }

            $delivery->startDispatch($now);

            $attemptId = $delivery->beginAttempt($now);

            try {
                $sender = $this->senderRegistry->get($delivery->channel());
                $content = $delivery->content();

                if ($content instanceof TemplateRefContent) {
                    $rendered = $this->templateRenderer->render($content->templateRef(), $content->variables());
                    $content = new SnapshotContent(
                        $delivery->channel(),
                        new JsonObject($this->contentFactory->buildPayload($rendered)),
                    );
                }

                $providerMessageId = $sender->send($delivery->address(), $content);
                $delivery->attemptSucceeded($attemptId, $providerMessageId, $now);
            } catch (Throwable $exception) {
                $error = $this->errorMapper->map($exception);

                $attemptNumber = $delivery->attemptCount();
                $retryPlan = $this->retryPolicy->planRetry($delivery->channel(), $attemptNumber, $error, $now);

                $delivery->attemptFailed($attemptId, $error, $now, $retryPlan);
            }

            $this->deliveryRepository->save($delivery);
            $this->outbox->enqueue(...$delivery->pullDomainEvents());
        });
    }
}
