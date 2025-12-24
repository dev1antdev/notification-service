<?php

declare(strict_types=1);

namespace App\Domain\Delivery\Entity;

use App\Domain\Delivery\Enum\AttemptStatus;
use App\Domain\Delivery\ValueObject\AttemptId;
use App\Domain\Delivery\ValueObject\ErrorInfo;
use App\Domain\Delivery\ValueObject\ProviderMessageId;
use App\Domain\Delivery\ValueObject\ProviderName;
use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Time\Instant;

final class DeliveryAttempt
{
    private AttemptStatus $status;
    private ?ProviderMessageId $providerMessageId = null;
    private ?ErrorInfo $error = null;
    private ?Instant $finishedAt = null;

    private function __construct(
        private readonly AttemptId $id,
        private readonly ProviderName $provider,
        private readonly Instant $startedAt,
    ) {
        $this->status = AttemptStatus::STARTED;
    }

    public static function start(AttemptId $id, ProviderName $provider, Instant $startedAt): self
    {
        return new self($id, $provider, $startedAt);
    }

    public function succeed(ProviderMessageId $providerMessageId, Instant $now): void
    {
        if ($this->status !== AttemptStatus::STARTED) {
            throw InvariantViolation::because('Attempt can be finished only once.');
        }

        $this->status = AttemptStatus::SUCCEEDED;
        $this->providerMessageId = $providerMessageId;
        $this->finishedAt = $now;
    }

    public function fail(ErrorInfo $error, Instant $now): void
    {
        if ($this->status !== AttemptStatus::STARTED) {
            throw InvariantViolation::because('Attempt can be finished only once.');
        }

        $this->status = AttemptStatus::FAILED;
        $this->error = $error;
        $this->finishedAt = $now;
    }

    public function id(): AttemptId
    {
        return $this->id;
    }

    public function provider(): ProviderName
    {
        return $this->provider;
    }

    public function status(): AttemptStatus
    {
        return $this->status;
    }

    public function startedAt(): Instant
    {
        return $this->startedAt;
    }

    public function finishedAt(): ?Instant
    {
        return $this->finishedAt;
    }

    public function providerMessageId(): ?ProviderMessageId
    {
        return $this->providerMessageId;
    }

    public function error(): ?ErrorInfo
    {
        return $this->error;
    }
}
