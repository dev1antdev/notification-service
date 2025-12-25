<?php

declare(strict_types=1);

namespace App\Domain\Template\Event;

use App\Domain\Shared\Event\AbstractDomainEvent;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Time\Instant;
use App\Domain\Template\ValueObject\TemplateId;
use App\Domain\Template\ValueObject\TemplateVersionId;

final readonly class TemplateVersionPublished extends AbstractDomainEvent
{
    public function __construct(
        string $eventId,
        Instant $occurredAt,
        ?CorrelationId $correlationId,
        private TemplateId $templateId,
        private TemplateVersionId $templateVersionId,
        private int $versionNumber,
    ) {
        parent::__construct($eventId, $occurredAt, $correlationId);
    }

    public static function eventName(): string
    {
        return 'template.version_published';
    }

    public function payload(): array
    {
        return [
            'templateId' => $this->templateId->toString(),
            'templateVersionId' => $this->templateVersionId->toString(),
            'versionNumber' => $this->versionNumber,
        ];
    }
}
