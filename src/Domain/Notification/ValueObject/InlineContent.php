<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;
use App\Domain\Shared\Identity\AbstractId;

final readonly class InlineContent implements NotificationContent
{
    public function __construct(
        private ?string $subject,
        private ?string $text,
        private ?string $html,
        private ?string $pushTitle,
        private ?string $pushBody,
        private array $pushData = [],
    ) {
        $this->assertNotCompletelyEmpty();
        $this->assertPushDataSerializable($this->pushData);
    }

    public function kind(): string
    {
        return 'inline';
    }

    public function subject(): ?string
    {
        return $this->subject;
    }

    public function text(): ?string
    {
        return $this->text;
    }

    public function html(): ?string
    {
        return $this->html;
    }

    public function pushTitle(): ?string
    {
        return $this->pushTitle;
    }

    public function pushBody(): ?string
    {
        return $this->pushBody;
    }

    public function pushData(): array
    {
        return $this->pushData;
    }

    private function assertNotCompletelyEmpty(): void
    {
        $all = [
            $this->subject,
            $this->text,
            $this->html,
            $this->pushTitle,
            $this->pushBody,
        ];

        if (array_any($all, static fn(?string $value) => $value !== null && trim($value) !== '')) {
            return;
        }

        if ($this->pushData !== []) {
            return;
        }

        throw InvariantViolation::because('Inline content cannot be completely empty.');
    }

    private function assertPushDataSerializable(array $data): void
    {
        $check = static function (mixed $value) use (&$check): bool {
            if ($value === null || is_scalar($value)) {
                return true;
            }

            if (is_array($value)) {
                return array_all($value, static fn($v) => $check($v));
            }

            return false;
        };

        if (!$check($data)) {
            throw InvariantViolation::because('Push data must be serializable.');
        }
    }
}
