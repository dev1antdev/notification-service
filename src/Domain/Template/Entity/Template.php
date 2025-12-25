<?php

declare(strict_types=1);

namespace App\Domain\Template\Entity;

use App\Domain\Shared\Entity\AggregateRoot;
use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Identity\CorrelationId;
use App\Domain\Shared\Notification\BuiltInChannel;
use App\Domain\Shared\Time\Instant;
use App\Domain\Template\Enum\TemplateStatus;
use App\Domain\Template\Event\TemplateArchived;
use App\Domain\Template\Event\TemplateCreated;
use App\Domain\Template\Event\TemplateVersionPublished;
use App\Domain\Template\ValueObject\Locale;
use App\Domain\Template\ValueObject\TemplateId;
use App\Domain\Template\ValueObject\TemplateName;
use App\Domain\Template\ValueObject\TemplateVersionId;
use Symfony\Component\Uid\Uuid;

final class Template extends AggregateRoot
{
    private TemplateStatus $status;

    /** @var TemplateVersion[] */
    private array $versions = [];

    private function __construct(
        private readonly TemplateId $id,
        private TemplateName $name,
        private readonly CorrelationId $correlationId,
        private readonly Instant $createdAt,
    ) {
        parent::__construct();

        $this->status = TemplateStatus::ACTIVE;

        $this->record(
            new TemplateCreated(
                eventId: Uuid::v4()->toRfc4122(),
                occurredAt: $createdAt,
                correlationId: $this->correlationId,
                templateId: $this->id,
            ),
        );
    }

    public static function create(
        TemplateId $id,
        TemplateName $name,
        CorrelationId $correlationId,
        Instant $now,
    ): self {
        return new self(
            $id,
            $name,
            $correlationId,
            $now,
        );
    }

    public function rename(TemplateName $name): void
    {
        $this->assertActive();

        $this->name = $name;

        // TODO: add event TemplateRenamed
    }

    public function publishVersion(TemplateVersion $version, Instant $now): void
    {
        $this->assertActive();

        $expected = $this->nextVersionNumber();

        if ($version->versionNumber() !== $expected) {
            throw InvariantViolation::because("Template version number must be {$expected}");
        }

        foreach ($this->versions as $value) {
            if ($value->id()->equals($version->id())) {
                throw InvariantViolation::because('Template version already exists.');
            }
        }

        $this->versions[] = $version;

        $this->record(new TemplateVersionPublished(
            eventId: Uuid::v4()->toRfc4122(),
            occurredAt: $now,
            correlationId: $this->correlationId,
            templateId: $this->id,
            templateVersionId: $version->id(),
            versionNumber: $version->versionNumber(),
        ));
    }

    public function archive(Instant $now): void
    {
        $this->assertActive();
        $this->status = TemplateStatus::ARCHIVED;

        $this->record(new TemplateArchived(
            eventId: Uuid::v4()->toRfc4122(),
            occurredAt: $now,
            correlationId: $this->correlationId,
            templateId: $this->id,
        ));
    }

    public function latestVersionFor(BuiltInChannel $channel, ?Locale $locale = null): ?TemplateVersion
    {
        $result = null;

        foreach ($this->versions as $version) {
            if ($version->channel() !== $channel) {
                continue;
            }

            if ($locale !== null && $version->locale()->value() !== $locale->value()) {
                continue;
            }

            if ($result === null || $version->versionNumber() > $result->versionNumber()) {
                $result = $version;
            }
        }

        return $result;
    }

    public function findVersionById(TemplateVersionId $templateVersionId): ?TemplateVersion
    {
        return array_find($this->versions, static fn($version) => $version->id()->equals($templateVersionId));
    }

    public function id(): TemplateId
    {
        return $this->id;
    }

    public function name(): TemplateName
    {
        return $this->name;
    }

    public function status(): TemplateStatus
    {
        return $this->status;
    }

    public function createdAt(): Instant
    {
        return $this->createdAt;
    }

    public function versions(): array
    {
        return $this->versions;
    }

    private function assertActive(): void
    {
        if ($this->status !== TemplateStatus::ACTIVE) {
            throw InvariantViolation::because('Template is not active.');
        }
    }

    private function nextVersionNumber(): int
    {
        $max = 0;

        foreach ($this->versions as $version) {
            $max = max($max, $version->versionNumber());
        }

        return $max + 1;
    }

}
