<?php

declare(strict_types=1);

namespace App\Domain\Template\Event;

use App\Domain\Shared\Event\AbstractDomainEvent;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Time\Instant;
use App\Domain\Template\ValueObject\TemplateId;

final readonly class TemplateArchived extends AbstractDomainEvent
{
    public function __construct(
        string $eventId,
        Instant $occurredAt,
        ?CorrelationId $correlationId,
        private TemplateId $templateId,
    ) {
        parent::__construct($eventId, $occurredAt, $correlationId);
    }

    public static function eventName(): string
    {
        return 'template.archived';
    }

    public function payload(): array
    {
        return [
            'templateId' => $this->templateId->toString(),
        ];
    }
}
